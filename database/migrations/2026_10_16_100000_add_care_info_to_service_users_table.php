<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 15: the gap assessment found that allergies, medical conditions,
 * communication needs, preferences and other "important care information"
 * weren't a dedicated, central part of a service user's profile — some of
 * it existed only inside a care plan's plan_data, which isn't guaranteed
 * to exist and isn't what you'd check first for something safety-critical
 * like an allergy. These five columns put that information directly on
 * the service user record itself, visible without needing a care plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_users', function (Blueprint $table) {
            $table->text('allergies')->nullable()->after('nhs_number');
            $table->text('medical_conditions')->nullable()->after('allergies');
            $table->text('communication_needs')->nullable()->after('medical_conditions');
            $table->text('preferences')->nullable()->after('communication_needs');
            $table->text('important_information')->nullable()->after('preferences');
        });
    }

    public function down(): void
    {
        Schema::table('service_users', function (Blueprint $table) {
            $table->dropColumn([
                'allergies', 'medical_conditions', 'communication_needs',
                'preferences', 'important_information',
            ]);
        });
    }
};
