<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Journal;
use App\Models\Accounting\VatReturn;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Batch 44c: the VAT return, worked out from the ledger (standard /
 * accrual basis — VAT counts on the invoice or bill date).
 *
 *  - Sales-side journals (invoices, credit notes, money in, shop sales):
 *    VAT on them goes in box 1, their income (net of VAT) in box 6.
 *  - Purchase-side journals (bills, supplier credits, money out, goods
 *    received): VAT goes in box 4, their cost in box 7.
 *  - Anything else that touches a VAT account (a manual journal, say) is
 *    an adjustment: a net credit adds to box 1, a net debit to box 4.
 *  - A reversal counts on the same side as the journal it reverses, so a
 *    voided invoice drops out of box 1 by itself.
 *
 * Filing a return stamps every journal it used (vat_return_id), so a late
 * entry dated in an already-filed period is picked up by the next return
 * rather than being missed or counted twice.
 */
class VatReturns
{
    public const SALES = ['invoice', 'credit_note', 'money_in', 'shop_sale'];
    public const PURCHASES = ['bill', 'bill_credit', 'money_out', 'grn'];
    /** Never part of a return: brought-forward balances, year-end, and paying the VAT itself. */
    public const EXCLUDED = ['opening', 'year_end', 'vat_payment'];

    public const BOXES = [
        'box1' => 'VAT due on sales and other outputs',
        'box2' => 'VAT due on acquisitions from EU member states (Northern Ireland only)',
        'box3' => 'Total VAT due (box 1 + box 2)',
        'box4' => 'VAT reclaimed on purchases and other inputs',
        'box5' => 'Net VAT to pay, or reclaim (box 3 − box 4)',
        'box6' => 'Total value of sales and all other outputs, excluding VAT',
        'box7' => 'Total value of purchases and all other inputs, excluding VAT',
        'box8' => 'Total value of supplies of goods to EU member states (Northern Ireland only)',
        'box9' => 'Total value of acquisitions of goods from EU member states (Northern Ireland only)',
    ];

    public function __construct(public AccountingSetting $settings)
    {
    }

    public static function for(string $agencyId): self
    {
        return new self(AccountingSetting::forAgency($agencyId));
    }

    // -- Periods ------------------------------------------------------------------

    /** @return array{0: Carbon, 1: Carbon} the VAT period a date falls in */
    public function periodFor($date): array
    {
        $date = Carbon::parse($date)->startOfMonth();
        $months = in_array((int) $this->settings->vat_period_months, [1, 3, 12], true) ? (int) $this->settings->vat_period_months : 3;
        $endMonth = max(1, min(12, (int) ($this->settings->vat_period_end_month ?: 3)));
        for ($k = 0; $k < 12; $k++) {
            $m = $date->copy()->addMonthsNoOverflow($k);
            if ((($m->month - $endMonth) % $months + $months) % $months === 0) {
                $end = $m->copy()->endOfMonth()->startOfDay();

                return [$end->copy()->subMonthsNoOverflow($months - 1)->startOfMonth(), $end];
            }
        }

        return [$date, $date->copy()->endOfMonth()->startOfDay()]; // unreachable
    }

    /** Filing deadline: one calendar month and seven days after the period ends (HMRC). */
    public function dueDate(Carbon $end): Carbon
    {
        return $end->copy()->addDay()->addMonthNoOverflow()->subDay()->addDays(7);
    }

    public function latestFiled(): ?VatReturn
    {
        return VatReturn::where('agency_id', $this->settings->agency_id)->orderByDesc('period_end')->first();
    }

    /** The next period to file: straight after the last one filed, or from the VAT start date. */
    public function nextPeriod(): array
    {
        if ($last = $this->latestFiled()) {
            return $this->periodFor($last->period_end->copy()->addDay());
        }
        $from = $this->settings->vat_start_date
            ?? $this->earliestVatDate()
            ?? today();

        return $this->periodFor($from);
    }

