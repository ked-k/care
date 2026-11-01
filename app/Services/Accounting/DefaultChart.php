<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;

/**
 * Batch 43: the chart of accounts every agency starts with. Codes follow the
 * old ERP's standard chart (so its import lines up account-for-account),
 * with the few extra accounts CareTrust's automatic posting needs.
 *
 * Each row: code => [name, subtype, system_key|null].
 */
class DefaultChart
{
    public const CHART = [
        // Assets
        '1050' => ['Accounts Receivable', 'receivable', 'receivables'],
        '1060' => ['Bank Current Account', 'bank', 'bank'],
        '1065' => ['Petty Cash', 'cash', 'cash'],
        '1070' => ['Card & Online Payments Clearing', 'bank', 'card_clearing'],
        '1205' => ['Allowance for doubtful accounts', 'receivable', null],
        '1210' => ['Staff Loans and Advances', 'current_asset', 'staff_loans'],
        '1510' => ['Inventory', 'inventory', 'inventory'],
        '1520' => ['Stock of Raw Materials', 'inventory', null],
        '1530' => ['Stock of Work In Progress', 'inventory', null],
        '1540' => ['Stock of Finished Goods', 'inventory', null],
        '1550' => ['Goods Received Clearing account', 'inventory', null],
        '1810' => ['Land and Buildings', 'non_current_asset', null],
        '1820' => ['Office Furniture and Equipment', 'non_current_asset', null],
        '1825' => ['Accum. depreciation - Furniture and Equipment', 'non_current_asset', null],
        '1840' => ['Motor Vehicle', 'non_current_asset', null],
        '1845' => ['Accum. depreciation - Motor Vehicle', 'non_current_asset', null],
        // Liabilities
        '2100' => ['Accounts Payable', 'payable', 'payables'],
        '2105' => ['Deferred Income', 'current_liability', null],
        '2110' => ['Accrued Income Tax - Central', 'current_liability', null],
        '2120' => ['Income Tax Payable', 'current_liability', null],
        '2130' => ['Accrued Franchise Tax', 'current_liability', null],
        '2140' => ['VAT Provision', 'vat', null],
        '2145' => ['Purchase Tax', 'vat', null],
        '2150' => ['VAT Pay / Refund', 'vat', 'vat'],
        '2151' => ['Zero Rated', 'vat', null],
        '2152' => ['Capital import', 'vat', null],
        '2153' => ['Standard Import', 'vat', null],
        '2154' => ['Capital Standard', 'vat', null],
        '2155' => ['VAT Exempt', 'vat', null],
        '2160' => ['Accrued Use Tax Payable', 'current_liability', null],
        '2190' => ['Goods Received Not Invoiced', 'current_liability', 'grni'],
        '2210' => ['Net Wages Payable', 'payroll_liability', 'net_wages'],
        '2215' => ['PAYE Payable', 'payroll_liability', 'paye'],
        '2216' => ['Pension / NSSF Payable', 'payroll_liability', 'pension'],
        '2217' => ['Other Payroll Deductions', 'payroll_liability', 'other_deductions'],
        '2220' => ['Accrued Comp Time', 'payroll_liability', null],
        '2230' => ['Accrued Holiday Pay', 'payroll_liability', null],
        '2240' => ['Accrued Vacation Pay', 'payroll_liability', null],
        '2310' => ['Accr. Benefits - Central Provident Fund', 'payroll_liability', null],
        '2320' => ['Accr. Benefits - Stock Purchase', 'payroll_liability', null],
        '2330' => ['Accr. Benefits - Med, Den', 'payroll_liability', null],
        '2340' => ['Accr. Benefits - Payroll Taxes', 'payroll_liability', null],
        '2350' => ['Accr. Benefits - Credit Union', 'payroll_liability', null],
        '2360' => ['Accr. Benefits - Savings Bond', 'payroll_liability', null],
        '2370' => ['Accr. Benefits - Group Insurance', 'payroll_liability', null],
        '2380' => ['Accr. Benefits - Charity Cont.', 'payroll_liability', null],
        '2620' => ['Bank Loans', 'non_current_liability', null],
        '2680' => ['Loans from Shareholders', 'non_current_liability', null],
        // Equity
        '3020' => ['Opening Balances and adjustments', 'equity', 'opening_balance'],
        '3025' => ['Owners Contribution', 'equity', null],
        '3035' => ['Retained Earnings', 'retained_earnings', 'retained_earnings'],
        '3350' => ['Share Capital', 'share_capital', 'share_capital'],
        '3590' => ['Reserves and Surplus', 'reserves', null],
        '3595' => ['Owners Drawings', 'equity', null],
        // Income
        '4010' => ['Sales Income', 'revenue', 'shop_sales'],
        '4020' => ['Care Services Income', 'revenue', 'care_income'],
        '4430' => ['Shipping and Handling', 'other_income', 'delivery_income'],
        '4435' => ['Sundry Income', 'other_income', null],
        '4440' => ['Interest Received', 'other_income', null],
        '4450' => ['Foreign Exchange Gain', 'other_income', null],
        '4500' => ['Unallocated Income', 'other_income', null],
        '4510' => ['Discounts Received', 'other_income', null],
        // Cost of sales
        '5005' => ['Cost of Sales - On Services', 'cost_of_sales', null],
        '5010' => ['Cost of Sales - Purchases', 'cost_of_sales', 'cogs'],
        '5015' => ['Operating Costs', 'cost_of_sales', null],
        '5020' => ['Material Usage Variance', 'cost_of_sales', null],
        '5025' => ['Breakage and Replacement Costs', 'cost_of_sales', null],
        '5027' => ['Stock Write-offs and Adjustments', 'cost_of_sales', 'stock_adjustments'],
        '5030' => ['Consumable Materials', 'cost_of_sales', null],
        '5035' => ['Sub-contractor Costs', 'cost_of_sales', null],
        '5040' => ['Purchase Price Variance', 'cost_of_sales', null],
        '5045' => ['Direct Labour - COS', 'cost_of_sales', null],
        '5050' => ['Purchases of Materials', 'cost_of_sales', null],
        '5060' => ['Discounts Received', 'cost_of_sales', null],
        '5100' => ['Freight Costs', 'cost_of_sales', null],
        // Staff costs
        '5410' => ['Salaries and Wages', 'staff_costs', 'wages'],
        '5415' => ['Directors Fees & Remuneration', 'staff_costs', null],
        '5420' => ['Wages - Overtime', 'staff_costs', 'overtime'],
        '5425' => ['Members Salaries', 'staff_costs', null],
        '5430' => ['UIF Payments', 'staff_costs', null],
        '5440' => ['Payroll Taxes', 'staff_costs', null],
        '5450' => ['Workers Compensation', 'staff_costs', null],
        '5470' => ['General Benefits', 'staff_costs', null],
        // Tax
        '5460' => ['Normal Taxation Paid', 'tax', null],
        '5510' => ['Provisional Tax Paid', 'tax', null],
        '5520' => ['Income Tax Expense', 'tax', null],
        // Administration
        '5530' => ['Taxes - Real Estate', 'admin_expenses', null],
        '5540' => ['Taxes - Personal Property', 'admin_expenses', null],
        '5550' => ['Taxes - Franchise', 'admin_expenses', null],
        '5560' => ['Taxes - Foreign Withholding', 'admin_expenses', null],
        '5610' => ['Accounting Fees', 'admin_expenses', null],
        '5611' => ['Audit Fees', 'governance', null],
        '5612' => ['Board and General Meeting Costs', 'governance', null],
        '5615' => ['Advertising and Promotions', 'admin_expenses', null],
        '5620' => ['Bad Debts', 'admin_expenses', null],
        '5625' => ['Courier and Postage', 'admin_expenses', null],
        '5660' => ['Depreciation Expense', 'depreciation', 'depreciation'],
        '5685' => ['Insurance Expense', 'admin_expenses', null],
        '5690' => ['Bank Charges', 'finance_costs', 'bank_charges'],
        '5695' => ['Interest Paid', 'finance_costs', null],
        '5700' => ['Office Expenses - Consumables', 'admin_expenses', null],
        '5705' => ['Printing and Stationery', 'admin_expenses', null],
        '5710' => ['Security Expenses', 'admin_expenses', null],
        '5715' => ['Subscription - Membership Fees', 'admin_expenses', null],
        '5755' => ['Electricity, Gas and Water', 'admin_expenses', null],
        '5760' => ['Rent Paid', 'admin_expenses', null],
        '5765' => ['Repairs and Maintenance', 'admin_expenses', null],
        '5770' => ['Motor Vehicle Expenses', 'admin_expenses', null],
        '5771' => ['Petrol and Oil', 'admin_expenses', null],
        '5775' => ['Equipment Hire - Rental', 'admin_expenses', null],
        '5780' => ['Telephone and Internet', 'admin_expenses', null],
        '5785' => ['Travel and Accommodation', 'admin_expenses', null],
        '5786' => ['Meals and Entertainment', 'admin_expenses', null],
        '5787' => ['Staff Training', 'admin_expenses', null],
        '5790' => ['Utilities', 'admin_expenses', null],
        '5791' => ['Computer Expenses', 'admin_expenses', null],
        '5795' => ['Registrations', 'admin_expenses', null],
        '5800' => ['Licenses', 'admin_expenses', null],
        '5810' => ['Foreign Exchange Loss', 'finance_costs', null],
    ];

