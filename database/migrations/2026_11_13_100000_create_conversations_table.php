<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 23: a free-form, multi-party conversation thread that can mix
     * staff and family participants freely in one thread — unlike
     * ChatSession (exactly one service user, two fixed "sides") or the flat
     * Message sender_id/receiver_id DM (exactly two staff members). Started
     * from the floating "New conversation" button
     * (App\Livewire\Messaging\NewConversationComponent), available to
     * Admin/Manager/Carer/Super Admin — see App\Models\User::canStartConversations().
     * See conversation_participants for who's actually in it.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('subject')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
