<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 39: a signed (e.g. scanned/wet-signature, or printed-and-signed)
 * care plan PDF a manager can attach to the care plan it belongs to —
 * separate from the browser-print "Download PDF" button Batch 37 added,
 * which generates a fresh copy of the live record rather than storing a
 * signed one. Follows the same plain-uuid-column MediaFile convention as
 * training_progress.certificate_media_id (Batch 36) — a genuine document
 * worth the same provenance (who uploaded it, when) as the staff
 * compliance documents this batch also adds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('care_plans', function (Blueprint $table) {
            $table->uuid('signed_document_media_id')->nullable()->after('plan_data');
        });
    }

    public function down(): void
    {
        Schema::table('care_plans', function (Blueprint $table) {
            $table->dropColumn('signed_document_media_id');
        });
    }
};
