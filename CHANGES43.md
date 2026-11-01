# Batch 43: Finance & Accounts (the ledger core)

There is a new **Finance & Accounts** workspace in the top-bar switcher. It keeps proper double-entry books for each agency, in that agency's own currency and financial year.

You don't have to type anything twice. Care invoicing, payroll, shop sales and shop deliveries all keep working where they are, and they post into the books by themselves.

The old ERP's customers, invoices, bank account and chart of accounts can be brought in with one command.

## Run these, in order

```
php artisan migrate
php artisan db:seed --class=AccountingSeeder
php artisan accounts:import-erpgo storage/app/affiwspg_erp.sql --dry-run
php artisan accounts:import-erpgo storage/app/affiwspg_erp.sql
php artisan accounts:sync --from=2026-04-01
```

- **The migration** adds 11 tables, all starting with `acc_`. Nothing existing is changed, and it rolls back cleanly.
- **The seeder** adds the `manage_accounts` permission and gives it to Admin. Super Admin already has everything.
- **The ERP import:**
  - Copy the ERP dump into `storage/app` first.
  - Run it with `--dry-run` to see what it will bring in.
  - The test customer *Briar Bryant* (ERP customer 1) is left out by default, along with their invoice and payment.
  - If you have more than one agency, add `--agency="Affinity Healthcare Services Ltd"`.
- **`accounts:sync`** posts the care invoices, payroll runs and shop activity you already have, from the date you give. The same thing is a button in Finance → Settings ("Save and post past records now"). Records from before that date belong in your opening balances.
- Nothing needs building. No `composer require`, no `npm run build`.

## What the ERP import brings in

This is from your dump, run on the test copy:

- **Chart of accounts:** all 110 ERP accounts, matched by code. CareTrust's starting chart uses the same codes (1050 Receivables, 1060 Bank, 1065 Petty cash, 4020 Service income…).
  - Two ERP "Profit and Loss" accounts (3030 and 9990) aren't real accounts. The reports work them out, so they're left out.
- **Petty cash (1065):** gets its bank name and account number.
  - The ERP showed a balance of 800. That was the test customer's payment, so the opening balance is 0.
- **4 customers** (Thomas E Mclean, Anne Catherine Thorton, Arnold Musisi, Hortanse Chester).
  - Names in capitals are tidied.
  - If a customer has the same name as one of your service users, they're linked.
- **19 sales invoices**, INVO00002 to INVO00020, £40,743.00 in total.
  - Each keeps its date, due date, reference (AHCS/NKHC/HCS/…), description, quantity, price and discount.
- **No payments.** The only payment in the dump was the test customer's.
  - The ERP marked 7 invoices as part-paid, but it holds no payment records for them, so all 19 come in unpaid.
  - **Record any money already received** in Finance → Receipts.
- **The ERP's own ledger is not copied, on purpose.** It had posted four invoices more than once:
  - INVO00004 twice, INVO00012 five times, INVO00016 and INVO00017 three times each.
  - That made its income £61,023 instead of £40,743.
  - Each invoice here is counted once, and the import prints this check.
- **Currency:** GBP (£), from the ERP settings.

## Where things post automatically

You can switch each of these on or off in Finance → Settings. Each one is posted once only, so re-running is always safe.

| When this happens | It posts |
|---|---|
| A **care invoice is issued** | It becomes a sales invoice on the payer's account. Self-funded clients and local authorities each get their own customer record. Dr receivables, Cr care income. |
| A **care invoice is marked paid** | A receipt into the main bank account, matched to that invoice. |
| A care-invoice copy is **paid in Finance** | The care invoice is ticked as paid too, and so is its run once every invoice in it is paid. |
| A **payroll run is approved** | Dr wages and overtime (gross). Cr net pay owed, PAYE, pension/NSSF, staff loan repayments and other deductions, each worked out from the payslip lines. |
| A **payroll run is marked paid** | Dr net pay owed, Cr bank. |
| A payroll run is **cancelled** after approval | Both entries are reversed. |
| A **shop order is paid** | Dr takings, Cr sales, delivery and VAT. Card and PayPal money goes to "Card & online payments clearing", bank transfers to the bank, and pay-on-delivery to cash. |
| A shop order is **refunded** | The sale is reversed. |
| **Shop stock is sold** | Dr cost of sales, Cr inventory, at the cost of the actual batches it came out of (FEFO/FIFO from Batch 42). |
| Stock is **written off, expired or adjusted** | Inventory against "Stock write-offs and adjustments". |
| A **delivery (GRN) is received** | It becomes a supplier bill for its cost (Dr inventory, Cr payables), which you then pay in Finance. A delivery with no supplier goes to "Goods received not invoiced". |
| A GRN is **reversed** | Its bill is voided. |
| The first sync | A one-off "Opening stock" entry, so the Inventory account starts equal to the stock on the shelf. |

