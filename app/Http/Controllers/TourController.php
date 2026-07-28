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

        $filtered = Tour::published()
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('title_es', 'like', "%{$q}%")
                    ->orWhere('title_en', 'like', "%{$q}%")
                    ->orWhere('description_es', 'like', "%{$q}%");
            }))
            ->when($destino, fn ($query) => $query->whereHas('region', fn ($r) => $r->where('slug', $destino)));

        $tours = (clone $filtered)->ordered()->paginate(12)->withQueryString();

        // Sin resultados con los filtros aplicados: sugerir tours reales
        // (destacados/publicados) en vez de una pantalla vacía sin salida.
        $suggested = $tours->total() === 0
            ? Tour::published()->ordered()->limit(4)->get()
            : collect();

        return view('tours.results', [
            'q' => $q,
            'destino' => $destino,
            'fecha' => $fecha,
            'pax' => $pax,
            'tours' => $tours,
            'suggested' => $suggested,
        ]);
    }
}
