<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\DepreciationEntry;
use App\Models\Accounting\FixedAsset;
use App\Models\Accounting\Journal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Batch 44a: the fixed assets register.
 *
 *  - Adding an asset can post its purchase (from a bank account, or as an
 *    opening balance for something owned before the books started), or
 *    record one already posted through a bill / money out.
 *  - Depreciation is charged a month at a time: straight line (a fixed %
 *    of cost each year) or reducing balance (a % of what's left). One
 *    journal per run, one stored entry per asset per month, so the register
 *    and the ledger always agree.
 *  - Disposal charges depreciation to the month before, then removes cost
 *    and accumulated depreciation and posts any profit or loss.
 */
class FixedAssets
{
    public static function nextNumber(string $agencyId): string
    {
        $last = FixedAsset::where('agency_id', $agencyId)->where('number', 'like', 'FA-%')->orderByDesc('number')->value('number');

        return 'FA-'.str_pad((string) ((int) substr((string) $last, 3) + 1), 4, '0', STR_PAD_LEFT);
    }

    /** The asset and accumulated-depreciation accounts for a category (created if missing). */
    public static function categoryAccounts(string $agencyId, string $category): array
    {
        $c = FixedAsset::CATEGORIES[$category] ?? FixedAsset::CATEGORIES['other'];

        return [DefaultChart::byCode($agencyId, $c[3]), DefaultChart::byCode($agencyId, $c[4])];
    }

    /**
     * @param  array{type: string, bank_account_id?: ?string}  $funding  type: already | bank | opening
     */
    public static function create(string $agencyId, array $data, array $funding, ?int $userId): FixedAsset
    {
        return DB::transaction(function () use ($agencyId, $data, $funding, $userId) {
            $s = AccountingSetting::forAgency($agencyId);
            $asset = FixedAsset::create($data + [
                'agency_id' => $agencyId,
                'number' => self::nextNumber($agencyId),
                'status' => 'active',
                'created_by' => $userId,
            ]);
            $cost = (float) $asset->cost;
            $opening = (float) $asset->opening_depreciation;

            $journal = match ($funding['type']) {
                'bank' => Ledger::post($agencyId, [
                    'date' => $asset->purchase_date, 'type' => 'asset', 'reference' => $asset->number,
                    'memo' => __('Bought :n', ['n' => $asset->name]), 'created_by' => $userId,
                    'source_type' => 'fixed_asset', 'source_id' => $asset->id, 'source_event' => 'purchase',
                ], [
                    ['account' => $asset->asset_account_id, 'debit' => $cost, 'description' => $asset->name],
                    ['account' => $funding['bank_account_id'], 'credit' => $cost, 'description' => $asset->name],
                ]),
                'opening' => Ledger::post($agencyId, [
                    'date' => self::openingDate($s, $asset), 'type' => 'opening', 'reference' => $asset->number,
                    'memo' => __('Opening balance — :n', ['n' => $asset->name]), 'created_by' => $userId,
                    'source_type' => 'fixed_asset', 'source_id' => $asset->id, 'source_event' => 'purchase',
                ], [
                    ['account' => $asset->asset_account_id, 'debit' => $cost, 'description' => __('Cost')],
                    ['account' => $asset->accumulated_account_id, 'credit' => $opening, 'description' => __('Depreciation to date')],
                    ['account' => 'opening_balance', 'credit' => round($cost - $opening, 2), 'description' => $asset->name],
                ]),
                default => null, // already in the books through a bill or money out
            };
            if ($journal) {
                $asset->update(['purchase_journal_id' => $journal->id]);
            }
            activity('accounts')->performedOn($asset)->causedBy($userId)->log(__('Fixed asset :n added', ['n' => $asset->number]));

            return $asset->fresh();
        });
    }

    private static function openingDate(AccountingSetting $s, FixedAsset $asset): string
    {
        $date = $s->posting_from ?? $asset->purchase_date;
        if ($s->isLocked($date)) {
            $date = $s->lock_date->copy()->addDay();
        }

        return Carbon::parse($date)->toDateString();
    }

    /** One month's charge for an asset, given what's been charged so far. */
    public static function monthlyCharge(FixedAsset $asset, float $accumulated): float
    {
        if ($asset->method === 'none' || (float) $asset->rate <= 0) {
            return 0.0;
        }
        $left = round((float) $asset->cost - (float) $asset->residual_value - $accumulated, 2);
        if ($left <= 0) {
            return 0.0;
        }
        $charge = $asset->method === 'reducing_balance'
            ? ((float) $asset->cost - $accumulated) * (float) $asset->rate / 100 / 12
            : ((float) $asset->cost - (float) $asset->residual_value) * (float) $asset->rate / 100 / 12;

        return round(min($charge, $left), 2);
    }

    /**
     * What a run up to $upTo (a month end) would charge, month by month.
     *
     * @return Collection<int, array{asset: FixedAsset, months: array<string, float>, total: float}>
     */
    public static function preview(string $agencyId, string $upTo, ?FixedAsset $only = null): Collection
    {
        $upTo = Carbon::parse($upTo)->endOfMonth()->startOfDay();
        $assets = $only ? collect([$only]) : FixedAsset::where('agency_id', $agencyId)->where('status', 'active')->orderBy('number')->get();

        return $assets->map(function (FixedAsset $a) use ($upTo) {
            $month = $a->depreciated_to
                ? $a->depreciated_to->copy()->addDay()->endOfMonth()->startOfDay()
                : $a->depreciation_start->copy()->endOfMonth()->startOfDay();
            $acc = $a->accumulated();
            $months = [];
            while ($month->lte($upTo)) {
                $c = self::monthlyCharge($a, $acc);
                if ($c > 0) {
                    $months[$month->toDateString()] = $c;
                    $acc += $c;
                }
                $month = $month->copy()->addDay()->endOfMonth()->startOfDay();
            }

            return ['asset' => $a, 'months' => $months, 'total' => round(array_sum($months), 2)];
        })->filter(fn ($r) => $r['total'] > 0)->values();
    }

    /** Post depreciation for every active asset up to a month end. */
    public static function run(string $agencyId, string $upTo, ?int $userId, ?FixedAsset $only = null): ?Journal
    {
        $upTo = Carbon::parse($upTo)->endOfMonth()->toDateString();
        $rows = self::preview($agencyId, $upTo, $only);
        if ($rows->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($agencyId, $upTo, $rows, $userId, $only) {
            $lines = [];
            foreach ($rows as $r) {
                $a = $r['asset'];
                $span = array_key_first($r['months']) === array_key_last($r['months'])
                    ? Carbon::parse(array_key_first($r['months']))->format('M Y')
                    : Carbon::parse(array_key_first($r['months']))->format('M Y').' – '.Carbon::parse(array_key_last($r['months']))->format('M Y');
                $lines[] = ['account' => $a->expense_account_id, 'debit' => $r['total'], 'description' => $a->number.' '.$a->name.' · '.$span];
                $lines[] = ['account' => $a->accumulated_account_id, 'credit' => $r['total'], 'description' => $a->number.' '.$a->name];
            }
            $journal = Ledger::post($agencyId, [
                'date' => $upTo, 'type' => 'depreciation',
                'reference' => __('Depreciation to :d', ['d' => Carbon::parse($upTo)->format('M Y')]),
                'memo' => $only ? __('Depreciation — :n', ['n' => $only->name]) : __('Depreciation for :n assets to :d', ['n' => $rows->count(), 'd' => Carbon::parse($upTo)->format('d M Y')]),
                'created_by' => $userId,
            ], $lines);
            foreach ($rows as $r) {
                foreach ($r['months'] as $month => $amount) {
                    DepreciationEntry::create(['asset_id' => $r['asset']->id, 'journal_id' => $journal->id, 'period_end' => $month, 'amount' => $amount]);
                }
                $r['asset']->update(['depreciated_to' => array_key_last($r['months'])]);
            }

            return $journal;
        });
    }

    /** Undo the most recent depreciation run (reverses its journal). */
    public static function undoLastRun(string $agencyId, ?int $userId): ?Journal
    {
        $journal = Journal::where('agency_id', $agencyId)->where('type', 'depreciation')->where('status', 'posted')
            ->whereHas('lines')->orderByDesc('date')->orderByDesc('created_at')->first();
        if (! $journal) {
            return null;
        }
        if (DepreciationEntry::where('journal_id', $journal->id)->whereHas('asset', fn ($q) => $q->where('status', 'disposed'))->exists()) {
            throw new LedgerException(__('That run includes an asset that has since been disposed of — undo the disposal first.'));
        }

        return DB::transaction(function () use ($journal, $userId) {
            $assetIds = DepreciationEntry::where('journal_id', $journal->id)->pluck('asset_id')->unique();
            DepreciationEntry::where('journal_id', $journal->id)->delete();
            $rev = Ledger::reverse($journal, __('Depreciation run undone'));
            foreach (FixedAsset::whereIn('id', $assetIds)->get() as $a) {
                $a->update(['depreciated_to' => DepreciationEntry::where('asset_id', $a->id)->max('period_end')]);
            }
            activity('accounts')->causedBy($userId)->log(__('Depreciation run :n undone', ['n' => $journal->number]));

            return $rev;
        });
    }

    public static function dispose(FixedAsset $asset, string $date, float $proceeds, ?string $bankAccountId, ?int $userId): Journal
    {
        if ($asset->isDisposed()) {
            throw new LedgerException(__('Already disposed of.'));
        }
        if ($proceeds > 0 && ! $bankAccountId) {
            throw new LedgerException(__('Choose the account the sale money went into.'));
        }

        return DB::transaction(function () use ($asset, $date, $proceeds, $bankAccountId, $userId) {
            $date = Carbon::parse($date);
            // Charge depreciation up to the end of the month before it went.
            $before = $date->copy()->startOfMonth()->subDay();
            if ($before->gte($asset->depreciation_start->copy()->endOfMonth()->startOfDay())) {
                self::run($asset->agency_id, $before->toDateString(), $userId, $asset);
            }
            $asset->refresh();
            $cost = (float) $asset->cost;
            $acc = $asset->accumulated();
            $gain = round($proceeds - ($cost - $acc), 2);

            $lines = [
                ['account' => $asset->accumulated_account_id, 'debit' => $acc, 'description' => __('Depreciation to date')],
                ['account' => $asset->asset_account_id, 'credit' => $cost, 'description' => __('Cost')],
            ];
            if ($proceeds > 0) {
                $lines[] = ['account' => $bankAccountId, 'debit' => $proceeds, 'description' => __('Sale proceeds')];
            }
            if ($gain > 0) {
                $lines[] = ['account' => 'disposal_gain', 'credit' => $gain, 'description' => __('Profit on disposal')];
            } elseif ($gain < 0) {
                $lines[] = ['account' => 'disposal_loss', 'debit' => -$gain, 'description' => __('Loss on disposal')];
            }
            $journal = Ledger::post($asset->agency_id, [
                'date' => $date, 'type' => 'asset', 'reference' => $asset->number,
                'memo' => __('Disposal of :n', ['n' => $asset->name]), 'created_by' => $userId,
                'source_type' => 'fixed_asset', 'source_id' => $asset->id,
                // numbered, so an asset can be disposed of again after an undo
                'source_event' => 'disposal-'.(Journal::where('source_type', 'fixed_asset')->where('source_id', $asset->id)
                    ->where('source_event', 'like', 'disposal-%')->count() + 1),
            ], $lines);
            $asset->update(['status' => 'disposed', 'disposed_on' => $date->toDateString(), 'disposal_proceeds' => $proceeds, 'disposal_journal_id' => $journal->id]);
            activity('accounts')->performedOn($asset)->causedBy($userId)->log(__('Fixed asset :n disposed of', ['n' => $asset->number]));

            return $journal;
        });
    }

    public static function undoDisposal(FixedAsset $asset, ?int $userId): void
    {
        if (! $asset->isDisposed() || ! $asset->disposal_journal_id) {
            return;
        }
        DB::transaction(function () use ($asset, $userId) {
            Ledger::reverse(Journal::findOrFail($asset->disposal_journal_id), __('Disposal undone'));
            $asset->update(['status' => 'active', 'disposed_on' => null, 'disposal_proceeds' => null, 'disposal_journal_id' => null]);
            activity('accounts')->performedOn($asset)->causedBy($userId)->log(__('Disposal of :n undone', ['n' => $asset->number]));
        });
    }

    /**
     * The property, plant and equipment note for a period: per category,
     * cost and depreciation brought forward, additions, charge, disposals,
     * carried forward, and net book value.
     */
    public static function schedule(string $agencyId, string $from, string $to): array
    {
        $assets = FixedAsset::where('agency_id', $agencyId)->get();
        $entries = DepreciationEntry::whereIn('asset_id', $assets->pluck('id'))->get()->groupBy('asset_id');
        $rows = [];
        foreach ($assets as $a) {
            $purchased = $a->purchase_date->toDateString();
            $gone = $a->disposed_on?->toDateString();
            if ($purchased > $to || ($gone && $gone < $from)) {
                continue;
            }
            $e = $entries[$a->id] ?? collect();
            $depBefore = (float) $a->opening_depreciation + (float) $e->filter(fn ($x) => $x->period_end->toDateString() < $from)->sum('amount');
            $charge = (float) $e->filter(fn ($x) => $x->period_end->toDateString() >= $from && $x->period_end->toDateString() <= $to)->sum('amount');
            $disposedIn = $gone && $gone >= $from && $gone <= $to;
            $isNew = $purchased >= $from;

            $k = $a->category;
            $rows[$k] ??= ['label' => $a->categoryLabel(), 'cost_bf' => 0, 'additions' => 0, 'disposals' => 0, 'cost_cf' => 0,
                'dep_bf' => 0, 'charge' => 0, 'dep_disposals' => 0, 'dep_cf' => 0, 'nbv_cf' => 0, 'nbv_bf' => 0];
            $r = &$rows[$k];
            $isNew ? $r['additions'] += (float) $a->cost : $r['cost_bf'] += (float) $a->cost;
            $r['dep_bf'] += $isNew ? 0 : $depBefore;
            $r['charge'] += $charge + ($isNew ? $depBefore : 0);
            if ($disposedIn) {
                $r['disposals'] += (float) $a->cost;
                $r['dep_disposals'] += $depBefore + $charge;
            }
            unset($r);
        }
        foreach ($rows as &$r) {
            $r['cost_cf'] = round($r['cost_bf'] + $r['additions'] - $r['disposals'], 2);
            $r['dep_cf'] = round($r['dep_bf'] + $r['charge'] - $r['dep_disposals'], 2);
            $r['nbv_cf'] = round($r['cost_cf'] - $r['dep_cf'], 2);
            $r['nbv_bf'] = round($r['cost_bf'] - $r['dep_bf'], 2);
        }
        unset($r);
        $keys = ['cost_bf', 'additions', 'disposals', 'cost_cf', 'dep_bf', 'charge', 'dep_disposals', 'dep_cf', 'nbv_cf', 'nbv_bf'];
        $total = array_combine($keys, array_map(fn ($k) => round(array_sum(array_column($rows, $k)), 2), $keys));

        return ['rows' => $rows, 'total' => $total, 'from' => $from, 'to' => $to];
    }
}
