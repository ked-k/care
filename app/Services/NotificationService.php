<?php

namespace App\Services;

use App\Models\Notification;

/**
 * Creates in-app notification rows. Small and dependency-free, the same
 * shape as App\Services\AuditLogger — wired into a handful of meaningful
 * events (a shift assignment, a safeguarding escalation, a new message)
 * rather than retrofitted everywhere. There is no email/push channel behind
 * this; it only populates the in-app notification bell and center.
 */
class NotificationService
{
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        string $priority = Notification::PRIORITY_NORMAL,
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'data' => $data ?: null,
        ]);
    }
}
