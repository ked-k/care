<?php

use App\Livewire\Alert\AlertsManagerComponent;
use App\Livewire\Assessment\AssessmentIndexComponent;
use App\Livewire\CareTimeline\CarerQuickNoteComponent;
use App\Livewire\CareTimeline\ClientProfileComponent;
use App\Livewire\CareTimeline\TimelineIndexComponent;
use App\Livewire\Family\CarerMessagesComponent;
use App\Livewire\Messaging\ConversationShowComponent;
use App\Livewire\Messaging\ConversationsIndexComponent;
use App\Livewire\Notification\NotificationCenterComponent;
use App\Livewire\Rota\MyRotaComponent;
use App\Livewire\Rota\ShiftVisitComponent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Operational routes — carer rota view, assessments, care timeline,
| notification center
|--------------------------------------------------------------------------
| Require this file from routes/web.php inside the main authenticated staff
| group, the same way as safeguarding-consent-family.php and
| compliance-governance.php.
*/

Route::get('/my-rota', MyRotaComponent::class)->name('rota.mine');

// Batch 11: the carer's single mobile screen for one shift — check in/out,
// tasks, medications and notes all in one place. Also where a manager/admin
// lands to cover a carer's shift (ShiftVisitComponent::mount() gates that
// on a reason before anything else is shown — see its class doc comment).
Route::get('/rota/shift/{shiftId}/visit', ShiftVisitComponent::class)->name('rota.visit');

Route::get('/profile', \App\Livewire\Profile\ProfileComponent::class)->name('profile.show');

Route::get('/service-users/{serviceUserId}/assessments', AssessmentIndexComponent::class)
    ->name('assessments.manage');

Route::get('/service-users/{serviceUserId}/timeline', TimelineIndexComponent::class)
    ->name('timeline.manage');

Route::get('/notifications', NotificationCenterComponent::class)->name('notifications.index');

// Batch 16: a real alerting workflow around late/missed calls, missing
// records/evidence and medication concerns — see App\Support\AlertGenerator
// and AlertsManagerComponent::mount() for the manager/admin-only gate.
Route::get('/alerts', AlertsManagerComponent::class)->name('alerts.index');

// Batch 13: carer bottom-tab destinations — a quick way to add a note on
// one of their assigned service users, and to message that person's next
// of kin. No {serviceUserId} in the URL: both components start with a
// picker of the carer's own assigned people (see User::isCarerOnly() and
// each component's own mount()-time gate).
Route::get('/notes', CarerQuickNoteComponent::class)->name('notes.quick');
Route::get('/messages', CarerMessagesComponent::class)->name('messages.carer');

// Batch 14: the carer's standalone client-profile page (Care Call
// Timeline + links into Notes/Messages for that person), independent of
// any one shift — reached from My Rota, the Dashboard's next-shift card,
// and the Notes/Messages pickers.
Route::get('/clients/{serviceUserId}', ClientProfileComponent::class)->name('clients.show');

// Batch 23: free-form, multi-party conversations (see App\Models\Conversation),
// reached via the floating "New conversation" button
// (App\Livewire\Messaging\NewConversationComponent) on both this layout and
// layouts.carer. Viewing/replying is gated per-conversation on
// participation inside the component itself, not by role here — the same
// pair of routes serves every staff role. The matching family-side routes
// live in routes/family-portal.php, reusing these same two components.
Route::get('/conversations', ConversationsIndexComponent::class)->name('conversations.index');
Route::get('/conversations/{conversationId}', ConversationShowComponent::class)->name('conversations.show');
