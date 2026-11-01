<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Services\Accounting\LedgerException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use RuntimeException;

/**
 * Batch 43: base for every page in the Finance & Accounts workspace.
 * Needs the manage_accounts permission (re-checked on every Livewire
 * request) and an agency; everything is scoped to that agency's books.
 */
abstract class AccountsComponent extends Component
{
    public function boot(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->canManageAccounts(), 403);
        abort_unless($user->agency_id, 403, __('Your account isn’t linked to an agency, so there are no accounts to show.'));
    }

    protected function agencyId(): string
    {
        return Auth::user()->agency_id;
    }

    protected function settings(): AccountingSetting
    {
        return AccountingSetting::forAgency($this->agencyId());
    }

    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }

    /** Run an action and turn a ledger refusal into a friendly toast. */
    protected function attempt(callable $fn, ?string $success = null): mixed
    {
        try {
            $result = $fn();
            $success && $this->toast($success);

            return $result;
        } catch (LedgerException|RuntimeException $e) {
            $this->toast($e->getMessage(), 'error');

            return null;
        }
    }

    protected function accountOptions(?array $types = null, bool $includeMoney = true)
    {
        return Account::where('agency_id', $this->agencyId())->where('is_active', true)
            ->when($types, fn ($q) => $q->whereIn('type', $types))
            ->when(! $includeMoney, fn ($q) => $q->whereNotIn('subtype', Account::MONEY_SUBTYPES))
            ->orderBy('code')->get(['id', 'code', 'name', 'type', 'subtype']);
    }

    protected function moneyAccounts()
    {
        return Account::where('agency_id', $this->agencyId())->money()->where('is_active', true)->orderBy('code')->get();
    }

    protected function page(string $view, array $data = [], ?string $title = null)
    {
        return view($view, $data + ['cur' => $this->settings()])
            ->layout('layouts.admin-layout')
            ->title($title ? $title.' — '.__('Finance') : __('Finance'));
    }
}
