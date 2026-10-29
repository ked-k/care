<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Batch 42: goods received notes, batches (lots) with expiry dates, and
 * FEFO/FIFO stock rotation.
 *
 * shop_grns / shop_grn_lines  a delivery from a supplier: many products,
 *                             each with quantity, unit cost, lot and expiry.
 * shop_stock_lots             every batch of stock on the shelf and how much
 *                             of it is left. The lots of an item always add
 *                             up to its stock_qty (while stock_qty >= 0).
 * shop_lot_allocations        which lot each stock movement came out of (or
 *                             went back into). This is what makes a recall
 *                             possible: lot → orders → customers.
 *
 * Existing stock gets one "opening balance" lot per item, without a lot
 * number or expiry, so the books balance from day one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_stores', function (Blueprint $table) {
            $table->unsignedInteger('next_grn_number')->default(1)->after('next_order_number');
            $table->unsignedSmallInteger('expiry_warning_days')->default(60)->after('next_grn_number');
        });

        Schema::table('shop_products', function (Blueprint $table) {
            $table->boolean('track_expiry')->default(false)->after('allow_backorder');
            $table->string('stock_rotation', 4)->default('fefo')->after('track_expiry'); // fefo | fifo
        });

        Schema::create('shop_suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('shop_stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->string('account_number', 60)->nullable(); // your account with them
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'name']);
        });

        Schema::create('shop_grns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('shop_stores')->cascadeOnDelete();
            $table->string('number', 30);
            $table->foreignUuid('supplier_id')->nullable()->constrained('shop_suppliers')->nullOnDelete();
            $table->string('supplier_reference')->nullable(); // delivery note / invoice number
            $table->string('purchase_order')->nullable();
            $table->date('received_on');
            $table->string('status', 12)->default('draft'); // draft | received | reversed
            $table->text('notes')->nullable();
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'number']);
            $table->index(['store_id', 'status', 'received_on']);
        });

        Schema::create('shop_stock_lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('shop_stores')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('shop_product_variants')->cascadeOnDelete();
            $table->string('lot_number', 80)->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('received_on');
            $table->integer('qty_received');
            $table->integer('qty_remaining');
            $table->decimal('unit_cost', 10, 4)->nullable();
            $table->string('source', 20); // grn | opening | adjustment | return
            $table->uuid('grn_id')->nullable();
            $table->uuid('grn_line_id')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'variant_id', 'qty_remaining']);
            $table->index(['store_id', 'expiry_date']);
            $table->index('lot_number');
        });

        Schema::create('shop_grn_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('grn_id')->constrained('shop_grns')->cascadeOnDelete();
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->string('name'); // snapshot, "Nitrile Gloves — Large · Box of 100"
            $table->string('sku', 80)->nullable();
            $table->unsignedInteger('qty');
            $table->decimal('unit_cost', 10, 4)->nullable();
            $table->decimal('line_total', 12, 2)->default(0);
            $table->string('lot_number', 80)->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignUuid('lot_id')->nullable()->constrained('shop_stock_lots')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_id');
        });

        Schema::table('shop_stock_movements', function (Blueprint $table) {
            $table->uuid('grn_id')->nullable()->after('order_id');
        });
        // New movement type "expired" — a plain string from now on.
        Schema::table('shop_stock_movements', function (Blueprint $table) {
            $table->string('type', 20)->change();
        });

        Schema::create('shop_lot_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lot_id')->constrained('shop_stock_lots')->cascadeOnDelete();
            $table->foreignUuid('movement_id')->constrained('shop_stock_movements')->cascadeOnDelete();
            $table->uuid('order_id')->nullable();
            $table->uuid('order_item_id')->nullable();
            $table->integer('qty'); // + taken out of the lot, - put back into it
            $table->timestamps();

            $table->index('lot_id');
            $table->index('order_item_id');
            $table->index('order_id');
        });

        $this->openingLots();
        $this->suppliersFromProducts();
    }

    /** One lot per item for the stock already on hand. */
    private function openingLots(): void
    {
        $now = now();
        $today = $now->toDateString();

        DB::table('shop_products')->where('track_stock', true)->where('has_variants', false)->where('stock_qty', '>', 0)
            ->whereNull('deleted_at')->orderBy('id')
            ->chunk(500, function ($products) use ($now, $today) {
                DB::table('shop_stock_lots')->insert($products->map(fn ($p) => [
                    'id' => (string) Str::uuid(), 'store_id' => $p->store_id, 'product_id' => $p->id, 'variant_id' => null,
                    'received_on' => $today, 'qty_received' => $p->stock_qty, 'qty_remaining' => $p->stock_qty,
                    'unit_cost' => $p->cost_price, 'source' => 'opening', 'created_at' => $now, 'updated_at' => $now,
                ])->all());
            });

        DB::table('shop_product_variants as v')->join('shop_products as p', 'p.id', '=', 'v.product_id')
            ->where('p.track_stock', true)->where('p.has_variants', true)->whereNull('p.deleted_at')->where('v.stock_qty', '>', 0)
            ->select('v.id', 'v.product_id', 'v.stock_qty', 'v.cost_price', 'p.store_id', 'p.cost_price as product_cost')
            ->orderBy('v.id')
            ->chunk(500, function ($variants) use ($now, $today) {
                DB::table('shop_stock_lots')->insert($variants->map(fn ($v) => [
                    'id' => (string) Str::uuid(), 'store_id' => $v->store_id, 'product_id' => $v->product_id, 'variant_id' => $v->id,
                    'received_on' => $today, 'qty_received' => $v->stock_qty, 'qty_remaining' => $v->stock_qty,
                    'unit_cost' => $v->cost_price ?? $v->product_cost, 'source' => 'opening', 'created_at' => $now, 'updated_at' => $now,
                ])->all());
            });
    }

    /** Start the supplier list from the supplier names already on products. */
    private function suppliersFromProducts(): void
    {
        $now = now();
        DB::table('shop_products')->whereNotNull('supplier_name')->where('supplier_name', '!=', '')
            ->select('store_id', 'supplier_name')->distinct()->get()
            ->each(fn ($row) => DB::table('shop_suppliers')->insert([
                'id' => (string) Str::uuid(), 'store_id' => $row->store_id, 'name' => $row->supplier_name,
                'website' => str_contains($row->supplier_name, '.') ? 'https://'.ltrim(preg_replace('~^https?://~', '', $row->supplier_name), '/') : null,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_lot_allocations');
        Schema::table('shop_stock_movements', function (Blueprint $table) {
            $table->dropColumn('grn_id');
        });
        Schema::dropIfExists('shop_grn_lines');
        Schema::dropIfExists('shop_stock_lots');
        Schema::dropIfExists('shop_grns');
        Schema::dropIfExists('shop_suppliers');
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn(['track_expiry', 'stock_rotation']);
        });
        Schema::table('shop_stores', function (Blueprint $table) {
            $table->dropColumn(['next_grn_number', 'expiry_warning_days']);
        });
        // shop_stock_movements.type stays a string: "expired" rows may exist.
    }
};
