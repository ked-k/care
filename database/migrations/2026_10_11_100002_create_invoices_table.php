<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Batch 21 (Invoicing). One invoice per payer per service user per
 * billing period — a 'split' service user (local authority + client
 * top-up) generates two rows here, linked by split_group_id. Mirrors
 * payslips: billing/payer details are snapshotted at generation time so
 * a later edit to the service user's billing contact doesn't
 * retroactively change a historical invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_run_id')->constrained('invoice_runs')->cascadeOnDelete();
            $table->foreignUuid('service_user_id')->constrained('service_users')->cascadeOnDelete();
            $table->enum('payer_type', ['self_funded', 'local_authority']);
            // Links the two invoices generated for one service user/period
            // when the service user's payer_type is 'split'. Null otherwise.
            $table->uuid('split_group_id')->nullable();

            $table->string('invoice_number')->unique();
            $table->date('billing_period_start');
            $table->date('billing_period_end');

            $table->string('payer_name')->nullable();
            $table->text('payer_address')->nullable();
            $table->string('payer_email')->nullable();
            $table->string('local_authority_reference')->nullable();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);

            $table->enum('status', ['draft', 'issued', 'paid'])->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['service_user_id', 'billing_period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
