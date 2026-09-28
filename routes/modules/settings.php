<?php

use Illuminate\Support\Facades\Route;

// Settings module — now served by Livewire components
Route::get('/settings', \App\Livewire\Settings\GeneralComponent::class)->name('settings.general');
Route::get('/settings/company', \App\Livewire\Settings\CompanyComponent::class)->name('settings.company');
Route::get('/settings/localization', \App\Livewire\Settings\LocalizationComponent::class)->name('settings.localization');
Route::get('/settings/notifications', \App\Livewire\Settings\NotificationsComponent::class)->name('settings.notifications');
Route::get('/settings/appearance', \App\Livewire\Settings\AppearanceComponent::class)->name('settings.appearance');
Route::get('/settings/security', \App\Livewire\Settings\SecurityComponent::class)->name('settings.security');

// Admin role manager (Livewire)
Route::get('/roles/manage', \App\Livewire\Admin\RoleManagerComponent::class)->name('roles.manage');
