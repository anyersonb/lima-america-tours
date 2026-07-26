<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Region;
use App\Models\Tour;
use App\Support\WpTourParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Importa los 26 tours reales exportados del WordPress de limaamericatours.com
 * (storage/app/wp-import/tours.json + storage/app/wp-import/images/) a la tabla
 * `tours`. Idempotente por slug (updateOrCreate). Copia las imágenes a
 * storage/app/public/tours/. Ver docs/data/IMPORT-TOURS-WP.md.
 */
class ImportWpTours extends Command
{
    protected $signature = 'tours:import-wp
        {--json=wp-import/tours.json : Ruta del JSON dentro de storage/app}
        {--images=wp-import/images : Carpeta con las imágenes dentro de storage/app}
        {--keep-demo : No despublicar los tours demo previos}
        {--dry-run : Solo muestra lo que haría, sin escribir}';

    protected $description = 'Importa los tours reales del WordPress (limaamericatours.com) a la BD';

    private string $imgSrcBase;

    public function handle(): int
    {
        $jsonPath = storage_path('app/'.$this->option('json'));
        $this->imgSrcBase = storage_path('app/'.$this->option('images'));

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
        $destDir = storage_path('app/public/tours');
        if (! $dry) {
            File::ensureDirectoryExists($destDir);
        }

        $regions = Region::pluck('id', 'name_es');       // ['Lima'=>1,...]
        $categories = Category::pluck('id', 'name_es');

        $imported = [];
        $created = 0;
        $updated = 0;
        $imgCopied = 0;

        foreach ($data as $w) {
            $meta = $w['meta'] ?? [];
            $slug = $w['slug'] ?: Str::slug($w['title']);

            $split = WpTourParser::splitIncludesExcludes($meta['que-incluye'] ?? '');
            $price = (float) preg_replace('/[^0-9.]/', '', (string) ($meta['_apartment_price'] ?? 0));

            // Imágenes -----------------------------------------------------
            $cover = $this->copyImage($w['image_urls']['imagen-post'] ?? null, $dry, $imgCopied);
            $seoImg = $this->copyImage($w['image_urls']['imagen_header'] ?? null, $dry, $imgCopied);
            $gallery = [];
            $galSources = array_merge(
                $w['galeria_urls'] ?? [],
                array_values(array_filter([
                    $w['image_urls']['foto_1'] ?? null, $w['image_urls']['foto_2'] ?? null,
                    $w['image_urls']['foto_3'] ?? null, $w['image_urls']['foto_4'] ?? null,
                    $w['image_urls']['foto_5'] ?? null,
                ]))
            );
            foreach ($galSources as $g) {
                $p = $this->copyImage($g, $dry, $imgCopied);
                if ($p && ! in_array($p, $gallery, true)) {
                    $gallery[] = $p;
                }
            }
            if (! $cover && $gallery) {
                $cover = $gallery[0];
            }

            // Notas: días de salida + pasajeros mínimos --------------------
            $notes = [];
            if (! empty($meta['dias-de-salida'])) {
                $notes[] = trim(strip_tags((string) $meta['dias-de-salida']));
            }
            if (! empty($meta['pasajeros-minimos'])) {
                $notes[] = 'Pasajeros mínimos: '.trim(strip_tags((string) $meta['pasajeros-minimos']));
            }

            $attrs = [
                'region_id' => $this->inferRegion($meta['lugar'] ?? '', $w['title'], $regions),
                'category_id' => $this->inferCategory($w['title'], $categories),
                'title_es' => $w['title'],
                'subtitle_es' => Str::limit(WpTourParser::htmlToText($meta['frase-inicial'] ?? ''), 250, ''),
                'description_es' => WpTourParser::htmlToText($meta['acerca-del-tour'] ?? ''),
                'itinerary_es' => WpTourParser::parseItinerary($meta['itinerario'] ?? ''),
                'includes_es' => $split['includes'],
                'excludes_es' => $split['excludes'],
                'recommendations_es' => WpTourParser::htmlToText($meta['que-llevar'] ?? ''),
                'notes_es' => implode("\n", $notes) ?: null,
                'price' => $price,
                'currency' => 'PEN',
                'duration' => trim((string) ($meta['duracion'] ?? '')) ?: null,
                'language' => $this->normalizeLanguages($meta['idiomas'] ?? ''),
                'departure_time' => trim((string) ($meta['salidas'] ?? '')) ?: null,
                'cover_image' => $cover,
                'gallery' => $gallery ?: null,
                'seo_image' => $seoImg,
                'seo_description' => Str::limit(WpTourParser::htmlToText($meta['descripcion-corta-del-tour'] ?? $meta['acerca-del-tour'] ?? ''), 300, ''),
                'order' => (int) ($meta['orden'] ?? 0),
                // Precio 0 (2 tours nuevos sin cargar) -> borrador para no
                // mostrar "S/0" en el sitio.
                'is_published' => ($w['status'] === 'publish' && $price > 0),
            ];

            if ($dry) {
                $this->line(sprintf('[dry] %-55s S/%-6s reg=%s cat=%s img=%d %s',
                    Str::limit($w['title'], 52), $price, $attrs['region_id'], $attrs['category_id'],
                    count($gallery) + ($cover ? 1 : 0), $attrs['is_published'] ? 'pub' : 'DRAFT'));
                $imported[] = $slug;

                continue;
            }

            $existing = Tour::withTrashed()->where('slug', $slug)->first();
            $tour = Tour::withTrashed()->updateOrCreate(['slug' => $slug], $attrs);
            if ($tour->trashed()) {
                $tour->restore();
            }
            $existing ? $updated++ : $created++;
            $imported[] = $slug;
        }

        // Despublicar tours demo previos (los que no vinieron del WP) ------
        $demoUnpublished = 0;
        if (! $dry && ! $this->option('keep-demo')) {
            $demoUnpublished = Tour::whereNotIn('slug', $imported)
                ->where('is_published', true)
                ->update(['is_published' => false]);
        }

        $this->newLine();
        $this->info("Tours importados: creados=$created, actualizados=$updated (total ".count($imported).')');
        $this->info("Imágenes copiadas: $imgCopied");
        if (! $this->option('keep-demo')) {
            $this->info("Tours demo despublicados: $demoUnpublished (reversibles desde el panel)");
        }

        return self::SUCCESS;
    }

