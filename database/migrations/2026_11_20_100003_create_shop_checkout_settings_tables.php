<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Batch 40 (Shop workspace) — carts, delivery methods and discount codes.
 *
 * Carts are database-backed (not session arrays) so a cart survives a
 * logged-in customer moving between devices, and so an unpaid basket can be
 * seen by staff later. A guest cart is found by `token` (kept in the
 * session); a signed-in customer's by customer_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_delivery_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('shop_stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('free_over', 10, 2)->nullable(); // free when the goods subtotal reaches this
            $table->unsignedSmallInteger('min_days')->nullable();
            $table->unsignedSmallInteger('max_days')->nullable();
            $table->boolean('is_collection')->default(false); // click & collect — no address needed
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shop_discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('shop_stores')->cascadeOnDelete();
            $table->string('code');
            $table->string('description')->nullable();
            $table->enum('type', ['percent', 'fixed', 'free_delivery']);
            $table->decimal('value', 10, 2)->default(0);
            $table->decimal('min_subtotal', 10, 2)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('once_per_customer')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'code']);
        });

        Schema::create('shop_carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('shop_stores')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('shop_customers')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('discount_code')->nullable();
            $table->uuid('delivery_method_id')->nullable();
            $table->timestamps();
        });

        Schema::create('shop_cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->constrained('shop_carts')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->unsignedInteger('qty');
            $table->timestamps();

            $table->unique(['cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_cart_items');
        Schema::dropIfExists('shop_carts');
        Schema::dropIfExists('shop_discounts');
        Schema::dropIfExists('shop_delivery_methods');
    }
};
