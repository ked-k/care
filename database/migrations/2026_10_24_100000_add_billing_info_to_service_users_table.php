<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Batch 21 (Invoicing): billing configuration lives directly on the
 * service user, the same way Batch 15's "key care information" and
 * Batch 16's care_coordinator_id do — no care plan or separate billing
 * profile needs to exist first. See InvoiceRun::generateInvoices().
 */
return new class extends Migration
{
    public function up(): void
    {
        // return;
        Schema::table('service_users', function (Blueprint $table) {
            $table->enum('billing_type', ['per_visit', 'flat_package'])->default('per_visit')->after('care_coordinator_id');
            $table->decimal('billing_rate', 8, 2)->nullable()->after('billing_type');
            $table->decimal('billing_package_amount', 10, 2)->nullable()->after('billing_rate');
            $table->enum('billing_package_period', ['weekly', 'monthly'])->nullable()->after('billing_package_amount');

            $table->enum('payer_type', ['self_funded', 'local_authority', 'split'])->default('self_funded')->after('billing_package_period');
            $table->string('payer_name')->nullable()->after('payer_type');
            $table->text('payer_address')->nullable()->after('payer_name');
            $table->string('payer_email')->nullable()->after('payer_address');

            $table->string('local_authority_name')->nullable()->after('payer_email');
            $table->string('local_authority_reference')->nullable()->after('local_authority_name');
            // Used only when payer_type = 'split': the flat amount the local
            // authority contributes per invoicing period. The client is
            // billed whatever's left of the period's total — see
            // InvoiceRun::generateInvoices().
            $table->decimal('local_authority_share_amount', 10, 2)->nullable()->after('local_authority_reference');
        });
    }

    public function down(): void
    {
        Schema::table('service_users', function (Blueprint $table) {
            $table->dropColumn([
                'billing_type', 'billing_rate', 'billing_package_amount', 'billing_package_period',
                'payer_type', 'payer_name', 'payer_address', 'payer_email',
                'local_authority_name', 'local_authority_reference', 'local_authority_share_amount',
            ]);
        });
    }
};
