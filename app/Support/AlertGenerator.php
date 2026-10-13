<?php

namespace App\Support;

use App\Models\Agency;
use App\Models\Alert;
use App\Models\CareCall;
use App\Models\MediaFile;
use App\Models\Medication;
use App\Models\MedicationAdministration;
use App\Models\ServiceUser;
use App\Models\Shift;
use App\Models\TaskLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Batch 16: scans one agency's current shifts, care calls, tasks and
 * medications for the conditions the gap assessment calls out — late
 * calls, missed calls, missing records, missing evidence, medication
 * concerns — and creates an Alert the first time each one is detected.
 * Every creation call is deduplicated so the same occurrence never raises
 * a second alert (see alreadyAlerted()).
 *
 * There's no cron/queue worker configured for this app (app/Console/
 * Kernel.php's schedule is empty), so this runs synchronously wherever a
 * manager might see its results — called from AlertsManagerComponent and
 * the manager dashboard on every load/poll. Same "compute it where it's
 * needed" approach already used for App\Support\CareCallStatus. The
 * 3-day lookback keeps each run cheap regardless of how long the agency
 * has been using the app; a very large agency running this on every
 * 30-second dashboard poll is the kind of thing a real queue/cron job
 * should eventually take over, but that's out of scope for this batch.
 */
class AlertGenerator
{
    private const LOOKBACK_DAYS = 3;

    public static function run(Agency $agency): void
    {
        self::scanLateAndMissedCalls($agency);
        self::scanMissingRecords($agency);
        self::scanMissingEvidence($agency);
        self::scanMedicationConcerns($agency);
    }

    /**
     * A Shift or CareCall that's currently Late (window open, no check-in
     * yet) or Missed (window passed, no check-in at all). If a visit is
     * first seen Late and nobody acts before it tips over to Missed, the
     * stored alert type doesn't escalate — it's still open and actionable,
     * just still labelled "Late call". A known, deliberate simplification;
     * see CHANGES16.md.
     */
    protected static function scanLateAndMissedCalls(Agency $agency): void
    {
        $since = now()->subDays(self::LOOKBACK_DAYS);

        $shifts = Shift::where('agency_id', $agency->id)
            ->where('scheduled_start', '>=', $since)
            ->whereNull('actual_end')
            ->with('serviceUser')
            ->get();

        foreach ($shifts as $shift) {
            $status = CareCallStatus::compute(
                $shift->scheduled_start, $shift->scheduled_end, $shift->actual_start, $shift->actual_end,
            );
            self::recordCallAlert($agency, $shift, $status, $shift->serviceUser, $shift->shift_type ? ucfirst($shift->shift_type).' shift' : __('Shift'));
        }

        $calls = CareCall::where('agency_id', $agency->id)
            ->where('scheduled_start', '>=', $since)
            ->whereNull('actual_end')
            ->where('status', '!=', CareCall::STATUS_CANCELLED)
            ->with('serviceUser')
            ->get();

        foreach ($calls as $call) {
            $status = CareCallStatus::compute(
                $call->scheduled_start, $call->scheduled_end, $call->actual_start, $call->actual_end, $call->isCancelled(),
            );
            self::recordCallAlert($agency, $call, $status, $call->serviceUser, $call->displayLabel());
        }
    }

    protected static function recordCallAlert(Agency $agency, Shift|CareCall $visit, string $status, ?ServiceUser $serviceUser, string $label): void
    {
        if (! $serviceUser || ! in_array($status, [CareCallStatus::LATE, CareCallStatus::MISSED], true)) {
            return;
        }

        if (self::alreadyAlerted($visit, [Alert::TYPE_LATE_CALL, Alert::TYPE_MISSED_CALL])) {
            return;
        }

        $type = $status === CareCallStatus::MISSED ? Alert::TYPE_MISSED_CALL : Alert::TYPE_LATE_CALL;

        self::createAlert($agency, $serviceUser, $type, $visit, sprintf(
            '%s: %s for %s, scheduled %s–%s',
            $type === Alert::TYPE_MISSED_CALL ? __('Missed call') : __('Late call'),
            $label,
            $serviceUser->name,
            $visit->scheduled_start->format('H:i'),
            $visit->scheduled_end->format('H:i'),
        ));
    }

    /**
     * A Shift/CareCall that's now Completed (checked out) with nothing
     * logged for this service user — no TaskLog, no MedicationAdministration
     * — anywhere inside its actual visit window. Catches a visit that
     * happened but was never documented.
     */
    protected static function scanMissingRecords(Agency $agency): void
    {
        $since = now()->subDays(self::LOOKBACK_DAYS);

        $visits = Shift::where('agency_id', $agency->id)
            ->where('actual_end', '>=', $since)
            ->whereNotNull('actual_end')
            ->with('serviceUser')
            ->get()
            ->concat(
                CareCall::where('agency_id', $agency->id)
                    ->where('actual_end', '>=', $since)
                    ->whereNotNull('actual_end')
                    ->with('serviceUser')
                    ->get()
            );

        foreach ($visits as $visit) {
            $serviceUser = $visit->serviceUser;
            if (! $serviceUser || self::alreadyAlerted($visit, [Alert::TYPE_MISSING_RECORD])) {
                continue;
            }

            $window = [$visit->actual_start ?? $visit->scheduled_start, $visit->actual_end];

            $hasTaskActivity = TaskLog::whereHas('task.carePlan', fn ($q) => $q->where('service_user_id', $serviceUser->id))
                ->whereBetween('completed_at', $window)
                ->exists();

            $hasMedActivity = MedicationAdministration::whereHas('medication', fn ($q) => $q->where('service_user_id', $serviceUser->id))
                ->whereBetween('actual_time', $window)
                ->exists();

            if ($hasTaskActivity || $hasMedActivity) {
                continue;
            }

            $label = $visit instanceof Shift
                ? ($visit->shift_type ? ucfirst($visit->shift_type).' shift' : __('Shift'))
                : $visit->displayLabel();

            self::createAlert($agency, $serviceUser, Alert::TYPE_MISSING_RECORD, $visit, sprintf(
                '%s: %s for %s on %s had no task or medication recorded',
                __('Missing record'), $label, $serviceUser->name, $visit->actual_end->format('d M'),
            ));
        }
    }

