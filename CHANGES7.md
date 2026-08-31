# Batch 7 — Payslip redesigned as a printable document

You asked for the payslip to look like a standard printable document. It's been redesigned along those lines and is ready to print or save as a PDF from the browser.

## What changed

`resources/views/livewire/payroll/payslip-show.blade.php` is rebuilt around a single letterhead-style document (`#payslip-document`) instead of the previous two-card admin-panel layout:

- A dark "Confidential — Payroll Document" bar with the payroll run's reference number.
- A letterhead with the agency's name, address and contact details (pulled from `Agency`), and a logo if one's ever uploaded — there's no logo upload screen in the app today, so it falls back to a plain initial badge until one exists.
- Employee details (name, employee number, job title) and payslip details (pay period, issue date, payment method, and a masked bank account number when the carer has bank details on file) side by side.
- Earnings and deductions as two proper tables — regular pay, overtime pay, and any extra line items on one side; deductions on the other — each with its own subtotal.
- A gross/deductions/net summary strip and a highlighted "Net Payable" figure.
- Sign-off lines for "Authorized By" (the manager who approved the payroll run) and "Received By" (the employee), with a printed name and date rather than requiring a real signature — matching the footer disclaimer that a computer-generated payslip doesn't need a physical one.
- A footer disclaimer plus a generated-on timestamp.

All the existing functionality is still there and unchanged: the status badge, adding/removing line items, and approving a draft payslip — those controls just live in the toolbar above the document now rather than inside it, and disappear when you print (see below).

## Print / Download PDF

Two buttons above the document both open the browser's print dialog (`window.print()`) — this is the same pattern this app already uses for its Reports export button, so it's consistent with the rest of the codebase rather than a new mechanism. "Download PDF" is labeled as such because every major browser's print dialog offers "Save as PDF" as a destination, which gets you a real PDF file without adding a new dependency to the app.

Worth knowing: this app doesn't have a PDF-generation library installed (no `dompdf`/similar in `composer.json`). There's actually an existing `CarePlanReportService::generatePDF()` that references a `PDF::loadView(...)` facade as if one were installed — it isn't, so that method would fail if it were ever called (it doesn't currently appear to be called from anywhere). That's a pre-existing, unrelated issue I noticed while checking how PDFs are handled elsewhere in this app; flagging it rather than fixing it here, since it's outside what you asked for. If you'd like a genuine one-click PDF download (rather than going through the print dialog) for the payslip or anywhere else, that's a small, separate addition — happy to do it if you want it.

A print-only style block hides everything else on the page (sidebar, top bar, buttons) so what actually prints is just the payslip, regardless of which layout wraps this screen.

## Files changed

- `resources/views/livewire/payroll/payslip-show.blade.php` — rebuilt as described above.
- `app/Livewire/Payroll/PayslipShowComponent.php` — `payslip()` now eager-loads the agency, pay profile, and approving manager the new layout needs.
