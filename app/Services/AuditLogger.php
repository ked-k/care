<?php

namespace App\Services;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;

/**
 * Writes append-only entries to the `audits` table. Nothing wrote to this
 * table before — it existed as schema only. This is intentionally a small,
 * dependency-free service (no activity-log package is actually installed
 * despite the `activity_log` table existing in the schema) rather than a
 * blanket "log every model event" solution — it's wired into a handful of
 * meaningful actions (safeguarding, consent, service users, policies,
 * login) rather than retrofitted across the whole app in one pass.
 *
 * Usage: AuditLogger::log('SAFEGUARDING_ESCALATED', $report, ['to' => $to->name]);
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