    /**
     * A task logged Completed or Partly completed whose task requires a
     * photo or signature, but one wasn't actually attached. Possible
     * because completeTask()'s validation only forces the photo/signature
     * when the outcome is a full "Completed" (see TaskListComponent /
     * ShiftVisitComponent) — a Partly completed log can slip through
     * without one.
     */
    protected static function scanMissingEvidence(Agency $agency): void
    {
        $since = now()->subDays(self::LOOKBACK_DAYS);

        $logs = TaskLog::whereHas('task', fn ($q) => $q->where(function ($q2) {
                $q2->where('requires_photo', true)->orWhere('requires_signature', true);
            }))
            ->whereHas('task.carePlan.serviceUser', fn ($q) => $q->where('agency_id', $agency->id))
            ->whereIn('status', ['completed', 'partly_completed'])
            ->where('completed_at', '>=', $since)
            ->with('task.carePlan.serviceUser')
            ->get();

        foreach ($logs as $log) {
            $task = $log->task;
            $serviceUser = $task?->carePlan?->serviceUser;
            if (! $task || ! $serviceUser || self::alreadyAlerted($log, [Alert::TYPE_MISSING_EVIDENCE])) {
                continue;
            }

            $missingPhoto = $task->requires_photo && ! $log->photo_id;
            $missingSignature = $task->requires_signature && ! MediaFile::where('related_type', TaskLog::class)
                ->where('related_id', $log->id)
                ->where('meta->kind', 'signature')
                ->exists();

            if (! $missingPhoto && ! $missingSignature) {
                continue;
            }

            $what = $missingPhoto && $missingSignature
                ? __('a photo and signature')
                : ($missingPhoto ? __('a photo') : __('a signature'));
            $outcome = $log->status === 'partly_completed' ? __('partly completed') : __('completed');

            self::createAlert($agency, $serviceUser, Alert::TYPE_MISSING_EVIDENCE, $log, sprintf(
                '%s: "%s" for %s was marked %s but is missing %s',
                __('Missing evidence'), $task->title, $serviceUser->name, $outcome, $what,
            ));
        }
    }

    /**
     * A medication with 2+ refused/missed doses in the trailing 7 days
     * (App\Models\MedicationAdministration::recentConcernCounts(), the
     * same threshold Batch 15 used for the warning icon on the MAR
     * chart). Dedupes only against a still-open/acknowledged alert for
     * that medication, not against resolved ones — a concern that was
     * dealt with and later reappears (fresh refusals) raises a fresh
     * alert rather than staying silent forever.
     */
    protected static function scanMedicationConcerns(Agency $agency): void
    {
        $medIds = Medication::whereHas('serviceUser', fn ($q) => $q->where('agency_id', $agency->id))
            ->where('is_prn', false)
            ->pluck('id');

        $concerns = MedicationAdministration::recentConcernCounts($medIds);

        if (! $concerns) {
            return;
        }

        $meds = Medication::with('serviceUser')->whereIn('id', array_keys($concerns))->get();

        foreach ($meds as $med) {
            $serviceUser = $med->serviceUser;
            if (! $serviceUser) {
                continue;
            }

            $exists = Alert::where('alertable_type', Medication::class)
                ->where('alertable_id', $med->id)
                ->where('type', Alert::TYPE_MEDICATION_CONCERN)
                ->whereIn('status', [Alert::STATUS_OPEN, Alert::STATUS_ACKNOWLEDGED])
                ->exists();

            if ($exists) {
                continue;
            }

            self::createAlert($agency, $serviceUser, Alert::TYPE_MEDICATION_CONCERN, $med, sprintf(
                '%s: %s for %s had %d refused/missed dose(s) in the last 7 days',
                __('Medication concern'), $med->medication_name, $serviceUser->name, $concerns[$med->id],
            ));
        }
    }

    protected static function alreadyAlerted(object $model, array $types): bool
    {
        return Alert::where('alertable_type', $model::class)
            ->where('alertable_id', $model->id)
            ->whereIn('type', $types)
            ->exists();
    }

    /**
     * Batch 17: delegates to Alert::raiseFor() — the named-responder
     * assignment and notification logic used to live here, but now lives
     * on the model itself so a carer-raised alert (HandoverComponent's
     * "flag as urgent", a significant WellbeingObservation) shares the
     * exact same path instead of duplicating it.
     */
    protected static function createAlert(Agency $agency, ServiceUser $serviceUser, string $type, Model $alertable, string $summary): void
    {
        Alert::raiseFor($serviceUser, $type, $summary, $alertable, $agency->id);
    }
}
