<?php

namespace App\Console\Commands;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Contact;
use App\Models\Accounting\Document;
use App\Models\Accounting\Payment;
use App\Models\Agency;
use App\Models\ServiceUser;
use App\Services\Accounting\Documents;
use App\Services\Accounting\Ledger;
use App\Services\Accounting\Payments;
use App\Support\Shop\SqlDumpReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Batch 43: bring the old ERPGo accounts into Finance — chart of accounts,
 * bank/cash accounts, customers, sales invoices (with their lines) and the
 * payments against them.
 *
 *   php artisan accounts:import-erpgo storage/app/affiwspg_erp.sql --dry-run
 *   php artisan accounts:import-erpgo storage/app/affiwspg_erp.sql
 *
 * Re-running is safe: anything already imported is recognised and skipped.
 * The ERP's own ledger lines (add_transaction_lines) are deliberately NOT
 * copied — every invoice and payment is re-posted through CareTrust's
 * ledger instead, which also fixes two problems found in the old ledger
 * (VAT booked as income, one invoice posted twice). The command prints the
 * difference so it can be checked.
 */
class AccountsImportErpgo extends Command
{
    protected $signature = 'accounts:import-erpgo
        {file : Path to the ERPGo .sql dump}
        {--agency= : Agency id or name (needed when there is more than one)}
        {--skip-customer=1 : ERP customer id(s) to leave out, comma separated (test data). Use 0 to skip none}
        {--dry-run : Show what would be imported without saving anything}';

    protected $description = 'Import customers, invoices, payments, bank accounts and the chart of accounts from an ERPGo SQL dump';

    /** ERPGo sub-type id => CareTrust subtype (ERPGo files share capital and retained earnings under liabilities). */
    private const SUBTYPE_MAP = [
        1 => 'receivable', 2 => 'current_asset', 3 => 'inventory', 4 => 'non_current_asset',
        5 => 'payable', 6 => 'current_liability', 7 => 'non_current_liability',
        8 => 'share_capital', 9 => 'reserves', 10 => 'equity',
        11 => 'revenue', 12 => 'other_income', 13 => 'cost_of_sales', 14 => 'staff_costs', 15 => 'admin_expenses',
    ];

    /** Calculated by the reports, so not real accounts here. */
    private const SKIP_CODES = ['9990', '3030'];

    private array $accountMap = [];   // erp account id => Account

    private array $customerMap = [];  // erp customer id => Contact

