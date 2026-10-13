# Batch 21 — Invoicing

Billing whoever pays for a service user's care — distinct from payroll,
which pays carers. Scoped with you up front (per the roadmap): per
service user, billing can be per-completed-visit or a flat recurring
package; the payer can be self-funded, local-authority-funded, or a
split between the two; the billing rate is independent of what the
carer is paid; and invoices are persisted records with a draft → issued
→ paid status, mirroring how Payroll/Payslip already works.

## What was built

**Billing setup, on the service user's own record** — the Service Users
edit drawer gained a "Billing" section: billing basis (per visit, with
an hourly rate separate from the carer's pay rate, or a flat weekly/
monthly package amount) and who pays (self-funded client/family,
local authority, or split). A split service user stores a fixed local
authority contribution per period; the client is billed whatever's left
of the period's total.

**Invoicing** (`/invoicing`) — a new sidebar item, alongside Payroll and
Timesheets in a group renamed **Finance** (was "Timesheets & Payroll").
Works exactly like Payroll's own flow:

1. Create an invoice run for a billing period (weekly or monthly).
2. **Generate invoices** — for every service user with billing configured,
   builds one invoice per payer from that period's completed visits
   (`Shift::hoursWorked()`, preferring actual check-in/out times over
   the scheduled window — same preference payroll gives attendance data)
   or the flat package charge. A split service user gets two invoices —
   one to the local authority, one to the client — both listing the
   full visit/package detail so either payer can see what was delivered,
   with an adjustment line netting each one down to its own contracted
   share rather than dividing every visit proportionally.
3. **Issue the run** once the invoices look right, then **mark paid**
   per invoice (different payers pay at different times) or all at once.

**Invoice document** (`/invoices/{id}`) — styled the same way Batch 7
redesigned the payslip: a letterhead-style printable document (agency
details, billed-to block, itemized lines, subtotal/tax/total, an
"Amount Due" callout) with Print/Download PDF buttons using the
browser's own print dialog — no new PDF dependency, consistent with how
payslips already work. A manager can add a one-off adjustment line
(e.g. a discount or a manual correction) before issuing.

## Known scope cuts

- **No VAT/tax calculation.** The `tax_amount` field exists on every
  invoice but is always 0 — UK domiciliary care is typically VAT-exempt,
  but this wasn't confirmed for your specific setup, so nothing is
  computed automatically. Add a manual adjustment line if a particular
  invoice needs one.
- **No automatic reminders** for an issued-but-unpaid invoice, or any
  overdue-tracking — same manual-log approach Batch 2 took for Subject
  Access Requests and breach reports.
- **Local authority contact has no login or portal** — it's a billing
  name/reference on the invoice, not an account. If councils need to
  log in and see their own invoices, that's new ground (closer to a
  second family-portal-style login than an invoicing feature).
- A service user with billing not yet configured is silently skipped on
  a run (same "skip rather than guess" convention Payroll uses for a
  carer with no pay profile) — the Service Users list now shows a small
  grey $ icon next to anyone this applies to.

## One thing you need to run

```
php artisan migrate
```

Four new migrations: billing fields on `service_users`, and the new
`invoice_runs`, `invoices`, `invoice_lines` tables.

```
php artisan db:seed --class=Database\\Seeders\\InvoicingSeeder
```

(or just `php artisan db:seed`, since `DatabaseSeeder` now calls it too).
Creates the `manage_invoicing` permission and grants it to the `Admin`
role — `Super Admin` already gets everything via the existing
`Gate::before` rule. Nobody can open `/invoicing` without this permission
(or being Super Admin), including an existing Admin account, until the
seeder runs.

## Files touched

**New migrations:** `2026_10_11_100000_add_billing_info_to_service_users_table.php`,
`2026_10_11_100001_create_invoice_runs_table.php`,
`2026_10_11_100002_create_invoices_table.php`,
`2026_10_11_100003_create_invoice_lines_table.php`.

**New models:** `InvoiceRun`, `Invoice`, `InvoiceLine`.

**New seeder:** `InvoicingSeeder`.

**New Livewire components:** `Invoicing\InvoiceRunIndexComponent`,
`Invoicing\InvoiceRunShowComponent`, `Invoicing\InvoiceShowComponent`.

**New views:** `livewire/invoicing/invoice-run-index.blade.php`,
`livewire/invoicing/invoice-run-show.blade.php`,
`livewire/invoicing/invoice-show.blade.php`.

**New routes:** `routes/invoicing.php`, included from `routes/web.php`.

**Modified models:** `ServiceUser` (billing/payer fields, `invoices()`
relation, `hasBillingConfigured()`), `Shift` (new `hoursWorked()` helper),
`User` (new `canManageInvoicing()`), `Agency` (new `invoiceRuns()`
relation).

**Modified Livewire component:** `ServiceUser\ServiceUserManagerComponent`
(billing form fields).

**Modified view:** `livewire/service-user/service-user-manager.blade.php`
(Billing section in the edit drawer; a grey $ icon for anyone without
billing configured).

**Modified seeder:** `DatabaseSeeder` (calls `InvoicingSeeder`).

**Config:** `config/menu.php` — "Timesheets & Payroll" renamed **Finance**,
with a new Invoicing item (gated on `manage_invoicing`).

## Next up

Batch 20 (audit depth) is next on the roadmap. You asked about using
`spatie/laravel-activitylog` for that and chose the full-migration option
— worth noting here since it's a bigger lift than the roadmap originally
scoped: replacing the custom `Audit`/`AuditLogger` system entirely,
wiring `LogsActivity` into every model it currently covers (including
the new `Invoice`/`InvoiceRun` from this batch), and repointing the
existing Audit Log page at the dormant `activity_log` table that's been
sitting in your schema since before this engagement started.
