<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Moneda del sitio a USD (decisión del cliente, 2026-07-29).
 *
 * Los 26 tours se importaron del WordPress etiquetados como PEN. El cliente
 * confirmó que **los números ya son dólares** (no se convierten): el 720 de un
 * tour es USD 720. Por eso esta migración cambia SOLO la etiqueta de moneda y
 * NO toca la columna `price` — si además convirtiera, cobraría dos veces el
 * cambio.
 *
 * `bookings` NO se toca: cada reserva guarda la moneda con la que se cobró en
 * su momento y reescribirla haría que un histórico mienta sobre lo que el
 * cliente pagó de verdad.
 *
 * Reversible: down() devuelve los tours a PEN y borra el Setting, que es el
 * estado exacto de antes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tours')->where('currency', 'PEN')->update(['currency' => 'USD']);

        DB::table('settings')->updateOrInsert(
            ['key' => 'site_currency'],
            ['value' => 'USD', 'type' => 'string', 'group' => 'payments', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('tours')->where('currency', 'USD')->update(['currency' => 'PEN']);

        DB::table('settings')->where('key', 'site_currency')->delete();
    }
};
