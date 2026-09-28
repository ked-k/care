# Batch 8 — CareTrust User Guide (Documentation)

## What this batch is

A new end-user documentation deliverable: `CareTrust_User_Guide.docx`, added at the
repo root alongside `CareTrust_System_Documentation_Revised.docx`. No application
code was changed in this batch.

## Why this guide, and not something else

The project already had two documentation artefacts, and neither is an end-user
guide:

- `CareTrust_System_Documentation_Revised.docx` — architecture-level (data flow
  diagrams, entities, flowcharts). Written for developers, not day-to-day users.
- `CareTrust_Current_State_Overview.md` — references an older "CareTrust User
  Manual" that predates Batches 1–7 and no longer matches the live system.

So the actual gap was a **current, role-based guide to what a person actually
sees and can do in the app today** — after the Timesheet/Payroll fix (Batch 6)
and the payslip redesign (Batch 7). That's what this guide is.

## What's in it

Twenty short sections, built directly from `config/menu.php` (the real sidebar
structure) and the Livewire components behind each screen, covering:

1. Welcome & the five roles (Super Admin, Admin, Manager, Carer, Family)
2. Getting started — logging in, navigation, the Dashboard, notifications
3. Service Users
4. Care Plans & Tasks (including how the Care Plans screen and the Care
   Management plan editor relate — they edit the same underlying plan)
5. Assessments
6. Consents
7. Family Access
8. Medication Management
9. Rota Management
10. Timesheets
11. Payroll (including the new printable payslip from Batch 7)
12. Safeguarding
13. Compliance & Governance (Policies, Training, Compliance Dashboard, Audit
    Log, Subject Access Requests, Data Breaches)
14. Messaging & Notifications
15. Staff & Agency Administration
16. Quick Reference for Carers
17. Quick Reference for Family Members
18. Roles & Permissions at a Glance (summary table)
19. Status Reference (the lifecycle of a task/timesheet/payroll run/safeguarding
    report/consent/SAR, at a glance)
20. Appendix: Trying CareTrust with Sample Data (points to `DEMO_DATA_GUIDE.md`)

## A gap noticed while writing it (not fixed — flagged for you)

`config/menu.php` gates the "Audit Log" sidebar item behind a `compliance.manage`
permission:

```php
['label' => 'Audit Log', ..., 'can' => 'compliance.manage'],
```

That permission has never actually been seeded by any role/permission seeder
across Batches 1–7 (only `manage_role, manage_permission, manage_user,
manage_rota, manage_tasks` exist). In practice this means **only Super Admin**
(via the `Gate::before` bypass) can currently see the Audit Log menu item —
Admins and Managers can't, even though the feature itself works fine. It's
noted as a `Note` callout inside section 13 of the guide, and flagged here in
case you want it fixed (adding `compliance.manage` to the Admin/Manager role
seeding) as a follow-up batch.

## Delivery

- `CareTrust_User_Guide.docx` — sent to you and written to the repo root.
- This changelog — written to the repo root and to the project as
  `claude/Batch8_User_Guide_Documentation.md`.

Note: the `.docx` itself could not be uploaded as a binary project file in this
session (the project write API rejected it), so only this markdown changelog
is in the project — the guide itself lives in the file card above and in your
repo.
