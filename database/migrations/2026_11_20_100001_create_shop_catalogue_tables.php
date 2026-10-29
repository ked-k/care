<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Batch 40 (Shop workspace) — the catalogue: categories, products, product
 * images, quantity price tiers and the stock ledger.
 *
 * Prices are stored exactly as the store displays them (see
 * shop_stores.prices_include_vat): VAT-inclusive for a consumer shop. VAT is
 * worked out per line at checkout from vat_rate, never stored on the product.
 *
 * stock_qty is a denormalised running total kept in step with
 * shop_stock_movements (the ledger every change is written to), so listing
 * thousands of products never has to SUM() the ledger.
 *
 * supplier_url / supplier_sku / cost_price are internal-only: they're never
 * rendered on the storefront.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('shop_stores')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // iconkit class, e.g. "ik ik-shield"
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'slug']);
        });

        Schema::create('shop_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('shop_stores')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('shop_categories')->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->string('brand')->nullable();
            $table->string('pack_size')->nullable(); // "Box of 100", "1 Tub"
            $table->string('summary', 500)->nullable();
            $table->longText('description')->nullable();

            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('compare_at_price', 10, 2)->nullable(); // "was" price
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('vat_rate', 5, 2)->default(20);

            $table->boolean('track_stock')->default(true);
            $table->integer('stock_qty')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('allow_backorder')->default(false);
            $table->unsignedInteger('min_order_qty')->default(1);
            $table->unsignedInteger('max_order_qty')->nullable();
            $table->unsignedInteger('weight_grams')->nullable();

            $table->string('supplier_name')->nullable();
            $table->string('supplier_sku')->nullable();
            $table->string('supplier_url', 1000)->nullable();

            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->string('source', 50)->nullable(); // "botble-import", "manual"
            $table->string('source_ref')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['store_id', 'slug']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'sku']);
        });

        Schema::create('shop_product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shop_price_tiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->unsignedInteger('min_qty');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();

            $table->unique(['product_id', 'min_qty']);
        });

        Schema::create('shop_stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->integer('qty'); // + in, - out
            $table->enum('type', ['receipt', 'sale', 'return', 'adjustment', 'cancellation']);
            $table->string('reference')->nullable(); // order number, delivery note...
            $table->uuid('order_id')->nullable();
            $table->string('note')->nullable();
            $table->integer('balance_after')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_stock_movements');
        Schema::dropIfExists('shop_price_tiers');
        Schema::dropIfExists('shop_product_images');
        Schema::dropIfExists('shop_products');
        Schema::dropIfExists('shop_categories');
    }
};
