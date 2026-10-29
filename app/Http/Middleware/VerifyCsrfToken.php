<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // Batch 40: Stripe posts payment events server-to-server with no
        // session or CSRF token; the endpoint verifies Stripe's own
        // signature instead (StorefrontController::stripeWebhook()).
        'store-webhooks/*',
    ];
}
