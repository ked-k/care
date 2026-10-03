<?php

use App\Livewire\Assessment\AssessmentIndexComponent;
use App\Livewire\CareTimeline\CarerQuickNoteComponent;
use App\Livewire\CareTimeline\TimelineIndexComponent;
use App\Livewire\Family\CarerMessagesComponent;
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

// Batch 13: carer bottom-tab destinations — a quick way to add a note on
// one of their assigned service users, and to message that person's next
// of kin. No {serviceUserId} in the URL: both components start with a
// picker of the carer's own assigned people (see User::isCarerOnly() and
// each component's own mount()-time gate).
Route::get('/notes', CarerQuickNoteComponent::class)->name('notes.quick');
Route::get('/messages', CarerMessagesComponent::class)->name('messages.carer');
