<?php

use App\Livewire\Audit\AuditIndexComponent;
use App\Livewire\Compliance\ComplianceDashboardComponent;
use App\Livewire\DataProtection\BreachReportIndexComponent;
use App\Livewire\DataProtection\SarIndexComponent;
use App\Livewire\Policy\PolicyIndexComponent;
use App\Livewire\Training\TrainingIndexComponent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Compliance & Governance routes (staff-side)
|--------------------------------------------------------------------------
| Require this file from routes/web.php inside the main authenticated
| staff group, the same way as safeguarding-consent-family.php. All of
| these components self-gate manager-only actions internally (canManage()),
| except the audit log which aborts entirely for non-managers in mount().
*/

Route::get('/policies', PolicyIndexComponent::class)->name('policies.index');
Route::get('/training', TrainingIndexComponent::class)->name('training.index');
Route::get('/compliance', ComplianceDashboardComponent::class)->name('compliance.dashboard');
Route::get('/audits', AuditIndexComponent::class)->name('audits.index');
Route::get('/data-protection/sar', SarIndexComponent::class)->name('data-protection.sar');
Route::get('/data-protection/breaches', BreachReportIndexComponent::class)->name('data-protection.breaches');