**If something can't post** (for example, the books are locked), the invoice, payroll run or order is still saved as normal. The problem is shown at the top of the Finance dashboard with a **Retry** button.

## The Finance workspace

**Dashboard**
- Cash and bank, what customers owe you, what you owe suppliers, and this year's profit or surplus.
- Income against expenses for the last six months.
- Overdue invoices, bills due this week, bank balances and the latest entries.
- A **New** menu for every kind of entry.

**Sales invoices and supplier bills**
- Any number of lines. Each line has an account, quantity, price, discount and VAT %, with a "prices already include VAT" option.
- Live totals while you type. A **+** button adds a new customer or supplier without leaving the page.
- *Save as draft* or *Save and approve*. Approving posts it, and after that it can't be edited, only **voided**. Voiding posts a reversing entry and keeps the reason in the audit trail.
- **Receive payment / Pay bill** from the invoice itself.
- If the customer has unmatched credit, there's a button to use it on this invoice.
- A print / PDF page, and a "PAID" or "OVERDUE" stamp.

**Receipts and supplier payments**
- Choose who paid, and their open invoices appear.
- **Match oldest first** spreads the money over them.
- Anything left over stays on their account as credit.
- Voiding a payment re-opens the invoices it paid.
- You can print a receipt or a remittance advice.

**Customers and suppliers**
- Balances, open items, payment terms, and a link to a service user.
- **Statement** for any date range, printable.

**Bank & cash**
- Every bank, cash and mobile-money account with its balance and this month's money in and out.
- Add accounts, **transfer between them**, and open each one's full register.

**Money in / out**
- Spend or receive money straight through an account, such as bank charges, a grant or a cash expense.
- These work like an invoice, with lines, VAT and a print page.

**Journals**
- Every entry in the books, with filters.
- **Journal entry** is for opening balances, accruals and corrections. It shows "Balanced" or "Out by £x", and won't post until it balances.
- Journals typed in by hand can be reversed with a reason. Automatic ones are undone at their source (void the invoice or payment).

**Chart of accounts**
- Grouped by assets, liabilities, equity, income and expenses, with balances.
- Each account has a *heading* that decides where it appears on the statements: Staff costs, Administration, Governance, Finance costs and so on.
- Accounts used by automatic posting are marked ⚡. They can be renamed but not archived.
- An account that has entries can only be archived, not deleted.

**Financial years**
- Years are made automatically from your year start. You can add earlier ones for comparatives.
- **Lock date:** nothing on or before it can be posted or reversed.
- **Close year** locks the whole year. Re-opening asks for a reason.
- The accounts audit trail is on the same page.

