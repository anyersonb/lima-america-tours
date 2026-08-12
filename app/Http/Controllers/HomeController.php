<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Guide;
use App\Models\Offer;
use App\Models\Region;
use App\Models\Testimonial;
use App\Models\Tour;
use App\Services\HomeStatsResolver;
use App\Services\ReviewAggregator;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ReviewAggregator $reviews,
        private readonly HomeStatsResolver $homeStats,
    ) {}

    public function index(): View
    {
        $featuredTours = $this->fetchFeaturedTours();
        $toursIca = $this->fetchToursByRegion('Ica');
        $toursLima = $this->fetchToursByRegion('Lima');
        $toursCusco = $this->fetchToursByRegion('Cusco');
        $regions = $this->fetchRegions();
        $testimonials = $this->fetchTestimonials();
        $offers = $this->fetchOffers();
        $homeStatsResolved = $this->homeStats->resolve(app()->getLocale());
        $activeGuides = $this->fetchActiveGuides();
        $realTestimonials = $this->fetchRealTestimonials();
        $destinationRegions = $this->fetchDestinationRegions();
        $homeCategories = $this->fetchHomeCategories();

        return view('home', compact(
            'featuredTours',
            'toursIca',
            'toursLima',
            'toursCusco',
            'regions',
            'testimonials',
            'offers',
            'homeStatsResolved',
            'activeGuides',
            'realTestimonials',
            'destinationRegions',
            'homeCategories',
        ));
    }

    // ─── Private query helpers ────────────────────────────────────────────────

    private function fetchFeaturedTours(): \Illuminate\Support\Collection
    {
        try {
            // "Más Comprados" tiene su propio orden manual: `featured_order`
            // (1 = primero; NULL va al final y se ordena por número real de
            // reservas, excluyendo canceladas). Es independiente de `order`,
            // que siguen usando las secciones por región (Ica/Lima/Cusco).
            $byPurchases = static fn ($query) => $query
                ->withCount(['bookings as purchases_count' => static fn ($b) => $b->where('status', '!=', 'cancelled')])
                ->orderByRaw('featured_order IS NULL')
                ->orderBy('featured_order')
                ->orderByDesc('purchases_count');

            $featured = $byPurchases(Tour::published()->featured())
                ->ordered()
                ->limit(8)
                ->get();

            // Si hay menos de 3 destacados, completar con tours publicados
            // para garantizar al menos 3 cards visibles en el grid desktop.
            if ($featured->count() < 3) {
                $existingIds = $featured->pluck('id')->all();
                $fill = $byPurchases(Tour::published())
                    ->ordered()
                    ->when(count($existingIds) > 0, fn ($q) => $q->whereNotIn('id', $existingIds))
                    ->limit(8 - $featured->count())
                    ->get();

                $featured = $featured->concat($fill);
            }

            return $featured;
        } catch (\Throwable $e) {
            Log::error('HomeController: failed to fetch featured tours', [
                'exception' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Fetch published tours whose region name (name_es) matches the given
     * destination string. Uses whereHas so no new migration is required.
     *
     * @param  non-empty-string  $regionName  e.g. 'Ica', 'Lima', 'Cusco'
     */
    private function fetchToursByRegion(string $regionName): \Illuminate\Support\Collection
    {
        try {
            $regionTours = Tour::published()
                ->ordered()
                ->whereHas('region', static function ($query) use ($regionName): void {
                    $query->where('name_es', $regionName);
                })
                ->limit(8)
                ->get();

            // Respaldo a tours reales si la región no tiene tours (evita placeholders 404).
            if ($regionTours->isEmpty()) {
                $regionTours = Tour::published()->ordered()->limit(8)->get();
            }

            return $regionTours;
        } catch (\Throwable $e) {
            Log::error('HomeController: failed to fetch tours by region', [
                'region' => $regionName,
                'exception' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    private function fetchRegions(): \Illuminate\Support\Collection
    {
        try {
            return Region::active()->orderBy('order')->get();
        } catch (\Throwable $e) {
            Log::error('HomeController: failed to fetch regions', [
                'exception' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Testimonios del home: mezcla reseñas reales de Google (API) con los
     * testimonios destacados del CMS, usando el mismo servicio que /resenas.
     * Si la API de Google no está activa, muestra solo los del CMS (como antes).
     */
    private function fetchTestimonials(): \Illuminate\Support\Collection
    {
        try {
            $cms = Testimonial::active()->featured()->orderBy('order')->limit(8)->get();

            return $this->reviews->merge($cms, app()->getLocale())->take(9);
        } catch (\Throwable $e) {
            Log::error('HomeController: failed to fetch testimonials', [
                'exception' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Guías/equipo activos para la sección "Conoce a tu guía" del home. La
     * tabla `guides` arranca vacía (nada de sembrar personas de mentira —
     * ver Guide migration/factory), así que en la mayoría de instalaciones
     * esto devuelve una colección vacía a propósito: el maquetador debe
     * ocultar la sección entera cuando `$activeGuides->isEmpty()`.
     */
    private function fetchActiveGuides(): \Illuminate\Support\Collection
    {
        try {
            return Guide::active()->ordered()->limit(8)->get();
        } catch (\Throwable $e) {
            Log::error('HomeController: failed to fetch guides', [
                'exception' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Reseñas reales del CMS (sin mezclar con las de la API de Google/
     * Tripadvisor, que no traen tour asociado ni una fecha confiable) para
     * mostrar "Nombre · fecha · tour" en la portada. Ordenadas por
     * destacadas primero y luego por fecha de reseña más reciente.
     * Colección vacía si no hay ninguna activa: mismo criterio que los
     * guías, la vista debe ocultar la sección.
     */
    private function fetchRealTestimonials(): \Illuminate\Support\Collection
    {
        try {
            return Testimonial::active()
                ->with('tour')
                ->orderByDesc('is_featured')
                ->orderByDesc('reviewed_at')
                ->limit(12)
                ->get();
        } catch (\Throwable $e) {
            Log::error('HomeController: failed to fetch real testimonials', [
                'exception' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Destinos reales para "Explora los increíbles destinos del Perú"
     * (mockups 01 y 02): solo regiones activas con al menos un tour
     * publicado, con su conteo. El mockup pide 6 tarjetas fijas (incluye
     * Cusco, Arequipa y Puno) pero esas 3 no son intercambiables por
     * decreto — Arequipa y Puno hoy no tienen ni región ni tour cargado, así
     * que no aparecerán hasta que el cliente los cargue (guard automático,
     * ver Region::scopeWithPublishedTours). Cusco SÍ tiene tours reales hoy
     * (7, ver reporte) y por lo tanto aparecerá, aunque el brief lo daba
     * como destino "sin tours" — la fuente de verdad es la base de datos.
     */
    private function fetchDestinationRegions(): \Illuminate\Support\Collection
    {
        try {
            return Region::active()->orderBy('order')->withPublishedTours()->get();
        } catch (\Throwable $e) {
            Log::error('HomeController: failed to fetch destination regions', [
                'exception' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Categorías reales para la tira de íconos + "Explora por categoría"
     * (mockup 01): solo categorías activas con al menos un tour publicado,
     * con su conteo real. El mockup dibuja 6 categorías de ejemplo
     * (Aventura, Cultura, Naturaleza, Grupos, Experiencias, Relajación) que
     * no existen como tales en el catálogo — las reales hoy son 4
     * (Culturales, Aventura, Culinarias, Otros). Guard automático: si el
     * cliente da de baja la última categoría "Otros", esa tarjeta
     * desaparece sola.
     */
    private function fetchHomeCategories(): \Illuminate\Support\Collection
    {
        try {
            return Category::active()->orderBy('order')->withPublishedTours()->get();
        } catch (\Throwable $e) {
            Log::error('HomeController: failed to fetch home categories', [
                'exception' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    private function fetchOffers(): \Illuminate\Support\Collection
    {
        try {
            // eager-load "tour" — el front lo usa para el link de la tarjeta
            // cuando la oferta no trae cta_url propia.
            return Offer::active()->with('tour')->orderBy('order')->limit(3)->get();
        } catch (\Throwable $e) {
            Log::error('HomeController: failed to fetch offers', [
                'exception' => $e->getMessage(),
            ]);

            return collect();
        }
    }
}
