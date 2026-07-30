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
        // OJO: las rutas de PayPal estuvieron exentas mientras eran closures
        // muertos que abortaban 404 (la exclusión existía solo para que un POST
        // respondiera 404 y no 419). Al reactivarlas (2026-07-29) vuelven a
        // exigir CSRF: son endpoints que crean y capturan órdenes de pago, y
        // exentos se podrían disparar desde otro sitio. El SDK de PayPal las
        // llama por fetch con el header X-CSRF-TOKEN.
    ];
}
