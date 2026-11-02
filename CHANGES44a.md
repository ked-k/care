# Batch 44a: bank reconciliation, fixed assets and year-end close

## Run these, in order

```
php artisan migrate
php artisan optimize:clear
```

The migration adds 4 tables (`acc_reconciliations`, `acc_statement_lines`, `acc_fixed_assets`, `acc_depreciation_entries`) and one column, `reconciliation_id` on `acc_journal_lines`. It rolls back cleanly.

## Bank reconciliation

**Where:** Finance → Bank & cash → **Reconcile** on any bank, cash or mobile-money account.

1. **Start:** enter the statement date and its closing balance. You can also attach the bank's CSV.
   - The CSV reader handles `Date / Description / Amount` and `Date / Description / Paid in / Paid out` (also Money in/out, Credit/Debit).
   - It reads UK dates (31/01/2026) and ISO dates, and skips title and balance rows.
2. **Automatic matching:** each statement line is matched to the entry in the books with the same amount, dated within 7 days, taking the closest date first.
3. **Tick off the rest:** tick the entries that appear on the statement (or **Tick all**). The four totals at the top update as you go:
   - the statement balance (you can edit it)
   - cleared in the books
   - ticked in / out
   - **difference**
4. **Lines nobody entered** (bank charges, interest, a direct debit): pick what they were for and press **Post it**. A money-in or money-out entry is posted and matched in one step. Or press **Ignore**.
5. **Finish reconciliation** unlocks when the difference is **zero**. Those entries are then locked as reconciled.
   - Ticks are saved as you go, so you can leave and come back.
   - **Re-open** undoes the most recent reconciliation; **Delete** throws away one that's in progress.
   - Bank & cash cards now show "Reconciled to …", "In progress" or "Never reconciled".

## Fixed assets

**Where:** Finance → **Fixed assets**.

**Adding an asset**
- **Category:**
  - Land and buildings: 2% straight line
  - Motor vehicles: 25% reducing balance
  - Furniture and fittings: 12.5% reducing balance
  - Computers and IT: 25% straight line
  - Equipment and machinery: 12.5% reducing balance
  - Other: 20% straight line

  The method and rate can be changed per asset, and "not depreciated" (for land) is also an option. Each category posts to its own cost and accumulated-depreciation accounts, which are created if missing.
- **How the purchase is in the books:**
  - *already entered* (on a supplier bill or money-out posted to the asset account)
  - *paid from a bank account* (posted now)
  - *owned before these books started* (an opening balance, with any depreciation already charged)

**Run depreciation** charges every month up to a chosen month end. It shows a preview per asset first and posts one journal, with one entry per asset per month.
- **Straight line:** cost × rate ÷ 12 each month.
- **Reducing balance:** what's left × rate ÷ 12.
- It never goes below the "worth at the end" value.
- **Undo** reverses the last run.

**Asset page:** months charged, totals by financial year, a 5-year projection, edit details, **Depreciate to last month**, **Sell or scrap**.
- **Disposal:** depreciation is charged to the month before, then cost and depreciation are removed and any profit or loss posts to "Gain/Loss on disposal of assets".
- **Undo disposal** puts the asset back on the register.

**Reports → Property, plant and equipment:** the PPE note in the audited-accounts layout. Per category:
- cost at the start, additions, disposals, cost at the end
- depreciation at the start, charge, on disposals, at the end
- net book value at both ends

It has print and CSV, and it agrees with the ledger.

## Year-end close

**Where:** Finance → Financial years → **Close year**. This is a new page in four steps.

1. **Checks.** *Required* ones block the close; *recommended* ones warn.
   - **Required:**
     - the trial balance balances
     - the year has ended
     - appropriations are no more than the surplus, and each goes to an equity account
   - **Recommended:**
     - the year before is closed
     - no draft invoices or bills dated in the year
     - nothing waiting to post
     - depreciation charged to the year end
     - every bank account reconciled to the year end
