<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Region;
use App\Models\Tour;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $locales = config('app.supported_locales', ['es', 'en', 'pt']);
        $base = rtrim(config('app.url'), '/');

        $urls = [];

        // Static routes
        $staticRoutes = [
            ['path' => '',           'priority' => '1.0', 'changefreq' => 'daily'],
            ['path' => '/tours',     'priority' => '0.9', 'changefreq' => 'daily'],
            ['path' => '/nosotros',  'priority' => '0.7', 'changefreq' => 'monthly'],
            ['path' => '/contacto',  'priority' => '0.6', 'changefreq' => 'monthly'],
            ['path' => '/resenas',   'priority' => '0.6', 'changefreq' => 'weekly'],
            ['path' => '/terminos',  'priority' => '0.3', 'changefreq' => 'yearly'],
            ['path' => '/privacidad', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach ($staticRoutes as $r) {
            foreach ($locales as $locale) {
                $urls[] = [
                    'loc' => $base.'/'.$locale.$r['path'],
                    'lastmod' => now()->toAtomString(),
                    'priority' => $r['priority'],
                    'changefreq' => $r['changefreq'],
                    'alternates' => collect($locales)->mapWithKeys(fn ($l) => [$l => $base.'/'.$l.$r['path']])->all(),
                ];
            }
        }

        // Region category pages
        foreach (Region::active()->get() as $region) {
            foreach ($locales as $locale) {
                $urls[] = [
                    'loc' => $base.'/'.$locale.'/tours/categoria/'.$region->slug,
                    'lastmod' => $region->updated_at?->toAtomString() ?? now()->toAtomString(),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                    'alternates' => collect($locales)->mapWithKeys(fn ($l) => [$l => $base.'/'.$l.'/tours/categoria/'.$region->slug])->all(),
                ];
            }
        }

        // Tour pages
        foreach (Tour::published()->ordered()->get() as $tour) {
            foreach ($locales as $locale) {
                $urls[] = [
                    'loc' => $base.'/'.$locale.'/tours/detalle/'.$tour->slug,
                    'lastmod' => $tour->updated_at?->toAtomString() ?? now()->toAtomString(),
                    'priority' => $tour->is_featured ? '0.9' : '0.7',
                    'changefreq' => 'weekly',
                    'image' => $tour->cover_image ? $base.'/'.ltrim($tour->cover_image, '/') : null,
                    'alternates' => collect($locales)->mapWithKeys(fn ($l) => [$l => $base.'/'.$l.'/tours/detalle/'.$tour->slug])->all(),
                ];
            }
        }

        // Blog index pages (one per locale)
        foreach ($locales as $locale) {
            $urls[] = [
                'loc' => $base.'/'.$locale.'/blog',
                'lastmod' => now()->toAtomString(),
                'priority' => '0.7',
                'changefreq' => 'daily',
                'alternates' => collect($locales)->mapWithKeys(fn ($l) => [$l => $base.'/'.$l.'/blog'])->all(),
            ];
        }

        // Blog post pages
        foreach (BlogPost::published()->orderByDesc('updated_at')->get() as $post) {
            foreach ($locales as $locale) {
                $urls[] = [
                    'loc' => $base.'/'.$locale.'/blog/'.$post->slug,
                    'lastmod' => $post->updated_at?->toAtomString() ?? now()->toAtomString(),
                    'priority' => '0.6',
                    'changefreq' => 'weekly',
                    'image' => $post->cover_image ? $base.'/storage/'.ltrim($post->cover_image, '/') : null,
                    'alternates' => collect($locales)->mapWithKeys(fn ($l) => [$l => $base.'/'.$l.'/blog/'.$post->slug])->all(),
                ];
            }
        }

        // CMS pages
        foreach (Page::published()->where('show_in_sitemap', true)->get() as $page) {
            foreach ($locales as $locale) {
                $urls[] = [
                    'loc' => $base.'/'.$locale.'/'.$page->slug,
                    'lastmod' => $page->updated_at?->toAtomString() ?? now()->toAtomString(),
                    'priority' => $page->sitemap_priority ?: '0.5',
                    'changefreq' => $page->sitemap_changefreq ?: 'monthly',
                    'alternates' => collect($locales)->mapWithKeys(fn ($l) => [$l => $base.'/'.$l.'/'.$page->slug])->all(),
                ];
            }
        }

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    public function robots(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $env = config('app.env');

        $lines = [];
        if ($env !== 'production') {
            $lines[] = 'User-agent: *';
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'User-agent: *';
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin';
            $lines[] = 'Disallow: /admin/';
            $lines[] = 'Disallow: /checkout';
            $lines[] = 'Disallow: /es/checkout';
            $lines[] = 'Disallow: /en/checkout';
            $lines[] = 'Disallow: /pt/checkout';
            // SEO S-03 (2026-07-27): /buscar ya NO se bloquea aquí. La vista de
            // resultados emite <meta name="robots" content="noindex,follow">, y
            // combinar Disallow con noindex es contraproducente: si el bot no
            // puede rastrear la URL, tampoco puede leer el noindex. Con follow
            // además sigue los enlaces internos a los tours.
            $lines[] = '';
            $lines[] = 'User-agent: AhrefsBot';
            $lines[] = 'Crawl-delay: 10';
            $lines[] = '';
            $lines[] = 'User-agent: SemrushBot';
            $lines[] = 'Crawl-delay: 10';
            $lines[] = '';

            // ── Bots de IA / agentes: bienvenidos al contenido público ──
            // (agentic browsing + visibilidad en asistentes). Se mantiene el
            // bloqueo de /admin y /checkout. Cada agente respeta su propio
            // User-agent, por eso se listan explícitamente.
            $aiBots = [
                'GPTBot', 'ChatGPT-User', 'OAI-SearchBot',   // OpenAI
                'ClaudeBot', 'Claude-Web', 'anthropic-ai',    // Anthropic
                'PerplexityBot', 'Perplexity-User',           // Perplexity
                'Google-Extended',                            // Gemini/Vertex
                'Applebot-Extended',                          // Apple Intelligence
                'CCBot',                                      // Common Crawl
                'Bytespider', 'Amazonbot', 'Meta-ExternalAgent',
            ];
            foreach ($aiBots as $bot) {
                $lines[] = 'User-agent: '.$bot;
                $lines[] = 'Allow: /';
                $lines[] = 'Disallow: /admin';
                $lines[] = 'Disallow: /admin/';
                $lines[] = 'Disallow: /checkout';
                $lines[] = 'Disallow: /es/checkout';
                $lines[] = 'Disallow: /en/checkout';
                $lines[] = 'Disallow: /pt/checkout';
                $lines[] = '';
            }

            $lines[] = 'Sitemap: '.$base.'/sitemap.xml';
            $lines[] = '';
            $lines[] = '# LLM-friendly site summary: '.$base.'/llms.txt';
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * /llms.txt — resumen del sitio legible por LLMs/agentes (formato
     * llmstxt.org, Markdown). Se genera dinámicamente desde los tours
     * publicados para mantenerse siempre actualizado.
     */
    public function llms(): Response
    {
        $base = rtrim(config('app.url'), '/');
        // Sin fallback a otro número: si el cliente no cargó el teléfono
        // todavía, la línea de contacto simplemente no lo menciona (ver
        // App\Models\Setting::contactPhone()).
        $phone = \App\Models\Setting::contactPhone();
        // Mismo criterio que el teléfono: sin correo cargado, la línea no lo
        // menciona en vez de publicar una casilla inventada (ver
        // App\Models\Setting::contactEmail()).
        $email = \App\Models\Setting::contactEmail();
        $wa = \App\Models\Setting::whatsappNumber();

        $L = [];
        $L[] = '# Lima América Tours';
        $L[] = '';
        $L[] = '> Agencia de turismo en Perú especializada en experiencias premium en Lima, Ica y Cusco: city tours, Machu Picchu, Huacachina, Islas Ballestas, Líneas de Nazca, Laguna Humantay, Montaña de 7 Colores y más. Reserva online con cancelación gratuita y guías bilingües (español/inglés).';
        $L[] = '';
        $L[] = 'Idiomas del sitio: Español ('.$base.'/es), English ('.$base.'/en), Português ('.$base.'/pt).';
        $L[] = '';

        $L[] = '## Tours';
        foreach (Tour::published()->ordered()->get() as $tour) {
            $url = $base.'/es/tours/detalle/'.$tour->slug;
            $price = $tour->price ? ('US$'.number_format((float) $tour->price, 0)) : null;
            $dur = $tour->duration ? (', '.$tour->duration) : '';
            $desc = trim((string) ($tour->subtitle_es ?: strip_tags((string) $tour->description_es)));
            $desc = $desc !== '' ? \Illuminate\Support\Str::limit($desc, 140) : 'Tour en Perú';
            $suffix = $price ? (' — desde '.$price.' por persona'.$dur) : $dur;
            $L[] = '- ['.$tour->title.']('.$url.'): '.$desc.$suffix;
        }
        $L[] = '';

        $L[] = '## Páginas';
        $L[] = '- [Todos los tours]('.$base.'/es/tours): catálogo completo con filtros por región.';
        $L[] = '- [Nosotros]('.$base.'/es/nosotros): quiénes somos y por qué reservar con nosotros.';
        $L[] = '- [Reseñas]('.$base.'/es/resenas): opiniones verificadas de viajeros (Google, TripAdvisor y web).';
        $L[] = '- [Blog]('.$base.'/es/blog): guías de viaje y consejos sobre Perú.';
        $canales = array_filter([
            $phone ? 'WhatsApp '.$phone : null,
            $email ? 'Email '.$email : null,
        ]);
        $contactLine = '- [Contacto]('.$base.'/es/contacto)'.($canales ? ': '.implode(' · ', $canales).'.' : '.');
        $L[] = $contactLine;
        $L[] = '';

        $L[] = '## Reservar / contactar';
        if ($wa) {
            $L[] = '- WhatsApp: https://wa.me/'.$wa;
        }
        if ($email) {
            $L[] = '- Email: '.$email;
        }
        $L[] = '- Reserva online directa desde la página de cada tour (botón "Reservar ahora").';
        $L[] = '';

        $L[] = '## Recursos';
        $L[] = '- [Sitemap XML]('.$base.'/sitemap.xml)';

        return response(implode("\n", $L)."\n", 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
