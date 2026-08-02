<?php

namespace App\Services;

use App\Models\Testimonial;
use Illuminate\Support\Collection;

/**
 * Combina reseñas de las APIs externas (Google, Tripadvisor) con los
 * testimonios cargados desde el CMS, deduplicando por origen.
 *
 * Se usa tanto en la página pública de reseñas (/resenas) como en la
 * sección de opiniones del home, para que ambas muestren la misma mezcla.
 */
class ReviewAggregator
{
    public function __construct(
        private readonly GoogleReviewsService $googleReviews,
        private readonly TripadvisorReviewsService $tripadvisorReviews,
    ) {}

    /**
     * Reseñas de la API de Google como objetos "pseudo-Testimonial"
     * (mismo interface que usa la vista: name, rating, quote, source, avatar…).
     * Devuelve colección vacía si la API no está habilitada/configurada.
     */
    public function googleApiReviews(string $locale): Collection
    {
        if (! $this->googleReviews->isEnabled()) {
            return collect();
        }

        return collect($this->googleReviews->getReviews($locale))->map(fn (array $r) => (object) [
            'name' => $r['author'],
            'rating' => $r['rating'],
            'quote' => $r['text'],
            'source' => 'google',
            'avatar' => $r['profile_photo'],
            'country' => null,
            'tour' => null,
            'is_featured' => false,
        ]);
    }

    /**
     * Reseñas de la API de Tripadvisor (stub: vacío hasta que se apruebe el acceso).
     */
    public function tripadvisorApiReviews(): Collection
    {
        if (! $this->tripadvisorReviews->isEnabled()) {
            return collect();
        }

        return collect($this->tripadvisorReviews->getReviews())->map(fn (array $r) => (object) [
            'name' => $r['author'],
            'rating' => $r['rating'],
            'quote' => $r['text'],
            'source' => 'tripadvisor',
            'avatar' => $r['profile_photo'] ?? null,
            'country' => null,
            'tour' => null,
            'is_featured' => false,
        ]);
    }

    /**
     * Fusiona reseñas de API con testimonios del CMS.
     *
     * Cuando una API está activa y devuelve datos, los testimonios del CMS
     * con ese mismo origen se descartan (la API manda, para no duplicar). Los
     * testimonios de otros orígenes (web, etc.) siempre se conservan.
     *
     * Orden resultante: Google (API) → Tripadvisor (API) → CMS filtrado.
     *
     * @param  Collection  $cms  Colección de testimonios del CMS (modelos Testimonial).
     */
    public function merge(Collection $cms, string $locale): Collection
    {
        $googleApi = $this->googleApiReviews($locale);
        $tripadvisorApi = $this->tripadvisorApiReviews();

        $dropSources = [];

        if ($this->googleReviews->isEnabled() && $googleApi->isNotEmpty()) {
            $dropSources[] = 'google';
        }

        if ($this->tripadvisorReviews->isEnabled() && $tripadvisorApi->isNotEmpty()) {
            $dropSources[] = 'tripadvisor';
        }

        $filteredCms = $dropSources
            ? $cms->reject(fn ($t) => in_array(
                $this->normalizeSource(is_object($t) ? ($t->source ?? '') : ($t['source'] ?? '')),
                $dropSources,
                true
            ))->values()
            : $cms;

        return $googleApi->concat($tripadvisorApi)->concat($filteredCms)->values();
    }

    /** Normaliza el origen a una clave estable: google | tripadvisor | trivago | web */
    public function normalizeSource(?string $source): string
    {
        $s = strtolower(trim((string) $source));

        return match (true) {
            str_contains($s, 'google') => 'google',
            str_contains($s, 'tripadvisor') => 'tripadvisor',
            str_contains($s, 'trivago') => 'trivago',
            default => 'web',
        };
    }

    /** ¿La API de Google está habilitada y configurada? */
    public function isGoogleEnabled(): bool
    {
        return $this->googleReviews->isEnabled();
    }

    /**
     * Rating promedio + conteo total de reseñas del SITIO ENTERO: la misma
     * mezcla (API + CMS, deduplicada por `merge()`) que ya usan /resenas
     * (ReviewController) y el home. Cualquier consumidor nuevo que necesite
     * "el rating real" o "cuántas reseñas hay" debe pasar por aquí — nunca
     * recalcular aparte, porque terminaría mostrando un número distinto al
     * resto del sitio (ver TAREA 1 del lote 2026-08: barra de stats del hero).
     *
     * `rating` es null cuando no hay ninguna reseña (nunca se inventa un
     * "5.0 por defecto" aquí; ese fallback es decisión de cada vista/consumidor).
     *
     * @return array{rating: float|null, count: int}
     */
    public function overallStats(string $locale): array
    {
        $cms = Testimonial::active()->get();
        $merged = $this->merge($cms, $locale);
        $count = $merged->count();

        if ($count === 0) {
            return ['rating' => null, 'count' => 0];
        }

        $rating = round((float) $merged->avg(
            fn ($t) => is_object($t) ? ($t->rating ?? 5) : ($t['rating'] ?? 5)
        ), 1);

        return ['rating' => $rating, 'count' => $count];
    }

    /**
     * Estadísticas agregadas de Google (rating + total), o null si no aplica.
     *
     * @return array{rating: float, total: int}|null
     */
    public function googleStats(string $locale): ?array
    {
        return $this->googleReviews->isEnabled()
            ? $this->googleReviews->getRatingStats($locale)
            : null;
    }
}
