<?php

namespace App\Services\Shop\Payments;

use RuntimeException;

/** A payment provider refused or couldn't be reached. The message is safe to show customers. */
class PaymentException extends RuntimeException
{
}
