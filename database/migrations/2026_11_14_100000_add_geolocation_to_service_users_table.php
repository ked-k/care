<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 35: a geofenced check-in restriction needs something to measure a
 * carer's check-in location AGAINST — and until now a service user only
 * ever had a free-text `address` field, nothing coordinate-based. These
 * four columns let a location be auto-geocoded from that address (via
 * App\Services\GeocodingService) and/or pinned manually by a manager when
 * geocoding gets it wrong or an address is too vague to resolve at all.
 *
 * geocoded_address caches the exact address string that produced
 * latitude/longitude, so the app can tell when the address has since
 * changed and the coordinates are stale — rather than silently trusting a
 * pin that was for a different address.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_users', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('location_source')->nullable()->after('longitude'); // 'geocoded' | 'manual'
            $table->string('geocoded_address')->nullable()->after('location_source');
        });
    }

    public function down(): void
    {
        Schema::table('service_users', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_source', 'geocoded_address']);
        });
    }
};
