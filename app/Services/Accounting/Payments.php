<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Document;
use App\Models\Accounting\Payment;
use App\Models\Accounting\PaymentAllocation;
use Illuminate\Support\Facades\DB;

/**
 * Batch 43: money received from customers and paid to suppliers.
 *
 * A receipt posts Dr bank / Cr receivables (against the customer); a
 * supplier payment posts Dr payables / Cr bank. Matching the money to
 * particular invoices or bills ("allocating") doesn't touch the ledger —
 * it only decides which documents show as paid. Anything not matched
 * stays on the contact's account as credit.
 */
class Payments
{
    /**
     * @param  array{kind: string, contact_id: string, bank_account_id: string, date: mixed, amount: float, method?: string, reference?: ?string, notes?: ?string, source_type?: ?string, source_id?: ?string}  $data
     * @param  array<string, float>  $allocations  document id => amount
     */
    public static function record(string $agencyId, array $data, array $allocations = [], ?int $userId = null): Payment
    {
        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw new LedgerException(__('The amount must be more than zero.'));
        }
        if (empty($data['contact_id'])) {
            throw new LedgerException($data['kind'] === 'receipt' ? __('Choose who paid you.') : __('Choose who you paid.'));
        }
        $allocations = array_filter(array_map(fn ($v) => round((float) $v, 2), $allocations), fn ($v) => $v > 0);
        if (round(array_sum($allocations), 2) > $amount + 0.004) {
            throw new LedgerException(__('You have matched more than the amount received.'));
        }

        return DB::transaction(function () use ($agencyId, $data, $allocations, $amount, $userId) {
            if (! empty($data['source_type']) && ($existing = Payment::where('agency_id', $agencyId)
                ->where('source_type', $data['source_type'])->where('source_id', $data['source_id'])->where('status', 'posted')->first())) {
                return $existing;
            }
            $receipt = $data['kind'] === 'receipt';
            $payment = Payment::create([
                'agency_id' => $agencyId,
                'kind' => $data['kind'],
                'number' => AccountingSetting::takeNumber($agencyId, $receipt ? 'receipt' : 'payment'),
                'contact_id' => $data['contact_id'],
                'bank_account_id' => $data['bank_account_id'],
                'date' => $data['date'],
                'amount' => $amount,
                'method' => $data['method'] ?? 'bank_transfer',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'posted',
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'created_by' => $userId,
            ]);
            $payment->load('contact');

            $journal = Ledger::post($agencyId, [
                'date' => $payment->date,
                'type' => $receipt ? 'receipt' : 'payment',
                'reference' => $payment->number.($payment->reference ? ' / '.$payment->reference : ''),
                'memo' => $payment->kindLabel().' — '.$payment->contact->name,
                'source_type' => 'payment',
                'source_id' => $payment->id,
                'source_event' => 'posted',
                'created_by' => $userId,
                'system' => ! empty($data['source_type']),
            ], [
                ['account' => $payment->bank_account_id, 'contact_id' => $payment->contact_id, 'description' => $payment->contact->name,
                    $receipt ? 'debit' : 'credit' => $amount],
                ['account' => $receipt ? 'receivables' : 'payables', 'contact_id' => $payment->contact_id, 'description' => $payment->number,
                    $receipt ? 'credit' : 'debit' => $amount],
            ]);
            $payment->update(['journal_id' => $journal->id]);

            foreach ($allocations as $docId => $amt) {
                self::allocate($payment, Document::findOrFail($docId), $amt);
            }

            return $payment->fresh();
        });
    }

    /** Match (part of) a payment's unallocated money to an open invoice/bill. */
    public static function allocate(Payment $payment, Document $doc, float $amount): PaymentAllocation
    {
        return DB::transaction(function () use ($payment, $doc, $amount) {
            $doc = Document::lockForUpdate()->findOrFail($doc->id);
            $expected = $payment->kind === 'receipt' ? 'invoice' : 'bill';
            if ($doc->kind !== $expected || $doc->contact_id !== $payment->contact_id || $doc->agency_id !== $payment->agency_id) {
                throw new LedgerException(__(':n isn’t an open :k for this contact.', ['n' => $doc->number, 'k' => $expected === 'invoice' ? __('invoice') : __('bill')]));
            }
            if (! $doc->isOpen()) {
                throw new LedgerException(__(':n is not open for payment.', ['n' => $doc->number]));
            }
            $amount = round(min($amount, $doc->balance(), $payment->unallocated()), 2);
            if ($amount <= 0) {
                throw new LedgerException(__('Nothing left to match.'));
            }
            $existing = PaymentAllocation::where('payment_id', $payment->id)->where('document_id', $doc->id)->first();
            $alloc = $existing
                ? tap($existing)->update(['amount' => round((float) $existing->amount + $amount, 2)])
                : PaymentAllocation::create(['payment_id' => $payment->id, 'document_id' => $doc->id, 'amount' => $amount]);
            Documents::refreshPaid($doc);

            return $alloc;
        });
    }

    /** Use a contact's unmatched credit (from any of their payments) against a document. */
    public static function applyCredit(Document $doc, float $amount): float
    {
        $kind = $doc->kind === 'invoice' ? 'receipt' : 'payment';
        $applied = 0;
        foreach (Payment::where('contact_id', $doc->contact_id)->where('kind', $kind)->where('status', 'posted')->orderBy('date')->get() as $p) {
            $left = round($amount - $applied, 2);
            if ($left <= 0) {
                break;
            }
            $free = $p->unallocated();
            if ($free > 0) {
                $before = $doc->fresh()->balance();
                self::allocate($p, $doc, min($free, $left));
                $applied += $before - $doc->fresh()->balance();
            }
        }

        return round($applied, 2);
    }

    public static function unallocatedFor(string $contactId, string $kind): float
    {
        return round(Payment::where('contact_id', $contactId)->where('kind', $kind)->where('status', 'posted')->get()
            ->sum(fn (Payment $p) => $p->unallocated()), 2);
    }

    public static function void(Payment $payment, string $reason, ?int $userId = null): Payment
    {
        return DB::transaction(function () use ($payment, $reason, $userId) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->isVoid()) {
                return $payment;
            }
            $docs = Document::whereIn('id', $payment->allocations()->pluck('document_id'))->get();
            $payment->allocations()->delete();
            foreach ($docs as $d) {
                Documents::refreshPaid($d);
            }
            if ($payment->journal) {
                Ledger::reverse($payment->journal, $reason, null, 'void');
            }
            $payment->update(['status' => 'void', 'voided_at' => now(), 'notes' => trim(($payment->notes ? $payment->notes."\n" : '').__('Voided: :r', ['r' => $reason]))]);
            activity('accounts')->performedOn($payment)->causedBy($userId)->withProperties(['reason' => $reason])
                ->log(__(':k :n voided', ['k' => $payment->kindLabel(), 'n' => $payment->number]));

            return $payment;
        });
    }
}
