<?php

namespace App\Livewire\Training;

use App\Models\Notification;
use App\Models\TrainingModule;
use App\Models\TrainingProgress;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Batch 36: the manager-facing "training matrix" — every member of staff
 * down the rows, every training module across the columns, coloured by
 * App\Models\TrainingProgress::matrixStatus() (not started / started /
 * completed / expiring soon / expired). TrainingIndexComponent only ever
 * showed a user their own progress; this is the first place anyone gets a
 * cross-staff view, which is the whole point of the feature the user asked
 * for after seeing a competitor's ad advertise exactly this.
 *
 * Like App\Support\AlertGenerator, there's no cron/queue worker configured
 * on this app (app/Console/Kernel.php's schedule is empty), so renewal
 * reminders are computed "where they're needed" — here, once whenever a
 * manager opens this page — rather than on a schedule. See
 * sendRenewalReminders() for the dedup rule that keeps that cheap and
 * non-repetitive.
 */
class TrainingMatrixComponent extends Component
{
    public function mount(): void
    {
        $this->authorizeMatrixAccess();
        $this->sendRenewalReminders();
    }

    protected function authorizeMatrixAccess(): void
    {
        $user = Auth::user();
        abort_unless($user->can('training.manage') || $user->hasRole(['Admin', 'Super Admin']), 403, 'Only a manager can view the training matrix.');
    }

    /**
     * Notify a trainee (and, for a mandatory module, their agency's
     * managers too) the first time a certificate is found expired or
     * expiring within 30 days — then re-check no sooner than 7 days later,
     * so this doesn't re-notify everyone on every single page load. A
     * fresh TrainingProgress::logProgress() call resets last_reminded_at
     * to null itself (see TrainingIndexComponent::logProgress()), so a
     * renewed certificate starts being watched again immediately.
     */
    protected function sendRenewalReminders(): void
    {
        $agencyId = Auth::user()->agency_id;

        $candidates = TrainingProgress::query()
            ->where('status', TrainingProgress::STATUS_COMPLETED)
            ->whereNotNull('expires_at')
            ->where(function ($q) {
                $q->whereNull('last_reminded_at')
                    ->orWhere('last_reminded_at', '<=', now()->subDays(7));
            })
            ->whereHas('user', fn ($q) => $q->where('agency_id', $agencyId))
            ->with(['user', 'module'])
            ->get()
            ->filter(fn (TrainingProgress $p) => $p->isExpired() || $p->isExpiringSoon());

        if ($candidates->isEmpty()) {
            return;
        }

        $managerIds = User::where('agency_id', $agencyId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Manager', 'Admin', 'Super Admin']))
            ->pluck('id');

        foreach ($candidates as $progress) {
            $trainee = $progress->user;
            $module = $progress->module;
            if (! $trainee || ! $module) {
                continue;
            }

            $verb = $progress->isExpired() ? __('has expired') : __('is expiring soon');
            $message = sprintf(
                '%s: "%s" %s (%s)',
                $trainee->full_name, $module->title, $verb, $progress->expires_at->format('d M Y'),
            );

            // concat(), not push() — push() mutates the collection in place
            // and returns $this, so reusing a $recipients variable across
            // loop iterations would otherwise keep growing one shared
            // collection object rather than giving each row its own list.
            $recipients = collect([$trainee->id])
                ->concat($module->is_mandatory ? $managerIds : [])
                ->unique()
                ->filter();

            foreach ($recipients as $recipientId) {
                NotificationService::send(
                    $recipientId,
                    'training_renewal_due',
                    __('Training renewal due'),
                    $message,
                    $progress->isExpired() ? Notification::PRIORITY_HIGH : Notification::PRIORITY_NORMAL,
                    ['training_progress_id' => $progress->id],
                );
            }

            $progress->update(['last_reminded_at' => now()]);
        }
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;

        $staff = User::where('agency_id', $agencyId)
            ->active()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Family'))
            ->orderBy('first_name')->orderBy('last_name')
            ->get();

        $modules = TrainingModule::orderByDesc('is_mandatory')->orderBy('category')->orderBy('title')->get();

        $progressByKey = TrainingProgress::whereIn('user_id', $staff->pluck('id'))
            ->whereIn('module_id', $modules->pluck('id'))
            ->get()
            ->keyBy(fn (TrainingProgress $p) => $p->user_id.'|'.$p->module_id);

        return view('livewire.training.training-matrix', [
            'staff' => $staff,
            'modules' => $modules,
            'progressByKey' => $progressByKey,
        ]);
    }
}
