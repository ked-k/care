# Batch 27 — One shared installable app + install link

You're right that separate apps per role don't make sense — login already
sends everyone to their correct portal, so there's no reason for Admin,
Carer and Family to each install a different "app." Consolidated to one.

## What changed

- **`public/manifest.json`** is now the single app identity for everyone:
  `id`/`start_url`/`scope` all set to `/` (the bare site root), name
  "CareAffinity." Previously this file was admin-specific
  (`start_url: /dashboard`) and carer/family each had their own separate
  manifest with their own identity — that's what caused three different
  "apps" to show up if more than one role installed on the same phone.
- **Carer and Family layouts now link the same `manifest.json`** instead
  of their own `manifest-carer.json` / `manifest-family.json`. Those two
  files are still sitting in `public/` but nothing references them anymore
  — I can't delete files on your machine from here, so they're just inert
  leftovers now (same pattern as the old unused template modules from
  Batch 4).
- **The sign-in page itself is now installable.** Previously the manifest
  and service worker only loaded on pages you reach after logging in, so
  there was nothing to "install" from the link you'd actually share. Now
  `https://care.affinityhealthcareltd.co.uk/` itself carries the manifest
  — that's your install link, full stop. Share that one URL with anyone,
  any role.

## Why `/` as the start_url is safe for every role

Checked the actual redirect chain before relying on it:

- Not logged in → `/` shows the sign-in page. Normal.
- Logged in as Admin/Manager/Carer → Laravel's "already authenticated"
  guard sends you straight to `/dashboard`.
- Logged in as Family → same guard first tries `/dashboard`, but the
  `not-family` middleware on that route catches it and redirects to
  `/family/` instead. Two hops, but it lands correctly — no dead end, no
  error.

So one link, one installed icon, and everyone who opens it — logged in or
not, whichever role — ends up exactly where they should.

## Verified live

Checked this against `care.affinityhealthcareltd.co.uk` directly (not just
locally) — confirmed the site loads over HTTPS, found and fixed the
`manifest-family.json` scope bug from earlier today in the same pass
(separate note — that fix needs deploying to production same as this one;
see below). Wasn't able to click through an actual install on your phone
myself, obviously — that's the one thing you'll need to check once this is
deployed.

## Still true from the last message: this needs to reach production

Everything above is committed to your local WAMP folder
(`C:\wamp64\www\ked\care`), not yet on `care.affinityhealthcareltd.co.uk`
— that's a separate server from what I can see, so however you normally
push changes there (git, FTP, your host's file manager) is still the next
step, for this batch and the family-manifest scope fix from earlier.

## Worth checking once deployed

Open `https://care.affinityhealthcareltd.co.uk/` on the Android phone
(logged out, or in a private tab) and look for Chrome's install prompt or
the "Add to Home screen" option in the ⋮ menu. Then confirm after
installing and opening it: a Family login lands on the family portal, a
Carer login lands on their dashboard — same installed app, different
destination, as designed.
