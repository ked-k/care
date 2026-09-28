<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the existing flat staff-to-staff Message model so it can also
 * carry grouped "chat session" messages (family <-> staff, about one
 * service user, potentially several people on each side). A session
 * message has no single receiver_id — it belongs to everyone on both
 * sides of the session — so receiver_id has to stop being mandatory.
 *
 * This project doesn't have doctrine/dbal installed (composer.json has no
 * such dependency), which Blueprint::change() requires to alter an
 * existing column — so the nullability change below uses raw SQL instead
 * of ->nullable()->change(). This only relaxes NULL/NOT NULL; it doesn't
 * touch the column's type or its existing foreign key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignUuid('chat_session_id')->nullable()->after('id')
                ->constrained('chat_sessions')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE messages MODIFY receiver_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['chat_session_id']);
            $table->dropColumn('chat_session_id');
        });

        DB::statement('ALTER TABLE messages MODIFY receiver_id BIGINT UNSIGNED NOT NULL');
    }
};
