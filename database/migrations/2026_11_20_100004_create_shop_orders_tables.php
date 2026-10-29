<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Batch 40 (Shop workspace) — orders, their lines, payments and a timeline.
 *
 * Two separate status axes, the way a real shop is run:
 *   status          — fulfilment: pending → processing → shipped → completed
 *                     (or cancelled / refunded)
 *   payment_status  — money: unpaid → paid (or failed / refunded)
 * A bank-transfer order is "pending + unpaid" until staff confirm the money
 * arrived; a card order becomes "processing + paid" as soon as Stripe or
 * PayPal confirm it.
 *
 * Everything a customer saw at checkout — product names, unit prices, VAT
 * rate, addresses, delivery price — is snapshotted onto the order so a later
 * price or product edit never rewrites a past order.
 *
 * access_token lets a guest open their own order page from the confirmation
 * email without an account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('shop_stores')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('shop_customers')->nullOnDelete();
            $table->string('number')->unique();
            $table->string('access_token', 64)->unique();

            $table->string('email');
            $table->string('phone')->nullable();
            $table->json('billing_address');
            $table->json('shipping_address')->nullable(); // null for click & collect

            $table->uuid('delivery_method_id')->nullable();
            $table->string('delivery_name')->nullable();
            $table->decimal('delivery_price', 10, 2)->default(0);

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('vat_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 3)->default('GBP');
            $table->boolean('prices_include_vat')->default(true);
            $table->string('discount_code')->nullable();

            $table->enum('status', ['pending', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'failed', 'refunded'])->default('unpaid');
            $table->string('payment_method', 30);

            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('staff_notes')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('stock_released')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'created_at']);
        });

        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('shop_orders')->cascadeOnDelete();
            $table->uuid('product_id')->nullable(); // kept even if the product is later deleted
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('pack_size')->nullable();
            $table->unsignedInteger('qty');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });

        Schema::create('shop_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('shop_orders')->cascadeOnDelete();
            $table->string('gateway', 30);
            $table->string('provider_ref')->nullable(); // Stripe session id, PayPal order id
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->enum('status', ['pending', 'succeeded', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable(); // staff member for manual payments
            $table->timestamps();

            $table->index(['gateway', 'provider_ref']);
        });

        Schema::create('shop_order_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('shop_orders')->cascadeOnDelete();
            $table->string('type', 40); // placed, paid, status_changed, note, email_sent...
            $table->string('message');
            $table->boolean('visible_to_customer')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_order_events');
        Schema::dropIfExists('shop_payments');
        Schema::dropIfExists('shop_order_items');
        Schema::dropIfExists('shop_orders');
    }
};
