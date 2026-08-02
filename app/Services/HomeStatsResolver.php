<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Tour;
use App\Support\HeroIcons;

/**
 * Resuelve los 4 slots de la barra de estadísticas del hero.
 *
 * Hasta este lote los 4 valores eran texto libre en Settings
 * (`home_stat_{1..4}_value`) con defaults literales en código ("4.9",
 * "50K+ viajeros felices", "100%", "10+") — cifras que nadie puede
 * verificar y que el informe SEO marcó como riesgo de credibilidad
 * (docs/proyecto/08-seo.md, hallazgo S-04).
 *
 * Cada slot ahora elige una FUENTE (`home_stat_{n}_source`):
 *   - manual          → el texto libre de siempre (Setting `home_stat_{n}_value`).
 *   - rating_real     → rating promedio real de las reseñas EN ESTA BD, vía
 *                       ReviewAggregator::overallStats() (MISMA cuenta que usa
 *                       /resenas y el home: nunca un número paralelo).
 *   - reviews_count   → cantidad de reseñas real, misma fuente que rating_real.
 *   - rating_external → rating + conteo verificados de una fuente EXTERNA
 *                       auditable (p. ej. el snapshot de Trustindex en el
 *                       WordPress viejo: 5.0 sobre 255 opiniones de Google+
 *                       Tripadvisor) que esta base de datos todavía no tiene
 *                       replicada. Se carga a mano en Settings porque HOY no
 *                       hay integración con esas APIs — pero a diferencia de
 *                       "manual", viaja con `url` a la ficha real, que es lo
 *                       que distingue un dato verificable de uno inventado.
 *   - tours_count     → Tour::published()->count().
 *   - years_active    → años desde el Setting `company_started_year`. Sin
 *                       default en código: un valor sin confirmar del
 *                       cliente es peor que un slot oculto.
 *
 * NO existe una fuente calculada de "viajeros atendidos": el volcado de
 * producción no tiene de dónde sacar ese número (3 pedidos totales en
 * WooCommerce, formularios en 0, JetBooking son bloqueos de disponibilidad
 * sin comprador) — un cálculo real ahí daría un número ridículamente bajo,
 * peor que no tenerlo. Ese slot se deja en `manual` a propósito.
 *
 * Un slot con fuente calculada que no tiene datos (p. ej. 0 reseñas, o
 * `company_started_year`/`reviews_external_rating` vacíos o inválidos)
 * devuelve `show => false`: la vista debe OCULTAR ese slot en vez de
 * imprimir un cero o un guion. Nunca se inventa un valor de relleno.
 */
class HomeStatsResolver
{
    public const SOURCES = ['manual', 'rating_real', 'reviews_count', 'rating_external', 'tours_count', 'years_active'];

    /** Valores por defecto — idénticos a los que tenía el Blade hasta ahora. */
    private const DEFAULTS = [
        1 => ['value' => '4.9', 'icon' => 'star', 'label' => ['es' => 'Valoración de viajeros', 'en' => 'Traveler rating', 'pt' => 'Avaliação dos viajantes']],
        2 => ['value' => '50K+', 'icon' => 'group', 'label' => ['es' => 'Viajeros felices', 'en' => 'Happy travelers', 'pt' => 'Viajantes felizes']],
        3 => ['value' => '100%', 'icon' => 'shield', 'label' => ['es' => 'Cancelación gratuita', 'en' => 'Free cancellation', 'pt' => 'Cancelamento gratuito']],
        4 => ['value' => '10+', 'icon' => 'award', 'label' => ['es' => 'Años de experiencia', 'en' => 'Years of experience', 'pt' => 'Anos de experiência']],
    ];

    private ?array $overallStatsCache = null;

    public function __construct(
        private readonly ReviewAggregator $reviews,
    ) {}

    /**
     * @return array{
     *     enabled: bool,
     *     slots: array<int, array{
     *         source: string, show: bool, value: ?string, label: string,
     *         icon: string, count: ?int, url: ?string
     *     }>
     * }
     */
    public function resolve(string $locale): array
    {
        $enabledRaw = Setting::get('home_stats_enabled');
        $enabled = $enabledRaw === null ? true : filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN);

