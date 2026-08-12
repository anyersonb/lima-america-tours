<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Busca en la base de datos datos que pertenecen a OTRO cliente de la agencia.
 *
 * Por qué existe: este proyecto nació como fork del motor de Lima View Tours, y
 * ya son TRES los datos suyos que llegaron a estar publicados acá:
 *
 *   1. 2026-07-28 — el WhatsApp `51925886725` en el FAB, mandando los leads de
 *      esta agencia al teléfono de la otra.
 *   2. 2026-08-12 — una foto (`...lima-view-tours.jpg`) en la galería de la home.
 *   3. 2026-08-12 — la dirección `Av. Larcomar 233, Of. 410` en footer, JSON-LD,
 *      Términos y Privacidad de staging.
 *
 * Los tres tuvieron el mismo patrón: **el código ya estaba corregido y el dato
 * seguía vivo en la tabla `settings`**. Y los tres se escaparon porque en la base
 * local estaba limpio: el arrastre vivía solo en el servidor. Un test de PHPUnit
 * no puede verlo (corre sobre sqlite en memoria), así que la comprobación tiene
 * que poder ejecutarse CONTRA EL ENTORNO REAL:
 *
 *   sudo -u <usuario> <php> artisan data:audit-foreign
 *
 * Devuelve código de salida 1 si encuentra algo, para poder encadenarlo al final
 * de un deploy y que falle a la vista.
 */
class AuditForeignClientData extends Command
{
    protected $signature = 'data:audit-foreign {--json : Devuelve el resultado como JSON}';

    protected $description = 'Busca en la BD datos de otros clientes (Lima View Tours) que no deberían estar publicados acá';

    /**
     * Cada patrón con el motivo por el que se busca. Se comparan en minúsculas.
     *
     * Nada de patrones sueltos tipo "lima": el nombre de la ciudad aparece en
     * medio catálogo legítimamente. Solo cadenas que identifican al otro cliente
     * sin ambigüedad.
     *
     * @var array<string, string>
     */
    private const PATTERNS = [
        'lima-view' => 'nombre de archivo de un asset de Lima View Tours',
        'limaview' => 'nombre de archivo o handle de Lima View Tours',
        'lima view' => 'nombre del otro cliente en texto',
        'limaviewtours' => 'dominio del otro cliente',
        'larcomar 233' => 'dirección física de Lima View Tours',
        'larcomar ave' => 'dirección física de Lima View Tours (versión en inglés)',
        '925886725' => 'teléfono/WhatsApp de Lima View Tours',
        '925 886 725' => 'teléfono de Lima View Tours con espacios',
        'jr. lampa' => 'dirección heredada del fork, nunca confirmada por la clienta',
        'lvt-' => 'prefijo de código de reserva de Lima View Tours',
    ];

    public function handle(): int
    {
        $hits = [];

        foreach ($this->textColumns() as [$table, $column]) {
            foreach (self::PATTERNS as $needle => $reason) {
                try {
                    $rows = DB::table($table)
                        ->whereRaw("LOWER(CAST({$column} AS CHAR)) LIKE ?", ['%'.$needle.'%'])
                        ->limit(20)
                        ->get();
                } catch (\Throwable $e) {
                    // Una columna que el motor no sabe castear no invalida la auditoría.
                    continue;
                }

                foreach ($rows as $row) {
                    $value = (string) ($row->{$column} ?? '');
                    $id = $row->id ?? ($row->key ?? '?');

                    $hits[] = [
                        'table' => $table,
                        'column' => $column,
                        'id' => $id,
                        'pattern' => $needle,
                        'reason' => $reason,
                        'value' => mb_strimwidth(preg_replace('/\s+/', ' ', $value), 0, 90, '…'),
                    ];
                }
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($hits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $hits === [] ? self::SUCCESS : self::FAILURE;
        }

        if ($hits === []) {
            $this->info('Limpio: ningún dato de otro cliente en la base.');

            return self::SUCCESS;
        }

        $this->error(count($hits).' dato(s) de otro cliente en la base de este proyecto:');
        $this->newLine();

        $this->table(
            ['tabla', 'columna', 'id/key', 'patrón', 'valor', 'por qué importa'],
            array_map(fn (array $h) => [$h['table'], $h['column'], $h['id'], $h['pattern'], $h['value'], $h['reason']], $hits)
        );

        $this->newLine();
        $this->warn('Corregir el código NO alcanza: hay que limpiar estas filas en ESTE entorno.');

        return self::FAILURE;
    }

    /**
     * Columnas de texto de las tablas de contenido. Se descubren del esquema en
     * vez de listarlas a mano, así una tabla nueva entra sola en la auditoría.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function textColumns(): array
    {
        $tables = [
            'settings', 'tours', 'regions', 'categories', 'blog_posts', 'pages',
            'offers', 'guides', 'testimonials', 'bookings', 'contact_leads',
            'newsletter_subscribers',
        ];

        $out = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (Schema::getColumnListing($table) as $column) {
                $type = Schema::getColumnType($table, $column);

                if (in_array($type, ['string', 'text', 'json', 'longtext', 'mediumtext'], true)) {
                    $out[] = [$table, $column];
                }
            }
        }

        return $out;
    }
}
