<?php

namespace App\Console\Commands;

use App\Models\Testimonial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Importa a `testimonials` las reseñas REALES de Tripadvisor que el sitio de
 * producción (limaamericatours.com, WordPress) ya publica.
 *
 * Por qué existe:
 * El jefe pidió el 2026-08-24 que la página de Reseñas mostrara también las de
 * Tripadvisor, y que la configuración de comentarios de producción se trajera a
 * staging. En producción esas reseñas las sirve el plugin TrustIndex, que las
 * guarda en `wp_trustindex_tripadvisor_reviews`. De ahí salió el JSON de
 * `database/data/tripadvisor-reviews.json`.
 *
 * NO es un reemplazo de la Content API de Tripadvisor
 * (App\Services\TripadvisorReviewsService), que sigue apagada por falta de API
 * Key. Es la misma decisión que ya se tomó con las 13 reseñas del WordPress:
 * contenido real del cliente, cargado como testimonios del CMS y editable desde
 * Filament → Testimonios. Si algún día se habilita la API, `ReviewAggregator`
 * descarta solos los testimonios de origen "Tripadvisor" para no duplicarlos.
 *
 * Idempotente por `external_ref` ("ta_review_{reviewId}").
 *
 * Espeja el patrón de App\Console\Commands\ImportWpReviews.
 */
class ImportTripadvisorReviews extends Command
{
    /**
     * El JSON vive DENTRO del repo (`database/data/`), no en `storage/app/`
     * como el de ImportWpReviews. A propósito: `storage/app/.gitignore` ignora
     * todo, así que el `wp-import/reviews.json` de aquel comando quedó fuera
     * del control de versiones y hoy no se puede volver a correr en un
     * servidor limpio sin ir a buscar el archivo a mano. Son 5 KB de contenido
     * del cliente; que viajen con el código es lo que hace la carga
     * reproducible.
     */
    protected $signature = 'reviews:import-tripadvisor
        {--json=database/data/tripadvisor-reviews.json : Ruta del JSON, relativa a la raíz del proyecto}
        {--dry-run : Solo muestra lo que haría, sin escribir}';

    protected $description = 'Importa las reseñas de Tripadvisor publicadas en producción a testimonials';

    public function handle(): int
    {
        $jsonPath = base_path($this->option('json'));

        if (! File::exists($jsonPath)) {
            $this->error("No existe el JSON: $jsonPath");

            return self::FAILURE;
        }

        $data = json_decode(File::get($jsonPath), true);
        if (! is_array($data)) {
            $this->error('JSON inválido.');

            return self::FAILURE;
        }

        // Las ocultas en el plugin de producción están ocultas a propósito: no
        // se traen. Publicar acá una reseña que el cliente escondió allá sería
        // deshacerle una decisión editorial sin avisarle.
        $data = array_values(array_filter(
            $data,
            fn ($r) => is_array($r) && empty($r['hidden']) && trim((string) ($r['text'] ?? '')) !== ''
        ));

        // Más nuevas primero, que es como las muestra Tripadvisor.
        usort($data, fn ($a, $b) => [$b['date'] ?? '', $b['reviewId'] ?? ''] <=> [$a['date'] ?? '', $a['reviewId'] ?? '']);

        $dry = (bool) $this->option('dry-run');
        $created = 0;
        $updated = 0;

        foreach ($data as $index => $r) {
            $externalRef = 'ta_review_'.$r['reviewId'];

            $attrs = [
                'name' => trim((string) ($r['user'] ?? '')) ?: 'Viajero',
                'country' => null,
                // Sin foto: las de Tripadvisor son URLs de su CDN (y la mitad
                // son el avatar genérico del plugin, no una foto real). El
                // mismo criterio que el import del WordPress — la tarjeta pinta
                // el círculo con la inicial, que no depende de un tercero ni
                // se rompe si esa URL cambia.
                'avatar' => null,
                'quote_es' => self::normalizeText((string) ($r['text'] ?? '')),
                'quote_en' => null,
                'quote_pt' => null,
                'rating' => self::rating($r['rating'] ?? null),
                'source' => 'Tripadvisor',
                'tour_id' => null,
                'is_active' => true,
                'is_featured' => false,
                'order' => $index,
                'review_date' => (string) ($r['date'] ?? ''),
                'reviewed_at' => $r['date'] ?? null,
            ];

            if ($dry) {
                $this->line("[dry] $externalRef — {$attrs['name']} ({$attrs['rating']}★, {$attrs['review_date']})");

                continue;
            }

            $existing = Testimonial::where('external_ref', $externalRef)->first();
            $testimonial = Testimonial::updateOrCreate(['external_ref' => $externalRef], $attrs);
            $existing ? $updated++ : $created++;
        }

        if ($dry) {
            $this->info(count($data).' reseñas de Tripadvisor listas para importar (dry-run, no se escribió nada).');

            return self::SUCCESS;
        }

        $this->info("Tripadvisor: $created creadas, $updated actualizadas.");

        return self::SUCCESS;
    }

    /**
     * El texto viene del plugin como "<strong>Titular</strong>\nCuerpo".
     *
     * Se quita el HTML y se pegan titular y cuerpo en un solo párrafo, que es
     * lo que la tarjeta de reseña sabe pintar (no tiene campo de titular). Si
     * el titular no termina en signo de puntuación se le agrega un punto: sin
     * eso el HTML colapsa el salto de línea y queda "Lindo recorrido Fue un
     * recorrido…". Es normalización de formato — NO se reescribe, resume ni
     * traduce ni una palabra del visitante.
     */
    public static function normalizeText(string $raw): string
    {
        $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match('#^\s*<strong>(.*?)</strong>\s*(.*)$#su', $raw, $m)) {
            $headline = trim(strip_tags($m[1]));
            $body = trim(strip_tags($m[2]));

            if ($headline !== '' && $body !== '') {
                if (! preg_match('/[.!?…]$/u', $headline)) {
                    $headline .= '.';
                }

                return $headline.' '.preg_replace('/\s+/u', ' ', $body);
            }

            $raw = $headline !== '' ? $headline : $body;
        }

        return trim(preg_replace('/\s+/u', ' ', strip_tags($raw)));
    }

    /** Rating acotado a 1–5; sin dato, 5 (todas las de este perfil lo son). */
    public static function rating(mixed $value): float
    {
        $n = (float) $value;

        return $n >= 1 && $n <= 5 ? round($n, 1) : 5.0;
    }
}
