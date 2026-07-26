<?php

namespace App\Services;

use App\Exceptions\RealChargeBlockedException;
use Illuminate\Support\Facades\Log;

/**
 * "Guarda anti-cobro-real": single choke-point that stops a real (LIVE)
 * charge from firing by accident while the sandbox flow is being validated
 * (Culqi + PayPal, browser tests against the qa DB, keys de prueba).
 *
 * Rule:
 *  - TEST/sandbox credentials -> always allowed, regardless of environment.
 *  - LIVE credentials -> allowed ONLY when config('payments.live') is true
 *    AND app()->environment() === 'production'. Any other combination
 *    (including PAYMENTS_LIVE=true on a non-production environment) blocks
 *    the charge.
 *
 * Call assertChargeAllowed() BEFORE the actual charge/capture HTTP call:
 *  - App\Services\PaymentService::createCharge() (Culqi)
 *  - App\Services\PayPalService::captureOrder() (PayPal)
 */
class PaymentGuard
{
    /**
     * @param  string  $gateway  'culqi' | 'paypal'
     * @param  string  $credential  Culqi: the secret key (sk_test_... / sk_live_...).
     *                              PayPal: the configured mode (sandbox|live).
     *
     * @throws RealChargeBlockedException when a LIVE charge is not authorized.
     */
    public static function assertChargeAllowed(string $gateway, string $credential): void
    {
        if (! self::isLive($gateway, $credential)) {
            return;
        }

        if (self::liveChargesAuthorized()) {
            return;
        }

        Log::warning('payment_guard.real_charge_blocked', [
            'gateway' => $gateway,
            'payments_live' => (bool) config('payments.live'),
            'environment' => app()->environment(),
        ]);

        throw new RealChargeBlockedException(sprintf(
            'Cobro real bloqueado: se detectaron credenciales LIVE de %s en un entorno no productivo (%s). '.
            'Un cobro real solo se permite con PAYMENTS_LIVE=true Y APP_ENV=production.',
            strtoupper($gateway),
            app()->environment()
        ));
    }

    /**
     * Detects whether the given credential/mode identifies LIVE (real money)
     * as opposed to TEST/sandbox credentials.
     */
    public static function isLive(string $gateway, string $credential): bool
    {
        return match (strtolower($gateway)) {
            'culqi' => str_starts_with($credential, 'sk_live_'),
            'paypal' => strtolower($credential) === 'live',
            default => false,
        };
    }

    /**
     * The only condition under which a LIVE charge is authorized to fire.
     */
    private static function liveChargesAuthorized(): bool
    {
        return (bool) config('payments.live') && app()->environment('production');
    }
}
