<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Support\WpBlogMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Imports the 10 real blog posts exported from the WordPress of
 * limaamericatours.com (storage/app/wp-import/blog.json + its local uploads
 * extract) into the `blog_posts` table. Idempotent by slug (updateOrCreate).
 * Copies cover images to storage/app/public/blog/. Mirrors the pattern of
 * App\Console\Commands\ImportWpTours for tours.
 */
class ImportWpBlog extends Command
{
    protected $signature = 'blog:import-wp
        {--json=wp-import/blog.json : Ruta del JSON dentro de storage/app}
        {--images=wp-import/prod-dump/uploads-extract/uploads : Carpeta con las imágenes dentro de storage/app}
        {--keep-demo : No despublicar los posts demo previos}
        {--dry-run : Solo muestra lo que haría, sin escribir}';

    protected $description = 'Importa los posts reales del blog de WordPress (limaamericatours.com) a la BD';

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
        $destDir = storage_path('app/public/blog');
        if (! $dry) {
            File::ensureDirectoryExists($destDir);
        }

        $imported = [];
        $created = 0;
        $updated = 0;
        $imgCopied = 0;

        foreach ($data as $w) {
            $meta = $w['meta'] ?? [];
            $slug = $w['slug'] ?: Str::slug($w['title']);

            $tituloGeneral = trim((string) ($meta['titulo-general'] ?? ''));
            $titulo1 = (string) ($meta['titulo-1'] ?? '');
            $texto1 = (string) ($meta['texto-1'] ?? '');
            $titulo2 = (string) ($meta['titulo-2'] ?? '');
            $texto2 = (string) ($meta['texto-2'] ?? '');

            $body = WpBlogMapper::buildBody($texto1, $titulo2, $texto2);

            $cover = $this->copyImage($w['images']['foto-1']['file'] ?? null, $dry, $imgCopied);

            $tags = WpBlogMapper::parseTags($meta['_aioseo_og_article_tags'] ?? null)
                ?? WpBlogMapper::parseTags($meta['_aioseo_keywords'] ?? null);

            $metaTitle = trim((string) ($meta['_aioseo_title'] ?? ''));
            $metaDescription = trim((string) ($meta['_aioseo_description'] ?? ''));

            $attrs = [
                'is_published' => $w['status'] === 'publish',
                'published_at' => $w['date'] ?? null,
                'author_name' => WpBlogMapper::authorName($w['author'] ?? []),
                'category' => null,
                'tags' => $tags,
                'cover_image' => $cover,
                'reading_minutes' => WpBlogMapper::readingMinutes($body),
                'title_es' => $tituloGeneral !== '' ? $tituloGeneral : $w['title'],
                'excerpt_es' => WpBlogMapper::excerpt($titulo1, $texto1),
                'body_es' => $body,
                'meta_title_es' => $metaTitle !== '' ? $metaTitle : null,
                'meta_description_es' => $metaDescription !== '' ? $metaDescription : null,
            ];

            if ($dry) {
                $this->line(sprintf('[dry] %-55s pub=%s img=%s',
                    Str::limit($w['title'], 52), $attrs['is_published'] ? 'si' : 'no', $cover ?: '-'));
                $imported[] = $slug;

                continue;
            }

            $existing = BlogPost::where('slug', $slug)->first();
            BlogPost::updateOrCreate(['slug' => $slug], $attrs);
            $existing ? $updated++ : $created++;
            $imported[] = $slug;
        }

        // Despublicar posts demo previos (los que no vinieron del WP) ------
        $demoUnpublished = 0;
        if (! $dry && ! $this->option('keep-demo')) {
            // Los posts demo previos (no vinieron del WP): despublicar para
            // que no contaminen el blog público. No se borran (reversible
            // desde el panel).
            $demoUnpublished = BlogPost::whereNotIn('slug', $imported)
                ->where('is_published', true)
                ->update(['is_published' => false]);
        }

        $this->newLine();
        $this->info("Posts importados: creados=$created, actualizados=$updated (total ".count($data).')');
        $this->info("Imágenes copiadas: $imgCopied");
        if (! $this->option('keep-demo')) {
            $this->info("Posts demo despublicados: $demoUnpublished (reversibles desde el panel)");
        }

        return self::SUCCESS;
    }

    /** Copies a cover image from the local uploads extract to storage/app/public/blog/. */
    private function copyImage(?string $file, bool $dry, int &$counter): ?string
    {
        if (! $file) {
            return null;
        }

        $rel = ltrim($file, '/'); // 2025/12/ceviche.webp
        $src = $this->imgSrcBase.'/'.$rel;
        if (! File::exists($src)) {
            return null;
        }

        $destRel = 'blog/'.str_replace('/', '-', $rel); // blog/2025-12-ceviche.webp
        if (! $dry) {
            $dest = storage_path('app/public/'.$destRel);
            if (! File::exists($dest)) {
                File::copy($src, $dest);
                $counter++;
            }
        }

        return $destRel;
    }
}
