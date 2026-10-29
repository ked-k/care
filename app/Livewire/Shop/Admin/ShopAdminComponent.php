<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopStore;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Batch 40: base for every page in the Shop workspace. Scopes everything to
 * the signed-in staff member's own agency's store (created on first visit),
 * and requires the manage_shop permission — checked again on every Livewire
 * request via boot(), not just on first load.
 */
abstract class ShopAdminComponent extends Component
{
    private ?ShopStore $resolvedStore = null;

    public function boot(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->canManageShop(), 403);
        abort_unless($user->agency, 403, __('Your account isn’t linked to an agency, so there’s no shop to manage.'));
    }

    protected function store(): ShopStore
    {
        return $this->resolvedStore ??= ShopStore::forAgency(Auth::user()->agency);
    }

    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }

    protected function adminPage(string $view, array $data = [], ?string $title = null)
    {
        return view($view, $data + ['store' => $this->store()])
            ->layout('layouts.admin-layout')
            ->title($title ? $title.' — '.__('Shop') : __('Shop'));
    }
}
