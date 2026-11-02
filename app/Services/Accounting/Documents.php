<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\CreditAllocation;
use App\Models\Accounting\Document;
use Illuminate\Support\Facades\DB;

/**
 * Batch 43: invoices, bills and money in / money out. Works out line
 * totals, posts the journal when a document is approved, and voids it
 * (reversing journal) when it needs to be cancelled.
 */
class Documents
{
    /**
     * Line maths. Prices are ex-VAT unless $inclusive. Discount is an
     * amount off the line, before VAT.
     *
     * @return array{net: float, vat: float, total: float}
     */
    public static function lineTotals(float $qty, float $unitPrice, float $discount, float $vatRate, bool $inclusive = false): array
    {
        $gross = round($qty * $unitPrice - $discount, 2);
        if ($inclusive) {
            $net = $vatRate > 0 ? round($gross / (1 + $vatRate / 100), 2) : $gross;
            $vat = round($gross - $net, 2);
        } else {
            $net = $gross;
            $vat = round($net * $vatRate / 100, 2);
        }

        return ['net' => $net, 'vat' => $vat, 'total' => round($net + $vat, 2)];
    }

    /**
     * Create or update a draft from form data.
     *
     * @param  array<int, array{account_id: ?string, description: ?string, qty: mixed, unit_price: mixed, discount?: mixed, vat_rate?: mixed}>  $lines
     */
    public static function saveDraft(?Document $doc, string $agencyId, string $kind, array $header, array $lines, ?int $userId = null): Document
    {
        return DB::transaction(function () use ($doc, $agencyId, $kind, $header, $lines, $userId) {
            if ($doc && ! $doc->isDraft()) {
                throw new LedgerException(__('Only a draft can be edited. Void it and create a new one instead.'));
            }
            $doc ??= new Document([
                'agency_id' => $agencyId,
                'kind' => $kind,
                'number' => AccountingSetting::takeNumber($agencyId, $kind),
                'status' => 'draft',
                'created_by' => $userId,
            ]);
            $doc->fill(array_intersect_key($header, array_flip([
                'contact_id', 'bank_account_id', 'issue_date', 'due_date', 'reference', 'notes', 'prices_include_vat',
                'source_type', 'source_id',
            ])));
            $doc->save();

            $doc->lines()->delete();
            [$sub, $vat, $tot] = [0, 0, 0];
            foreach (array_values($lines) as $i => $l) {
                $qty = (float) ($l['qty'] ?? 1);
                $price = (float) ($l['unit_price'] ?? 0);
                if ($qty == 0 && $price == 0 && trim((string) ($l['description'] ?? '')) === '') {
                    continue;
                }
                $t = self::lineTotals($qty, $price, (float) ($l['discount'] ?? 0), (float) ($l['vat_rate'] ?? 0), (bool) $doc->prices_include_vat);
                $doc->lines()->create([
                    'account_id' => $l['account_id'] ?: null,
                    'description' => $l['description'] ?? null,
                    'qty' => $qty,
                    'unit_price' => $price,
                    'discount' => (float) ($l['discount'] ?? 0),
                    'vat_rate' => (float) ($l['vat_rate'] ?? 0),
                    'sort_order' => $i,
                ] + $t);
                $sub += $t['net'];
                $vat += $t['vat'];
                $tot += $t['total'];
            }
            $doc->update(['subtotal' => round($sub, 2), 'vat_total' => round($vat, 2), 'total' => round($tot, 2)]);

            return $doc->fresh('lines');
        });
    }