    /** system_key => default code — what posting looks up. */
    public const SYSTEM_ACCOUNTS = [
        'receivables' => '1050', 'bank' => '1060', 'cash' => '1065', 'card_clearing' => '1070',
        'staff_loans' => '1210', 'inventory' => '1510', 'payables' => '2100', 'vat' => '2150',
        'grni' => '2190', 'net_wages' => '2210', 'paye' => '2215', 'pension' => '2216',
        'other_deductions' => '2217', 'opening_balance' => '3020', 'retained_earnings' => '3035',
        'share_capital' => '3350', 'shop_sales' => '4010', 'care_income' => '4020',
        'delivery_income' => '4430', 'cogs' => '5010', 'stock_adjustments' => '5027',
        'wages' => '5410', 'overtime' => '5420', 'depreciation' => '5660', 'bank_charges' => '5690',
    ];

    /** What each system account is for, shown on the settings page. */
    public const SYSTEM_LABELS = [
        'receivables' => 'Money customers owe you',
        'payables' => 'Money you owe suppliers',
        'vat' => 'VAT control',
        'bank' => 'Main bank account (default for payments and payroll)',
        'cash' => 'Cash (shop pay-on-delivery)',
        'card_clearing' => 'Card / PayPal takings from the shop',
        'care_income' => 'Income from care invoices',
        'shop_sales' => 'Shop sales',
        'delivery_income' => 'Shop delivery charges',
        'inventory' => 'Shop stock (at cost)',
        'cogs' => 'Cost of shop goods sold',
        'stock_adjustments' => 'Stock written off or adjusted',
        'grni' => 'Deliveries received with no supplier',
        'wages' => 'Wages (gross pay)',
        'overtime' => 'Overtime pay',
        'net_wages' => 'Net pay owed to staff',
        'paye' => 'Income tax (PAYE) withheld',
        'pension' => 'Pension / NSSF withheld',
        'other_deductions' => 'Other payroll deductions',
        'staff_loans' => 'Staff loans (repayments taken from pay)',
        'opening_balance' => 'Opening balances',
        'retained_earnings' => 'Retained earnings',
        'share_capital' => 'Share capital',
        'depreciation' => 'Depreciation',
        'bank_charges' => 'Bank charges',
    ];

