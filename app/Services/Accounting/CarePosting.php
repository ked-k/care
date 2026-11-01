<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Contact;
use App\Models\Accounting\Document;
use App\Models\Accounting\Journal;
use App\Models\Invoice;
use App\Models\PayrollRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Batch 43: puts care invoicing and payroll into the ledger. Both stay
 * exactly as they were (Finance → Invoicing / Payroll in the care
 * workspace); these methods only look at a record's current state and make
 * the books match it, so they're safe to run any number of times — from the
 * model observers (AutoPosting) or from `php artisan accounts:sync`.
 */
class CarePosting
{
    /**
     * Care invoice issued → a sales invoice on the payer's account
     * (Dr receivables / Cr care income). Marked paid → a receipt into the
     * main bank account, matched to it.
     */
    public static function syncCareInvoice(Invoice $invoice): ?Document
    {
        if (! in_array($invoice->status, ['issued', 'paid'], true)) {
            return null; // drafts never touch the books
        }
        $invoice->loadMissing('invoiceRun', 'lines', 'serviceUser');
        $agencyId = $invoice->invoiceRun?->agency_id;
        if (! $agencyId) {
            return null;
        }
        $settings = AccountingSetting::forAgency($agencyId);
        $issued = Carbon::parse($invoice->issued_at ?? $invoice->paid_at ?? $invoice->created_at);
        if (! $settings->autoPosts('care_invoices') || $settings->beforePostingStart($issued)) {
            return null;
        }

        return DB::transaction(function () use ($invoice, $agencyId, $settings, $issued) {
            $doc = Document::where('agency_id', $agencyId)->where('source_type', 'care_invoice')->where('source_id', $invoice->id)->first();

            if (! $doc) {
                $contact = self::payerContact($invoice, $agencyId);
                $doc = new Document([
                    'agency_id' => $agencyId,
                    'kind' => 'invoice',
                    'number' => self::freeNumber($agencyId, $invoice->invoice_number),
                    'status' => 'draft',
                    'source_type' => 'care_invoice',
                    'source_id' => $invoice->id,
                    'created_by' => $invoice->created_by,
                ]);
                $lines = $invoice->lines->map(fn ($l) => [
                    'account_id' => $settings->account('care_income')->id,
                    'description' => $l->description,
                    'qty' => $l->quantity !== null && $l->rate !== null && (float) $l->quantity != 0 ? (float) $l->quantity : 1,
                    'unit_price' => $l->quantity !== null && $l->rate !== null && (float) $l->quantity != 0 ? (float) $l->rate : (float) $l->amount,
                    'vat_rate' => 0,
                ])->all();
                if (! $lines) {
                    $lines = [['account_id' => $settings->account('care_income')->id, 'description' => __('Care services'), 'qty' => 1, 'unit_price' => (float) $invoice->subtotal, 'vat_rate' => 0]];
                }
                $doc = Documents::saveDraft($doc, $agencyId, 'invoice', [
                    'contact_id' => $contact->id,
                    'issue_date' => $issued->toDateString(),
                    'due_date' => $issued->copy()->addDays($contact->payment_terms_days ?? $settings->payment_terms_days)->toDateString(),
                    'reference' => $invoice->local_authority_reference
                        ?: __('Care :from – :to', ['from' => $invoice->billing_period_start?->format('d M'), 'to' => $invoice->billing_period_end?->format('d M Y')]),
                    'notes' => $invoice->notes,
                ], $lines, $invoice->created_by);

                // Rates × hours can round a penny away from the care invoice's own
                // total; the care invoice is what the payer received, so it wins.
                $diff = round((float) $invoice->total_amount - (float) $doc->total, 2);
                if ($diff != 0 && $doc->lines->isNotEmpty()) {
                    $first = $doc->lines->first();
                    $first->update(['net' => (float) $first->net + $diff, 'total' => (float) $first->total + $diff]);
                    $doc->update(['subtotal' => (float) $doc->subtotal + $diff, 'total' => (float) $doc->total + $diff]);
                }
                // Care tax (if any was ever set) goes on as VAT.
                if ((float) $invoice->tax_amount > 0) {
                    $doc->update(['vat_total' => (float) $invoice->tax_amount, 'total' => (float) $doc->subtotal + (float) $invoice->tax_amount]);
                    $first = $doc->lines()->first();
                    $first?->update(['vat' => (float) $invoice->tax_amount, 'total' => (float) $first->net + (float) $invoice->tax_amount]);
                }
                $doc = Documents::post($doc->fresh('lines'), $invoice->created_by);
            }

            if ($invoice->status === 'paid' && $doc->isOpen() && $doc->balance() > 0) {
                $paidOn = Carbon::parse($invoice->paid_at ?? now());
                Payments::record($agencyId, [
                    'kind' => 'receipt',
                    'contact_id' => $doc->contact_id,
                    'bank_account_id' => $settings->account('bank')->id,
                    'date' => $settings->isLocked($paidOn) ? today() : $paidOn->toDateString(),
                    'amount' => $doc->balance(),
                    'method' => 'bank_transfer',
                    'reference' => $invoice->payment_reference ?: $invoice->invoice_number,
                    'notes' => __('Marked paid in care invoicing'),
                    'source_type' => 'care_invoice_paid',
                    'source_id' => $invoice->id,
                ], [$doc->id => $doc->balance()]);
            }

            return $doc->fresh();
        });
    }

