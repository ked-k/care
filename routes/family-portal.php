<?php

use App\Livewire\Family\FamilyPortalComponent;
use App\Livewire\Family\FamilyServiceUserComponent;
use App\Livewire\Messaging\ConversationShowComponent;
use App\Livewire\Messaging\ConversationsIndexComponent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Family Portal routes
|--------------------------------------------------------------------------
| Deliberately NOT required inside the main staff 'auth' group in web.php —
| require it at the top level instead. A Family-role login must only ever
| reach these two routes, never the staff application area (enforced by the
| 'not-family' middleware on the staff group, and 'role:Family' here).
*/

Route::middleware(['auth', 'role:Family'])->prefix('family')->name('family.')->group(function () {
    Route::get('/', FamilyPortalComponent::class)->name('portal');
    Route::get('/service-user/{serviceUserId}', FamilyServiceUserComponent::class)->name('service-user');

    // Batch 23: a family member can now be added as a real participant in
    // a free-form staff-started App\Models\Conversation (see
    // routes/operational.php), not just reached through a per-service-user
    // ChatSession — these reuse the exact same two Livewire components,
    // since access there is "you're a participant", already role-agnostic.
    Route::get('/conversations', ConversationsIndexComponent::class)->name('conversations.index');
    Route::get('/conversations/{conversationId}', ConversationShowComponent::class)->name('conversations.show');
});
