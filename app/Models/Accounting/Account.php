<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Batch 43: one line in an agency's chart of accounts. `type` is the
 * accounting class (decides debit/credit nature and which statement it
 * lands on); `subtype` decides the heading it's grouped under on that
 * statement. `system_key` marks the accounts automatic posting uses
 * (receivables, vat, wages…) — those can be renamed but not deleted.
 */
class Account extends Model
{
    use HasUuids;

    protected $table = 'acc_accounts';

    protected $fillable = [
        'agency_id', 'code', 'name', 'type', 'subtype', 'system_key', 'description', 'is_active',
        'bank_name', 'account_number', 'sort_code', 'external_ref',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public const TYPES = [
        'asset' => 'Assets',
        'liability' => 'Liabilities',
        'equity' => 'Equity',
        'income' => 'Income',
        'expense' => 'Expenses',
    ];

    /** subtype => [type, label]. Order here is the order on the statements. */
    public const SUBTYPES = [
        'bank' => ['asset', 'Bank'],
        'cash' => ['asset', 'Cash'],
        'mobile_money' => ['asset', 'Mobile money'],
        'receivable' => ['asset', 'Trade and other receivables'],
        'inventory' => ['asset', 'Inventory'],
        'current_asset' => ['asset', 'Other current assets'],
        'non_current_asset' => ['asset', 'Property, plant and equipment'],

        'payable' => ['liability', 'Trade and other payables'],
        'vat' => ['liability', 'VAT'],
        'payroll_liability' => ['liability', 'Payroll liabilities'],
        'current_liability' => ['liability', 'Other current liabilities'],
        'non_current_liability' => ['liability', 'Non-current liabilities'],

        'share_capital' => ['equity', 'Share capital'],
        'reserves' => ['equity', 'Reserves'],
        'retained_earnings' => ['equity', 'Retained earnings'],
        'equity' => ['equity', 'Other equity'],

        'revenue' => ['income', 'Revenue'],
        'other_income' => ['income', 'Other income'],

        'cost_of_sales' => ['expense', 'Cost of sales'],
        'staff_costs' => ['expense', 'Staff costs'],
        'admin_expenses' => ['expense', 'Administration expenses'],
        'governance' => ['expense', 'Governance costs'],
        'depreciation' => ['expense', 'Depreciation'],
        'finance_costs' => ['expense', 'Finance costs'],
        'other_expenses' => ['expense', 'Other expenses'],
        'tax' => ['expense', 'Taxation'],
    ];

    public const MONEY_SUBTYPES = ['bank', 'cash', 'mobile_money'];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    /** Assets and expenses grow with debits; the rest with credits. */
    public function isDebitNature(): bool
    {
        return in_array($this->type, ['asset', 'expense'], true);
    }

    public function isMoney(): bool
    {
        return in_array($this->subtype, self::MONEY_SUBTYPES, true);
    }

    public function label(): string
    {
        return $this->code.' · '.$this->name;
    }

    public function subtypeLabel(): string
    {
        return __(self::SUBTYPES[$this->subtype][1] ?? ucfirst(str_replace('_', ' ', $this->subtype)));
    }

    public static function subtypesFor(string $type): array
    {
        return array_filter(self::SUBTYPES, fn ($s) => $s[0] === $type);
    }

    public function scopeForAgency($q, string $agencyId)
    {
        return $q->where('agency_id', $agencyId);
    }

    public function scopeMoney($q)
    {
        return $q->whereIn('subtype', self::MONEY_SUBTYPES);
    }
}
