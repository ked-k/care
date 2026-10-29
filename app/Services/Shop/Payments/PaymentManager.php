<?php

namespace App\Services\Shop\Payments;

use App\Models\Shop\ShopStore;

class PaymentManager
{
    /** Checkout order of the payment options. */
    public const GATEWAYS = [
        'stripe' => StripeGateway::class,
        'paypal' => PayPalGateway::class,
        'bank_transfer' => BankTransferGateway::class,
        'pay_on_delivery' => PayOnDeliveryGateway::class,
    ];

    public static function get(string $code): ?PaymentGateway
    {
        $class = self::GATEWAYS[$code] ?? null;

        return $class ? app($class) : null;
    }

    /** @return array<string, PaymentGateway> */
    public static function all(): array
    {
        return array_map(fn ($class) => app($class), self::GATEWAYS);
    }

    /** @return array<string, PaymentGateway> the methods a customer can pick right now */
    public static function available(ShopStore $store): array
    {
        return array_filter(self::all(), fn (PaymentGateway $g) => $g->isAvailable($store));
    }

    public static function label(string $code, ?ShopStore $store = null): string
    {
        return match ($code) {
            'stripe' => __('Card'),
            'paypal' => 'PayPal',
            'bank_transfer' => __('Bank transfer'),
            'pay_on_delivery' => __('Pay on delivery or collection'),
            'manual' => __('Recorded manually'),
            default => ucfirst(str_replace('_', ' ', $code)),
        };
    }
}
