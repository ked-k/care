# Batch 24 — Installable PWA (no frontend rewrite)

You asked to make CareTrust installable on phones without rewriting it in
React/Vue — this adds the three things a server-rendered Blade/Livewire app
actually needs for that: a web app manifest per portal, a service worker,
and a proper icon set. Nothing about how pages are built or rendered
changed.

Scope, as you confirmed: **all three portals** (Admin/Manager, Carer,
Family) become installable, at **"fast-load caching"** level — static
assets (CSS/JS/images/fonts) get cached for quicker repeat visits, but no
page or Livewire data is ever cached, so there's no way this can show
stale/offline data. Full offline viewing stays a separate, not-yet-built
item (same one already sitting on the roadmap from the original gap
assessment).

## What was added

- **Three manifests** (`public/manifest.json`, `manifest-carer.json`,
  `manifest-family.json`) — one per portal, each with its own name, start
  page, and app identity (`id`), so a manager, a carer, and a family member
  installing on the same phone get three distinct icons, not one that gets
  overwritten. The family one is scoped to `/family/` (it already lives
  under that prefix); the other two use `/` scope since neither the
  admin nor the carer routes share one common prefix.
- **Icon set** generated from your existing CareTrust mark
  (`public/images/brand/caretrust-mark.png`) at `public/images/brand/pwa/`:
  192px/512px standard icons (transparent) and maskable versions (solid
  navy `#1e3a5f` background, logo kept inside the safe zone so Android's
  circular/squircle crop doesn't clip it), plus a 180px Apple touch icon.
- **`public/sw.js`** — a minimal service worker. It only ever intercepts
  same-origin GET requests for genuinely static paths (`/build/*` — your
  Vite output, `/images/*`, `/img/*`, `/plugins/*`, the manifests/icons
  themselves), cache-first with a background refresh. It explicitly skips
  anything else: every HTML page, every Livewire request (checked both by
  method and by the `X-Livewire` header), every POST. That's the whole
  safety story for "this can never show stale care/medication/safeguarding
  data."
- **`resources/views/include/pwa-register.blade.php`** — registers the
  service worker; included once from each of the three layouts.
- Each layout (`admin-layout`, `carer`, `family`) now links its own
  manifest and Apple touch icon, with matching `theme-color`/
  `apple-mobile-web-app-*` meta tags (the carer and family layouts already
  had most of these from Batches 9/11 — this just makes them actually
  *installable* instead of "feels like an app once you manually add it to
  the home screen").

## One real limitation, worth knowing up front

A service worker only registers in what browsers call a "secure context" —
HTTPS, or `localhost`/`127.0.0.1`. Your local WAMP setup at
`http://127.0.0.1:8000` qualifies, so this works as-is for testing. **Once
this is deployed anywhere real (a LAN IP, a staging domain, production),
it needs to be served over HTTPS** for the install prompt and caching to
work at all — plain HTTP there will silently just not register the service
worker (the registration call is wrapped so that failure never breaks the
page, it just means no install prompt / no caching on that connection).

## What I could verify, and what I couldn't this time

Code-level: all three manifests are valid JSON referencing real, now-
committed icon files; the service worker's path-matching and Livewire
guards were written and re-checked by hand; the layout edits are additive
(new `<link>`/`<meta>` tags and one `@include`, no new PHP logic) so the
risk of a Blade syntax error is low.

I wasn't able to do a live browser pass this time — the browser pane
couldn't reach `http://127.0.0.1:8000` at all (even `/login` failed to
load), which looks like the local server wasn't reachable from here just
now rather than anything wrong with these changes. **Worth a quick check
on your end:** open the dashboard, Carer portal and Family portal in
Chrome, confirm the install icon shows up in the address bar (or "Add to
Home Screen" on a phone), and check DevTools → Application → Manifest/
Service Workers shows each one registered with no errors. No migration or
Composer step needed for any of this — it's pure static files plus layout
changes.

## Files touched

New:
- `public/manifest.json`, `public/manifest-carer.json`, `public/manifest-family.json`
- `public/sw.js`
- `public/images/brand/pwa/icon-192.png`, `icon-512.png`,
  `icon-maskable-192.png`, `icon-maskable-512.png`, `apple-touch-icon.png`
- `resources/views/include/pwa-register.blade.php`

Changed:
- `resources/views/layouts/admin-layout.blade.php`
- `resources/views/layouts/carer.blade.php`
- `resources/views/layouts/family.blade.php`