    private function earliestVatDate(): ?Carbon
    {
        $d = DB::table('acc_journal_lines as l')->join('acc_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('l.agency_id', $this->settings->agency_id)->whereIn('l.account_id', $this->vatIds() ?: ['-'])
            ->whereNotIn('j.type', self::EXCLUDED)->min('l.date');

        return $d ? Carbon::parse($d) : null;
    }

    /** Every account of the VAT type (the control account plus any sub-accounts). */
    public function vatIds(): array
    {
        return Account::where('agency_id', $this->settings->agency_id)->where('subtype', 'vat')->pluck('id')->all();
    }

    // -- Working out the boxes --------------------------------------------------------

    /**
     * The return for a period. For one already filed, exactly the journals it
     * used; otherwise everything dated in the period not yet on a return, plus
     * any late entries dated in earlier, already-filed periods.
     */
    public function compute($start, $end, ?VatReturn $filed = null): array
    {
        $start = Carbon::parse($start)->toDateString();
        $end = Carbon::parse($end)->toDateString();
        $agency = $this->settings->agency_id;

        $q = Journal::where('agency_id', $agency)->with(['lines.account:id,type,subtype', 'reverses:id,type']);
        if ($filed) {
            $q->where('vat_return_id', $filed->id);
        } else {
            $floor = VatReturn::where('agency_id', $agency)->min('period_start');
            $q->whereNull('vat_return_id')->where('date', '<=', $end)
                ->where('date', '>=', $floor && $floor < $start ? $floor : $start);
        }
        $vatIds = array_flip($this->vatIds());

        $boxes = array_fill_keys(array_keys(self::BOXES), 0.0);
        $rows = collect();
        foreach ($q->orderBy('date')->orderBy('number')->get() as $j) {
            $row = $this->classify($j, $vatIds);
            if (! $row) {
                continue;
            }
            $boxes['box1'] += $row['box1'];
            $boxes['box4'] += $row['box4'];
            $boxes['box6'] += $row['box6'];
            $boxes['box7'] += $row['box7'];
            $row['late'] = ! $filed && $j->date->toDateString() < $start;
            $rows->push($row);
        }

        $boxes['box1'] = round($boxes['box1'], 2);
        $boxes['box4'] = round($boxes['box4'], 2);
        $boxes['box3'] = round($boxes['box1'] + $boxes['box2'], 2);
        $boxes['box5'] = round($boxes['box3'] - $boxes['box4'], 2);
        // Boxes 6–9 are whole pounds — pence are left off, not rounded.
        foreach (['box6', 'box7', 'box8', 'box9'] as $b) {
            $boxes[$b] = (float) ($boxes[$b] < 0 ? -floor(-round($boxes[$b], 2)) : floor(round($boxes[$b], 2)));
        }

        $vatBalance = round(-(float) DB::table('acc_journal_lines')->where('agency_id', $agency)
            ->whereIn('account_id', array_keys($vatIds) ?: ['-'])->where('date', '<=', $end)->sum(DB::raw('debit - credit')), 2);

        return [
            'start' => $start, 'end' => $end,
            'due' => $this->dueDate(Carbon::parse($end))->toDateString(),
            'boxes' => $boxes,
            'rows' => $rows,
            'late' => $rows->where('late', true),
            'bySide' => $rows->groupBy('side')->map(fn (Collection $g) => [
                'count' => $g->count(),
                'vat' => round($g->sum(fn ($r) => $r['box1'] - $r['box4']), 2),
                'net' => round($g->sum(fn ($r) => $r['box6'] + $r['box7']), 2),
            ]),
            'vatBalance' => $vatBalance, // what the VAT accounts say is owed at the period end
        ];
    }

    /** One journal's part in the return, or null when it plays none. */
    private function classify(Journal $j, array $vatIds): ?array
    {
        $type = $j->type === 'reversal' ? ($j->reverses?->type ?? 'manual') : $j->type;
        if (in_array($type, self::EXCLUDED, true)) {
            return null;
        }
        $vat = 0.0; // credit − debit on VAT accounts
        $income = 0.0;
        $costs = 0.0;
        foreach ($j->lines as $l) {
            $cr = (float) $l->credit - (float) $l->debit;
            $a = $l->account;
            if (isset($vatIds[$l->account_id])) {
                $vat += $cr;
            } elseif ($a?->type === 'income') {
                $income += $cr;
            } elseif (in_array($a?->type, ['expense', 'asset'], true)
                && ! in_array($a->subtype, array_merge(Account::MONEY_SUBTYPES, ['receivable']), true)) {
                $costs -= $cr;
            }
        }
        $side = in_array($type, self::SALES, true) ? 'sales' : (in_array($type, self::PURCHASES, true) ? 'purchases' : 'adjustments');
        if ($side === 'adjustments' && abs($vat) < 0.005) {
            return null;
        }
        $row = ['journal' => $j, 'type' => $type, 'side' => $side, 'box1' => 0.0, 'box4' => 0.0, 'box6' => 0.0, 'box7' => 0.0];
        if ($side === 'sales') {
            $row['box1'] = round($vat, 2);
            $row['box6'] = round($income, 2);
        } elseif ($side === 'purchases') {
            $row['box4'] = round(-$vat, 2);
            $row['box7'] = round($costs, 2);
        } elseif ($vat > 0) {
            $row['box1'] = round($vat, 2);
        } else {
            $row['box4'] = round(-$vat, 2);
        }
        if ($side !== 'adjustments' && abs($row['box1']) + abs($row['box4']) + abs($row['box6']) + abs($row['box7']) < 0.005) {
            return null;
        }

        return $row;
    }

    // -- Filing, paying, undoing ----------------------------------------------------------

    /** @throws LedgerException */
    public function file(string $start, string $end, array $data, ?int $userId = null): VatReturn
    {
        [$expStart, $expEnd] = $this->nextPeriod();
        if ($start !== $expStart->toDateString() || $end !== $expEnd->toDateString()) {
            throw new LedgerException(__('Returns are filed in order — the next one due is :a to :b.', [
                'a' => $expStart->format('j M Y'), 'b' => $expEnd->format('j M Y'),
            ]));
        }
        if (Carbon::parse($end)->gte(today())) {
            throw new LedgerException(__('This period hasn’t ended yet — it can be filed from :d.', ['d' => Carbon::parse($end)->addDay()->format('j M Y')]));
        }

        return DB::transaction(function () use ($start, $end, $data, $userId) {
            $calc = $this->compute($start, $end);
            $ret = VatReturn::create([
                'agency_id' => $this->settings->agency_id,
                'period_start' => $start, 'period_end' => $end, 'due_date' => $calc['due'],
                'status' => 'filed', 'boxes' => $calc['boxes'], 'journal_count' => $calc['rows']->count(),
                'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
                'filed_by' => $userId, 'filed_at' => now(),
            ]);
            Journal::whereIn('id', $calc['rows']->map(fn ($r) => $r['journal']->id)->all() ?: ['-'])->update(['vat_return_id' => $ret->id]);

            // Optionally stop anything else being dated into the filed period.
            if (! empty($data['lock']) && (! $this->settings->lock_date || $this->settings->lock_date->toDateString() < $end)) {
                $this->settings->update(['lock_date' => $end]);
            }
            if (abs($calc['boxes']['box5']) < 0.005) {
                $ret->update(['status' => 'paid']); // nothing to pay
            }
            activity('accounts')->performedOn($ret)->causedBy($userId)->withProperties(['boxes' => $calc['boxes']])
                ->log(__('VAT return :p filed', ['p' => $ret->label()]));

            return $ret;
        });
    }

    /** Record paying the VAT (or receiving a refund): Dr VAT / Cr bank, or the other way. */
    public function pay(VatReturn $ret, string $bankAccountId, string $date, ?int $userId = null): VatReturn
    {
        if ($ret->isPaid()) {
            throw new LedgerException(__('This return is already marked as paid.'));
        }
        $net = $ret->net();
        $vat = $this->settings->account('vat');
        $n = Journal::where('agency_id', $ret->agency_id)->where('source_type', 'vat_return')->where('source_id', $ret->id)->count();

        return DB::transaction(function () use ($ret, $bankAccountId, $date, $userId, $net, $vat, $n) {
            $j = Ledger::post($ret->agency_id, [
                'date' => $date, 'type' => 'vat_payment', 'reference' => __('VAT :p', ['p' => $ret->label()]),
                'memo' => $net >= 0 ? __('VAT paid for :p', ['p' => $ret->label()]) : __('VAT refund for :p', ['p' => $ret->label()]),
                'source_type' => 'vat_return', 'source_id' => $ret->id, 'source_event' => 'paid-'.($n + 1), 'created_by' => $userId,
            ], [
                ['account' => $vat, $net >= 0 ? 'debit' : 'credit' => abs($net), 'description' => __('VAT return :p', ['p' => $ret->label()])],
                ['account' => $bankAccountId, $net >= 0 ? 'credit' : 'debit' => abs($net), 'description' => $net >= 0 ? __('VAT payment') : __('VAT refund')],
            ]);
            $ret->update(['status' => 'paid', 'payment_journal_id' => $j->id, 'paid_on' => $date]);

            return $ret->fresh();
        });
    }

    public function undoPayment(VatReturn $ret, ?int $userId = null): VatReturn
    {
        return DB::transaction(function () use ($ret, $userId) {
            if ($ret->paymentJournal) {
                $n = Journal::where('agency_id', $ret->agency_id)->where('source_type', 'vat_return')->where('source_id', $ret->id)->count();
                Ledger::reverse($ret->paymentJournal, __('VAT payment undone'), null, 'rev-paid-'.$n);
            }
            $ret->update(['status' => 'filed', 'payment_journal_id' => null, 'paid_on' => null]);

            return $ret->fresh();
        });
    }

    /** Take back the most recent return (e.g. filed by mistake) so its period can be redone. */
    public function reopen(VatReturn $ret, ?int $userId = null): void
    {
        $latest = $this->latestFiled();
        if (! $latest || $latest->id !== $ret->id) {
            throw new LedgerException(__('Only the most recent return can be re-opened.'));
        }
        if ($ret->paymentJournal) {
            throw new LedgerException(__('Undo the payment first.'));
        }
        DB::transaction(function () use ($ret, $userId) {
            Journal::where('vat_return_id', $ret->id)->update(['vat_return_id' => null]);
            activity('accounts')->causedBy($userId)->withProperties(['boxes' => $ret->boxes])
                ->log(__('VAT return :p re-opened', ['p' => $ret->label()]));
            $ret->delete();
        });
    }
}