**Reports**
- Read only from the ledger, so they always agree.
- Each has print / PDF (with "Approved by" and date lines on the statements) and CSV.
- The reports:
  - **Profit and loss / Income statement:** revenue, cost of sales, gross result, other income, expenditure by heading and the result, with last year alongside.
  - **Balance sheet / Statement of financial position:** non-current and current assets, equity (including this year's result), current and long-term liabilities, with last year alongside and a ✓ when it balances.
  - **Trial balance**
  - **General ledger** for any account, with a running balance.
  - **Aged debtors** and **aged creditors** (not yet due, 1–30, 31–60, 61–90, over 90 days).

**Settings**
- Legal name, registration and VAT/TIN numbers.
- Organisation type, currency and year start.
- Payment terms, VAT and number prefixes.
- Automatic posting on/off, and the date to post from.
- **Which account each automatic job uses.**
- **Year-end appropriations.**

## Clients like the audited co-operative accounts you sent

- **Organisation type** changes the wording.
  - A *Co-operative / SACCO* gets an "Income statement" with "Revenue", "Expenditure" and "Surplus for the year", and a "Statement of financial position".
  - A *Charity* gets a "Statement of financial activities".
  - A *Company* keeps "Profit and loss" and "Balance sheet".
- **Currency is per agency:** UGX, KES, GBP, USD… UGX shows no decimals.
- **The year start is per agency:** 1 January for the co-operative, 1 April for UK companies.
- The statements follow the PDF's layout: current year and comparative columns, brackets for negatives, and headings like Staff costs, Administration and Governance.
- **Appropriations** are set up now and used at year end in the next batch.
  - "Use the co-operative template" fills in a statutory reserve (10% of surplus), a development revolving fund (5%) and an education fund (1% of turnover).
  - It also creates the reserve accounts for them. Every rate can be changed.

## Files

**New**
- `database/migrations/2026_11_23_100000_create_accounting_tables.php`
- `database/seeders/AccountingSeeder.php`
- `app/Models/Accounting/`: `Account`, `AccountingSetting`, `Contact`, `Document`, `DocumentLine`, `FinancialYear`, `Journal`, `JournalLine`, `Payment`, `PaymentAllocation`, `PostingIssue`, `Concerns/PlainDates`
- `app/Services/Accounting/`: `Ledger`, `LedgerException`, `DefaultChart`, `Documents`, `Payments`, `Reports`, `ReportBuilder`, `CarePosting`, `ShopPosting`, `AutoPosting`, `Backfill`
- `app/Console/Commands/AccountsImportErpgo.php`, `AccountsSync.php`
- `app/Http/Controllers/Accounting/AccountsPrintController.php`
- `app/Livewire/Accounting/`: 17 page components
- `resources/views/livewire/accounting/`: 17 views
- `resources/views/accounting/reports/` and `resources/views/accounting/print/`: the statements, plus the printable invoice, receipt and statement
- `routes/accounts.php`

**Changed (small additions only)**

If you've edited any of these yourself since Batch 42, merge the lines below rather than overwriting.

- `app/Providers/AppServiceProvider.php`: one line at the end of `boot()`, `\App\Services\Accounting\AutoPosting::register();`
- `app/Models/User.php`: new `canManageAccounts()`
- `routes/web.php`: `include __DIR__ . '/accounts.php';` after the shop-admin include
- `config/menu.php`:
  - a "Finance & Accounts" entry in `top`
  - an "Accounts & reports" link in the care Finance group
  - the new `accounts` sidebar
- `resources/views/layouts/sidebar.blade.php` and `include/modalmenu.blade.php`: show the Finance menu on `/accounts` pages
- `database/seeders/DatabaseSeeder.php`: calls `AccountingSeeder`

## How it was tested

The batch was tested on the same copy of the app as Batches 40–42 (SQLite), with the care invoicing and payroll tables added, your shop data and your ERP dump.

- **Ledger rules:**
  - Unbalanced entries are refused.
  - The lock date is enforced.
  - Posting the same source twice gives one entry.
  - Voids and reversals leave the trial balance and the balance sheet balanced.
- **Care invoicing:**
  - Issuing posts it, and "mark paid" records the receipt.
  - Paying in Finance ticks the care invoice and its run as paid.
  - The payer contact is reused for the next invoice.
- **Payroll:** a run with £570 gross, a £100 live-in allowance, £80 PAYE, £30 NSSF and a £20 loan repayment posts £670 gross, £90 of it overtime, and £540 net. Marking it paid posts £540 out of the bank.
- **Shop:**
  - A GRN with a supplier became a £25.00 bill.
  - Selling 3 posted £7.50 cost of sales from the right batch.
  - Paying the bill cleared aged creditors.
  - A reversed GRN voided its bill.
  - After every step the Inventory account equalled the stock on the shelf at cost.
- **ERP import:**
  - 110 accounts, 4 customers and 19 invoices (£40,743), with the test customer left out.
  - A second run added nothing.
- **Browser test (Playwright): 29 checks, all passing, no JavaScript errors.**
  - **Invoice:** quick-add a customer; a two-line invoice with VAT totalling £964.80; approve; receive £500 so it shows part paid with £464.80 due; print.
  - **Bill and payment:** a bill, then paying £200 with "match oldest first", which left £50 credit.
  - **Bank:** money out, and a transfer to petty cash.
  - **Journals:** a journal blocked until balanced, then reversed.
  - **Reports:** trial balance, balance sheet, aged debtors and CSV, and the ledger.
  - **Settings:** co-operative wording and the template.
  - **Lock date and void:** a locked date refused, and voiding an invoice.
  - **Phone:** three pages at phone width with no sideways scrolling.
- **Other workspaces:** Care and Shop pages still load. Every new view uses only classes in your compiled CSS.

## Not in this batch (Batch 44, as agreed)

- Cash flow statement, statement of changes in equity, notes to the accounts
- Fixed assets register and automatic depreciation (25% / 12.5% rates like the audited accounts)
- Bank reconciliation (match statement lines and tick off entries)
- Budgets and budget against actual
- VAT return
- Year-end close with the appropriations posted, and the audit pack (all statements, trial balance and ledgers in one PDF)
- Credit notes (for now, void and re-issue)
- Employer pension/NSSF contributions on payroll. The payroll module doesn't record the employer's share yet, so only what's deducted from staff is posted.
