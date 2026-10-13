<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_run_id', 'service_user_id', 'payer_type', 'split_group_id',
        'invoice_number', 'billing_period_start', 'billing_period_end',
        'payer_name', 'payer_address', 'payer_email', 'local_authority_reference',
        'subtotal', 'tax_amount', 'total_amount',
        'status', 'issued_at', 'paid_at', 'payment_reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function invoiceRun(): BelongsTo
    {
        return $this->belongsTo(InvoiceRun::class);
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * The other half of a split invoice pair (one self_funded, one
     * local_authority, same service user/period) — null for a non-split
     * invoice. See InvoiceRun::generateInvoices().
     */
    public function splitCounterpart(): ?Invoice
    {
        if (! $this->split_group_id) {
            return null;
        }

        return static::where('split_group_id', $this->split_group_id)
            ->where('id', '!=', $this->id)
            ->first();
    }

    public function recalculateTotal(): void
    {
        $this->subtotal = $this->lines()->sum('amount');
        $this->total_amount = $this->subtotal + $this->tax_amount;
        $this->save();
    }

    public function issue(): void
    {
        $this->update(['status' => 'issued', 'issued_at' => now()]);
    }

    public function markPaid(?string $paymentReference = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $paymentReference ?: $this->payment_reference,
        ]);
    }

    public function addAdjustment(string $description, float $amount): InvoiceLine
    {
        $line = $this->lines()->create([
            'line_type' => 'adjustment',
            'description' => $description,
            'amount' => $amount,
        ]);
        $this->recalculateTotal();

        return $line;
    }
}
