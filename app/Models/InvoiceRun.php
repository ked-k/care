<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InvoiceRun extends Model
{
    use HasUuids;

    protected $fillable = [
        'agency_id', 'reference', 'billing_period_start', 'billing_period_end',
        'frequency', 'status', 'total_billed', 'processed_by',
    ];

    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'total_billed' => 'decimal:2',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Build invoices for every service user in this agency with billing
     * configured, covering completed visits (billing_type = per_visit) or
     * a flat charge (billing_type = flat_package) within this run's
     * period. A service user with payer_type = 'split' gets two invoices
     * — one to the local authority for its contracted share, one to the
     * client for the balance — both listing the same visit/package
     * detail so either payer can see what was delivered, with an
     * adjustment line netting each one down to its own share. Skips
     * anyone with no billing rate/package amount set up yet, or already
     * invoiced for this exact period, rather than guessing — same
     * convention PayrollRun::generateFromApprovedTimesheets() uses for a
     * carer with no pay profile.
     */
    /**
     * Returns a summary of what this run did, so the caller can tell the
     * user something meaningful rather than a blanket "done" — testing
     * found that with billing unconfigured (the default for every demo
     * service user) this previously created zero invoices while still
     * showing a generic "Invoices generated" success toast, which reads as
     * the feature being broken rather than correctly having nothing to do.
     *
     * @return array{invoices: int, service_users_skipped_no_billing: int}
     */
    public function generateInvoices(): array
    {
        $serviceUsers = ServiceUser::where('agency_id', $this->agency_id)->get();

        $invoicesCreated = 0;
        $skippedNoBilling = 0;

        foreach ($serviceUsers as $su) {
            if (! $su->hasBillingConfigured()) {
                $skippedNoBilling++;
                continue;
            }

            if (Invoice::where('service_user_id', $su->id)
                ->where('billing_period_start', $this->billing_period_start)
                ->where('billing_period_end', $this->billing_period_end)
                ->exists()) {
                continue;
            }

            $lines = $su->billing_type === 'flat_package'
                ? $this->packageLines($su)
                : $this->visitLines($su);

            $fullTotal = round(array_sum(array_column($lines, 'amount')), 2);
            if ($fullTotal <= 0) {
                continue;
            }

            if ($su->payer_type === 'split') {
                $splitGroupId = (string) Str::uuid();
                $laShare = round(min((float) $su->local_authority_share_amount, $fullTotal), 2);
                $clientShare = round($fullTotal - $laShare, 2);

                if ($laShare > 0) {
                    $this->createInvoice($su, 'local_authority', $laShare, $fullTotal, $lines, $splitGroupId);
                    $invoicesCreated++;
                }
                if ($clientShare > 0) {
                    $this->createInvoice($su, 'self_funded', $clientShare, $fullTotal, $lines, $splitGroupId);
                    $invoicesCreated++;
                }
            } else {
                $payerType = $su->payer_type === 'local_authority' ? 'local_authority' : 'self_funded';
                $this->createInvoice($su, $payerType, $fullTotal, $fullTotal, $lines, null);
                $invoicesCreated++;
            }
        }

        $this->recalculateTotals();

        return [
            'invoices' => $invoicesCreated,
            'service_users_skipped_no_billing' => $skippedNoBilling,
        ];
    }

    /**
     * One line per completed Shift in the period, billed at the service
     * user's own hourly billing_rate (independent of what the carer is
     * paid) — see Shift::hoursWorked().
     */
    protected function visitLines(ServiceUser $su): array
    {
        $shifts = Shift::where('service_user_id', $su->id)
            ->where('status', 'completed')
            ->whereBetween('scheduled_start', [
                $this->billing_period_start->copy()->startOfDay(),
                $this->billing_period_end->copy()->endOfDay(),
            ])
            ->orderBy('scheduled_start')
            ->get();

        $lines = [];
        foreach ($shifts as $shift) {
            $hours = $shift->hoursWorked();
            if ($hours <= 0) {
                continue;
            }

            $lines[] = [
                'line_type' => 'visit',
                'shift_id' => $shift->id,
                'description' => $shift->scheduled_start->format('d M Y').' — '.ucfirst(str_replace('_', ' ', $shift->shift_type ?? 'visit')).' visit',
                'quantity' => $hours,
                'rate' => $su->billing_rate,
                'amount' => round($hours * (float) $su->billing_rate, 2),
            ];
        }

        return $lines;
    }

    protected function packageLines(ServiceUser $su): array
    {
        return [[
            'line_type' => 'package',
            'shift_id' => null,
            'description' => ucfirst($su->billing_package_period ?: 'recurring').' care package — '
                .$this->billing_period_start->format('d M').' to '.$this->billing_period_end->format('d M Y'),
            'quantity' => null,
            'rate' => null,
            'amount' => round((float) $su->billing_package_amount, 2),
        ]];
    }

    protected function createInvoice(ServiceUser $su, string $payerType, float $targetTotal, float $fullTotal, array $lines, ?string $splitGroupId): void
    {
        $isLocalAuthority = $payerType === 'local_authority';

        $invoice = Invoice::create([
            'invoice_run_id' => $this->id,
            'service_user_id' => $su->id,
            'payer_type' => $payerType,
            'split_group_id' => $splitGroupId,
            'invoice_number' => $this->nextInvoiceNumber(),
            'billing_period_start' => $this->billing_period_start,
            'billing_period_end' => $this->billing_period_end,
            'payer_name' => $isLocalAuthority ? ($su->local_authority_name ?: 'Local Authority') : ($su->payer_name ?: $su->name),
            'payer_address' => $isLocalAuthority ? null : $su->payer_address,
            'payer_email' => $isLocalAuthority ? null : $su->payer_email,
            'local_authority_reference' => $isLocalAuthority ? $su->local_authority_reference : null,
            'status' => 'draft',
            'created_by' => $this->processed_by,
        ]);

        foreach ($lines as $line) {
            $invoice->lines()->create($line);
        }

        // Both halves of a split invoice list the same full set of
        // visit/package lines (so each payer can see what was actually
        // delivered); this adjustment line nets each one down to its own
        // contracted share rather than trying to divide every individual
        // visit proportionally.
        if ($splitGroupId) {
            $adjustment = round($fullTotal - $targetTotal, 2);
            if ($adjustment > 0) {
                $invoice->lines()->create([
                    'line_type' => 'adjustment',
                    'description' => $isLocalAuthority
                        ? 'Balance invoiced to client/family separately'
                        : 'Local authority contracted contribution',
                    'amount' => -$adjustment,
                ]);
            }
        }

        $invoice->recalculateTotal();
    }

    protected function nextInvoiceNumber(): string
    {
        $count = Invoice::whereHas('invoiceRun', fn ($q) => $q->where('agency_id', $this->agency_id))->count() + 1;

        return 'INV-'.now()->year.'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    public function recalculateTotals(): void
    {
        $this->total_billed = $this->invoices()->sum('total_amount');
        $this->save();
    }

    public function issue(): void
    {
        $this->invoices()->where('status', 'draft')->get()->each(fn (Invoice $invoice) => $invoice->issue());
        $this->update(['status' => 'issued']);
    }

    public function markAllPaid(): void
    {
        $this->invoices()->where('status', '!=', 'paid')->get()->each(fn (Invoice $invoice) => $invoice->markPaid());
        $this->update(['status' => 'paid']);
    }
}
