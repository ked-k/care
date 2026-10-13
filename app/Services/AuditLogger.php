<?php

namespace App\Services;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;

/**
 * Batch 20: retired. As of this batch, nothing in the app calls
 * AuditLogger::log() any more — every model that used to be logged through
 * here (SafeguardingReport, Consent, Policy, PolicyAcknowledgment,
 * ServiceUser, SubjectAccessRequest, BreachReport, and now also CarePlan,
 * Medication, MedicationAdministration, ComplianceCheck) writes to the
 * `activity_log` table automatically via spatie/laravel-activitylog's
 * LogsActivity trait instead, and LoginController's login event uses that
 * package's activity() helper directly. See App\Models\ActivityLogEntry
 * and the "Legacy Audit Log" tab on the Audit Log page.
 *
 * This class and the `audits` table it wrote to are left in place,
 * unmodified and still fully functional, purely so the audit history
 * written before Batch 20 stays readable — nothing new will ever be
 * written here again. Safe to delete in a future cleanup pass once nobody
 * needs that pre-Batch-20 history any more.
 */
class AuditLogger
{
    public static function log(string $action, ?Model $entity = null, array $metadata = [], ?int $userId = null): Audit
    {
        $request = RequestFacade::instance();

        return Audit::create([
            'user_id' => $userId ?? Auth::id(),
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id' => $entity?->getKey(),
            'action' => $action,
            'metadata' => $metadata ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
            'session_id' => $request?->hasSession() ? $request->session()->getId() : null,
        ]);
    }
}
