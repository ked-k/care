<?php

use App\Livewire\Family\FamilyPortalComponent;
use App\Livewire\Family\FamilyServiceUserComponent;
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
});
