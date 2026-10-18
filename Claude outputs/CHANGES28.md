# Batch 28 — An actual "Install" button

Fair question — there wasn't one. Batches 24/27 made the site *installable*
(manifest + service worker), but the only way to trigger it was the
browser's own address-bar icon or a buried "Add to Home screen" menu item.
Fine for someone who knows to look for it, not something a family member
visiting a link for the first time would ever find.

## What's new

A slim navy banner across the top of the page — shows the app icon, "Install
CareAffinity for quicker access," an **Install** button, and a small ×
to dismiss. It only appears when there's actually something to install:

- **Android / desktop Chrome & Edge**: the banner shows once the browser
  itself decides the page qualifies (valid manifest, service worker
  registered, a bit of page engagement) and fires its `beforeinstallprompt`
  event. Tapping **Install** triggers the real native install dialog —
  same one as the address-bar icon, just reachable without hunting for it.
- **iOS Safari**: Apple give web pages no way to trigger "Add to Home
  Screen" programmatically — there's no button that can do it. So on iOS
  the banner shows instructions instead ("tap Share, then Add to Home
  Screen") with no button.
- **Already installed**: the banner never shows (checks
  `display-mode: standalone`, and `navigator.standalone` for iOS).
- **Dismissed**: tapping × hides it and won't show it again on that device
  for 14 days, so it's a one-time nudge, not a nag.

It's added to all four pages that carry the manifest: the sign-in screen
(so it's visible even before logging in, on your shared link), and the
Admin, Carer and Family layouts. One new file
(`resources/views/include/pwa-install-prompt.blade.php`), included once
near the top of `<body>` in each of the other four.

## Why it's positioned the way it is

It sits in normal page flow, right above the header — not fixed/floating
— specifically so it can never collide with Carer's fixed bottom tab bar,
and it needs no special-casing per layout. It scrolls away once you scroll
down the page; it isn't meant to follow you around.

## One real limitation — same as before, worth repeating

Chrome won't fire `beforeinstallprompt` the very first time someone visits
a brand-new site — it waits for a little engagement signal first (this is
Chrome's own heuristic, not something in this code). So right after you
deploy this, the very first visit to the live site might not show the
banner immediately; a second visit, or navigating around a bit first,
should trigger it. iOS shows its version immediately since it isn't gated
the same way.

## Files

New: `resources/views/include/pwa-install-prompt.blade.php`
Changed (one line each — the `@include`):
`resources/views/components/auth-split.blade.php`,
`resources/views/layouts/admin-layout.blade.php`,
`resources/views/layouts/carer.blade.php`,
`resources/views/layouts/family.blade.php`

Committed to your WAMP folder — same as last time, this needs deploying to
`care.affinityhealthcareltd.co.uk` before it's visible to anyone real.
