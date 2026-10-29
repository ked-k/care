<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 41: product variants — one product ("Nitrile Gloves") sold in several
 * sizes, colours or pack sizes, each with its own code, barcode, stock and
 * (optionally) price.
 *
 * shop_products.options holds the option names and their values in order,
 *   e.g. [{"name":"Size","values":["Small","Medium","Large"]},
 *         {"name":"Pack","values":["Box of 100","Case of 10 boxes"]}]
 * shop_product_variants holds one row per combination (option1..option3 are
 *   the values, in the same order as the options).
 *
 * Pricing: a variant with no price of its own sells at the product's price and
 * shares the product's bulk prices — and the quantities of all those variants
 * count together towards a bulk price ("mix sizes"). A variant with its own
 * price can have its own bulk prices (shop_price_tiers.variant_id).
 *
 * Stock is held per variant; shop_products.stock_qty becomes the total across
 * its variants, kept in step by ShopProductVariant::adjustStock().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->boolean('has_variants')->default(false)->after('status');
            $table->json('options')->nullable()->after('has_variants');
            // What one unit is sold as — "box", "pair", "case" — for "£24.60 per box".
            $table->string('unit_name', 40)->nullable()->after('pack_size');
        });

        Schema::create('shop_product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->string('option1', 150)->nullable();
            $table->string('option2', 150)->nullable();
            $table->string('option3', 150)->nullable();
            $table->string('sku', 80)->nullable();
            $table->string('barcode', 80)->nullable();
            $table->decimal('price', 10, 2)->nullable();            // null = the product's price
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();       // null = the product's cost
            $table->integer('stock_qty')->default(0);
            $table->unsignedInteger('low_stock_threshold')->nullable(); // null = the product's
            $table->unsignedInteger('weight_grams')->nullable();
            $table->uuid('image_id')->nullable();
            $table->string('supplier_sku', 80)->nullable();
            $table->string('supplier_url', 1000)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('source_ref')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
            $table->index('sku');
            $table->index('source_ref');
        });

        // Bulk prices of a single variant. Add the new unique key before
        // dropping the old one: MySQL needs an index on product_id for the
        // foreign key at every moment.
        Schema::table('shop_price_tiers', function (Blueprint $table) {
            $table->foreignUuid('variant_id')->nullable()->after('product_id')->constrained('shop_product_variants')->cascadeOnDelete();
            $table->unique(['product_id', 'variant_id', 'min_qty'], 'shop_price_tiers_product_variant_min_unique');
        });
        Schema::table('shop_price_tiers', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'min_qty']);
        });

        Schema::table('shop_cart_items', function (Blueprint $table) {
            $table->foreignUuid('variant_id')->nullable()->after('product_id')->constrained('shop_product_variants')->cascadeOnDelete();
            $table->unique(['cart_id', 'product_id', 'variant_id'], 'shop_cart_items_cart_product_variant_unique');
        });
        Schema::table('shop_cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id']);
        });

        // Order lines keep a snapshot of the variant, like they do of the product.
        Schema::table('shop_order_items', function (Blueprint $table) {
            $table->uuid('variant_id')->nullable()->after('product_id');
            $table->string('variant_title')->nullable()->after('name');
        });

        Schema::table('shop_stock_movements', function (Blueprint $table) {
            $table->foreignUuid('variant_id')->nullable()->after('product_id')->constrained('shop_product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shop_stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variant_id');
        });
        Schema::table('shop_order_items', function (Blueprint $table) {
            $table->dropColumn(['variant_id', 'variant_title']);
        });

        // Carts and bulk prices can't go back to one row per product while
        // variant rows exist, so those rows are removed first.
        \Illuminate\Support\Facades\DB::table('shop_cart_items')->whereNotNull('variant_id')->delete();
        Schema::table('shop_cart_items', function (Blueprint $table) {
            $table->unique(['cart_id', 'product_id']);
        });
        Schema::table('shop_cart_items', function (Blueprint $table) {
            $table->dropUnique('shop_cart_items_cart_product_variant_unique');
            $table->dropConstrainedForeignId('variant_id');
        });

        \Illuminate\Support\Facades\DB::table('shop_price_tiers')->whereNotNull('variant_id')->delete();
        Schema::table('shop_price_tiers', function (Blueprint $table) {
            $table->unique(['product_id', 'min_qty']);
        });
        Schema::table('shop_price_tiers', function (Blueprint $table) {
            $table->dropUnique('shop_price_tiers_product_variant_min_unique');
            $table->dropConstrainedForeignId('variant_id');
        });

        Schema::dropIfExists('shop_product_variants');

        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn(['has_variants', 'options', 'unit_name']);
        });
    }
};
