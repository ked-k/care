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
        Schema::create('audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->string('action')->nullable(); // created, updated, deleted, viewed, exported
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
             $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->text('device_info')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->index(['entity_type', 'entity_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
