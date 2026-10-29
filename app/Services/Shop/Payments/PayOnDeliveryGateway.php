<?php

namespace App\Services\Shop\Payments;

use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopStore;
use Illuminate\Http\Request;

/** Pay by card or cash when the order is delivered or collected; staff record it afterwards. */
class PayOnDeliveryGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'pay_on_delivery';
    }

    public function label(ShopStore $store): string
    {
        return __('Pay on delivery or collection');
    }

    public function description(ShopStore $store): string
    {
        return $store->paymentSetting('pay_on_delivery', 'instructions')
            ?: __('Pay by card or cash when your order arrives or when you collect it.');
    }

    public function icon(): string
    {
        return 'fas fa-hand-holding-usd';
    }

    public function isOnline(): bool
    {
        return false;
    }

    public function isAvailable(ShopStore $store): bool
    {
        return (bool) $store->paymentSetting('pay_on_delivery', 'enabled');
    }

    public function settingsFields(): array
    {
        return [
            ['key' => 'instructions', 'label' => 'What customers see', 'type' => 'textarea', 'hint' => 'e.g. "Local deliveries in Croydon only. Card or cash accepted."'],
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
}