    /** Copia una imagen del export local a storage/app/public/tours/. */
    private function copyImage(?string $url, bool $dry, int &$counter): ?string
    {
        if (! $url) {
            return null;
        }
        $path = parse_url($url, PHP_URL_PATH); // /wp-content/uploads/2024/02/x.webp
        if (! $path) {
            return null;
        }
        $rel = preg_replace('#^.*/uploads/#', '', ltrim($path, '/')); // 2024/02/x.webp
        $src = $this->imgSrcBase.'/wp-content/uploads/'.$rel;
        if (! File::exists($src)) {
            return null;
        }
        $destRel = 'tours/'.str_replace('/', '-', $rel); // tours/2024-02-x.webp
        if (! $dry) {
            $dest = storage_path('app/public/'.$destRel);
            if (! File::exists($dest)) {
                File::copy($src, $dest);
                $counter++;
            }
        }

        return $destRel;
    }

    private function inferRegion(string $lugar, string $title, $regions): ?int
    {
        $h = Str::lower($this->deaccent($lugar.' '.$title));
        // Coincidencia por palabra completa: 'ica' NO debe matchear dentro de
        // "gastronomica" ni "tipicas" (esos tours son de Lima).
        $word = fn (array $keys) => collect($keys)->contains(
            fn ($k) => (bool) preg_match('/(?<![a-z])'.preg_quote($k, '/').'(?![a-z])/', $h)
        );
        $ica = ['nazca', 'paracas', 'ica', 'huacachina', 'ballestas'];
        $cusco = ['cusco', 'machu picchu', 'machupicchu', 'valle sagrado', 'humantay', 'maras', 'moray', 'montana', 'arcoiris', '7 colores', 'rainbow'];
        if ($word($cusco)) {
            return $regions['Cusco'] ?? null;
        }
        if ($word($ica)) {
            return $regions['Ica'] ?? null;
        }

        return $regions['Lima'] ?? null;
    }

    private function inferCategory(string $title, $categories): ?int
    {
        $h = Str::lower($this->deaccent($title));
        $map = [
            'Experiencias Culinarias' => ['gastronomic', 'pisco', 'ceviche', 'street food', 'degustac', 'anticucho', 'sabor', 'culinari', 'mercado'],
            'Tours de Aventura' => ['cuatrimoto', 'laguna', 'montana', 'humantay', 'aventura', 'sobrevuelo', 'nazca', 'ballestas', 'palomino', 'huacachina', 'arcoiris', 'cuatrimotos'],
            'Tours Culturales' => ['city tour', 'histor', 'museo', 'casa aliaga', 'catacumba', 'pachacamac', 'huaca', 'callao', 'larco', 'cultural', 'valle sagrado', 'machu picchu', 'miraflores', 'barranco'],
        ];
        foreach ($map as $cat => $keys) {
            foreach ($keys as $k) {
                if (str_contains($h, $k)) {
                    return $categories[$cat] ?? ($categories['Otros'] ?? null);
                }
            }
        }

        return $categories['Otros'] ?? null;
    }

    private function normalizeLanguages(string $raw): string
    {
        $raw = trim(strip_tags($raw));
        if ($raw === '') {
            return 'Español / Inglés';
        }
        $has = fn ($n) => Str::contains(Str::lower($this->deaccent($raw)), $n);
        $langs = [];
        if ($has('espanol') || $has('spanish')) {
            $langs[] = 'Español';
        }
        if ($has('ingl') || $has('english')) {
            $langs[] = 'Inglés';
        }

        return $langs ? implode(' / ', $langs) : $raw;
    }

    private function deaccent(string $s): string
    {
        return strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);
    }
}
