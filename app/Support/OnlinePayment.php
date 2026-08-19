<?php

namespace App\Support;

use App\Services\PayPalService;
use App\Services\PaymentService;

/**
 * ¿Hay alguna pasarela capaz de cobrar de verdad ahora mismo?
 *
 * La expresión vivía duplicada en CartController::checkout(). Cuando la ficha
 * de tour necesitó la misma respuesta (para no prometer "Pago 100% seguro" sin
 * pasarela detrás, ver docs/rebrand/inventario/00-VALIDACION-STAGING.md) se
 * extrajo acá en vez de copiarla por tercera vez.
 *
 * CheckoutController NO usa este helper a propósito: ahí se necesitan las dos
 * banderas por separado (Culqi y PayPal) para pintar cada botón, no el "o".
 *
 * PayPal además exige que la moneda del sitio sea una de las que soporta: con
 * las llaves cargadas pero el sitio en una moneda no soportada, el botón
 * llevaría al cliente hasta el último clic para fallar ahí.
 */
class OnlinePayment
{
    public static function available(): bool
    {
        if (app(PaymentService::class)->isConfigured()) {
            return true;
        }

        $paypal = app(PayPalService::class);

        return $paypal->isConfigured()
            && in_array(Money::site(), PayPalService::SUPPORTED_CURRENCIES, true);
    }
}
