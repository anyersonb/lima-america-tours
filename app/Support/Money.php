<?php

namespace App\Support;

/**
 * Formatea montos monetarios con el símbolo correcto de la moneda.
 *
 * El front antes hardcodeaba "$" para el precio de los tours; tras la
 * importación de los 26 tours reales del WordPress los precios quedaron
 * etiquetados en soles (PEN), por lo que "$360" se leía como dólares.
 *
 * MONEDA DEL SITIO (decisión del cliente, 2026-07-29): el negocio cobra en
 * **USD**. Antes esa respuesta estaba copiada como el literal `'PEN'` en ~30
 * sitios (vistas, controllers, Filament, correos) y cambiarla obligaba a
 * cazarlos uno por uno, con el riesgo de dejar la mitad del checkout en una
 * moneda y la otra mitad en otra — que es exactamente el bug que costó el
 * sobrecobro de ~3.7× detectado en la auditoría. Ahora hay UNA fuente:
 * `Money::site()`. Ver docs/pagos/PLAN-PASARELAS.md §13.
 */
class Money
{
    /** Símbolos conocidos por código de moneda ISO 4217. */
    private const SYMBOLS = [
        'PEN' => 'S/ ',
        'USD' => '$',
        'EUR' => '€',
    ];

    /** Monedas en las que el sitio sabe cobrar (las dos pasarelas las admiten). */
    public const SUPPORTED = ['USD', 'PEN'];

    /**
     * Moneda en la que opera el sitio: la que se muestra en el front, la que
     * se manda a la pasarela y la que se graba en `bookings.currency`.
     *
     * Sale del Setting `site_currency` (editable en el panel) con fallback a
     * `config('services.site_currency')`. Se valida contra SUPPORTED: un valor
     * suelto en la BD no puede dejar el checkout cobrando en una moneda que la
     * pasarela rechaza.
     *
     * Tolerante a fallos a propósito: se invoca desde vistas y correos, y en
     * tests unitarios (o antes de migrar) la tabla `settings` puede no existir.
     */
    public static function site(): string
    {
        $default = strtoupper((string) config('services.site_currency', 'USD'));

        try {
            $configured = strtoupper(trim((string) \App\Models\Setting::get('site_currency', '')));
        } catch (\Throwable $e) {
            $configured = '';
        }

        foreach ([$configured, $default] as $code) {
            if (in_array($code, self::SUPPORTED, true)) {
                return $code;
            }
        }

        return 'USD';
    }

    /**
     * @param  float|int|string  $amount
     */
    public static function format($amount, ?string $currency, int $decimals = 0): string
    {
        $value = (float) $amount;
        $code = strtoupper(trim((string) $currency)) ?: 'USD';
        $formatted = number_format($value, $decimals);

        if (isset(self::SYMBOLS[$code])) {
            return self::SYMBOLS[$code].$formatted;
        }

        return $code.' '.$formatted;
    }

    /**
     * Prefijo de moneda (símbolo o "CODE ") sin el monto, útil cuando el
     * número se compone en JS (ej. total dinámico según pasajeros).
     */
    public static function prefix(?string $currency): string
    {
        $code = strtoupper(trim((string) $currency)) ?: 'USD';

        return self::SYMBOLS[$code] ?? $code.' ';
    }
}