    public static function install(string $agencyId): int
    {
        $existing = Account::where('agency_id', $agencyId)->pluck('code')->all();
        $made = 0;
        foreach (self::CHART as $code => [$name, $subtype, $key]) {
            if (in_array((string) $code, $existing, true)) {
                continue;
            }
            Account::create([
                'agency_id' => $agencyId,
                'code' => (string) $code,
                'name' => $name,
                'type' => Account::SUBTYPES[$subtype][0],
                'subtype' => $subtype,
                'system_key' => $key && ! Account::where('agency_id', $agencyId)->where('system_key', $key)->exists() ? $key : null,
            ]);
            $made++;
        }

        return $made;
    }

    /** Make sure one system account exists (re-creating it under a free code if needed). */
    public static function ensure(string $agencyId, string $key): Account
    {
        if ($found = Account::where('agency_id', $agencyId)->where('system_key', $key)->first()) {
            return $found;
        }
        $code = self::SYSTEM_ACCOUNTS[$key];
        [$name, $subtype] = self::CHART[$code];
        $byCode = Account::where('agency_id', $agencyId)->where('code', $code)->first();
        if ($byCode && ! $byCode->system_key) {
            $byCode->update(['system_key' => $key]);

            return $byCode;
        }
        while (Account::where('agency_id', $agencyId)->where('code', $code)->exists()) {
            $code = (string) ((int) $code + 1);
        }

        return Account::create([
            'agency_id' => $agencyId, 'code' => $code, 'name' => $name,
            'type' => Account::SUBTYPES[$subtype][0], 'subtype' => $subtype, 'system_key' => $key,
        ]);
    }
}
