<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 16: the "named person responsible" the gap assessment calls
     * for. A manager picks one person (a Manager/Admin/Super Admin at the
     * agency) per service user; App\Support\AlertGenerator assigns every
     * alert it raises for that person's calls, tasks and medications to
     * this coordinator. Left null until a manager sets it — alerts for a
     * service user with no coordinator yet fall back to any manager/admin
     * at the agency rather than going unassigned (see AlertGenerator::
     * fallbackResponder()).
     */
    public function up(): void
    {
        Schema::table('service_users', function (Blueprint $table) {
            $table->foreignId('care_coordinator_id')->nullable()
                ->after('important_information')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('care_coordinator_id');
        });
    }
};
