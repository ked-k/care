<?php

namespace App\Services\Shop;

/** VAT helpers. Amounts are pounds (floats), rounded to pennies by the caller. */
final class Vat
{
    /**
     * The VAT in an amount.
     *  - inclusive: £12.00 at 20% contains £2.00 VAT
     *  - exclusive: £10.00 at 20% attracts £2.00 VAT on top
     */
    public static function of(float $amount, float $rate, bool $inclusive): float
    {
        if ($rate <= 0 || $amount <= 0) {
            return 0.0;
        }

        return $inclusive
            ? $amount - $amount / (1 + $rate / 100)
            : $amount * $rate / 100;
    }

    /** Convert a VAT-inclusive price to ex-VAT, to the penny. */
    public static function exclusive(float $gross, float $rate): float
    {
        return round($gross / (1 + $rate / 100), 2);
    }
}
