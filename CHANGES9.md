# Batch 9 — Family Portal Enrichment + Account Access Emails

## What changed

### 1. Account credential emails (new)

There was no email-sending anywhere in the app before this batch (no
`app/Mail`, no `app/Notifications`, no `Mail::` calls). Added the first one:

- `app/Mail/AccountAccessMail.php` — a Mailable covering both scenarios:
  - a **brand-new account** (staff or family) — includes the login email and
    the generated/entered password
  - an **existing account newly linked** to another service user (family
    only) — no password included, since one isn't (and shouldn't be)
    re-generated for an account that already has one
- `resources/views/emails/account-access.blade.php` — the actual email
  (agency name in the header, login email, password when relevant, a
  "Log in to CareTrust" button linking to your login page)

Wired in at both places an account or access grant is created:

- `FamilyMemberManagerComponent::addFamilyMember()` — sends it whenever a
  new family account is created, or an existing one is freshly linked to a
  service user. Re-saving an existing link's checkboxes doesn't re-send it.
  The drawer's on-screen "here's the password" fallback is kept alongside
  the email, not replaced by it, in case mail isn't configured.
- `StaffManagerComponent::saveStaff()` — sends it when a new staff member is
  created (not on edits, since editing doesn't touch the password field
  unless the manager sets a new one).

Both wrap the send in a try/catch — a missing/broken SMTP configuration
(this box currently has `.env`'s example `mailtrap.io` placeholder, which
won't actually deliver anything) logs a warning and shows a "couldn't send
the email" toast rather than failing the whole save. **You'll need real
MAIL_* values in `.env` for this to actually deliver** — happy to help wire
up a provider if you want.

### 2. Family Portal — from a single page to five

Before this batch, a family login saw exactly one screen: a care plan
summary plus a timeline of visible-to-family updates. Rebuilt into a
tabbed view — Overview / Care Plan / Medications / Schedule / Notes:

- **Overview** — next visit, agency contact card (tap-to-call/email), and a
  3-item preview of the latest updates
- **Care Plan** ("Programs") — every active care plan's title/summary/review
  date, plus goals and daily routine when a plan has that structured data,
  plus the plan's task list with due times and status
- **Medications** — active medications (name, dose, route, schedule/PRN,
  instructions) and each one's last 5 administration records (given/
  prompted/refused/missed). **Gated behind an active "medication-related
  communication" consent** for that service user — the existing `Consent`
  model already has this exact consent type, so this just makes it mean
  something. Without that consent on file, family sees an explanation
  instead of the data.
- **Schedule** — upcoming and recent visits (date/time, assigned carer).
  Only ever shows shifts from a *published* rota period — same rule
  `MyRotaComponent` already applies for carers, so a draft rota (a
  manager's working copy) doesn't leak here either.
- **Notes** — the existing timeline, unchanged in substance

New helper: `ServiceUser::hasActiveMedicationConsent()` — checks live
(never cached) since a consent can be revoked at any time.

Tab switching is instant (Alpine `x-show`, not a server round-trip per
click) — a phone app doesn't spin a loader to switch screens. The current
tab is still reflected in the URL (`?tab=medications`) and survives the
back button, via a `.live`-entangled Alpine variable that syncs to the
server in the background without blocking the click.

If a family login is linked to exactly one service user (the overwhelmingly
common case), the portal now skips straight to that person's page instead
of showing a "pick who to view" screen with one option on it.

### 3. Mobile-app polish

- A real bottom tab bar (icons + labels) on phone-width screens; a top tab
  strip on tablet/desktop — both drive the same underlying tab state
- `viewport-fit=cover` + safe-area padding so content sits correctly around
  a phone's notch/home-indicator
- `apple-mobile-web-app-*` meta tags so "Add to Home Screen" on iOS opens
  full-screen like an app rather than inside Safari's chrome

**Deliberately not included:** a real installable PWA (web manifest +
service worker) with offline caching. This portal is always showing live
care data — a cached offline copy could show stale medication/schedule
information, which is the wrong trade-off for something like this. The
meta-tag polish above gets most of the "feels like an app" benefit without
that risk.

### 4. Reset password / resend login details (addendum)

Follow-up ask in the same session: an admin needed a way to hand someone
their login again after the fact — either because the email in section 1
never arrived (mail wasn't configured yet, spam filter, wrong address) or
because a family member's account already existed and they just needed a
fresh password. Since the app never stores a plaintext password anywhere,
"resend the same password" isn't possible — the only honest option is to
issue a new one and email it, which is what both of these do:

- **Staff Management** — a new "Reset password" action per row (next to
  Edit/Deactivate): generates a fresh password, saves it, and emails it via
  the same `AccountAccessMail`. Also: typing a new password into the
  existing Edit drawer now emails it too — that was already possible before
  this addendum, it just silently changed the password with no way for the
  person to find out.
- **Family Access** — a new "Resend login" action per row (next to Remove),
  doing the same thing for a family member's account.

Both actions are scoped to the acting admin/manager's own agency before
touching anything, as a deliberate hardening while adding a
password-changing action: `FamilyMemberManagerComponent`'s existing
`removeFamilyMember()` and `StaffManagerComponent`'s existing
`toggleActive()` only check the caller's role, not that the target record
belongs to their agency (family member ids are UUIDs, low practical risk;
staff ids are sequential integers, more realistic to guess) — flagging that
as a pre-existing gap worth closing generally, left as-is here since it
wasn't part of what was asked, but the two new reset actions do carry an
explicit agency check.

**Already covered, in case it looked missing:** adding a family member with
an email that already has an account does NOT create a duplicate or a new
password — it links the existing account to the new service user and sends
a notification-only email (no password, since nothing changed about their
login). That's what the drawer's helper text under the Email field is
describing. This addendum is for the separate case of needing to hand out
*new* credentials.

## Something worth knowing

Medications only appear once `medication_communication` consent is granted
and active for that service user — if you want a family member to see
medications and they're seeing the locked message instead, check Consents
for that service user.

## Files touched

New: `app/Mail/AccountAccessMail.php`, `resources/views/emails/account-access.blade.php`

Changed: `app/Models/ServiceUser.php`, `app/Livewire/Family/FamilyMemberManagerComponent.php`,
`app/Livewire/Family/FamilyPortalComponent.php`, `app/Livewire/Family/FamilyServiceUserComponent.php`,
`app/Livewire/Staff/StaffManagerComponent.php`, `resources/views/livewire/family/family-portal.blade.php`,
`resources/views/livewire/family/family-service-user.blade.php`, `resources/views/livewire/family/family-member-manager.blade.php`,
`resources/views/layouts/family.blade.php`, `resources/views/livewire/staff/staff-manager.blade.php` (addendum)
