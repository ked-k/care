# Batch 37 — Service Users: Full Profile, Directions, and a cleaner list

## What changed

**Service Users list, modernized.**
The old table crammed 8 inline text links (Medications/Consents/Family/Messages/Assessments/Timeline/Calls/Edit, plus Deactivate) into one cell. Now each row has: an avatar with initials, a cleaner "Full Profile" button for the one thing most people reaching for a row actually want, and everything else moved into a tidy "⋮" actions menu. Added a Location column showing a "Directions" link (or "Not located" if the address hasn't been geocoded yet).

**New: Full Profile page.**
`/service-users/{id}/profile` — one consolidated, read-only page per service user: basic details, key care information (allergies/conditions/communication needs/preferences), the active care plan(s) and their tasks, current medications, consents, next of kin, care coordinator, and linked family members. Reachable from the list's new "Full Profile" button. Gated the same way every other service-user page in this app already is (`canAccessServiceUser()`) — a manager/admin sees anyone in their agency, a plain carer only someone they actually have a shift with.

Left out deliberately: billing/payer detail and safeguarding reports stay on their own pages — this is the clinical/care picture, not a dump of everything in the database.

**Download care plan PDF — via the browser's print dialog.**
Same pattern this app already uses for payslips and invoices (Batch 7/21): a "Print / Download PDF" button opens the print dialog, where "Save as PDF" gives a real file. No new dependency. It's on the Full Profile page (which includes the care plan) and now on the family portal's new tab too — printing it gives a clean one-page document with the page chrome stripped out, not the live admin editing screen.

**Click for directions — carers, staff, and family.**
Added `ServiceUser::directionsUrl()` — a one-tap Google Maps directions link using the person's geocoded/pinned coordinates from Batch 35's geofencing work (Google's own maps.google.com link opens in whichever app is the phone's default, Apple Maps included on iPhone — no separate per-platform link needed). Wired in:
- The Service Users list (Location column) and the new Full Profile page.
- The carer's shift visit screen (`ShiftVisitComponent`), right in the Visit card.
- The carer's standalone client-profile page (`ClientProfileComponent`).
- The family portal's new Full Profile tab.

Quietly absent (no broken link, no error) wherever a service user has no address/location on file yet — same rule Batch 35 already established for the geofence itself.

**Family Portal gets a Full Profile tab too.**
A new 7th tab, "Full Profile" — basic details, key care info, next of kin, the active care plan, directions, and its own Print/Download PDF button. Reached from the top tab strip on tablet/desktop, and from a new card on the Overview tab on phones (kept off the bottom tab bar, which was already a full 6 columns — squeezing in a 7th would have hurt the exact mobile UX this batch was trying to improve elsewhere).

What family does **not** get here that staff do: billing/payer information, the care coordinator assignment, and the Consents list — none of that was visible to family anywhere in this portal before, and this batch doesn't change that.

## Files touched

- `app/Models/ServiceUser.php` — added `directionsUrl()`.
- `app/Livewire/ServiceUser/ServiceUserProfileComponent.php` (new) + `resources/views/livewire/service-user/service-user-profile.blade.php` (new).
- `resources/views/livewire/service-user/service-user-manager.blade.php` — list redesign.
- `routes/static-data.php` — added `service-users.profile`.
- `app/Livewire/Family/FamilyServiceUserComponent.php` + `resources/views/livewire/family/family-service-user.blade.php` — new Profile tab.
- `resources/views/livewire/rota/shift-visit.blade.php` — directions link in the Visit card.
- `resources/views/livewire/care-timeline/client-profile.blade.php` — directions icon button in the header.

No migration this time — everything needed (latitude/longitude/location_source) already exists from Batch 35.

## Known limitations

- The Full Profile page's "Download PDF" is the browser print dialog, not a one-click real PDF file — consistent with how payslips and invoices already work in this app, but if you'd rather have an actual one-click `.pdf` download (useful for a family member who doesn't know the "Save as PDF" trick), that's a separate, small addition (adding the `dompdf` package) — happy to do it if you change your mind.
- "Not located" in the list just means no address has been geocoded yet (or the geocode failed) — same silent-by-design behaviour as the geofenced check-in. Re-saving the service user's address (or pinning a location manually) will pick it up.
