<?php

namespace App\Http\Controllers;

use App\Models\BlockedDate;
use App\Models\Category;
use App\Models\Region;
use App\Models\Testimonial;
use App\Models\Tour;
use App\Support\ImagePath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TourController extends Controller
{
    public function index(): View
    {
        return view('tours.index', [
            'categoria' => null,
            'tours' => Tour::published()->ordered()->get(),
            'categories' => Category::active()->orderBy('order')
                ->withCount(['tours' => fn ($q) => $q->published()])
                ->get(),
            'regions' => Region::active()->orderBy('order')->get(),
            'testimonials' => Testimonial::active()->featured()->orderBy('order')->limit(4)->get(),
        ]);
    }

    public function category(string $locale, string $categoria): View
    {
        $region = Region::where('slug', $categoria)->firstOrFail();

        return view('tours.index', [
            'categoria' => $categoria,
            'region' => $region,
            'tours' => Tour::published()->where('region_id', $region->id)->ordered()->get(),
            'categories' => Category::active()->orderBy('order')
                ->withCount(['tours' => fn ($q) => $q->published()])
                ->get(),
            'regions' => Region::active()->orderBy('order')->get(),
            'testimonials' => Testimonial::active()->featured()->orderBy('order')->limit(4)->get(),
        ]);
    }

    public function show(string $locale, string $slug): View
    {
        $tour = Tour::published()->where('slug', $slug)->firstOrFail();
        $related = Tour::published()->where('id', '!=', $tour->id)
            ->when($tour->region_id, fn ($q) => $q->where('region_id', $tour->region_id))
            ->limit(4)->get();
        if ($related->count() < 4) {
            $related = Tour::published()->where('id', '!=', $tour->id)->limit(4)->get();
        }

        // SEO por-tour (docs/qa/ficha-tour.md hallazgo #4): seo_title/seo_description/
        // seo_image/seo_keywords, con fallback al título/descripción/portada del
        // propio tour, y al genérico del sitio si todo lo demás viene vacío
        // (ese último fallback ya lo resuelve resources/views/layouts/app.blade.php
        // cuando estas variables llegan vacías). No se toca tours/show.blade.php:
        // el layout compartido las prioriza sobre el @section('title'/'description')
        // que ya define esa vista.
        $seoTitle = $tour->seo_title ?: ($tour->title.' — '.__('seo.site_name'));
        $seoDescription = $tour->seo_description
            ?: (__('seo.tour_description_prefix').$tour->title.__('seo.tour_description_suffix'));
        $seoImage = $tour->seo_image ? ImagePath::url($tour->seo_image) : $tour->cover_url;
        $seoKeywords = is_array($tour->seo_keywords) && count($tour->seo_keywords) > 0
            ? implode(', ', $tour->seo_keywords)
            : null;

        return view('tours.show', [
            'tour' => $tour,
            'related' => $related,
            'testimonials' => Testimonial::active()->featured()->orderBy('order')->limit(4)->get(),
            'tourReviews' => $tour->testimonials()
                ->where('is_active', true)
                ->latest()
                ->get(),
            'blockedDates' => BlockedDate::blockedDatesFor($tour->id),
            'blockedWeekdays' => BlockedDate::blockedWeekdaysFor($tour->id),
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'seoImage' => $seoImage,
            'seoKeywords' => $seoKeywords,
        ]);
    }

    /**
     * Guarda una reseña enviada por el visitante (caja estilo WooCommerce).
     * Queda is_active=false (pendiente) hasta que un admin la apruebe en Filament → Testimonios.
     */
    public function storeReview(Request $request, string $locale, string $slug): RedirectResponse
    {
        $tour = Tour::published()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
            // Honeypot anti-spam: debe venir vacío
            'website' => ['nullable', 'size:0'],
        ], [], [
            'rating' => 'puntuación',
            'name' => 'nombre',
            'email' => 'correo',
            'comment' => 'valoración',
        ]);

        Testimonial::create([
            'tour_id' => $tour->id,
            'name' => $data['name'],
            'quote_es' => $data['comment'],
            'rating' => $data['rating'],
            'source' => 'Web',
            'is_active' => false, // pendiente de moderación
            'is_featured' => false,
        ]);

        return redirect()
            ->route('tours.show', ['locale' => $locale, 'slug' => $slug])
            ->with('review_status', '¡Gracias! Tu reseña fue enviada y se publicará tras ser revisada.')
            ->withFragment('reviews');
    }

    /**
     * Buscador del home (4 campos: ¿qué tour buscas? + destino + fecha + personas).
     *
     * Guardrail funcional: la fecha es un dato del viajero (se guarda/pasa a la
     * vista), no un filtro duro — este catálogo no maneja disponibilidad por día,
     * así que filtrar por fecha solo produciría falsos "0 resultados". "Personas"
     * tampoco filtra (es informativo, como ya lo era antes de este cambio). Si la
     * combinación de texto+destino no arroja tours, la vista igual recibe
     * `$suggested` con tours reales para no dejar una pantalla muerta.
     */
    public function search(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $destino = trim((string) $request->query('destino'));
        $fecha = trim((string) $request->query('fecha'));
        $pax = trim((string) $request->query('pax'));

        // ── Fecha ────────────────────────────────────────────────────────────
        // El campo "Fecha" del hero llegaba y no filtraba nada: el buscador
        // ofrecía tours en fechas que el CMS ya tiene bloqueadas. Ahora se
        // respeta BlockedDate (las mismas reglas que la ficha de tour): un
        // bloqueo global tumba el catálogo completo para ese día, uno por tour
        // solo tumba ese tour, y los bloqueos por día de la semana aplican
        // igual. Fecha basura o pasada: se ignora en silencio (no es un error
        // del usuario que merezca una pantalla de error).
        $searchDate = $this->parseSearchDate($fecha);

        // ── Personas ─────────────────────────────────────────────────────────
        // `max_capacity` nulo = sin límite declarado, siempre se muestra. No
        // tiene sentido ofrecer un tour de cupo 2 a alguien que busca para 6.
        $paxWanted = $this->parseSearchPax($pax);

        $filtered = Tour::published()
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('title_es', 'like', "%{$q}%")
                    ->orWhere('title_en', 'like', "%{$q}%")
                    ->orWhere('description_es', 'like', "%{$q}%");
            }))
            ->when($destino, fn ($query) => $query->whereHas('region', fn ($r) => $r->where('slug', $destino)))
            ->when($paxWanted, fn ($query) => $query->where(function ($w) use ($paxWanted) {
                $w->whereNull('max_capacity')->orWhere('max_capacity', '>=', $paxWanted);
            }))
            ->when($searchDate, function ($query) use ($searchDate) {
                $weekday = (int) $searchDate->dayOfWeek; // 0=domingo, igual que BlockedDate

                $query->whereNotExists(function ($sub) use ($searchDate, $weekday) {
                    $sub->selectRaw('1')
                        ->from('blocked_dates')
                        ->where(function ($scope) {
                            // Bloqueo global (tour_id null) o del propio tour.
                            $scope->whereNull('blocked_dates.tour_id')
                                ->orWhereColumn('blocked_dates.tour_id', 'tours.id');
                        })
                        ->where(function ($rule) use ($searchDate, $weekday) {
                            $rule->whereDate('blocked_dates.date', $searchDate->toDateString())
                                ->orWhere('blocked_dates.weekday', $weekday);
                        });
                });
            });

        $tours = (clone $filtered)->ordered()->paginate(12)->withQueryString();

        // Sin resultados con los filtros aplicados: sugerir tours reales
        // (destacados/publicados) en vez de una pantalla vacía sin salida.
        $suggested = $tours->total() === 0
            ? Tour::published()->ordered()->limit(4)->get()
            : collect();

        // Si lo que dejó la búsqueda en cero fue la fecha (y no el texto o el
        // destino), se dice explícitamente: un "0 resultados" mudo hace pensar
        // que el catálogo está vacío. Solo se muestra cuando la fecha es la
        // culpable de verdad.
        // Ojo: NO se usa BlockedDate::isBlocked() aquí. Ese helper compara la
        // fecha con igualdad exacta (`where('date', $fecha)`), lo que funciona
        // en MySQL —columna DATE— pero no en SQLite, donde el cast `date` de
        // Eloquent guarda el valor con hora ('2026-08-08 00:00:00') y la
        // igualdad falla. `whereDate()` normaliza en los dos motores, que es lo
        // que ya usa el filtro de arriba. (El helper se deja intacto: lo usa la
        // ficha de tour contra MySQL y no es este el sitio para cambiarlo.)
        $dateBlocked = $searchDate !== null
            && $tours->total() === 0
            && BlockedDate::query()
                ->whereNull('tour_id')
                ->where(function ($rule) use ($searchDate) {
                    $rule->whereDate('date', $searchDate->toDateString())
                        ->orWhere('weekday', (int) $searchDate->dayOfWeek);
                })
                ->exists();

        return view('tours.results', [
            'q' => $q,
            'destino' => $destino,
            'fecha' => $searchDate?->toDateString() ?? '',
            'pax' => $pax,
            'tours' => $tours,
            'suggested' => $suggested,
            'dateBlocked' => $dateBlocked,
        ]);
    }

    /**
     * Normaliza el parámetro `fecha` del buscador del hero.
     *
     * Devuelve null (= no filtrar) para cualquier cosa que no sea una fecha
     * Y-m-d real de hoy en adelante: cadenas vacías, fechas pasadas, "2026-99-99",
     * intentos de inyección. La entrada viene de una URL pública, así que se
     * valida el FORMATO antes de parsear — `Carbon::parse('no-es-fecha')` lanza
     * excepción y un 500 en el buscador sería un bug de disponibilidad.
     */
    private function parseSearchDate(string $raw): ?\Illuminate\Support\Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }

        try {
            $date = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $raw);
        } catch (\Throwable $e) {
            return null;
        }

        // createFromFormat es permisivo: '2026-99-99' "rueda" a otra fecha en vez
        // de fallar. Se compara la reconstrucción con el original para descartarlo.
        if ($date === false || $date->format('Y-m-d') !== $raw) {
            return null;
        }

        return $date->startOfDay()->lt(now()->startOfDay()) ? null : $date->startOfDay();
    }

    /**
     * Normaliza el parámetro `pax`. El select del hero ofrece 1..9 y "10+";
     * "10+" se entiende como 10 (cupo mínimo para grupo grande). Cualquier otra
     * cosa (vacío, texto, 0, negativos) no filtra.
     */
    private function parseSearchPax(string $raw): ?int
    {
        if ($raw === '') {
            return null;
        }

        if (! preg_match('/^(\d{1,3})\+?$/', $raw, $m)) {
            return null;
        }

        $pax = (int) $m[1];

        return $pax >= 1 ? $pax : null;
    }
}
