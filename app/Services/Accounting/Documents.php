<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingSetting;
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

            $sales = $doc->isSales();
            $name = $doc->contact?->name;
            $lines = [];
            // The other side: the contact's account for invoices/bills, the bank for money in/out.
            $control = match ($doc->kind) {
                'invoice' => 'receivables',
                'bill' => 'payables',
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

            return $doc->fresh();
        });
    }

    /** @throws LedgerException */
    public static function validateForPosting(Document $doc): void
    {
        if ($doc->isOnAccount() && ! $doc->contact_id) {
            throw new LedgerException($doc->kind === 'invoice' ? __('Choose the customer.') : __('Choose the supplier.'));
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
        $paid = round((float) $doc->allocations()->sum('amount'), 2);
        $status = $paid <= 0 ? 'posted' : ($paid + 0.004 >= (float) $doc->total ? 'paid' : 'partial');
        $doc->update(['amount_paid' => $paid, 'status' => $status]);

        if ($status === 'paid' && $doc->source_type === 'care_invoice') {
            CarePosting::markCareInvoicePaid($doc);
        }

        return $doc;
    }
}
