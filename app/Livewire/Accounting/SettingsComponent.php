<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Journal;
use App\Services\Accounting\Backfill;
use App\Services\Accounting\DefaultChart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Batch 43: one agency's accounting set-up — organisation details,
 * currency, financial year, numbering, VAT, automatic posting, which
 * account each automatic job posts to, and the year-end appropriations
 * (reserve funds etc.) used by co-operatives.
 */
class SettingsComponent extends AccountsComponent
{
    public array $form = [];
    public array $numbering = [];
    public array $auto = [];
    public array $mapping = [];
    public array $appropriations = [];
    public ?array $backfill = null;

    public function mount(): void
    {
        $s = $this->settings();
        $this->form = [
            'legal_name' => (string) $s->legal_name, 'registration_number' => (string) $s->registration_number,
            'tax_number' => (string) $s->tax_number, 'address' => (string) $s->address, 'organisation_type' => $s->organisation_type,
            'currency_code' => $s->currency_code, 'currency_symbol' => $s->currency_symbol, 'currency_decimals' => (int) $s->currency_decimals,
            'fy_start_month' => (int) $s->fy_start_month, 'fy_start_day' => (int) $s->fy_start_day,
            'payment_terms_days' => (int) $s->payment_terms_days, 'vat_registered' => (bool) $s->vat_registered,
            'default_vat_rate' => (float) $s->default_vat_rate, 'invoice_footer' => (string) $s->invoice_footer,
            'posting_from' => (string) $s->posting_from?->toDateString(),
        ];
        foreach (AccountingSetting::NUMBERING_DEFAULTS as $k => [$prefix, $next]) {
            $this->numbering[$k] = ['prefix' => $s->numbering[$k]['prefix'] ?? $prefix, 'next' => (int) ($s->numbering[$k]['next'] ?? $next)];
        }
        foreach (AccountingSetting::AUTO_POST_DEFAULTS as $k => $v) {
            $this->auto[$k] = $s->autoPosts($k);
        }
        foreach (array_keys(DefaultChart::SYSTEM_ACCOUNTS) as $key) {
            $this->mapping[$key] = $s->account($key)->id;
        }
        $this->appropriations = array_values($s->appropriations ?? []);
    }