    /** Approve a draft: post its journal and open it for payment. */
    public static function post(Document $doc, ?int $userId = null): Document
    {
        return DB::transaction(function () use ($doc, $userId) {
            $doc = Document::with('lines.account', 'contact')->lockForUpdate()->findOrFail($doc->id);
            if (! $doc->isDraft()) {
                return $doc;
            }
            self::validateForPosting($doc);

            // Invoices and money in credit income; bills and money out debit
            // costs; a credit note posts the opposite way to what it cancels.
            $sales = $doc->postsAsIncome();
            $name = $doc->contact?->name;
            $lines = [];
            // The other side: the contact's account for invoices/bills/credit notes, the bank for money in/out.
            $control = match ($doc->kind) {
                'invoice', 'credit_note' => 'receivables',
                'bill', 'bill_credit' => 'payables',
                default => $doc->bank_account_id,
            };
            $lines[] = [
                'account' => $control,
                'contact_id' => $doc->contact_id,
                'description' => trim($doc->number.' '.($name ?? '')),
                $sales ? 'debit' : 'credit' => (float) $doc->total,
            ];
            foreach ($doc->lines as $l) {
                $lines[] = [
                    'account' => $l->account_id,
                    'contact_id' => $doc->contact_id,
                    'description' => mb_substr((string) ($l->description ?: $doc->number), 0, 255),
                    $sales ? 'credit' : 'debit' => (float) $l->net,
                ];
            }
            if ((float) $doc->vat_total != 0) {
                $lines[] = [
                    'account' => 'vat',
                    'contact_id' => $doc->contact_id,
                    'description' => __('VAT on :n', ['n' => $doc->number]),
                    $sales ? 'credit' : 'debit' => (float) $doc->vat_total,
                ];
            }

            $journal = Ledger::post($doc->agency_id, [
                'date' => $doc->issue_date,
                'type' => $doc->kind,
                'reference' => $doc->number.($doc->reference ? ' / '.$doc->reference : ''),
                'memo' => $doc->kindLabel().($name ? ' — '.$name : ''),
                'source_type' => 'document',
                'source_id' => $doc->id,
                'source_event' => 'posted',
                'created_by' => $userId,
                'system' => (bool) $doc->source_type,
            ], $lines);

            $onAccount = $doc->isOnAccount();
            $doc->update([
                'journal_id' => $journal->id,
                'status' => $onAccount ? 'posted' : 'paid',
                'amount_paid' => $onAccount ? 0 : $doc->total,
                'posted_at' => now(),
            ]);
            if ($onAccount) {
                self::refreshPaid($doc);
            }
            // Batch 44c: a credit note raised against an invoice that's still
            // open is used against it straight away.
            if ($doc->isCredit() && $doc->credited_document_id && ($orig = Document::find($doc->credited_document_id)) && $orig->isOpen()) {
                self::applyCredit($doc->fresh(), $orig, min($doc->total, $orig->balance()), $doc->issue_date->toDateString(), $userId);
            }

            return $doc->fresh();
        });
    }

    /** @throws LedgerException */
    public static function validateForPosting(Document $doc): void
    {
        if ($doc->isOnAccount() && ! $doc->contact_id) {
            throw new LedgerException($doc->isCustomerSide() ? __('Choose the customer.') : __('Choose the supplier.'));
        }
        if ($doc->isCredit() && $doc->credited_document_id) {
            $orig = Document::find($doc->credited_document_id);
            if (! $orig || $orig->kind !== Document::CREDIT_FOR[$doc->kind] || $orig->contact_id !== $doc->contact_id) {
                throw new LedgerException(__('The credit note must be for the same customer or supplier as the original.'));
            }
            if ($orig->status === 'void' || $orig->isDraft()) {
                throw new LedgerException(__(':n isn’t posted — there is nothing to credit.', ['n' => $orig->number]));
            }
            $already = (float) Document::where('credited_document_id', $orig->id)->where('id', '!=', $doc->id)
                ->whereNotIn('status', ['draft', 'void'])->sum('total');
            if ((float) $doc->total > round((float) $orig->total - $already, 2) + 0.004) {
                throw new LedgerException(__('That credits more than :n is worth — :a has been credited already out of :t.', [
                    'n' => $orig->number, 'a' => AccountingSetting::forAgency($doc->agency_id)->money($already),
                    't' => AccountingSetting::forAgency($doc->agency_id)->money($orig->total),
                ]));
            }
        }
        if (! $doc->isOnAccount() && ! $doc->bank_account_id) {
            throw new LedgerException(__('Choose the bank or cash account the money went through.'));
        }
        if ($doc->lines->isEmpty()) {
            throw new LedgerException(__('Add at least one line.'));
        }
        foreach ($doc->lines as $i => $l) {
            if (! $l->account_id) {
                throw new LedgerException(__('Line :n needs an account.', ['n' => $i + 1]));
            }
        }
        if ((float) $doc->total <= 0) {
            throw new LedgerException(__('The total must be more than zero.'));
        }
    }

