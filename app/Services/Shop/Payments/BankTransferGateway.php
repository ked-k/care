<?php

namespace App\Services\Shop\Payments;

use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopStore;
use Illuminate\Http\Request;

/** Customer pays by bank transfer using the order number as the reference; staff confirm receipt. */
class BankTransferGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'bank_transfer';
    }

    public function label(ShopStore $store): string
    {
        return __('Bank transfer');
    }

    public function description(ShopStore $store): string
    {
        return __('We’ll show our bank details after you place the order. Your order is dispatched once the payment arrives.');
    }

    public function icon(): string
    {
        return 'fas fa-university';
    }

    public function isOnline(): bool
    {
        return false;
    }

    public function isAvailable(ShopStore $store): bool
    {
        return (bool) $store->paymentSetting('bank_transfer', 'enabled')
            && filled($store->paymentSetting('bank_transfer', 'account_number'));
    }

    public function settingsFields(): array
    {
        return [
            ['key' => 'account_name', 'label' => 'Account name', 'type' => 'text'],
            ['key' => 'bank_name', 'label' => 'Bank', 'type' => 'text'],
            ['key' => 'sort_code', 'label' => 'Sort code', 'type' => 'text'],
            ['key' => 'account_number', 'label' => 'Account number', 'type' => 'text'],
            ['key' => 'instructions', 'label' => 'Extra instructions', 'type' => 'textarea', 'hint' => 'Optional. Shown with the bank details.'],
        ];
    }

    public function start(ShopOrder $order): ?string
    {
        return null;
    }

    public function confirmReturn(ShopOrder $order, Request $request): bool
    {
        return false;
    }

    /** @return array<string, string> label => value for display */
    public static function details(ShopStore $store): array
    {
        return array_filter([
            __('Account name') => $store->paymentSetting('bank_transfer', 'account_name'),
            __('Bank') => $store->paymentSetting('bank_transfer', 'bank_name'),
            __('Sort code') => $store->paymentSetting('bank_transfer', 'sort_code'),
            __('Account number') => $store->paymentSetting('bank_transfer', 'account_number'),
        ]);
    }
}
