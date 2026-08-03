<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 2025_11_05_000176_create_visit_checkins_table.php
        Schema::create('visit_checkins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('checkin_method'); // gps, qr_code, otp
            $table->dateTime('checkin_time');
            $table->dateTime('checkout_time')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('qr_code_scanned')->nullable();
            $table->string('otp_used')->nullable();
            $table->boolean('location_verified')->default(false);
            $table->decimal('distance_from_location', 8, 2)->nullable(); // meters
            $table->text('deviation_reason')->nullable();
            $table->json('device_info')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();
            $table->index(['shift_id', 'checkin_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_checkins');
    }
};
