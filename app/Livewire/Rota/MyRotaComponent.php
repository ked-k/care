<?php

namespace App\Livewire\Rota;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The carer-facing rota view the manual explicitly calls out as missing:
 * "carers only see timesheets, not the upcoming schedule". Read-only, and
 * deliberately shows only PUBLISHED rota periods — a draft rota is a
 * manager's working copy and shouldn't leak to carers before it's final.
 */
class MyRotaComponent extends Component
{
    #[Url]
    public ?string $week = null;

    public function weekStart(): Carbon
    {
        return $this->week ? Carbon::parse($this->week)->startOfWeek() : now()->startOfWeek();
    }

    public function previousWeek(): void
    {
        $this->week = $this->weekStart()->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->week = $this->weekStart()->addWeek()->toDateString();
    }

    public function goToday(): void
    {
        $this->week = null;
    }

    public function render()
    {
        $weekStart = $this->weekStart();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $shifts = Shift::with(['serviceUser', 'rotaPeriod'])
            ->where('assigned_to', Auth::id())
            ->whereHas('rotaPeriod', fn ($q) => $q->where('status', 'published'))
            ->whereBetween('scheduled_start', [$weekStart, $weekEnd])
            ->orderBy('scheduled_start')
            ->get()
            ->groupBy(fn (Shift $s) => $s->scheduled_start->toDateString());

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $weekStart->copy()->addDays($i);
        }

        return view('livewire.rota.my-rota', [
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'days' => $days,
            'shiftsByDate' => $shifts,
        ]);
    }
}