    /**
     * When the mirrored invoice is paid in Finance, tick the care invoice as
     * paid too (without firing its observer, so nothing posts twice).
     */
    public static function markCareInvoicePaid(Document $doc): void
    {
        $inv = Invoice::find($doc->source_id);
        if ($inv && $inv->status !== 'paid') {
            $ref = $doc->allocations()->with('payment')->latest()->first()?->payment?->number;
            Invoice::whereKey($inv->id)->update(['status' => 'paid', 'paid_at' => now(), 'payment_reference' => $inv->payment_reference ?: $ref]);
            $run = $inv->invoiceRun;
            if ($run && $run->invoices()->where('status', '!=', 'paid')->doesntExist()) {
                $run->newQuery()->whereKey($run->id)->update(['status' => 'paid']);
            }
        }
    }

    /** Who a care invoice is billed to, as a Finance customer (created on first use). */
    public static function payerContact(Invoice $invoice, string $agencyId): Contact
    {
        $su = $invoice->serviceUser;
        $la = $invoice->payer_type === 'local_authority';
        $name = $invoice->payer_name ?: ($la ? ($su?->local_authority_name ?: __('Local authority')) : ($su?->name ?? __('Care client')));
        $key = $la ? 'la:'.mb_strtolower(trim($name)) : 'self:'.$invoice->service_user_id;

        $contact = Contact::where('agency_id', $agencyId)->where('payer_key', $key)->first();
        if (! $contact && ! $la && $invoice->service_user_id) {
            // An imported customer already matched to this service user.
            $contact = Contact::where('agency_id', $agencyId)->where('service_user_id', $invoice->service_user_id)->whereNull('payer_key')->first();
            $contact?->update(['payer_key' => $key]);
        }

        return $contact ?? Contact::create([
            'agency_id' => $agencyId,
            'is_customer' => true,
            'name' => $name,
            'email' => $la ? null : $invoice->payer_email,
            'address' => $la ? null : $invoice->payer_address,
            'service_user_id' => $la ? null : $invoice->service_user_id,
            'payer_key' => $key,
            'notes' => $la ? __('Local authority — created from care invoicing') : __('Created from care invoicing'),
        ]);
    }

    private static function freeNumber(string $agencyId, string $wanted): string
    {
        $n = $wanted;
        $i = 1;
        while (Document::where('agency_id', $agencyId)->where('kind', 'invoice')->where('number', $n)->exists()) {
            $n = $wanted.'-'.(++$i);
        }

        return $n;
    }

    /** Care invoice deleted (e.g. its run removed) after it was posted. */
    public static function careInvoiceRemoved(Invoice $invoice): void
    {
        $doc = Document::where('source_type', 'care_invoice')->where('source_id', $invoice->id)->first();
        if ($doc && $doc->status !== 'void') {
            Documents::void($doc, __('The care invoice was deleted'));
        }
    }

    // -- Payroll ---------------------------------------------------------------