    public function updatedFormCurrencyCode($code): void
    {
        $code = strtoupper(trim($code));
        $this->form['currency_code'] = $code;
        if (isset(AccountingSetting::CURRENCIES[$code])) {
            $this->form['currency_symbol'] = AccountingSetting::CURRENCIES[$code];
            $this->form['currency_decimals'] = in_array($code, ['UGX', 'RWF', 'TZS'], true) ? 0 : 2;
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.legal_name' => 'nullable|string|max:255',
            'form.registration_number' => 'nullable|string|max:80',
            'form.tax_number' => 'nullable|string|max:80',
            'form.address' => 'nullable|string|max:1000',
            'form.organisation_type' => ['required', Rule::in(array_keys(AccountingSetting::ORGANISATION_TYPES))],
            'form.currency_code' => 'required|string|size:3|alpha',
            'form.currency_symbol' => 'required|string|max:8',
            'form.currency_decimals' => 'required|integer|min:0|max:3',
            'form.fy_start_month' => 'required|integer|min:1|max:12',
            'form.fy_start_day' => 'required|integer|min:1|max:28',
            'form.payment_terms_days' => 'required|integer|min:0|max:365',
            'form.default_vat_rate' => 'required|numeric|min:0|max:100',
            'form.invoice_footer' => 'nullable|string|max:2000',
            'form.posting_from' => 'nullable|date',
            'numbering.*.prefix' => 'required|string|max:12',
            'numbering.*.next' => 'required|integer|min:1',
            'appropriations.*.name' => 'required|string|max:120',
            'appropriations.*.account_id' => 'required|string',
            'appropriations.*.basis' => 'required|in:surplus,turnover,fixed',
            'appropriations.*.rate' => 'required|numeric|min:0',
        ]);
        $s = $this->settings();
        $hasJournals = Journal::where('agency_id', $this->agencyId())->exists();
        if ($hasJournals && ((int) $this->form['fy_start_month'] !== (int) $s->fy_start_month || (int) $this->form['fy_start_day'] !== (int) $s->fy_start_day)) {
            $this->addError('form.fy_start_month', __('The year start can’t change once entries are posted — set up the years you need under Financial years instead.'));

            return;
        }
        $accounts = Account::where('agency_id', $this->agencyId())->pluck('id')->all();
        $map = [];
        foreach ($this->mapping as $key => $id) {
            if (in_array($id, $accounts, true)) {
                $map[$key] = $id;
            }
        }
        $data = $this->form;
        $data['currency_code'] = strtoupper($data['currency_code']);
        $data['vat_registered'] = (bool) $data['vat_registered'];
        $data['posting_from'] = $data['posting_from'] ?: null;
        foreach (['legal_name', 'registration_number', 'tax_number', 'address', 'invoice_footer'] as $k) {
            $data[$k] = trim((string) $data[$k]) ?: null;
        }
        $numbering = [];
        foreach ($this->numbering as $k => $n) {
            $numbering[$k] = ['prefix' => trim($n['prefix']), 'next' => (int) $n['next']];
        }
        $s->update($data + [
            'numbering' => $numbering,
            'auto_post' => array_map('boolval', $this->auto),
            'accounts' => $map,
            'appropriations' => array_values(array_map(fn ($a) => ['name' => $a['name'], 'account_id' => $a['account_id'], 'basis' => $a['basis'], 'rate' => (float) $a['rate']], $this->appropriations)),
        ]);
        AccountingSetting::forgetCache();
        activity('accounts')->causedBy(Auth::user())->log(__('Accounting settings changed'));
        $this->toast(__('Settings saved.'));
    }

    public function postPast(): void
    {
        $this->save();
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }
        $this->backfill = Backfill::run($this->agencyId());
        $failed = collect($this->backfill)->sum('failed');
        $this->toast($failed ? __('Done — :n couldn’t post. See the Finance dashboard.', ['n' => $failed]) : __('Everything is posted.'), $failed ? 'warning' : 'success');
    }

    public function addAppropriation(): void
    {
        $this->appropriations[] = ['name' => '', 'account_id' => '', 'basis' => 'surplus', 'rate' => 0];
    }

    public function removeAppropriation(int $i): void
    {
        unset($this->appropriations[$i]);
        $this->appropriations = array_values($this->appropriations);
    }

    /** The usual Uganda co-operative appropriations, with reserve accounts to match. */
    public function cooperativeTemplate(): void
    {
        $make = function (string $name, int $code) {
            $a = Account::where('agency_id', $this->agencyId())->where('name', $name)->first();
            while (! $a && Account::where('agency_id', $this->agencyId())->where('code', (string) $code)->exists()) {
                $code++;
            }

            return $a ?? Account::create(['agency_id' => $this->agencyId(), 'code' => (string) $code, 'name' => $name, 'type' => 'equity', 'subtype' => 'reserves']);
        };
        $this->appropriations = [
            ['name' => __('Statutory reserve'), 'account_id' => $make('Statutory Reserve', 3591)->id, 'basis' => 'surplus', 'rate' => 10],
            ['name' => __('Development revolving fund'), 'account_id' => $make('Development Revolving Fund', 3592)->id, 'basis' => 'surplus', 'rate' => 5],
            ['name' => __('National education fund'), 'account_id' => $make('Education Fund', 3593)->id, 'basis' => 'turnover', 'rate' => 1],
        ];
        $this->form['organisation_type'] = 'cooperative';
        $this->toast(__('Co-operative appropriations filled in — check the rates, then Save.'));
    }

    public function render()
    {
        $all = Account::where('agency_id', $this->agencyId())->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'type', 'subtype']);
        $typeOf = fn (string $key) => \App\Models\Accounting\Account::SUBTYPES[DefaultChart::CHART[DefaultChart::SYSTEM_ACCOUNTS[$key]][1]][0];

        return $this->page('livewire.accounting.settings', [
            'accountsByType' => $all->groupBy('type'),
            'typeOf' => $typeOf,
            'equity' => $all->where('type', 'equity'),
            'hasJournals' => Journal::where('agency_id', $this->agencyId())->exists(),
            'months' => collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Illuminate\Support\Carbon::create(2000, $m, 1)->format('F')]),
        ], __('Settings'));
    }
}
