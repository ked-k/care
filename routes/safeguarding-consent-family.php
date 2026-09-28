<?php

use App\Livewire\Consent\ConsentManagerComponent;
use App\Livewire\Family\FamilyChatInboxComponent;
use App\Livewire\Family\FamilyMemberManagerComponent;
use App\Livewire\Safeguarding\SafeguardingIndexComponent;
use App\Livewire\Safeguarding\SafeguardingShowComponent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Safeguarding & Consent routes (staff-side)
|--------------------------------------------------------------------------
| Require this file from routes/web.php inside the main authenticated
| staff group, the same way as rota-payroll.php and task-management.php.
| The family-portal-facing routes live in routes/family-portal.php instead —
| they need different middleware and must never share this group.
*/

Route::get('/safeguarding', SafeguardingIndexComponent::class)->name('safeguarding.index');
Route::get('/safeguarding/{safeguardingReportId}', SafeguardingShowComponent::class)->name('safeguarding.show');

Route::get('/service-users/{serviceUserId}/consents', ConsentManagerComponent::class)->name('consents.manage');
Route::get('/service-users/{serviceUserId}/family', FamilyMemberManagerComponent::class)->name('family.manage');

// Batch 10: the staff-side inbox for a service user's family chat sessions
// (see App\Livewire\Family\FamilyChatInboxComponent). Kept alongside
// family.manage above since it's reached the same way — a link on the
// Service Users list — and gated by the same canManage() check.
Route::get('/service-users/{serviceUserId}/messages', FamilyChatInboxComponent::class)->name('messages.manage');
