# Batch 26 — Rebrand: CareTrust → CareAffinity

## One manual step required — nothing else works until you do this

I can't write to `.env` from here (blocked for secrets, enforced on the
remote-file tool itself, not something I can bypass). Everything else is
already committed, but the app will keep showing **"CareTrust"** until you
make this one change yourself:

```
APP_NAME=CareTrust
```
→
```
APP_NAME=CareAffinity
```

Then clear the config cache if you've ever run `config:cache`:
```
php artisan config:clear
```

## Why "CareAffinity" and not "Affinity CareLink"

You asked for "Affinity CareLink" first. Checking it, "CareLink" turned out
to be already in heavy use in exactly this space — Civica's "CareLink
Suite" is a care/client management platform (the closest kind of
competitor-name collision there is), on top of Medtronic's CareLink and
several registered trademarks. You confirmed "CareAffinity" instead, which
came back clear in a quick search — no live product or registration using
that exact name turned up.

## What changed

- **`.env`** — `APP_NAME` (**you do this one**, see above).
- **`config/app.php`** — the fallback default (used only if `APP_NAME` is
  ever unset) changed from the old "Radminly" template placeholder to
  `CareAffinity`.
- **`resources/views/components/brand-logo.blade.php`** — the wordmark next
  to the logo mark now reads "Care**Affinity**" instead of "Care**Trust**"
  (same two-tone styling, same navy accent colour). This is the one place
  that had the name hardcoded into markup rather than reading
  `config('app.name')`.
- **Three PWA manifests** (`public/manifest.json`, `manifest-carer.json`,
  `manifest-family.json`) — `name`/`short_name`/`description` updated.
  Anyone who already installed the app on their phone will keep seeing the
  old name until they uninstall and reinstall — manifests aren't
  re-fetched for an existing install.
- **`resources/views/emails/account-access.blade.php`** — the four places
  this had "CareTrust" typed directly into the email copy now read from
  `config('app.name')` instead, so a future rename won't require touching
  this file again.
- **`resources/views/include/head.blade.php`** — just a code comment
  updated for anyone reading the source later; nothing user-visible here.

Everywhere else — page titles, the sidebar, the footer, login screen,
payslips, invoices — already pulled from `config('app.name')` rather than
having "CareTrust" typed in literally, so those update automatically once
you make the `.env` change above.

## What deliberately did NOT change

- **The logo mark and navy color (#1e3a5f)** — kept exactly as-is, per what
  you confirmed.
- **Image filenames** (`caretrust-mark.png`, `caretrust-favicon.png`, the
  `pwa/` icon set) — left with their old filenames. Nothing on screen
  shows these filenames to a user, so renaming them would only have added
  risk (every reference would need re-pointing) for zero visible benefit.
- **Past project documentation and the existing user guide** — left as
  historical record under the old name, per what you confirmed. Only the
  live app changed.
- **Agency/tenant names** in the database (e.g. "Healthcare Services Ltd")
  — those are your customers' own names, unrelated to the platform's own
  brand name, and weren't touched.

## Worth checking on your end

Once you've updated `.env` and cleared config cache: load the dashboard,
login page, and the carer/family portals, and confirm "CareAffinity" shows
everywhere you'd expect (browser tab title, sidebar/header wordmark,
footer). Also worth re-triggering an account-access email (adding a family
member, or whatever flow sends it) to see the updated copy once.