    private array $invoiceMap = [];   // erp invoice id => Document

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }
        $agency = $this->agency();
        if (! $agency) {
            return self::FAILURE;
        }
        $dry = (bool) $this->option('dry-run');
        $skipCustomers = array_values(array_filter(array_map('intval', explode(',', (string) $this->option('skip-customer')))));

        $this->info(($dry ? '[dry run] ' : '').'Importing ERPGo accounts into '.$agency->name);
        $dump = new SqlDumpReader(file_get_contents($file));
        $rows = fn (string $t) => $dump->rows($t);

        $erpSettings = collect($rows('settings'))->pluck('value', 'name');
        $accounts = $rows('chart_of_accounts');
        $banks = $rows('bank_accounts');
        $customers = collect($rows('customers'))->reject(fn ($c) => in_array((int) $c['id'], $skipCustomers, true));
        $skippedCustomers = collect($rows('customers'))->filter(fn ($c) => in_array((int) $c['id'], $skipCustomers, true));
        $invoices = collect($rows('invoices'))->reject(fn ($i) => in_array((int) $i['customer_id'], $skipCustomers, true));
        $skippedInvoiceIds = collect($rows('invoices'))->filter(fn ($i) => in_array((int) $i['customer_id'], $skipCustomers, true))->pluck('id')->map('intval')->all();
        $lines = collect($rows('invoice_products'))->groupBy('invoice_id');
        $payments = collect($rows('invoice_payments'));
        $taxes = collect($rows('taxes'))->keyBy('id');
        $products = collect($rows('product_services'))->keyBy('id');

        $this->table(['What', 'In the ERP', 'Importing'], [
            ['Chart of accounts', count($accounts), count($accounts) - count(array_filter($accounts, fn ($a) => in_array($a['code'], self::SKIP_CODES, true)))],
            ['Bank / cash accounts', count($banks), count($banks)],
            ['Customers', $customers->count() + $skippedCustomers->count(), $customers->count()],
            ['Sales invoices', $invoices->count() + count($skippedInvoiceIds), $invoices->count()],
            ['Invoice payments', $payments->count(), $payments->reject(fn ($p) => in_array((int) $p['invoice_id'], $skippedInvoiceIds, true))->count()],
        ]);
        foreach ($skippedCustomers as $c) {
            $this->line("  Leaving out customer #{$c['id']} {$c['name']} (test data) and its invoices/payments.");
        }
        if ($dry) {
            $this->previewInvoices($invoices, $lines, $taxes, $payments);
            $this->comment('Nothing saved (dry run).');

            return self::SUCCESS;
        }

        $settings = AccountingSetting::forAgency($agency);
        try {
            DB::transaction(function () use ($agency, $settings, $erpSettings, $accounts, $banks, $customers, $invoices, $lines, $payments, $taxes, $products, $skippedInvoiceIds) {
                $this->importSettings($settings, $erpSettings);
                $this->importAccounts($agency->id, $accounts);
                $this->importBanks($agency->id, $banks, $payments, $skippedInvoiceIds);
                $this->importCustomers($agency->id, $customers);
                $this->importInvoices($agency->id, $settings, $invoices, $lines, $taxes, $products);
                $this->importPayments($agency->id, $payments, $skippedInvoiceIds);
            });
        } catch (Throwable $e) {
            $this->error('Import stopped, nothing was saved: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->checkAgainstErpLedger($rows('add_transaction_lines'), $skippedInvoiceIds);
        $this->info('Done. Open Finance → Sales invoices to see them.');

        return self::SUCCESS;
    }

    private function agency(): ?Agency
    {
        if ($opt = $this->option('agency')) {
            $a = Agency::where('id', $opt)->orWhere('name', $opt)->first();
            $a || $this->error("No agency called {$opt}.");

            return $a;
        }
        if (Agency::count() === 1) {
            return Agency::first();
        }
        $this->error('There is more than one agency — say which with --agency="Name".');
        $this->line('  '.Agency::pluck('name')->implode(', '));

        return null;
    }

    private function importSettings(AccountingSetting $settings, $erp): void
    {
        $hasJournals = \App\Models\Accounting\Journal::where('agency_id', $settings->agency_id)->exists();
        $changes = [];
        if (! $hasJournals && ($code = $erp['site_currency'] ?? null)) {
            $changes['currency_code'] = strtoupper($code);
            $changes['currency_symbol'] = $erp['site_currency_symbol'] ?? (AccountingSetting::CURRENCIES[$code] ?? $code);
            $changes['currency_decimals'] = (int) ($erp['decimal_number'] ?? 2);
        }
        if (! $settings->registration_number && ! empty($erp['registration_number'])) {
            $changes['registration_number'] = $erp['registration_number'];
        }
        if (! $settings->tax_number && ! empty($erp['vat_number'])) {
            $changes['tax_number'] = $erp['vat_number'];
        }
        if (! empty($erp['company_name']) && (! $settings->legal_name || $settings->legal_name === $settings->agency?->name)) {
            $changes['legal_name'] = $erp['company_name'];
        }
        if (! $settings->address && ! empty($erp['company_address'])) {
            $changes['address'] = $erp['company_address'];
        }
        if ($changes) {
            $settings->update($changes);
            $this->line('  Settings: '.implode(', ', array_keys($changes)));
        }
    }

    private function importAccounts(string $agencyId, array $accounts): void
    {
        $made = 0;
        $matched = 0;
        foreach ($accounts as $a) {
            if (in_array($a['code'], self::SKIP_CODES, true)) {
                continue;
            }
            $ref = 'erpgo:'.$a['id'];
            $account = Account::where('agency_id', $agencyId)->where('external_ref', $ref)->first()
                ?? Account::where('agency_id', $agencyId)->where('code', $a['code'])->first();
            if ($account) {
                $account->external_ref ??= $ref;
                $account->save();
                $matched++;
            } else {
                $subtype = self::SUBTYPE_MAP[(int) $a['sub_type']] ?? 'current_asset';
                $account = Account::create([
                    'agency_id' => $agencyId, 'code' => $a['code'], 'name' => $a['name'],
                    'type' => Account::SUBTYPES[$subtype][0], 'subtype' => $subtype,
                    'description' => $a['description'], 'is_active' => (bool) $a['is_enabled'], 'external_ref' => $ref,
                ]);
                $made++;
            }
            $this->accountMap[(int) $a['id']] = $account;
        }
        $this->line("  Chart of accounts: {$matched} matched by code, {$made} added");
    }

    private function importBanks(string $agencyId, array $banks, $payments, array $skippedInvoiceIds): void
    {
        foreach ($banks as $b) {
            $account = $this->accountMap[(int) $b['chart_account_id']] ?? null;
            if (! $account) {
                continue;
            }
            $this->bankChart[(int) $b['id']] = $account;
            $account->update([
                'subtype' => str_contains(strtolower($b['bank_name'].' '.$b['holder_name']), 'cash') ? 'cash' : 'bank',
                'bank_name' => $b['bank_name'],
                'account_number' => $b['account_number'],
            ]);
            // ERPGo adds every payment received into "opening_balance", so the
            // real opening figure is what's left after taking them back out.
            $received = (float) $payments->where('account_id', $b['id'])->sum('amount');
            $opening = round((float) $b['opening_balance'] - $received, 2);
            $skipped = (float) $payments->where('account_id', $b['id'])->filter(fn ($p) => in_array((int) $p['invoice_id'], $skippedInvoiceIds, true))->sum('amount');
            $this->line("  Bank/cash: {$b['bank_name']} → {$account->code} {$account->name}".
                ($skipped ? " (its ERP balance of {$b['opening_balance']} included {$skipped} from the test customer)" : ''));
            if (abs($opening) >= 0.01) {
                Ledger::post($agencyId, [
                    'date' => substr($b['created_at'] ?? now(), 0, 10), 'type' => 'opening',
                    'reference' => __('Opening balance'), 'memo' => __('Opening balance — :b (from ERP)', ['b' => $b['bank_name']]),
                    'source_type' => 'erpgo', 'source_id' => 'bank:'.$b['id'], 'source_event' => 'opening', 'system' => true,
                ], [
                    ['account' => $account, 'debit' => $opening],
                    ['account' => 'opening_balance', 'credit' => $opening],
                ]);
                $this->line("    opening balance {$opening} posted");
            }
        }
    }

    private function importCustomers(string $agencyId, $customers): void
    {
        $linked = 0;
        foreach ($customers as $c) {
            $ref = 'erpgo:customer:'.$c['id'];
            $contact = Contact::where('agency_id', $agencyId)->where('external_ref', $ref)->first();
            if (! $contact) {
                $address = implode("\n", array_filter([
                    $c['billing_name'] && strcasecmp($c['billing_name'], $c['name']) !== 0 ? $c['billing_name'] : null,
                    $c['billing_address'], trim(($c['billing_city'] ?? '').' '.($c['billing_zip'] ?? '')),
                    $c['billing_state'] !== $c['billing_city'] ? $c['billing_state'] : null, $c['billing_country'],
                ]));
                $serviceUser = ServiceUser::where('agency_id', $agencyId)
                    ->where(fn ($q) => $q->whereRaw('lower(name) = ?', [mb_strtolower(trim($c['name']))])
                        ->orWhereRaw('lower(name) = ?', [mb_strtolower(trim((string) $c['billing_name']))]))
                    ->first();
                $contact = Contact::create([
                    'agency_id' => $agencyId, 'is_customer' => true, 'name' => $this->tidyName($c['name']),
                    'contact_name' => $c['billing_name'] ?: null, 'email' => $c['email'] ?: null,
                    'phone' => $c['contact'] ?: ($c['billing_phone'] ?: null), 'address' => $address ?: null,
                    'tax_number' => $c['tax_number'] ?: null, 'service_user_id' => $serviceUser?->id,
                    'external_ref' => $ref, 'notes' => __('Imported from the old ERP (customer #:n)', ['n' => $c['customer_id']]),
                    'is_active' => (bool) $c['is_active'],
                ]);
                $serviceUser && $linked++;
            }
            $this->customerMap[(int) $c['id']] = $contact;
        }
        $this->line('  Customers: '.count($this->customerMap).' ('.$linked.' matched to a service user)');
    }

    private function tidyName(string $name): string
    {
        // "THOMAS E MCLEAN" → "Thomas E Mclean"; mixed case is left alone.
        return $name === mb_strtoupper($name) ? mb_convert_case(mb_strtolower($name), MB_CASE_TITLE) : $name;
    }

    private function importInvoices(string $agencyId, AccountingSetting $settings, $invoices, $lines, $taxes, $products): void
    {
        $posted = 0;
        $drafts = 0;
        $already = 0;
        $fallback = $settings->account('care_income');
        foreach ($invoices as $inv) {
            $existing = Document::where('agency_id', $agencyId)->where('source_type', 'erpgo')->where('source_id', 'invoice:'.$inv['id'])->first();
            if ($existing) {
                $this->invoiceMap[(int) $inv['id']] = $existing;
                $already++;

                continue;
            }
            $contact = $this->customerMap[(int) $inv['customer_id']] ?? null;
            $docLines = ($lines[$inv['id']] ?? collect())->map(function ($l) use ($products, $taxes, $fallback) {
                $product = $products[$l['product_id']] ?? null;
                $account = $this->accountMap[(int) ($product['sale_chartaccount_id'] ?? 0)] ?? $fallback;
                $rate = collect(explode(',', (string) $l['tax']))->filter()->sum(fn ($t) => (float) ($taxes[$t]['rate'] ?? 0));

                return [
                    'account_id' => $account->id,
                    'description' => trim(($product['name'] ?? '')."\n".($l['description'] ?? '')),
                    'qty' => (float) $l['quantity'], 'unit_price' => (float) $l['price'],
                    'discount' => (float) $l['discount'], 'vat_rate' => $rate,
                ];
            })->all();

            $number = 'INVO'.str_pad((string) $inv['invoice_id'], 5, '0', STR_PAD_LEFT);
            $doc = Documents::saveDraft(new Document([
                'agency_id' => $agencyId, 'kind' => 'invoice', 'status' => 'draft', 'number' => $number,
                'source_type' => 'erpgo', 'source_id' => 'invoice:'.$inv['id'],
            ]), $agencyId, 'invoice', [
                'contact_id' => $contact?->id,
                'issue_date' => $inv['issue_date'],
                'due_date' => $inv['due_date'],
                'reference' => $inv['ref_number'] ?: null,
                'notes' => __('Imported from the old ERP'),
            ], $docLines);
            if ((int) $inv['status'] > 0) {
                $doc = Documents::post($doc);
                $posted++;
            } else {
                $drafts++;
            }
            $this->invoiceMap[(int) $inv['id']] = $doc;
        }
        $this->line("  Sales invoices: {$posted} posted, {$drafts} left as drafts".($already ? ", {$already} already imported" : ''));
    }

    private function importPayments(string $agencyId, $payments, array $skippedInvoiceIds): void
    {
        $made = 0;
        foreach ($payments as $p) {
            if (in_array((int) $p['invoice_id'], $skippedInvoiceIds, true)) {
                continue;
            }
            $doc = $this->invoiceMap[(int) $p['invoice_id']] ?? null;
            if (! $doc || Payment::where('agency_id', $agencyId)->where('source_type', 'erpgo')->where('source_id', 'payment:'.$p['id'])->exists()) {
                continue;
            }
            $bankAccountId = ($this->bankChart[(int) $p['account_id']] ?? null)?->id
                ?? AccountingSetting::forAgency($agencyId)->account('bank')->id;
            Payments::record($agencyId, [
                'kind' => 'receipt', 'contact_id' => $doc->contact_id, 'bank_account_id' => $bankAccountId,
                'date' => $p['date'], 'amount' => (float) $p['amount'], 'method' => 'bank_transfer',
                'reference' => $p['reference'] ?: null, 'notes' => $p['description'] ?: null,
                'source_type' => 'erpgo', 'source_id' => 'payment:'.$p['id'],
            ], [$doc->id => (float) $p['amount']]);
            $made++;
        }
        $this->line("  Payments: {$made} recorded");

        // ERPGo shows some invoices as part-paid with no payment on record.
        $noPayments = collect($this->invoiceMap)->filter(fn ($d) => $d->fresh()->status === 'posted')->count();
        if ($noPayments) {
            $this->comment("  {$noPayments} invoices have no payment recorded in the ERP, so they come in unpaid. Record any money already received in Finance → Receipts.");
        }
    }

    private array $bankChart = []; // erp bank_accounts id => Account

    private function previewInvoices($invoices, $lines, $taxes, $payments): void
    {
        $rows = [];
        foreach ($invoices as $inv) {
            $total = ($lines[$inv['id']] ?? collect())->sum(function ($l) use ($taxes) {
                $net = (float) $l['quantity'] * (float) $l['price'] - (float) $l['discount'];
                $rate = collect(explode(',', (string) $l['tax']))->filter()->sum(fn ($t) => (float) ($taxes[$t]['rate'] ?? 0));

                return $net + round($net * $rate / 100, 2);
            });
            $rows[] = [
                'INVO'.str_pad((string) $inv['invoice_id'], 5, '0', STR_PAD_LEFT), $inv['issue_date'], $inv['ref_number'],
                number_format($total, 2), number_format((float) $payments->where('invoice_id', $inv['id'])->sum('amount'), 2),
            ];
        }
        $this->table(['Invoice', 'Date', 'Reference', 'Total', 'Paid'], $rows);
    }

    /** Compare with the ERP's own ledger so any difference is explained. */
    private function checkAgainstErpLedger(array $gl, array $skippedInvoiceIds): void
    {
        $erpIncome = collect($gl)->where('reference', 'Invoice')
            ->reject(fn ($l) => in_array((int) $l['reference_id'], $skippedInvoiceIds, true))
            ->sum(fn ($l) => (float) $l['credit']);
        $ours = collect($this->invoiceMap)->sum(fn ($d) => in_array($d->fresh()->status, ['draft', 'void'], true) ? 0 : (float) $d->total);
        $this->line(sprintf('  Check: invoiced total %s here vs %s in the ERP ledger.', number_format($ours, 2), number_format($erpIncome, 2)));
        if (abs($ours - $erpIncome) >= 0.01) {
            $dupes = collect($gl)->where('reference', 'Invoice')->where('credit', '>', 0)
                ->reject(fn ($l) => in_array((int) $l['reference_id'], $skippedInvoiceIds, true))
                ->groupBy('reference_id')->filter(fn ($g) => $g->count() > 1);
            $extra = $dupes->sum(fn ($g) => (float) $g->first()['credit'] * ($g->count() - 1));
            $list = $dupes->map(fn ($g, $id) => 'INVO'.str_pad((string) $id, 5, '0', STR_PAD_LEFT).' ×'.$g->count())->implode(', ');
            $whole = abs(($erpIncome - $ours) - $extra) < 0.01;
            $this->comment('  The old ERP ledger posted some invoices more than once'.($list ? " ({$list})" : '').
                ($whole ? ' — that is the whole difference.' : ' — that explains '.number_format($extra, 2).' of it.').
                ' The figures here are rebuilt from the invoices themselves, each counted once.');
        }
    }
}
