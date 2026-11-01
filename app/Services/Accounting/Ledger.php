<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Journal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Batch 43: the only place journals are written. Every caller — manual
 * journals, invoices, receipts, the automatic posting from care invoicing,
 * payroll and the shop — goes through post() / reverse(), so the rules are
 * enforced in one spot:
 *
 *  - debits must equal credits (to the penny);
 *  - nothing can be dated on or before the lock date, or in a closed year;
 *  - a source (e.g. care invoice X, event "issued") posts at most once —
 *    calling post() again returns the journal already there.
 */
class Ledger
{
    /**
     * @param  array{date: mixed, type: string, reference?: ?string, memo?: ?string, source_type?: ?string, source_id?: ?string, source_event?: ?string, created_by?: ?int, system?: bool}  $header
     * @param  array<int, array{account: Account|string, debit?: float, credit?: float, contact_id?: ?string, description?: ?string}>  $lines
     *                                                                                                                                       `account` is an Account, an account id, or a system key ("receivables").
     *
     * @throws LedgerException
     */
    public static function post(string $agencyId, array $header, array $lines): Journal
    {
        $settings = AccountingSetting::forAgency($agencyId);
        $date = Carbon::parse($header['date'])->startOfDay();

        if (! empty($header['source_type']) && ($existing = self::existing($agencyId, $header))) {
            return $existing;
        }

        $resolved = [];
        $debits = 0;
        $credits = 0;
        foreach ($lines as $i => $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);
            // A negative amount on one side is the same as a positive on the other.
            if ($debit < 0) {
                [$debit, $credit] = [0, $credit - $debit];
            }
            if ($credit < 0) {
                [$debit, $credit] = [$debit - $credit, 0];
            }
            if ($debit == 0 && $credit == 0) {
                continue;
            }
            // A line with both sides nets down to one.
            if ($debit > 0 && $credit > 0) {
                $net = round($debit - $credit, 2);
                [$debit, $credit] = $net >= 0 ? [$net, 0] : [0, -$net];
                if ($net == 0) {
                    continue;
                }
            }
            $account = self::account($settings, $line['account']);
            if (! $account->is_active && empty($header['system'])) {
                throw new LedgerException(__('The account :a is archived — choose another one.', ['a' => $account->label()]));
            }
            $resolved[] = [
                'account_id' => $account->id,
                'contact_id' => $line['contact_id'] ?? null,
                'description' => isset($line['description']) ? mb_substr((string) $line['description'], 0, 255) : null,
                'debit' => $debit,
                'credit' => $credit,
                'sort_order' => $i,
            ];
            $debits += $debit;
            $credits += $credit;
        }

        if (! $resolved) {
            throw new LedgerException(__('There is nothing to post — every line is zero.'));
        }
        if (abs(round($debits - $credits, 2)) >= 0.01) {
            throw new LedgerException(__('Debits (:d) and credits (:c) must be equal.', [
                'd' => $settings->money($debits), 'c' => $settings->money($credits),
            ]));
        }

        self::assertOpen($settings, $date);

        return DB::transaction(function () use ($agencyId, $header, $resolved, $date, $debits, $settings) {
            // Re-check inside the transaction: two requests racing to post the same source.
            if (! empty($header['source_type']) && ($existing = self::existing($agencyId, $header))) {
                return $existing;
            }
            $year = $settings->yearFor($date);
            $journal = Journal::create([
                'agency_id' => $agencyId,
                'financial_year_id' => $year->id,
                'number' => AccountingSetting::takeNumber($agencyId, 'journal'),
                'date' => $date->toDateString(),
                'type' => $header['type'],
                'reference' => isset($header['reference']) ? mb_substr((string) $header['reference'], 0, 255) : null,
                'memo' => $header['memo'] ?? null,
                'source_type' => $header['source_type'] ?? null,
                'source_id' => isset($header['source_id']) ? (string) $header['source_id'] : null,
                'source_event' => $header['source_event'] ?? null,
                'total' => round($debits, 2),
                'status' => 'posted',
                'reverses_id' => $header['reverses_id'] ?? null,
                'created_by' => $header['created_by'] ?? auth()->id(),
            ]);
            foreach ($resolved as $line) {
                $journal->lines()->create($line + ['agency_id' => $agencyId, 'date' => $date->toDateString()]);
            }

            return $journal;
        });
    }

    /**
     * Post the mirror image of a journal. Dated the same day when that's
     * still open, otherwise the first open day after the lock date.
     */
    public static function reverse(Journal $journal, ?string $reason = null, $date = null, ?string $event = null): Journal
    {
        if ($journal->isReversed() && $journal->reversed_by_id) {
            return Journal::find($journal->reversed_by_id);
        }
        $settings = AccountingSetting::forAgency($journal->agency_id);
        $date = $date ? Carbon::parse($date) : $journal->date->copy();
        if ($settings->isLocked($date) || $settings->yearFor($date)->isClosed()) {
            $date = today()->max($settings->lock_date?->copy()->addDay() ?? today());
        }

        return DB::transaction(function () use ($journal, $reason, $date, $event) {
            $reversal = self::post($journal->agency_id, [
                'date' => $date,
                'type' => 'reversal',
                'reference' => $journal->number,
                'memo' => trim(__('Reversal of :n', ['n' => $journal->number]).($reason ? ' — '.$reason : '')),
                'source_type' => $journal->source_type,
                'source_id' => $journal->source_id,
                'source_event' => $event ?? ($journal->source_type ? 'rev-'.($journal->source_event ?? 'x') : null),
                'reverses_id' => $journal->id,
                'system' => true,
            ], $journal->lines->map(fn ($l) => [
                'account' => $l->account_id,
                'contact_id' => $l->contact_id,
                'description' => $l->description,
                'debit' => (float) $l->credit,
                'credit' => (float) $l->debit,
            ])->all());
            $journal->update(['status' => 'reversed', 'reversed_by_id' => $reversal->id]);
            $reversal->update(['status' => 'reversed']);

            return $reversal;
        });
    }

    public static function existing(string $agencyId, array $header): ?Journal
    {
        return Journal::where('agency_id', $agencyId)
            ->where('source_type', $header['source_type'])
            ->where('source_id', (string) $header['source_id'])
            ->where('source_event', $header['source_event'] ?? null)
            ->first();
    }

    /** @throws LedgerException */
    public static function assertOpen(AccountingSetting $settings, $date): void
    {
        $date = Carbon::parse($date);
        if ($settings->isLocked($date)) {
            throw new LedgerException(__('The books are locked up to :d. Choose a later date, or move the lock date in Financial years.', [
                'd' => $settings->lock_date->format('d M Y'),
            ]));
        }
        $year = \App\Models\Accounting\FinancialYear::where('agency_id', $settings->agency_id)
            ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->first();
        if ($year && $year->isClosed()) {
            throw new LedgerException(__(':y is closed. Re-open it in Financial years to post into it.', ['y' => $year->name]));
        }
    }

    private static function account(AccountingSetting $settings, Account|string $ref): Account
    {
        if ($ref instanceof Account) {
            return $ref;
        }
        if (strlen($ref) === 36 && ($a = Account::where('agency_id', $settings->agency_id)->find($ref))) {
            return $a;
        }

        return $settings->account($ref);
    }
}
