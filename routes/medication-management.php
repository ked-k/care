<?php

use App\Livewire\Medication\MarChartComponent;
use App\Livewire\Medication\MedicationManagerComponent;
use App\Models\Medication;
use App\Models\MedicationAdministration;
use App\Models\ServiceUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Medication Management (MAR Chart) routes
|--------------------------------------------------------------------------
| Require this file from routes/web.php inside your authenticated group,
| the same way as rota-payroll.php and task-management.php.
*/

Route::get('/service-users/{serviceUserId}/medications', MedicationManagerComponent::class)
    ->name('medications.manage');

// MarChartComponent reads ?week= itself via #[Url], so it's not a route segment here.
Route::get('/service-users/{serviceUserId}/mar-chart', MarChartComponent::class)
    ->name('medications.mar-chart');

// Plain, non-Livewire printable view — not a component, so it stays a closure
// route with real Eloquent model binding (needs the actual model, not just
// its id, to query medications/administrations directly).
Route::get('/service-users/{serviceUser}/mar-chart/print', function (ServiceUser $serviceUser) {
    $weekStart = request('week')
        ? Carbon::parse(request('week'))->startOfWeek()
        : now()->startOfWeek();
    $weekEnd = $weekStart->copy()->endOfWeek();

    $days = [];
    for ($i = 0; $i < 7; $i++) {
        $date = $weekStart->copy()->addDays($i);
        $days[$date->toDateString()] = $date->format('D d/m');
    }

    $allMeds = $serviceUser->medications()->where('is_active', true)->orderBy('medication_name')->get();

    $scheduledMeds = $allMeds->where('is_prn', false)->values()->map(fn (Medication $m) => [
        'id' => $m->id, 'name' => $m->medication_name, 'dosage' => $m->dosage,
        'route' => $m->administration_route, 'time' => $m->scheduledTimeFormatted(),
    ])->toArray();

    $administrations = MedicationAdministration::whereIn('medication_id', $allMeds->pluck('id'))
        ->whereBetween('scheduled_time', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
        ->with('administeredBy')
        ->get();

    $adminByMedAndDate = $administrations->groupBy(fn ($a) => $a->medication_id.'|'.$a->scheduled_time->toDateString());

    $grid = [];
    foreach ($allMeds->where('is_prn', false) as $med) {
        foreach (array_keys($days) as $date) {
            $admin = $adminByMedAndDate->get($med->id.'|'.$date)?->first();
            $state = $admin
                ? $admin->status
                : ($med->isActiveOn(Carbon::parse($date))
                    ? (Carbon::parse($date.' '.$med->scheduled_times)->isPast() ? 'overdue' : 'upcoming')
                    : 'n/a');

            $grid[$med->id][$date] = ['state' => $state, 'administration' => $admin];
        }
    }

    $prnLogsThisWeek = $administrations
        ->whereIn('medication_id', $allMeds->where('is_prn', true)->pluck('id'))
        ->map(fn ($a) => [
            'actual_time' => $a->actual_time?->format('d M, H:i'),
            'scheduled_time' => $a->scheduled_time->format('d M, H:i'),
            'status' => $a->status,
            'administered_by' => $a->administeredBy->name ?? '—',
        ])->values()->toArray();

    return view('medications.mar-print', compact('serviceUser', 'weekStart', 'days', 'scheduledMeds', 'grid', 'prnLogsThisWeek'));
})->name('medications.mar-print');
