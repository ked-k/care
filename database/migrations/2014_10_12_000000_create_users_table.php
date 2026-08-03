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

        Schema::create('users', function (Blueprint $table) {

            $table->id();
            $table->uuid('uid');
            $table->string('name', 100);
            $table->string('category')->nullable();
            $table->string('first_name')->nullable();   // New field: first name
            $table->string('username')->nullable();     // New field: first name
            $table->string('last_name')->nullable();    // New field: last name
            $table->string('inits')->nullable();        // New field: initials (optional)
            $table->string('phone_number')->nullable(); // New field: phone number, must be unique
            $table->string('email', 100)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->timestamp('password_updated_at')->default(now());
            $table->timestamp('password_expires_at')->default(now());
            $table->boolean('information_share_consent')->default(0);
            $table->string('avatar')->nullable();
            $table->string('signature')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('is_admin')->default(0);
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->foreignUuid('agency_id')->nullable();
            $table->boolean('two_factor_auth_enabled')->default(false);
            $table->string('two_factor_channel')->nullable();
            $table->string('idp_key')->nullable();
            $table->string('two_factor_code')->nullable();
            $table->dateTime('two_factor_expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->string('device_token')->nullable(); // for push notifications
            $table->json('trusted_devices')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
