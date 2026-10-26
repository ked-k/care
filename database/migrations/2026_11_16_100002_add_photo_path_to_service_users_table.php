<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 39: a profile photo for the service user — a plain storage path,
 * not the MediaFile-row convention used elsewhere in this batch for
 * compliance documents. There's nothing to track an audit trail or
 * approval state for here, same reasoning as users.avatar (already on the
 * users table, unused until this batch wires it up on the staff side).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_users', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('service_users', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
