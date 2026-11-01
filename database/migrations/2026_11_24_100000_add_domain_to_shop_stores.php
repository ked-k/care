<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 43b: a shop can have its own web address (e.g.
 * shop.affinityhealthcareltd.co.uk). Requests to that host are served by
 * App\Http\Middleware\ServeShopDomain with clean URLs (/product/…, /basket).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_stores', function (Blueprint $table) {
            $table->string('domain')->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('shop_stores', function (Blueprint $table) {
            $table->dropUnique(['domain']);
            $table->dropColumn('domain');
        });
    }
};
