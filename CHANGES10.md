# Batch 10 — Family Messaging & Chat Sessions

## What changed

Family members previously had no way to contact anyone through the app —
the only messaging that existed (`Message` model, the "Colleagues" chat
drawer in the header) is staff-only and explicitly excludes the Family
role. This batch adds a family-facing messaging feature built on top of
that same `Message` model, organised into **chat sessions** — named,
closeable conversation threads, similar to a support ticket — rather than
one endless back-and-forth.

### 1. Data model

- **New `chat_sessions` table / `ChatSession` model** — a named
  conversation about one service user, with a status (`open`/`closed`)
  and a `last_message_at` used to sort the list. Deliberately **two-sided
  rather than one-to-one**: any family member linked to a service user
  can read and reply to any session for that person (a shared family
  inbox — useful when two children both look after one parent), and any
  staff member who can manage that service user's family access can do
  the same on the other side (a shared staff inbox). Nobody has a private
  1:1 thread with one specific person on the other side.
- **`messages` table** gained a nullable `chat_session_id` — existing
  staff-to-staff messages are untouched (it's just null for those).
  `receiver_id` had to become nullable too, since a session message
  belongs to both sides of a conversation, not one named recipient.
  This project doesn't have `doctrine/dbal` installed, which Laravel's
  migration `->change()` needs to alter an existing column, so that one
  column's nullability is relaxed with a raw `ALTER TABLE ... MODIFY`
  statement instead — it only touches nullability, not the column's type
  or its existing foreign key.
- Since a session's "other side" is now potentially more than one person,
  `Message.read_at` is reinterpreted for session messages as "seen by the
  other side" (family vs. staff) rather than "seen by the one receiver" —
  `ChatSession::markReadByFamily()` / `markReadByStaff()` and the
  matching `unreadCountForFamily()` / `unreadCountForStaff()` do this by
  checking whether a message's sender has the Family role, not by
  tracking a read per individual user.

### 2. Family portal — new "Messages" tab

The family portal's per-service-user page (Overview / Care Plan /
Medications / Schedule / Notes) gains a sixth tab, **Messages**, on both
the desktop tab strip and the phone's bottom nav bar:

- A list of chat sessions for that service user, each showing its
  subject, status, and an unread count.
- **New conversation** opens a short form (subject + message) and starts
  a session.
- Tapping a session opens a thread view — the same look as the staff
  "Colleagues" chat drawer (bubbles, own-message alignment, a composer at
  the bottom) — where family can keep replying. Sending a message on a
  session staff had marked resolved reopens it automatically.
- An unread-count badge appears on the Messages tab itself (both the
  phone bottom bar and the desktop strip) and on each service user's card
  on the family portal's landing page, so a new reply is visible without
  opening the tab.
- Built as its own nested Livewire component (`FamilyChatComponent`),
  mounted inside the existing tab's `x-show` panel rather than folded
  into the already-large `FamilyServiceUserComponent` — it polls every 20
  seconds for new messages on its own, independent of the parent tab.

### 3. Staff side — "Messages" per service user

A new "Messages" link sits next to "Family" on the Service Users list,
opening a page scoped to that person: a session list on the left, thread
+ reply box on the right, with an Open/Resolved/All filter and a "Mark
resolved" / "Reopen" toggle per conversation. Polls every 15 seconds.

**Viewing this page is gated more strictly than the Family Access list
it sits beside** — only Admin/Super Admin or a user with the
`family.manage` permission can open it at all (`FamilyMemberManagerComponent`'s
family list, by contrast, is viewable by any authenticated staff member;
only editing it is locked down). Conversation content felt more
sensitive than a name-and-relationship list, so viewing it got the
stricter treatment.

### 4. Notifications

Sending a message in either direction creates an in-app notification
(the existing `Notification`/`NotificationService` used elsewhere in the
app — the same mechanism behind shift-assignment and safeguarding
notifications) for the other side: family messages notify every staff
member who could act on `FamilyMemberManagerComponent`'s "Family" page
for that agency (Admin/Super Admin, or anyone individually granted
`family.manage`); staff replies notify every family member linked to
that service user.

**Deliberately not included:** an email per message. Account-access
emails (Batch 9) are a one-off you need to actually receive; a running
conversation emailing on every reply would get noisy fast, and there's
no read-receipt/throttling logic to soften that yet. The in-app badges
plus the 15–20 second polling on both sides cover the common case of
"I'm using the app right now" reasonably well without it. Happy to add a
throttled "you have a new message" email later if it'd help — this is a
100%-doable follow-up, just deliberately left out of this batch's scope.

## Something worth knowing

Chat sessions are shared per service user, not private per family member
— if two people are linked to the same person, they'll both see (and can
both reply in) the same conversations. If you'd rather each family
member had their own private line to the care team, that's a different
design (closer to the original 1:1 `Message` model) and would need a
follow-up change.

## Files touched

New: `database/migrations/2026_09_28_120000_create_chat_sessions_table.php`,
`database/migrations/2026_09_28_120001_add_chat_session_to_messages_table.php`,
`app/Models/ChatSession.php`, `app/Livewire/Family/FamilyChatComponent.php`,
`app/Livewire/Family/FamilyChatInboxComponent.php`,
`resources/views/livewire/family/family-chat.blade.php`,
`resources/views/livewire/family/family-chat-inbox.blade.php`

Changed: `app/Models/Message.php`, `app/Models/ServiceUser.php`, `app/Models/Notification.php`,
`app/Livewire/Family/FamilyServiceUserComponent.php`, `app/Livewire/Family/FamilyPortalComponent.php`,
`resources/views/livewire/family/family-service-user.blade.php`,
`resources/views/livewire/family/family-portal.blade.php`,
`resources/views/livewire/service-user/service-user-manager.blade.php`,
`routes/safeguarding-consent-family.php`

## Next step on your end

Run `php artisan migrate` to create `chat_sessions` and add the
`chat_session_id` column (and relax `receiver_id`) on `messages` — same
as every previous batch, this session can write the migration but can't
run it for you.
