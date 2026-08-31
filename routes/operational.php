<?php

use App\Livewire\Assessment\AssessmentIndexComponent;
use App\Livewire\CareTimeline\TimelineIndexComponent;
use App\Livewire\Notification\NotificationCenterComponent;
use App\Livewire\Rota\MyRotaComponent;
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

Route::get('/profile', \App\Livewire\Profile\ProfileComponent::class)->name('profile.show');

Route::get('/service-users/{serviceUserId}/assessments', AssessmentIndexComponent::class)
    ->name('assessments.manage');

Route::get('/service-users/{serviceUserId}/timeline', TimelineIndexComponent::class)
    ->name('timeline.manage');

Route::get('/notifications', NotificationCenterComponent::class)->name('notifications.index');
