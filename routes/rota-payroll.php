<?php

use App\Livewire\Payroll\PayrollRunIndexComponent;
use App\Livewire\Payroll\PayrollRunShowComponent;
use App\Livewire\Payroll\PayslipShowComponent;
use App\Livewire\Rota\RotaBuilder;
use App\Livewire\Rota\RotaPeriodIndex;
use App\Livewire\Timesheet\TimesheetIndexComponent;
use App\Livewire\Timesheet\WeeklyTimesheetComponent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rota, Timesheet & Payroll routes
|--------------------------------------------------------------------------
| Require this file from routes/web.php inside your authenticated group, e.g.:
|
|   Route::middleware(['auth'])->group(function () {
|       require __DIR__.'/rota-payroll.php';
|   });
|
| All page views extend layouts.main and embed a single Livewire component,
| per Radminly's documented "Add a new page" convention.
*/

// Route::get('/rota', function () {
//     return view('rota.index');
// })->name('rota.index');

// Route::get('/rota/{rotaPeriod}/builder', function (RotaPeriod $rotaPeriod) {
//     return view('rota.builder', compact('rotaPeriod'));
// })->name('rota.builder');

Route::get('/rota', RotaPeriodIndex::class)->name('rota.index');
Route::get('/rota/{rotaPeriodId}/builder', RotaBuilder::class)->name('rota.builder');

Route::get('/timesheets', TimesheetIndexComponent::class)->name('timesheets.index');

Route::get('/timesheets/{timesheetId}', WeeklyTimesheetComponent::class)->name('timesheets.show');

Route::get('/payroll', PayrollRunIndexComponent::class)->name('payroll.index');

Route::get('/payroll/{payrollRunId}', PayrollRunShowComponent::class)->name('payroll.show');

Route::get('/payroll/payslip/{payslip}', PayslipShowComponent::class)->name('payroll.payslip');
