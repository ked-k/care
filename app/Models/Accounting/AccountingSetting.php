<?php

namespace App\Models\Accounting;

use App\Models\Agency;
use App\Services\Accounting\DefaultChart;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Batch 43: one agency's accounting set-up. Created (with the default chart
 * of accounts) the first time anyone opens the Finance workspace or anything
 * posts to the ledger — see forAgency().
 */
class AccountingSetting extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['lock_date', 'posting_from'];

    protected $table = 'acc_settings';

    protected $fillable = [
        'agency_id', 'legal_name', 'registration_number', 'tax_number', 'address', 'organisation_type',
        'currency_code', 'currency_symbol', 'currency_decimals', 'fy_start_month', 'fy_start_day',
        'lock_date', 'posting_from', 'vat_registered', 'default_vat_rate', 'payment_terms_days',
        'numbering', 'auto_post', 'accounts', 'appropriations', 'invoice_footer',
    ];

    protected $casts = [
        'lock_date' => 'date',
        'posting_from' => 'date',
        'vat_registered' => 'boolean',
        'default_vat_rate' => 'decimal:2',
        'numbering' => 'array',
        'auto_post' => 'array',
        'accounts' => 'array',
        'appropriations' => 'array',
    ];

    public const ORGANISATION_TYPES = [
        'company' => 'Limited company',
        'cooperative' => 'Co-operative society / SACCO',
        'charity' => 'Charity / non-profit',
        'sole_trader' => 'Sole trader / partnership',
    ];

    public const NUMBERING_DEFAULTS = [
        'journal' => ['JNL-', 1],
        'invoice' => ['INV-', 1],
        'bill' => ['BILL-', 1],
        'money_in' => ['MI-', 1],
        'money_out' => ['MO-', 1],
        'receipt' => ['RCT-', 1],
        'payment' => ['PAY-', 1],
    ];

    public const AUTO_POST_DEFAULTS = [
        'care_invoices' => true,
        'payroll' => true,
        'shop_sales' => true,
        'shop_stock' => true,
    ];

    /** Common currencies for the settings picker; any ISO code can be typed. */
    public const CURRENCIES = [
        'GBP' => '£', 'UGX' => 'UGX', 'KES' => 'KSh', 'TZS' => 'TSh', 'RWF' => 'FRw', 'USD' => '$',
        'EUR' => '€', 'ZAR' => 'R', 'NGN' => '₦', 'GHS' => 'GH₵',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    private static array $cache = [];

    /** The agency's settings, creating them (and the default chart) on first use. */
    public static function forAgency(Agency|string $agency): self
    {
        $agencyId = $agency instanceof Agency ? $agency->id : $agency;
        if (isset(self::$cache[$agencyId])) {
            return self::$cache[$agencyId];
        }

        $settings = static::where('agency_id', $agencyId)->first();
        if (! $settings) {
            $settings = DB::transaction(function () use ($agencyId) {
                $existing = static::where('agency_id', $agencyId)->lockForUpdate()->first();
                if ($existing) {
                    return $existing;
                }
                $agencyModel = Agency::find($agencyId);
                // A shop already trading in a currency is the best guess.
                $shopCurrency = class_exists(\App\Models\Shop\ShopStore::class)
                    ? \App\Models\Shop\ShopStore::where('agency_id', $agencyId)->value('currency') : null;
                $code = $shopCurrency ?: 'GBP';

                $settings = static::create([
                    'agency_id' => $agencyId,
                    'legal_name' => $agencyModel?->name,
                    'address' => $agencyModel?->address,
                    'currency_code' => $code,
                    'currency_symbol' => self::CURRENCIES[$code] ?? $code,
                    'currency_decimals' => in_array($code, ['UGX', 'RWF', 'TZS'], true) ? 0 : 2,
                    'numbering' => [],
                    'auto_post' => self::AUTO_POST_DEFAULTS,
                    'accounts' => [],
                    'appropriations' => [],
                    'posting_from' => today()->startOfMonth(),
                    'fy_start_month' => 4,
                    'fy_start_day' => 1,
                    'organisation_type' => 'company',
                    'payment_terms_days' => 30,
                ])->refresh();
                DefaultChart::install($agencyId);

                return $settings;
            });
        }

        return self::$cache[$agencyId] = $settings;
    }

    public static function forgetCache(): void
    {
        self::$cache = [];
    }

    // -- Money ---------------------------------------------------------------

    public function money($amount, bool $symbol = true): string
    {
        $amount = (float) $amount;
        $s = number_format(abs($amount), (int) $this->currency_decimals);
        $sym = $this->currency_symbol ?: $this->currency_code;
        // Letter codes (UGX) read better with a space; symbols (£) don't.
        $s = $symbol ? (preg_match('/^[A-Za-z]+$/', $sym) ? $sym.' '.$s : $sym.$s) : $s;

        return $amount < -0.0000001 ? '-'.$s : $s;
    }

    /** Statement style: negatives in brackets, zero as a dash. */
    public function figure($amount): string
    {
        $amount = round((float) $amount, (int) $this->currency_decimals);
        if (abs($amount) < 0.005) {
            return '–';
        }
        $s = number_format(abs($amount), (int) $this->currency_decimals);

        return $amount < 0 ? '('.$s.')' : $s;
    }

    // -- Numbering -------------------------------------------------------------

    public function prefix(string $kind): string
    {
        return $this->numbering[$kind]['prefix'] ?? self::NUMBERING_DEFAULTS[$kind][0];
    }

    public function nextNumberPreview(string $kind): string
    {
        $next = (int) ($this->numbering[$kind]['next'] ?? self::NUMBERING_DEFAULTS[$kind][1]);

        return $this->prefix($kind).str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /** Takes the next number for a kind of record. Call inside a transaction. */
    public static function takeNumber(string $agencyId, string $kind): string
    {
        $row = static::where('agency_id', $agencyId)->lockForUpdate()->firstOrFail();
        $numbering = $row->numbering ?? [];
        $next = (int) ($numbering[$kind]['next'] ?? self::NUMBERING_DEFAULTS[$kind][1]);
        $prefix = $numbering[$kind]['prefix'] ?? self::NUMBERING_DEFAULTS[$kind][0];
        $numbering[$kind] = ['prefix' => $prefix, 'next' => $next + 1];
        $row->numbering = $numbering;
        $row->save();
        if (isset(self::$cache[$agencyId])) {
            self::$cache[$agencyId]->numbering = $numbering;
        }

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // -- Accounts --------------------------------------------------------------

    /** The account automatic posting uses for a role (receivables, vat…). */
    public function account(string $key): Account
    {
        $overrideId = $this->accounts[$key] ?? null;
        $account = $overrideId ? Account::where('agency_id', $this->agency_id)->find($overrideId) : null;
        $account ??= Account::where('agency_id', $this->agency_id)->where('system_key', $key)->first();

        if (! $account && isset(DefaultChart::SYSTEM_ACCOUNTS[$key])) {
            // The key's account was never created (an older chart) — add it.
            $account = DefaultChart::ensure($this->agency_id, $key);
        }
        if (! $account) {
            throw new RuntimeException(__('No account is set up for “:key”. Check Finance → Settings → Default accounts.', ['key' => $key]));
        }

        return $account;
    }

    public function autoPosts(string $what): bool
    {
        return (bool) (($this->auto_post ?? self::AUTO_POST_DEFAULTS)[$what] ?? self::AUTO_POST_DEFAULTS[$what] ?? false);
    }

    /** True when a record dated $date is old enough to belong in opening balances. */
    public function beforePostingStart($date): bool
    {
        return $this->posting_from && Carbon::parse($date)->toDateString() < $this->posting_from->toDateString();
    }

    public function isLocked($date): bool
    {
        return $this->lock_date && Carbon::parse($date)->toDateString() <= $this->lock_date->toDateString();
    }

    // -- Financial years -------------------------------------------------------

    /** The financial year a date falls in (created if it doesn't exist yet). */
    public function yearFor($date): FinancialYear
    {
        $date = Carbon::parse($date)->startOfDay();
        $existing = FinancialYear::where('agency_id', $this->agency_id)
            ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->first();
        if ($existing) {
            return $existing;
        }

        [$start, $end] = $this->yearBounds($date);

        return FinancialYear::firstOrCreate(
            ['agency_id' => $this->agency_id, 'starts_on' => $start->toDateString()],
            ['name' => $this->yearName($start, $end), 'ends_on' => $end->toDateString(), 'status' => 'open'],
        );
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function yearBounds($date): array
    {
        $date = Carbon::parse($date)->startOfDay();
        $month = max(1, min(12, (int) $this->fy_start_month));
        $day = max(1, min(28, (int) $this->fy_start_day));
        $start = Carbon::create($date->year, $month, $day);
        if ($start->gt($date)) {
            $start->subYear();
        }

        return [$start, $start->copy()->addYear()->subDay()];
    }

    public function yearName(Carbon $start, Carbon $end): string
    {
        return $start->year === $end->year ? 'FY '.$start->year : 'FY '.$start->year.'/'.substr((string) $end->year, -2);
    }

    // -- Wording ---------------------------------------------------------------

    /** Labels that differ between a company, a co-operative and a charity. */
    public function term(string $key): string
    {
        $coop = $this->organisation_type === 'cooperative';
        $charity = $this->organisation_type === 'charity';
        $terms = [
            'income_statement' => $charity ? 'Statement of financial activities' : ($coop ? 'Income statement' : 'Profit and loss'),
            'balance_sheet' => $coop || $charity ? 'Statement of financial position' : 'Balance sheet',
            'income' => $coop ? 'Revenue' : 'Income',
            'expenses' => $coop || $charity ? 'Expenditure' : 'Expenses',
            'gross' => $coop || $charity ? 'Gross surplus' : 'Gross profit',
            'result' => $coop ? 'Surplus for the year' : ($charity ? 'Net income for the year' : 'Profit for the year'),
            'result_short' => $coop ? 'Surplus' : ($charity ? 'Net income' : 'Profit'),
            'loss' => $coop ? 'Deficit for the year' : ($charity ? 'Net expenditure for the year' : 'Loss for the year'),
            'retained' => $coop ? 'Accumulated surplus' : ($charity ? 'Unrestricted funds' : 'Retained earnings'),
        ];

        return __($terms[$key] ?? $key);
    }
}
