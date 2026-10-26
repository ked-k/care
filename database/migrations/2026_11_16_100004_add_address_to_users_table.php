<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 39: found while building the Staff Profile page's personal-details
 * card — App\Livewire\Profile\ProfileComponent has read AND saved
 * `$user->address` since at least Batch 8/9's era, but no `address` column
 * has ever existed on `users` (only `phone_number`, which that same
 * component mismatches as `$user->phone`). Because Eloquent treats any
 * assigned-but-unknown attribute as dirty, every single "Update Profile"
 * save on that page — by any user, any role, whether or not they touched
 * the Address field — has been failing with an "Unknown column" SQL error
 * since this is a reachable page, not just a dead button. Not introduced
 * by this batch; just finally running into it because this batch also
 * displays address on the new Staff Profile page. ProfileComponent's
 * `phone` mismatch is fixed in the same batch (see CHANGES39.md) —
 * pointed at the real `phone_number` column rather than adding a second,
 * redundant one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('address')->nullable()->after('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
