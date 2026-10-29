<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Batch 40 (Shop workspace). One online shop per agency — the agency's
 * "Shop" workspace in the top-bar switcher manages it, and its public
 * storefront lives at /store/{slug}.
 *
 * payment_settings is an encrypted JSON blob (cast 'encrypted:array' on the
 * model) because it holds live gateway secrets — Stripe secret/webhook keys
 * and PayPal client secrets — which must never sit in plain text in a dump.
 *
 * prices_include_vat defaults to true: this is a public (B2C-first) shop and
 * UK consumer prices are shown VAT-inclusive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->unique()->constrained('agencies')->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('brand_color', 7)->default('#14283F');
            $table->string('accent_color', 7)->default('#3B5BDB');

            $table->string('contact_email')->nullable();
            $table->string('orders_email')->nullable(); // where "new order" notifications go
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('company_number')->nullable();
            $table->string('vat_number')->nullable();

            $table->string('currency', 3)->default('GBP');
            $table->boolean('prices_include_vat')->default(true);
            $table->decimal('default_vat_rate', 5, 2)->default(20);
            $table->string('order_prefix', 10)->default('ORD');
            $table->unsignedInteger('next_order_number')->default(1001);

            $table->text('about')->nullable();
            $table->text('delivery_info')->nullable();
            $table->text('returns_policy')->nullable();
            $table->text('terms')->nullable();
            $table->text('privacy')->nullable();

            $table->text('payment_settings')->nullable(); // encrypted:array
            $table->boolean('is_live')->default(false);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_stores');
    }
};
