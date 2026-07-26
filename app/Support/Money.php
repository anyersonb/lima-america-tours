<?php

namespace App\Support;

/**
 * Formatea montos monetarios con el símbolo correcto de la moneda.
 *
 * El front antes hardcodeaba "$" para el precio de los tours; tras la
 * importación de los 26 tours reales del WordPress, los precios en la tabla
 * `tours` están en soles (PEN), por lo que "$360" se leía como dólares.
 */
class Money
{
    /** Símbolos conocidos por código de moneda ISO 4217. */
    private const SYMBOLS = [
        'PEN' => 'S/ ',
        'USD' => '$',
        'EUR' => '€',
    ];

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