    /**
     * Cancel a posted document by reversing its journal. Payments that were
     * matched to it stay on the contact's account as credit to re-use.
     *
     * @return int how many payment allocations were released
     */
    public static function void(Document $doc, string $reason, ?int $userId = null): int
    {
        return DB::transaction(function () use ($doc, $reason, $userId) {
            $doc = Document::lockForUpdate()->findOrFail($doc->id);
            if ($doc->status === 'void') {
                return 0;
            }
            if ($doc->isDraft()) {
                $doc->update(['status' => 'void', 'voided_at' => now(), 'void_reason' => $reason]);

                return 0;
            }
            $released = $doc->allocations()->count();
            $doc->allocations()->delete();
            // Batch 44c: credit used against it (or, for a credit note, where
            // it was used) goes back to being available.
            $linked = $doc->isCredit() ? $doc->creditUses()->pluck('document_id') : $doc->creditsApplied()->pluck('credit_note_id');
            $released += $linked->count();
            ($doc->isCredit() ? $doc->creditUses() : $doc->creditsApplied())->delete();
            foreach (Document::whereIn('id', $linked)->get() as $other) {
                self::refreshPaid($other);
            }
            if ($doc->journal) {
                Ledger::reverse($doc->journal, $reason, null, 'void');
            }
            $doc->update(['status' => 'void', 'amount_paid' => 0, 'voided_at' => now(), 'void_reason' => $reason]);
            activity('accounts')->performedOn($doc)->causedBy($userId)->withProperties(['reason' => $reason])
                ->log(__(':k :n voided', ['k' => $doc->kindLabel(), 'n' => $doc->number]));

            return $released;
        });
    }

    /** Re-total what's been paid against a document and set its status. */
    public static function refreshPaid(Document $doc): Document
    {
        if (! $doc->isOnAccount() || in_array($doc->status, ['draft', 'void'], true)) {
            return $doc;
        }
        // Payments (or, for a credit note, refunds) plus credit used.
        $paid = round((float) $doc->allocations()->sum('amount')
            + (float) ($doc->isCredit() ? $doc->creditUses()->sum('amount') : $doc->creditsApplied()->sum('amount')), 2);
        $status = $paid <= 0 ? 'posted' : ($paid + 0.004 >= (float) $doc->total ? 'paid' : 'partial');
        $doc->update(['amount_paid' => $paid, 'status' => $status]);

        if ($status === 'paid' && $doc->source_type === 'care_invoice') {
            CarePosting::markCareInvoicePaid($doc);
        }

        return $doc;
    }

    // -- Batch 44c: credit notes ------------------------------------------------------

