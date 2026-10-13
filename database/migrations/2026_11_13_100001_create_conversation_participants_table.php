<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who's "in" a Conversation. Two distinct kinds of row, told apart by
     * which foreign key is set (never both, never neither):
     *   - user_id set: a real participant (staff or family login) who can
     *     open the thread and reply — see Conversation::isParticipant().
     *   - service_user_id set: a service user tagged for reference only
     *     ("this conversation is about Grace Thompson") — service users
     *     have no login of their own, so this is a label, not an inbox.
     * The two unique indexes below rely on MySQL treating every NULL as
     * distinct for uniqueness purposes, so each only ever blocks a genuine
     * duplicate (the same user, or the same tagged service user, added
     * twice to one conversation) and the two kinds of row never collide
     * with each other.
     */
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('service_user_id')->nullable()->constrained('service_users')->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->unique(['conversation_id', 'service_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