        return [
            'enabled' => $enabled,
            'slots' => array_map(
                fn (int $n) => $this->resolveSlot($n, $locale),
                [1, 2, 3, 4]
            ),
        ];
    }

    private function resolveSlot(int $n, string $locale): array
    {
        $default = self::DEFAULTS[$n];

        $source = Setting::get("home_stat_{$n}_source") ?: 'manual';
        if (! in_array($source, self::SOURCES, true)) {
            $source = 'manual';
        }

        $chosenIcon = Setting::get("home_stat_{$n}_icon");
        $icon = (is_string($chosenIcon) && HeroIcons::exists($chosenIcon)) ? $chosenIcon : $default['icon'];

        $label = Setting::get("home_stat_{$n}_label_{$locale}") ?: ($default['label'][$locale] ?? $default['label']['es']);

        $resolved = match ($source) {
            'rating_real' => $this->ratingReal($locale),
            'reviews_count' => $this->reviewsCount($locale),
            'rating_external' => $this->ratingExternal(),
            'tours_count' => $this->toursCount(),
            'years_active' => $this->yearsActive(),
            default => ['show' => true, 'value' => Setting::get("home_stat_{$n}_value") ?: $default['value'], 'count' => null, 'url' => null],
        };

        return [
            'source' => $source,
            'show' => $resolved['show'],
            'value' => $resolved['value'],
            'label' => $label,
            'icon' => $icon,
            // Solo `rating_external` los usa hoy; el resto siempre viaja null
            // para que el contrato sea el mismo shape en los 4 slots.
            'count' => $resolved['count'],
            'url' => $resolved['url'],
        ];
    }

    /** @return array{show: bool, value: ?string, count: ?int, url: ?string} */
    private function ratingReal(string $locale): array
    {
        $stats = $this->overallStats($locale);

        if ($stats['rating'] === null || $stats['count'] === 0) {
            return ['show' => false, 'value' => null, 'count' => null, 'url' => null];
        }

        return ['show' => true, 'value' => number_format($stats['rating'], 1), 'count' => $stats['count'], 'url' => null];
    }

    /** @return array{show: bool, value: ?string, count: ?int, url: ?string} */
    private function reviewsCount(string $locale): array
    {
        $stats = $this->overallStats($locale);

        if ($stats['count'] === 0) {
            return ['show' => false, 'value' => null, 'count' => null, 'url' => null];
        }

        return ['show' => true, 'value' => number_format($stats['count']), 'count' => $stats['count'], 'url' => null];
    }

    /**
     * Rating + conteo de una fuente EXTERNA auditable (Google/Tripadvisor vía
     * Trustindex u otro agregador), cargados a mano en Settings mientras no
     * exista una integración propia con esas APIs desde esta app. `url`
     * apunta a la ficha real (p. ej. la ficha de Google Business): es lo que
     * convierte "5.0" de un número de marketing a un dato verificable con un
     * clic, así que viaja siempre en el contrato aunque esté vacío.
     *
     * @return array{show: bool, value: ?string, count: ?int, url: ?string}
     */
    private function ratingExternal(): array
    {
        $ratingRaw = Setting::get('reviews_external_rating');
        $countRaw = Setting::get('reviews_external_count');
        $urlRaw = Setting::get('reviews_external_url');

        $rating = is_numeric($ratingRaw) ? (float) $ratingRaw : null;
        $count = is_numeric($countRaw) ? (int) $countRaw : null;
        $url = (is_string($urlRaw) && trim($urlRaw) !== '') ? trim($urlRaw) : null;

        if ($rating === null && $count === null) {
            return ['show' => false, 'value' => null, 'count' => null, 'url' => null];
        }

        $value = $rating !== null ? number_format($rating, 1) : (string) $count;

        return ['show' => true, 'value' => $value, 'count' => $count, 'url' => $url];
    }

    /** @return array{show: bool, value: ?string, count: ?int, url: ?string} */
    private function toursCount(): array
    {
        $count = Tour::published()->count();

        if ($count === 0) {
            return ['show' => false, 'value' => null, 'count' => null, 'url' => null];
        }

        return ['show' => true, 'value' => (string) $count, 'count' => $count, 'url' => null];
    }

    /** @return array{show: bool, value: ?string, count: ?int, url: ?string} */
    private function yearsActive(): array
    {
        $startYear = (int) Setting::get('company_started_year', 0);
        $currentYear = (int) now()->format('Y');

        if ($startYear <= 0 || $startYear > $currentYear) {
            return ['show' => false, 'value' => null, 'count' => null, 'url' => null];
        }

        $years = $currentYear - $startYear;

        if ($years <= 0) {
            return ['show' => false, 'value' => null, 'count' => null, 'url' => null];
        }

        return ['show' => true, 'value' => "{$years}+", 'count' => null, 'url' => null];
    }

    /** Memoiza dentro del request: dos slots pueden pedir la misma fuente. */
    private function overallStats(string $locale): array
    {
        return $this->overallStatsCache ??= $this->reviews->overallStats($locale);
    }
}
