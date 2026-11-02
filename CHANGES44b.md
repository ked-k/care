# Batch 44b: cash flow, changes in equity, notes and the audit pack

## Run these

```
php artisan migrate
php artisan optimize:clear
```

The migration adds one table, `acc_report_notes`, which holds the written notes for each financial year.

## New reports (Finance → Reports)

**Statement of cash flows** (indirect method), with last year alongside.

- **Operating activities:**
  - profit / surplus
  - depreciation added back
  - profit or loss on selling assets
  - changes in inventory, receivables, payables, VAT and payroll liabilities
- **Investing activities:** asset purchases and sale proceeds.
- **Financing activities:** share capital, loans, drawings and dividends.
- Ends with the net change, cash at the start and cash at the end.
- Cash and bank balances brought in as opening balances during the year show as their own line, so the opening figure isn't confused with trading cash.
- A **✓ check** confirms it agrees with the bank and cash accounts.

**Statement of changes in equity.**

- One column per equity account (share capital, each reserve, retained earnings / accumulated surplus) plus a total.
- **Rows:** balance at the start, opening balances brought in, the year's result, transfers to reserves (the year-end appropriations), other movements, and balance at the end.
- A **✓ check** confirms it agrees with the balance sheet.

Both work with the year picker, dates, print / PDF and CSV, like the other reports.

## Notes to the accounts (Finance → Reports → Notes to the accounts)

The first time a year is opened it gets a standard set of written notes. The wording depends on the organisation type (company, co-operative or charity):

1. General information
2. Basis of preparation: the reporting framework, currency and going concern
3. Revenue
4. Property, plant and equipment, listing the depreciation rates actually used on the fixed assets register
5. Inventory (actual batch cost, earliest expiry first)
6. Receivables and payables
7. Taxation
8. Reserves, or reserves and appropriations for a co-operative
9. Events after the reporting date

Every note can be edited, reordered, deleted or added to. A new year starts from the previous year's wording. **Reset to standard wording** is there if you want to start again.

## Audit pack (Financial years → Audit pack, or the button on the Notes page)

One printable document for the year: print, or save as PDF from the browser (A4, one section per page).

- **Cover:** name, registration number, "Annual report and financial statements for the year ended …", and a contents list.
- **Approval page:** statement of responsibilities, worded for directors, committee members or trustees, with signature and date lines.
- **The four statements**, each with last year alongside:
  - profit and loss / income statement
  - balance sheet / statement of financial position
  - changes in equity
  - cash flows
- **Notes:** the written notes, then numbered notes worked out from the books:
  - income by account
  - trade and other receivables
  - cash and cash equivalents
  - trade and other payables
  - share capital and reserves
  - the property, plant and equipment schedule
- **Appendix:** the trial balance at the year end.
- **Optional:** "Include every account's ledger" adds the full general ledger for the year.
- A year that isn't closed yet is stamped **DRAFT** on screen. The stamp doesn't print.

## Files

**New**
- **Migration:** `database/migrations/2026_11_26_100000_create_acc_report_notes.php`
- **Model:** `app/Models/Accounting/ReportNote.php`
- **Services:** `app/Services/Accounting/Statements.php` (cash flow and changes in equity), `app/Services/Accounting/Notes.php`
- **Livewire:** `app/Livewire/Accounting/NotesComponent.php`, `resources/views/livewire/accounting/notes.blade.php`
- **Printable views:** `resources/views/accounting/audit-pack.blade.php`, `resources/views/accounting/reports/cash-flow.blade.php`, `resources/views/accounting/reports/changes-in-equity.blade.php`

**Changed**
- `app/Services/Accounting/ReportBuilder.php`: the two new reports
- `app/Http/Controllers/Accounting/AccountsPrintController.php`: `auditPack()`
- `routes/accounts.php`: `/accounts/notes` and `/accounts/audit-pack/{year}`
- **Livewire views:** `reports.blade.php` (links to the notes and the audit pack), `years.blade.php` ("Audit pack" for each year), `report.blade.php` (filters for the new reports)
- **Report partials:** `reports/fixed-assets.blade.php` and `reports/ledger.blade.php`, which can now be shown inside the audit pack without their own heading

## How it was tested

**Services**, on the same two-year test as 44a: purchases, depreciation, a disposal undone and redone, reconciliation, and a closed year with appropriations.

- **Cash flow:** agrees with the bank in both years.
- **Purchases and proceeds:** a disposal that was undone and redone shows once, as £1,500 of proceeds. It is not counted as a new purchase.
- **Changes in equity:** agrees with the balance sheet in both years, including the appropriation (£1,376.40 from retained earnings to reserves) and the opening balances.
- **Notes:** 9 standard notes created, and the 6 figure notes built.

**Browser test (Playwright): 10 checks, all passing, no JavaScript errors.**

- **Reports:** both show their ✓ checks, and the CSV downloads.
- **Notes:** a note edited, a new "Related parties" note added, and both still there after reloading the page.
- **Audit pack:**
  - It contains every section and is marked draft.
  - The ledger appendix appears when requested.
  - It prints to an A4 PDF.

## Next (44c)

Budgets with budget-vs-actual, VAT return, credit notes.
