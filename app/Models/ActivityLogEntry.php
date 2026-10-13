<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;

/**
 * Batch 20: thin subclass of Spatie's own Activity model, used only to add
 * the small set of display helpers the old Audit model offered
 * (actionLabel()/entityLabel()) so the Audit Log page and its CSV export
 * don't need to re-derive them inline. This does NOT change where entries
 * are written — every model using LogsActivity, and the manual activity()
 * call in LoginController, still write through the package's own Activity
 * model, which reads back identically through this subclass since it's the
 * same `activity_log` table with no overridden $table/$connection.
 */
class ActivityLogEntry extends Activity
{
    public function actionLabel(): string
    {
        if ($this->event) {
            return ucfirst($this->event);
        }

        return ucfirst(strtolower(str_replace('_', ' ', (string) $this->log_name)));
    }

    public function entityLabel(): string
    {
        return $this->subject_type ? class_basename($this->subject_type) : '—';
    }

    public function causerLabel(): string
    {
        return $this->causer?->name ?? 'System';
    }
}
