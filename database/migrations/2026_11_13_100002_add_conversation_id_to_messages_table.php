<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 23: lets an existing Message row belong to the new free-form
     * Conversation instead of (exclusively) a ChatSession or a direct
     * receiver_id — see App\Models\Message's updated doc comment. Purely
     * additive (a new nullable column), unlike the chat_session_id
     * migration this mirrors, so no doctrine/dbal column-type change is
     * needed here.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignUuid('conversation_id')->nullable()->after('chat_session_id')
                ->constrained('conversations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
        });
    }
};
