<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Reglas de mapeo de las TAXONOMÍAS del WordPress de limaamericatours.com a
 * campos de la tabla `tours`. Puras y testeables (sin BD).
 *
 * - viaje-destacado ("Si"/"No") -> is_featured
 * - lugar (Lima/Callao/Nazca/Paracas/Cusco) -> región del modelo (Lima/Ica/Cusco)
 */
class WpTourMapper
{
    /** ¿La taxonomía viaje-destacado marca este tour como destacado? */
    public static function isFeatured(array $viajeDestacadoTerms): bool
    {
        foreach ($viajeDestacadoTerms as $t) {
            if (Str::lower(trim((string) $t)) === 'si') {
                return true;
            }
        }

        return false;
    }

    /**
     * Traduce el/los término(s) de la taxonomía `lugar` de WP a la región del
     * modelo de la app (que solo tiene Lima / Ica / Cusco). Callao cuenta como
     * Lima; Nazca y Paracas como Ica. Devuelve null si no hay término conocido
     * (el importador cae entonces a la inferencia por título).
     */
    public static function regionKeyFromLugar(array $lugarTerms): ?string
    {
        $map = [
            'lima' => 'Lima',
            'callao' => 'Lima',
            'ica' => 'Ica',
            'nazca' => 'Ica',
            'paracas' => 'Ica',
            'huacachina' => 'Ica',
            'cusco' => 'Cusco',
            'cuzco' => 'Cusco',
        ];
        foreach ($lugarTerms as $t) {
            $key = Str::lower(trim(strtr((string) $t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u'])));
            if (isset($map[$key])) {
                return $map[$key];
            }
        }

        return null;
    }

    /**
     * Extrae la anticipación mínima (en horas) para reservar desde la meta
     * `agendar` del WordPress ("24", "24 Horas", "  12 ", "abc"). Devuelve
     * null cuando no hay un entero al inicio del valor.
     */
    public static function advanceHours(?string $raw): ?int
    {
        $value = trim((string) $raw);
        if ($value === '' || ! preg_match('/^(\d+)/', $value, $m)) {
            return null;
        }

        return (int) $m[1];
    }
}
