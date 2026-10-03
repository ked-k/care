# Batch 13 — Carer Bottom Nav, Quick Notes, Family Messaging & Brand Colour

## What changed

### 1. Bottom tab bar on the carer side

`layouts/carer.blade.php` now has a persistent 5-tab bottom bar — **Home,
Rota, Notes, Messages, More** — matching the family-portal bottom-nav
pattern you pointed to. It replaces the Batch 12 header hamburger as the
*primary* way to get around, but doesn't throw that dropdown away: it's
still there under **More**, now holding the less-frequent destinations
(Timesheets, Notifications, Profile, Log out) so the main bar stays at 5
items. The bar is hidden on the shift-visit screen, which already has its
own bottom tab bar scoped to one shift (Overview/Tasks/Medications/Notes)
— two stacked bottom bars would collide.

Both "Messages" and "More" show a small dot badge when there's something
unread (a family reply, or an unread notification), computed directly in
the layout since it's shared by several unrelated Livewire pages.

The carer home screen's quick-link grid also gained tiles for the two new
destinations (Notes, Messages) alongside the existing ones.

### 2. Quick notes on an assigned service user

New **Notes** tab — `App\Livewire\CareTimeline\CarerQuickNoteComponent`
(`/notes`, `notes.quick`). A carer-only, two-step screen: pick one of your
assigned service users (anyone you've had a Shift with), then a short
note form (text, optional photo, a "visible to family" toggle) — no
active shift check-in required, unlike the Notes tab inside the shift
visit screen.

It writes into the same `care_timeline_entries` table the staff
`TimelineIndexComponent` already uses, so a note added here shows up on
the normal care timeline (and to family, if marked visible) exactly like
a staff-added one. Deliberately a new, mobile-first component rather than
just routing carers into `TimelineIndexComponent` directly — that page
has no layout branch (it would've landed the carer back on the full
desktop admin shell, the exact inconsistency Batch 12 fixed) and isn't
scoped to "service users this carer is actually assigned to."

### 3. Messaging the next of kin

New **Messages** tab — `App\Livewire\Family\CarerMessagesComponent`
(`/messages`, `messages.carer`). Same picker-then-detail shape as Notes:
pick an assigned service user, then see/start conversations with their
family, same look as the staff and family-portal chat screens (bubbles,
own-message alignment, a composer at the bottom).

This reuses the exact `ChatSession`/`Message` data and notification
plumbing Batch 10 built — nothing new in the database. It sits alongside
the two existing participants in that feature, with its own access rule:

| Component | Who can use it | Scope |
|---|---|---|
| `FamilyChatInboxComponent` (staff) | `family.manage` permission, or Admin/Super Admin | Any service user in the agency |
| `FamilyChatComponent` (family portal) | A family member | Service users they're linked to |
| `CarerMessagesComponent` (new) | A plain Carer account | Service users they're actually assigned to (have a Shift with) |

Carers can also **start** a new conversation (the staff inbox page can
only reply) — a carer is often the one who needs to reach out first
("Mum had a lovely afternoon today").

### 4. Some colour — the real CareTrust brand

- **`resources/css/app.css`**: the `--color-primary-*` ramp (the site's
  one shipped default theme, separate from the per-browser theme
  customizer) is re-sampled from the actual logo's blue half
  (`#0174f1` at the 500 step) instead of the old generic Bootstrap blue
  (`#007bff`). `.brand-grad` is now the logo's real purple→blue gradient
  (`#7635fb` → `#0174f1`) instead of the old placeholder gradient.
- **`resources/views/components/brand-logo.blade.php`**: swapped the
  hand-drawn placeholder SVG for the real logo mark (the heart with two
  people, a cross and a house), cropped tight and matted to transparency
  so it drops cleanly onto the dark sidebar, the light topbar, and the
  carer header alike.
- **Favicon**: replaced the generic template placeholder
  (`img/radminly-mark.svg`) with the real mark too, so the browser tab
  icon matches everywhere else now.

These are the *shipped defaults* every user sees unless they've
personally changed something in the theme customizer (that's a separate,
per-browser `localStorage` override and is untouched).

## Files touched

New: `app/Livewire/CareTimeline/CarerQuickNoteComponent.php`,
`app/Livewire/Family/CarerMessagesComponent.php`,
`resources/views/livewire/care-timeline/carer-quick-note.blade.php`,
`resources/views/livewire/family/carer-messages.blade.php`,
`public/images/brand/caretrust-mark.png`,
`public/images/brand/caretrust-favicon.png`

Changed: `resources/views/layouts/carer.blade.php`,
`resources/views/livewire/dashboard/carer-home.blade.php`,
`resources/css/app.css`, `resources/views/components/brand-logo.blade.php`,
`resources/views/include/head.blade.php`, `routes/operational.php`

## Something worth knowing

- The carer's new Messages/Notes access is scoped by **current or past
  Shift assignment** — once a carer has ever been rota'd to a service
  user, they keep access to notes/messaging for that person even after
  the rota moves on. If you'd rather this tightened to "currently
  rota'd this week" that's a quick follow-up change, just flagging the
  choice made here.
- Still outstanding from Batch 12 (not touched this batch): route-level
  permission gating (pages are hidden from carers in the UI, but the
  underlying URLs aren't locked down by role yet) and carer payslip
  self-service.
- Vite will need a rebuild for the new `.brand-grad`/primary colour
  values to take effect (`npm run build`, or `npm run dev` while
  developing) — same as any other Tailwind/CSS change on this project.

## Next step on your end

Nothing in the database changed this batch — no migration to run. Just
rebuild front-end assets (`npm run build`) so the new colours and the
updated `app.css`/logo take effect, and take a look at the new bottom
nav / Notes / Messages tabs on a carer account.