    /**
     * Approved → Dr wages (gross) / Cr net pay owed, PAYE, pension and other
     * deductions. Paid → Dr net pay owed / Cr bank. Cancelled after
     * approval → both reversed.
     */
    public static function syncPayrollRun(PayrollRun $run): void
    {
        if (! in_array($run->status, ['approved', 'paid', 'cancelled'], true)) {
            return;
        }
        $settings = AccountingSetting::forAgency($run->agency_id);
        if (! $settings->autoPosts('payroll')) {
            return;
        }
        $approvedOn = Carbon::parse($run->approved_at ?? $run->paid_at ?? $run->updated_at);
        if ($settings->beforePostingStart($approvedOn)) {
            return;
        }

        $approved = Journal::where('agency_id', $run->agency_id)->where('source_type', 'payroll_run')
            ->where('source_id', $run->id)->where('source_event', 'approved')->first();
        $paid = Journal::where('agency_id', $run->agency_id)->where('source_type', 'payroll_run')
            ->where('source_id', $run->id)->where('source_event', 'paid')->first();

        if ($run->status === 'cancelled') {
            foreach ([$paid, $approved] as $j) {
                if ($j && ! $j->isReversed()) {
                    Ledger::reverse($j, __('Payroll run cancelled'));
                }
            }

            return;
        }
        if (! in_array($run->status, ['approved', 'paid'], true)) {
            return;
        }

        if (! $approved) {
            $run->loadMissing('payslips.lines', 'payslips.user');
            $regular = 0;
            $overtime = 0;
            $other = 0;
            $deductions = [];
            foreach ($run->payslips as $p) {
                $reg = round((float) $p->regular_hours * (float) $p->regular_rate, 2);
                $reg = min($reg, (float) $p->gross_pay);
                $regular += $reg;
                $overtime += round((float) $p->gross_pay - $reg, 2);
                foreach ($p->lines as $l) {
                    if ($l->line_type === 'earning') {
                        $other += (float) $l->amount;
                    } else {
                        $key = match ($l->category) {
                            'paye', 'tax', 'income_tax' => 'paye',
                            'nssf', 'pension', 'nic' => 'pension',
                            'loan_repayment', 'advance' => 'staff_loans',
                            default => 'other_deductions',
                        };
                        $deductions[$key] = ($deductions[$key] ?? 0) + (float) $l->amount;
                    }
                }
            }
            $net = (float) $run->payslips->sum('net_pay');
            $labels = ['paye' => __('PAYE withheld'), 'pension' => __('Pension / NSSF withheld'), 'staff_loans' => __('Staff loan repayments'), 'other_deductions' => __('Other deductions')];
            $lines = [
                ['account' => 'wages', 'debit' => $regular + $other, 'description' => __('Gross pay :r', ['r' => $run->reference])],
                ['account' => 'overtime', 'debit' => $overtime, 'description' => __('Overtime :r', ['r' => $run->reference])],
                ['account' => 'net_wages', 'credit' => $net, 'description' => __('Net pay owed — :n staff', ['n' => $run->payslips->count()])],
            ];
            foreach ($deductions as $key => $amt) {
                $lines[] = ['account' => $key, 'credit' => $amt, 'description' => $labels[$key]];
            }
            // Payslip totals are rounded per person; any penny left over goes to wages.
            $diff = round(($regular + $other + $overtime) - ($net + array_sum($deductions)), 2);
            if ($diff != 0) {
                $lines[0]['debit'] -= $diff;
            }
            if (round($net + array_sum($deductions), 2) == 0) {
                return; // an empty run — nothing to post
            }
            $approved = Ledger::post($run->agency_id, [
                'date' => $settings->isLocked($approvedOn) ? today() : $approvedOn,
                'type' => 'payroll',
                'reference' => $run->reference,
                'memo' => __('Payroll :r (:from – :to)', ['r' => $run->reference, 'from' => $run->pay_period_start?->format('d M'), 'to' => $run->pay_period_end?->format('d M Y')]),
                'source_type' => 'payroll_run', 'source_id' => $run->id, 'source_event' => 'approved',
                'created_by' => $run->approved_by, 'system' => true,
            ], $lines);
        }

        if ($run->status === 'paid' && ! $paid) {
            $netAccount = $approved->lines()->orderByDesc('credit')->get()
                ->first(fn ($l) => $l->account_id === $settings->account('net_wages')->id);
            $net = (float) ($netAccount?->credit ?? $run->total_net);
            $paidOn = Carbon::parse($run->paid_at ?? now());
            if ($net > 0) {
                Ledger::post($run->agency_id, [
                    'date' => $settings->isLocked($paidOn) ? today() : $paidOn,
                    'type' => 'payroll',
                    'reference' => $run->reference,
                    'memo' => __('Net pay sent — :r', ['r' => $run->reference]),
                    'source_type' => 'payroll_run', 'source_id' => $run->id, 'source_event' => 'paid',
                    'system' => true,
                ], [
                    ['account' => 'net_wages', 'debit' => $net, 'description' => __('Net pay :r', ['r' => $run->reference])],
                    ['account' => 'bank', 'credit' => $net, 'description' => __('Payroll :r', ['r' => $run->reference])],
                ]);
            }
        }
    }
}
