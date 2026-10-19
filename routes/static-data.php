<?php

use App\Livewire\Agency\AgencySettingsComponent;
use App\Livewire\ServiceUser\ServiceUserManagerComponent;
use App\Livewire\ServiceUser\ServiceUserProfileComponent;
use App\Livewire\Staff\StaffManagerComponent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Static / reference data routes
|--------------------------------------------------------------------------
| Service users, staff (users + pay profiles), and agency settings — the
| records every other module assumes already exist. Require this file the
| same way as the others.
*/

Route::get('/service-users', ServiceUserManagerComponent::class)->name('service-users.index');

// Batch 37: the consolidated "full profile" page — see
// ServiceUserProfileComponent's class doc comment for what it pulls
// together and why it's gated on canAccessServiceUser() rather than a
// fixed role list.
Route::get('/service-users/{serviceUserId}/profile', ServiceUserProfileComponent::class)->name('service-users.profile');
Route::get('/staff', StaffManagerComponent::class)->name('staff.index');
Route::get('/agency/settings', AgencySettingsComponent::class)->name('agency.settings');
