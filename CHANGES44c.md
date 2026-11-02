# Batch 44c — Budgets, VAT returns, credit notes

Run after copying: `php artisan migrate && php artisan optimize:clear`

Migration `2026_11_27_100000_add_budgets_vat_returns_credit_notes` (reversible):
`acc_documents.credited_document_id`, `acc_credit_allocations`, `acc_vat_returns`,
`acc_journals.vat_return_id`, VAT period fields on `acc_settings`, `acc_budgets`, `acc_budget_lines`.

## Credit notes (Sales → Credit notes, Purchases → Supplier credits)
- Two new document kinds: `credit_note` (CN-) and `bill_credit` (SCN-). They post the opposite way
  to what they cancel: a credit note is Dr income / Dr VAT / Cr receivables; a supplier credit is
  Dr payables / Cr cost / Cr VAT.
- **Raise from the invoice/bill:** the "Credit note" / "Supplier credit" button on a posted invoice
  or bill opens a draft copying its lines (or one line for what's left if it's been part-credited).
  Edit it down for a part credit, then Approve.
- You can't credit more than the original is worth (checked across all its credit notes). Drafts
  and voided documents can't be credited.
- **Using a credit:** if the original is still open, the credit is used against it automatically
  on approval. Otherwise it sits on the customer's/supplier's account as credit to use on another
  invoice/bill ("Use it on this invoice"), with an undo link. It can also be refunded.
- **Refunds:** new payment kinds `refund` (RFD-, money back to a customer against a credit note)
  and `refund_in` (RFI-, money back from a supplier). Shown in the Supplier payments and Receipts
  lists respectively, with their own print headings.
- Credit notes read as "Not yet used / Part used / Used". Voiding one frees whatever it was used on.
- Aged debtors/creditors take used credits into account, and show unused credit per contact.

## VAT returns (Reports & year end → VAT returns)
- Worked out from the ledger on invoice and bill dates (standard accounting), into the nine
  boxes of the UK return:
  - Sales-side journals (invoices, credit notes, money in, shop sales) put their VAT in box 1 and
    their net value in box 6.
  - Purchase-side journals (bills, supplier credits, money out, goods received) use boxes 4 and 7.
  - Any other journal touching a VAT account is an adjustment.
  - Voids drop out by themselves.
  - Boxes 6–9 are whole pounds.
- **VAT periods:** monthly, quarterly or annual, plus the month a period ends in and an optional
  start date. The due date is one month and seven days after the period ends.
- **Filing:** returns are filed in order, and only once the period has ended. Filing freezes the
  boxes, stamps every journal it used, and can optionally move the lock date.
  - An entry dated in a period that's already filed is picked up by the next return, marked
    "late". It is never missed or counted twice.
- **Paying:** "Record payment" posts Dr VAT / Cr bank (the other way round for a refund). The
  payment is never counted on a return. It can be undone.
- **Re-open** the latest return (once any payment is undone) to redo it.
- The page shows each box, the entries behind them (filter by sales, purchases or adjustments),
  what the VAT accounts say is owed, and returns filed so far.
- Print view: `/accounts/vat/print?period=` or `?return=`.

## Budgets (Reports & year end → Budgets)
- Several budgets per financial year, one marked as the main budget.
- New budget can start blank, from last year's actual figures month by month, or as a copy of
  another budget. Copies and figures from last year take an optional ± % adjustment.
- The grid has a row per income or expense account and a column per month:
  - live totals for each row and month, and profit
  - "actual so far" under each account
  - spread a yearly figure evenly
  - fill from last year's actuals, or scale every figure by a %
  - add or remove rows
- **Budget vs actual** report (Reports): pick any run of months and which budget to use.
  - Shows budget, actual, variance and % per account, plus the full-year budget.
  - Variance is shown so that positive is good news.
  - Has a cumulative-profit chart, CSV export and print.

## Other
- Sidebar: Credit notes, Supplier credits, Budgets, VAT returns.
- Financial years page: the actions column (Statements / Audit pack / Close year) is now stacked —
  since 44b it spilled over the Lock date panel and blocked the Close year link.

## Tests
- `t44c.php`: 58 checks — journals, auto-use, over-credit, refund, unapply/reapply, void, box
  figures, late entry, order/period rules, pay/undo/re-open, budgets, statements still tie.
- `e2e44c.js`: 29 browser checks. The 43, 44a and 44b suites still pass.
