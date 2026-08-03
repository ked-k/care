<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rota_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('agency_id', 36);
            $table->date('week_commencing');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['agency_id', 'week_commencing']);

            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('updated_by')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rota_periods');
    }
};