2. **The result:** income, expenses and the surplus or profit.
3. **Appropriations** worked out from Settings: % of the surplus, % of turnover, or a fixed amount. Nothing is set aside from a loss.
4. **Close.** After you confirm, it posts two "year end" journals on the last day of the year:
   - **closing:** every income and expense account is cleared to retained earnings / accumulated surplus
   - **appropriations:** retained earnings to each reserve account

   Then it marks the year closed and locks the books to that date.

- **The statements stay right:**
  - The year's income statement still shows the full figures, because year-end journals are left out of it.
  - The balance sheet at the year end shows the result in retained earnings and the reserves, instead of a "profit for the year" line.
- **Re-open** (with a reason) unlocks the year and reverses both journals. Closing again later posts fresh ones.

## Files

**New**
- `database/migrations/2026_11_25_100000_add_reconciliation_and_fixed_assets.php`
- **Models:** `app/Models/Accounting/Reconciliation.php`, `StatementLine.php`, `FixedAsset.php`, `DepreciationEntry.php`
- **Services:** `app/Services/Accounting/Reconciler.php`, `FixedAssets.php`, `YearEnd.php`
- **Livewire:** `app/Livewire/Accounting/ReconcileComponent.php`, `AssetIndexComponent.php`, `AssetShowComponent.php`, `YearEndComponent.php`
- **Views:** `resources/views/livewire/accounting/reconcile.blade.php`, `assets.blade.php`, `asset-show.blade.php`, `year-end.blade.php`, plus `resources/views/accounting/reports/fixed-assets.blade.php`

**Changed** (all Batch 43 files)
- **Models:**
  - `JournalLine`: `reconciliation_id`
  - `Journal`: new types and source links
- **Services:**
  - `DefaultChart`: asset accounts 1815, 1830/1835, 1850/1855 and 1860/1865, plus "Gain on disposal" 4460 and "Loss on disposal" 5815
  - `Ledger`: reversing a year-end journal stays out of the P&L; new accounts are no longer mistaken for archived ones
  - `Reports`: the balance sheet handles closed years
  - `ReportBuilder`: the PPE report
- **Livewire:**
  - `AccountsComponent`: `attemptOk()`
  - `YearsComponent`: re-opening reverses the year-end journals
  - `BankingComponent`: reconciliation status
- **Routes and menu:** `routes/accounts.php`, `config/menu.php` ("Fixed assets" in the Finance sidebar)
- **Views:** `banking.blade.php`, `years.blade.php`, `report.blade.php`

## How it was tested

Tested on the test copy (SQLite).

**Services: 28 checks, all passing.**
- **Depreciation:**
  - Laptops at 25% straight line came to £450 for 9 months.
  - A van brought in at £12,000 less £4,000 depreciation came to £1,786.03 for 12 months at 25% reducing balance.
  - Running it again added nothing.
- **Reconciliation:**
  - The CSV with Money in / Money out columns was read correctly.
  - 1 line matched automatically and 2 were posted from the statement, bringing the difference to zero.
  - The reconciliation was locked once complete.
- **Year end** (co-operative appropriations: 10% of surplus plus 1% of turnover):
  - The surplus was £11,763.97 and £1,376.40 was appropriated.
  - The income statement was unchanged after closing.
  - The balance sheet balanced, with reserves showing the appropriation.
  - The closed year was locked.
  - Re-opening restored the figures, and closing again worked.
- **Disposal:**
  - Charged through the month before, and the loss was posted.
  - Undo and dispose-again both worked.
- **Overall:** the PPE schedule's book value equals the ledger, and the trial balance balances.

**Browser test (Playwright): 17 checks, all passing, no JavaScript errors.**
- **Reconciliation:** 3 of 4 statement lines matched; a £4.50 bank charge was posted from the statement; difference zero; finished.
- **Fixed assets:** asset added; a 6-month depreciation preview of £300 posted; disposal posted; the PPE note shows it.
- **Year end:** an earlier year was added, its checks shown, closed, then re-opened with a reason; the balance sheet still balances.

The Batch 43 browser test was run again: 29 of 29 passing.

## Next (44b and 44c)

- **44b:** cash flow statement, statement of changes in equity, notes to the accounts, and the audit pack (everything in one printable PDF)
- **44c:** budgets with budget-vs-actual, VAT return, credit notes
