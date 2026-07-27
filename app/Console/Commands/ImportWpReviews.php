<?php

namespace App\Console\Commands;

use App\Models\Testimonial;
use App\Support\WpReviewMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Imports the 13 real reviews exported from the WordPress of
 * limaamericatours.com (storage/app/wp-import/reviews.json) into the
 * `testimonials` table. These reviews all belong to the free tour (guide
 * "Augusto") and are not tied to any specific tour, so they are stored as
 * global testimonials (tour_id = null). Idempotent by `external_ref`
 * ("wp_review_{wp_id}"). Mirrors the pattern of App\Console\Commands\ImportWpBlog.
 * See docs/data/RECONCILIACION-REVIEWS-WP.md.
 */
class ImportWpReviews extends Command
{
    protected $signature = 'reviews:import-wp
        {--json=wp-import/reviews.json : Ruta del JSON dentro de storage/app}
        {--dry-run : Solo muestra lo que haría, sin escribir}';

    protected $description = 'Importa las reseñas reales del WordPress (limaamericatours.com) a testimonials (globales)';

    public function handle(): int
    {
        $jsonPath = storage_path('app/'.$this->option('json'));

        if (! File::exists($jsonPath)) {
            $this->error("No existe el JSON: $jsonPath");

            return self::FAILURE;
        }

        $data = json_decode(File::get($jsonPath), true);
        if (! is_array($data)) {
            $this->error('JSON inválido.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');

        // Orden de exhibición: por fecha real de la reseña (best-effort a
        // partir de meta.fecha) y, si no se puede parsear, por id de WP
        // ascendente. Ambos son estables y determinísticos entre corridas.
        usort($data, function (array $a, array $b) {
            $dateA = WpReviewMapper::sortableDate($a['meta']['fecha'] ?? null);
            $dateB = WpReviewMapper::sortableDate($b['meta']['fecha'] ?? null);

            return [$dateA ?? '9999-99-99', $a['id']] <=> [$dateB ?? '9999-99-99', $b['id']];
        });

        $created = 0;
        $updated = 0;

        foreach ($data as $index => $w) {
            $meta = $w['meta'] ?? [];
            $externalRef = 'wp_review_'.$w['id'];

            $attrs = [
                'name' => WpReviewMapper::resolveName($meta, (string) ($w['title'] ?? '')),
                'country' => WpReviewMapper::cleanCountry($meta['procedencia'] ?? null),
                'avatar' => null,
                'quote_es' => WpReviewMapper::cleanQuote((string) ($meta['comentario'] ?? '')),
                'quote_en' => null,
                'rating' => WpReviewMapper::rating($meta['valoracion'] ?? null),
                'source' => 'Google',
                'is_featured' => false,
                'is_active' => true,
                'order' => $index,
                'tour_id' => null,
                'review_date' => WpReviewMapper::reviewDate($meta['fecha'] ?? null),
                'traveled_as' => WpReviewMapper::travelType($meta['viajo-en'] ?? null),
            ];

            if ($dry) {
                $this->line(sprintf('[dry] %-20s %-12s %s "%s"',
                    $attrs['name'], $attrs['country'] ?? '-', $externalRef,
                    \Illuminate\Support\Str::limit($attrs['quote_es'], 40)));

                continue;
            }

            $existing = Testimonial::where('external_ref', $externalRef)->first();
            Testimonial::updateOrCreate(['external_ref' => $externalRef], $attrs);
            $existing ? $updated++ : $created++;
        }

        $this->newLine();
        if ($dry) {
            $this->info('Dry-run: '.count($data).' reseñas se importarían (nada escrito).');
        } else {
            $this->info("Reseñas importadas: creadas=$created, actualizadas=$updated (total ".count($data).')');
        }

        return self::SUCCESS;
    }
}
