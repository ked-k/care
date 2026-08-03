<?php

use App\Livewire\CarePlan\CarePlanIndexComponent;
use App\Livewire\CarePlan\CarePlanShowComponent;
use App\Livewire\Task\TaskListComponent;
use App\Models\Shift;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Task Management routes
|--------------------------------------------------------------------------
| Require this file from routes/web.php inside your authenticated group,
| the same way as rota-payroll.php.
*/

// Route::get('/care-plans', function () {
//     return view('care-plans.index');
// })->name('care-plans.index');

// Route::get('/care-plans/{carePlan}', function (CarePlan $carePlan) {
//     return view('care-plans.show', compact('carePlan'));
// })->name('care-plans.show');

// // "My tasks" for the logged-in carer, filterable by date.
// Route::get('/tasks', function () {
//     return view('tasks.index');
// })->name('tasks.index');

Route::get('/care-plans', CarePlanIndexComponent::class)->name('care-plans.index');
Route::get('/care-plans/{carePlanId}', CarePlanShowComponent::class)->name('care-plans.show');
// "My tasks" for the logged-in carer, filterable by date.
Route::get('/tasks', TaskListComponent::class)->name('tasks.index');

// Tasks scoped to one shift — e.g. linked from the shift check-in screen.
Route::get('/tasks/shift/{shift}', function (Shift $shift) {
    return view('tasks.by-shift', compact('shift'));
})->name('tasks.by-shift');