    /**
     * A draft credit note for the whole of an invoice (or a supplier credit
     * note for a bill), copying its lines — edit it down for a part credit.
     */
    public static function creditFrom(Document $orig, ?int $userId = null): Document
    {
        $kind = array_search($orig->kind, Document::CREDIT_FOR, true);
        if (! $kind) {
            throw new LedgerException(__('Only sales invoices and supplier bills can be credited.'));
        }
        if ($orig->isDraft() || $orig->status === 'void') {
            throw new LedgerException(__('Only a posted :k can be credited — a draft can simply be edited or deleted.', ['k' => mb_strtolower($orig->kindLabel())]));
        }
        $already = (float) Document::where('credited_document_id', $orig->id)->whereNotIn('status', ['draft', 'void'])->sum('total');
        $left = round((float) $orig->total - $already, 2);
        if ($left <= 0.004) {
            throw new LedgerException(__(':n has already been credited in full.', ['n' => $orig->number]));
        }
        $lines = $orig->lines->map(fn ($l) => [
            'account_id' => $l->account_id, 'description' => $l->description, 'qty' => (float) $l->qty,
            'unit_price' => (float) $l->unit_price, 'discount' => (float) $l->discount, 'vat_rate' => (float) $l->vat_rate,
        ])->all();
        if ($already > 0) {
            // Part credited already: one line for what's left, at the invoice's main account and rate.
            $first = $orig->lines->first();
            $rate = (float) ($first?->vat_rate ?? 0);
            $lines = [[
                'account_id' => $first?->account_id, 'description' => __('Balance of :n', ['n' => $orig->number]),
                'qty' => 1, 'unit_price' => $orig->prices_include_vat ? $left : round($left / (1 + $rate / 100), 2),
                'discount' => 0, 'vat_rate' => $rate,
            ]];
        }

        $draft = self::saveDraft(null, $orig->agency_id, $kind, [
            'contact_id' => $orig->contact_id,
            'issue_date' => today()->toDateString(),
            'reference' => $orig->number,
            'prices_include_vat' => (bool) $orig->prices_include_vat,
            'notes' => null,
        ], $lines, $userId);
        $draft->update(['credited_document_id' => $orig->id]);

        return $draft;
    }

    /** Use (part of) a credit note against an open invoice of the same contact. */
    public static function applyCredit(Document $credit, Document $target, float $amount, ?string $date = null, ?int $userId = null): CreditAllocation
    {
        return DB::transaction(function () use ($credit, $target, $amount, $date, $userId) {
            $credit = Document::lockForUpdate()->findOrFail($credit->id);
            $target = Document::lockForUpdate()->findOrFail($target->id);
            if (! $credit->isCredit() || $target->kind !== Document::CREDIT_FOR[$credit->kind]
                || $target->contact_id !== $credit->contact_id || $target->agency_id !== $credit->agency_id) {
                throw new LedgerException(__(':c can only be used against a :k for the same contact.', [
                    'c' => $credit->number, 'k' => mb_strtolower(__(Document::KINDS[Document::CREDIT_FOR[$credit->kind] ?? 'invoice'][0])),
                ]));
            }
            if (! $credit->isOpen()) {
                throw new LedgerException(__(':n has no credit left to use.', ['n' => $credit->number]));
            }
            if (! $target->isOpen()) {
                throw new LedgerException(__(':n is not open.', ['n' => $target->number]));
            }
            $amount = round(min($amount, $credit->balance(), $target->balance()), 2);
            if ($amount <= 0) {
                throw new LedgerException(__('Nothing left to match.'));
            }
            $alloc = CreditAllocation::create([
                'agency_id' => $credit->agency_id, 'credit_note_id' => $credit->id, 'document_id' => $target->id,
                'amount' => $amount, 'date' => $date ?? today()->toDateString(), 'created_by' => $userId,
            ]);
            self::refreshPaid($credit);
            self::refreshPaid($target);

            return $alloc;
        });
    }

    public static function unapplyCredit(CreditAllocation $alloc): void
    {
        DB::transaction(function () use ($alloc) {
            $docs = Document::whereIn('id', [$alloc->credit_note_id, $alloc->document_id])->get();
            $alloc->delete();
            foreach ($docs as $d) {
                self::refreshPaid($d);
            }
        });
    }

    /** Open credit notes a contact has that could be used against $doc. */
    public static function openCreditsFor(Document $doc)
    {
        $kind = array_search($doc->kind, Document::CREDIT_FOR, true);

        return $kind ? Document::where('agency_id', $doc->agency_id)->where('contact_id', $doc->contact_id)
            ->where('kind', $kind)->whereIn('status', ['posted', 'partial'])->orderBy('issue_date')->get() : collect();
    }
}
