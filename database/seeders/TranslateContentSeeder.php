<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Ejecuta todos los fragmentos de traduccion ES->EN/PT generados en
 * database/seeders/AutoTrans/ (un archivo por tour + Taxonomy).
 *
 * ⚠️ PELIGRO CONFIRMADO (2026-08-11) — NO correr sin re-auditar primero.
 * Cada fragmento `TourNTrans.php` escribe con
 * `DB::table('tours')->where('id', N)->update(...)`: un ID numérico
 * hardcodeado contra el estado de la BD del día en que se generó el
 * fragmento. Esa numeración quedó obsoleta: se comparó el `title_pt`/
 * contenido de los 16 fragmentos contra el tour que HOY ocupa cada ID en
 * `lima_america` y **15 de 16 ya no corresponden** (ejemplo: `Tour8Trans`
 * trae "MONTANHA ARCO-ÍRIS DE 7 CORES" pero hoy el id 8 es un tour de
 * prueba Lorem Ipsum sin publicar; `Tour9Trans` trae "LAGOA HUMANTAY" pero
 * el id 9 HOY es "Full day Nazca e Islas Ballestas desde Lima", un tour
 * real y publicado). Solo `Tour1Trans` (id 1, Huacachina + Ballestas)
 * sigue coincidiendo.
 *
 * Correr este seeder tal cual HOY escribiría contenido e imágenes de un
 * tour sobre otro completamente distinto — en varios casos, sobre un tour
 * real y publicado. Se corrigieron los placeholders `Rectangle 192XX.jpg` /
 * `image.jpg` / `image-1.jpg` que traían los 8 fragmentos que los tenían
 * (para que si algún día se re-audita y se corre, al menos no reintroduzca
 * el kit de imágenes degradado), pero el problema de fondo — el mapeo
 * ID→tour desactualizado — sigue sin resolver: requiere volver a emparejar
 * cada fragmento con su tour actual por contenido, uno por uno, y es una
 * tarea aparte de mayor alcance.
 *
 * Por eso este seeder ahora se niega a correr solo, y solo continúa si se
 * confirma explícitamente en un shell interactivo. En un `db:seed`
 * sin interacción (`--no-interaction`, CI, o instanciado directo como
 * `(new TranslateContentSeeder)->run()` sin comando asociado) NO hace nada.
 */
class TranslateContentSeeder extends Seeder
{
    public function run(): void
    {
        $dir = database_path('seeders/AutoTrans');
        if (! is_dir($dir)) {
            $this->command?->warn('No existe la carpeta AutoTrans; nada que ejecutar.');

            return;
        }

        if (! $this->confirmedSafeToRun()) {
            $this->command?->error(
                'TranslateContentSeeder: abortado. Los IDs de tour hardcodeados en '.
                'database/seeders/AutoTrans/TourNTrans.php están desactualizados '.
                '(15 de 16 ya no corresponden al tour real de ese ID en la BD actual). '.
                'Re-auditar el mapeo antes de forzar esto. Ver el docblock de esta clase.'
            );

            return;
        }

        $ran = 0;
        foreach (glob($dir.'/*.php') as $file) {
            require_once $file;
            $class = 'Database\\Seeders\\AutoTrans\\'.pathinfo($file, PATHINFO_FILENAME);
            if (class_exists($class)) {
                (new $class)->run();
                $ran++;
            }
        }

        $this->command?->info("TranslateContentSeeder: ejecutados {$ran} fragmentos.");
    }

    /**
     * Sin comando asociado (tests, `(new X)->run()` directo) o sin sesión
     * interactiva (`--no-interaction`, cron, CI) siempre es "no": el default
     * de `confirm()` es false y nunca hay un humano mirando la pantalla para
     * validar el mapeo ID→tour a mano.
     */
    private function confirmedSafeToRun(): bool
    {
        if (! $this->command) {
            return false;
        }

        return (bool) $this->command->confirm(
            '¿Ya re-auditaste que cada TourNTrans.php en database/seeders/AutoTrans '.
            'apunta al ID correcto del tour actual (no a otro tour ni a un registro de '.
            'prueba)? Si no lo hiciste, esto va a escribir contenido/imágenes de un tour '.
            'sobre otro.',
            false
        );
    }
}
