# Batch 3 — Carer-Facing & Operational Gaps

This batch covers the remaining items from the user manual's "What's Not in CareTrust Yet" list that fall into day-to-day operations: a carer-facing rota view, structured assessments, a staff-facing care timeline, in-app notifications, real messaging (replacing the template's fake demo chat), and CSV export where the manual asked for PDF/Excel.

As with Batches 1 and 2, everything below was written directly into your working tree — no shell access on this machine, so no git branch. `git status` / `git diff` before running the seeder-free changes below (this batch adds no new seeder — see "Permissions" note), and `git stash` to back out if needed.

There is no new artisan command to run for this batch — no new permissions or seed data were needed.

## What was built

**My Rota** (`/my-rota`, sidebar under Rota Management) — the manual's own words: "carers only see timesheets, not the upcoming schedule." This is that missing schedule: a week-by-week, read-only view of the logged-in carer's own shifts, showing the service user, shift time, and a link straight into that shift's tasks. It only ever shows **published** rota periods — a manager's draft rota stays private until published, matching how the existing rota builder already distinguishes draft/published.

While building this I found that publishing a rota is also the natural moment to notify carers — see Notifications below.

**Assessments** (`Service Users → Assessments`) — a real `Assessment` model and a per-service-user list/create screen. The vision doc describes many assessment types (moving & handling, nutrition, mental capacity, falls risk, etc.) without a fixed question set for each, so `questions_and_answers` is a free-form repeatable question/answer list rather than a separate schema per type — add as many or as few questions as the assessment needs. Each assessment records a risk level, an optional score, recommendations, and a review date (flagged once overdue).

**Care Timeline (staff view)** (`Service Users → Timeline`) — the `care_timeline_entries` table already existed and Batch 1 wired it to auto-populate from completed tasks, but there was no staff-facing way to see it or add to it manually (only the family portal could see it, and only automatically-generated entries existed). This adds a timeline view for staff with a manual "Add update" action — for anything that isn't a task completion (a GP visit, a phone call with family, a general note), with an optional photo and a per-entry "share with family" toggle.

**Notifications** — a real `Notification` model and a notification bell + full notification center (`/notifications`), replacing the admin template's hardcoded demo dropdown (previously always showed a fake "3" badge and fictional entries like "Steve Smith"). Wired into three real events: a carer is notified when their rota is published, a manager is notified when a safeguarding report is escalated to them, and a user is notified when they receive a new message. This polls every 20 seconds rather than pushing live — there's no websocket/broadcast layer (Reverb/Pusher) configured in this app, so this is deliberately near-live, not real-time.

**Messaging** — a real `Message` model behind the header's chat drawer, which previously held entirely fake, hardcoded contacts and conversations (fictional names, canned replies). It now lists your real colleagues (same agency, active accounts, excluding Family-role accounts who never reach this layout anyway) with real unread counts and lets you send and receive actual messages, saved to the database. Same caveat as notifications: this polls every 10 seconds, it isn't a live/push chat. The schema's `encrypted` column (default `true`) isn't backed by any actual encryption in this app, so messages are written with it explicitly set to `false` rather than implying a protection that doesn't exist — see the note in `App\Models\Message`.

**CSV export** — the manual asks for "PDF/Excel export"; this app has neither a PDF library (no dompdf) nor an Excel library (no maatwebsite/excel) installed, and I have no shell/composer access to add one. What's delivered instead is CSV export — opens directly in Excel/Sheets/Numbers, needs no new dependency — on the two screens where it's most useful: the **Audit Log** (respecting whatever filters are active on screen) and the **Compliance Dashboard** (a metrics snapshot plus the current checklist). If you specifically need PDF output, adding `barryvdh/laravel-dompdf` via Composer on your end and asking me to wire it in afterward would be the way to get true PDF exports.

## A bug fixed along the way

`/tasks/shift/{shift}` (linked as "Tasks scoped to one shift" in a code comment) pointed at a `tasks.by-shift` Blade view that was never created — `resources/views/tasks/` didn't exist, so this route would have errored for anyone who followed it. `TaskListComponent` already accepts an optional shift ID and scopes its list to that shift, so the route now points there instead of the missing view. My Rota's "View tasks" links use this fixed route.

## Known gotchas / things worth knowing

- **`Notification` vs Laravel's own notifications system**: `User` already had Laravel's `Notifiable` trait (for a currently-unused notification channel), which defines its own `notifications()` relation against `Illuminate\Notifications\DatabaseNotification` — a different class with a different table shape than this batch's custom `notifications` table (user_id/title/message/priority columns, not the notifiable-morph/data-blob convention). To avoid a silent collision, the relation to this batch's `Notification` model on `User` is named `appNotifications()`, not `notifications()`.
- **Rota Plans access**: while wiring My Rota I noticed the existing manager-facing rota builder (`/rota`, `/rota/{id}/builder`) has no permission check at all in its `mount()` — any authenticated staff member can currently reach it and edit shifts, not just managers. I didn't change this in this batch (hiding it from the sidebar only would have been a false sense of security without also gating the route, and that felt like a "Role & data cleanup" batch concern rather than an operational-gap one) — flagging it here so it doesn't get missed.
- **Messaging and notifications are polling-based**, not push-based, as noted above — expect up to a ~10–20 second delay before a new message or notification shows up without a manual refresh.
- `resources/views/include/chat.blade.php` is no longer included from the layout (the chat drawer now lives inline in `include/header.blade.php` as a single Livewire component, so the trigger button and the drawer panel share one instance and one set of real data). The old file is left in place with a comment explaining it's superseded, since this session can't delete files on your machine — safe to delete by hand.

## Files changed or added

**New models:** `Assessment`, `Notification`, `Message`.

**New service:** `App\Services\NotificationService`.

**New Livewire components:** `Rota\MyRotaComponent`, `Assessment\AssessmentIndexComponent`, `CareTimeline\TimelineIndexComponent`, `Notification\NotificationBellComponent`, `Notification\NotificationCenterComponent`, `Messaging\ChatDrawerComponent`.

**Modified Livewire components:** `Rota\RotaBuilder` and `Rota\RotaPeriodIndex` (publish now calls the new `RotaPeriod::publish()`, which notifies scheduled carers), `Audit\AuditIndexComponent` and `Compliance\ComplianceDashboardComponent` (CSV export actions).

**Modified models:** `RotaPeriod` (new `publish()` method), `SafeguardingReport` (escalate now also notifies the escalated-to manager), `ServiceUser` (added `assessments()`), `User` (added `appNotifications()`).

**New views:** `livewire/rota/my-rota.blade.php`, `livewire/assessment/assessment-index.blade.php`, `livewire/care-timeline/timeline-index.blade.php`, `livewire/notification/notification-bell.blade.php`, `livewire/notification/notification-center.blade.php`, `livewire/messaging/chat-drawer.blade.php`.

**Modified views:** `include/header.blade.php` (real notification bell and chat drawer replacing hardcoded demo content), `layouts/admin-layout.blade.php` (dropped the now-superseded `include.chat`), `service-user/service-user-manager.blade.php` (added Assessments/Timeline row links).

**Replaced view (superseded, not deleted):** `include/chat.blade.php`.

**Routes:** new `routes/operational.php` (My Rota, Assessments, Timeline, Notification Center), included from `routes/web.php`; `routes/task-management.php` fixed (see bug note above).

**Config:** `config/menu.php` — added "Notifications" and "My Rota" (under Rota Management) sidebar entries.
