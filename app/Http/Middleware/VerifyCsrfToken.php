<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'webhooks/*',
        // PayPal está EN PAUSA (routes/web.php aborta 404 en el closure).
        // Sin esta exclusión, un POST era interceptado por CSRF antes de
        // llegar al closure y respondía 419 en vez de 404 (CRO #6).
        '*/checkout/paypal/create',
        '*/checkout/paypal/capture',
    ];
}
