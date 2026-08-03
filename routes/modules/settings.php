<?php

use Illuminate\Support\Facades\Route;

// Settings module (UI / presentation only)
Route::view('/settings', 'settings.general');
Route::view('/settings/company', 'settings.company');
Route::view('/settings/localization', 'settings.localization');
Route::view('/settings/notifications', 'settings.notifications');
Route::view('/settings/appearance', 'settings.appearance');
Route::view('/settings/security', 'settings.security');
