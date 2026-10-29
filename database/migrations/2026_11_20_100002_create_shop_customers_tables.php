<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Batch 40 (Shop workspace) — public shop customers. Deliberately a separate
 * table and auth guard ('shop') from `users`: a member of the public signing
 * up to buy gloves must never become a row that Spatie roles, the staff
 * login, or any care data could ever be attached to.
 *
 * Accounts are per store (unique store_id + email), so the same person can
 * hold an account with two agencies' shops without them being linked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('shop_stores')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('password')->nullable(); // null = guest-only record
            $table->boolean('marketing_opt_in')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->text('notes')->nullable(); // staff-only
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['store_id', 'email']);
        });

        Schema::create('shop_customer_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('shop_customers')->cascadeOnDelete();
            $table->string('label')->nullable(); // "Home", "Care home"
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city');
            $table->string('county')->nullable();
            $table->string('postcode', 12);
            $table->string('country', 2)->default('GB');
            $table->string('phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('shop_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_password_reset_tokens');
        Schema::dropIfExists('shop_customer_addresses');
        Schema::dropIfExists('shop_customers');
    }
};
