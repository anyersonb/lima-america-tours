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
    public const SOURCES = ['manual', 'rating_real', 'reviews_count', 'rating_external', 'reviews_external_count', 'tours_count', 'years_active'];

    /**
     * Defaults por slot: fuente, ícono y etiqueta histórica.
     *
     * 2026-08-02 — el `source` por defecto pasa de `manual` a fuentes REALES.
     * Antes, con la clave sin configurar, el hero publicaba "4.9 / 50K+ / 100% /
     * 10+": cuatro cifras sin respaldo, y el "50K+" era **diez veces** lo que el
     * propio cliente publica en su web ("5000+"). Que el sitio dijera eso salvo
     * que alguien entrara al panel a arreglarlo era el defecto, no la
     * configuración por defecto correcta (hallazgo Bajo del CRO: el cableado
     * funcionaba pero nadie lo había activado).
     *
     * Ahora, sin tocar nada, cada slot muestra un dato calculado y **se oculta
     * solo si no hay dato** — nunca un número inventado. `manual` sigue
     * disponible para quien quiera escribir un texto a mano.
     *
     * El slot 2 arranca en `reviews_count` (las reseñas de esta base). Cuando el
     * cliente confirme el agregado externo real (5.0 sobre 255 entre Google y
     * TripAdvisor) conviene mover los slots 1 y 2 a `rating_external` y
     * `reviews_external_count`, que además enlazan a la ficha pública: un número
     * comprobable con un clic es lo que lo separa de uno inventado.
     */
    private const DEFAULTS = [
        1 => ['source' => 'rating_real', 'value' => '', 'icon' => 'star', 'label' => ['es' => 'Valoración de viajeros', 'en' => 'Traveler rating', 'pt' => 'Avaliação dos viajantes']],
        2 => ['source' => 'reviews_count', 'value' => '', 'icon' => 'group', 'label' => ['es' => 'Opiniones de viajeros', 'en' => 'Traveler reviews', 'pt' => 'Avaliações de viajantes']],
        // Ícono `map`, no el `shield` que heredaba del slot viejo ("Cancelación
        // gratuita"): un escudo junto a "Tours disponibles" no dice nada
        // (observación Bajo del CRO en la reverificación). Sigue siendo editable
        // desde el panel.
        3 => ['source' => 'tours_count', 'value' => '', 'icon' => 'map', 'label' => ['es' => 'Tours disponibles', 'en' => 'Tours available', 'pt' => 'Tours disponíveis']],
        4 => ['source' => 'years_active', 'value' => '', 'icon' => 'award', 'label' => ['es' => 'Años de experiencia', 'en' => 'Years of experience', 'pt' => 'Anos de experiência']],
    ];

    /**
     * Etiqueta por defecto SEGÚN LA FUENTE, no según el número de slot.
     *
     * Sin esto, cambiar la fuente en el panel publicaba disparates: al poner
     * el slot 4 en `tours_count` salía "24 · Años de experiencia", y el slot 3
     * en `rating_external` salía "5.0 · Cancelación gratuita" — el valor lo
     * resolvía la fuente nueva y la etiqueta seguía siendo la del slot viejo.
     * Detectado el 2026-08-02 probando el cableado CMS→front antes de pasarlo
     * a validación. La etiqueta escrita a mano en Settings sigue teniendo
     * prioridad: esto solo cubre el caso de dejarla vacía.
     */
    private const SOURCE_LABELS = [
        'rating_real' => ['es' => 'Valoración de viajeros', 'en' => 'Traveler rating', 'pt' => 'Avaliação dos viajantes'],
        'reviews_count' => ['es' => 'Opiniones de viajeros', 'en' => 'Traveler reviews', 'pt' => 'Avaliações de viajantes'],
        'rating_external' => ['es' => 'Valoración en Google y TripAdvisor', 'en' => 'Rating on Google and TripAdvisor', 'pt' => 'Avaliação no Google e TripAdvisor'],
        'reviews_external_count' => ['es' => 'Opiniones verificadas', 'en' => 'Verified reviews', 'pt' => 'Avaliações verificadas'],
        'tours_count' => ['es' => 'Tours disponibles', 'en' => 'Tours available', 'pt' => 'Tours disponíveis'],
        'years_active' => ['es' => 'Años de experiencia', 'en' => 'Years of experience', 'pt' => 'Anos de experiência'],
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

        $source = Setting::get("home_stat_{$n}_source") ?: $default['source'];
        if (! in_array($source, self::SOURCES, true)) {
            $source = 'manual';
        }

        $chosenIcon = Setting::get("home_stat_{$n}_icon");
        $icon = (is_string($chosenIcon) && HeroIcons::exists($chosenIcon)) ? $chosenIcon : $default['icon'];

        // Prioridad: etiqueta escrita en el panel → etiqueta propia de la
        // fuente calculada → etiqueta histórica del slot (solo `manual`).
        $fallbackLabels = self::SOURCE_LABELS[$source] ?? $default['label'];
        $label = Setting::get("home_stat_{$n}_label_{$locale}")
            ?: ($fallbackLabels[$locale] ?? $fallbackLabels['es']);

        $resolved = match ($source) {
            'rating_real' => $this->ratingReal($locale),
            'reviews_count' => $this->reviewsCount($locale),
            'rating_external' => $this->ratingExternal(),
            'reviews_external_count' => $this->reviewsExternalCount(),
            'tours_count' => $this->toursCount(),
            'years_active' => $this->yearsActive(),
            default => $this->manual($n, $default['value']),
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

    /**
     * Texto libre escrito en el panel.
     *
     * Un manual SIN texto se OCULTA, no se muestra vacío. Antes devolvía
     * siempre `show => true`, así que un slot en manual con el campo en blanco
     * viajaba con `value` vacío y solo se salvaba porque el Blade lo filtraba —
     * el contrato mentía y cualquier otra vista que lo consumiera habría pintado
     * una tarjeta hueca. Con los defaults ya sin cifras inventadas, este caso
     * pasó de teórico a normal.
     */
    private function manual(int $n, string $fallback): array
    {
        $raw = Setting::get("home_stat_{$n}_value");
        $value = trim((string) ($raw !== null && $raw !== '' ? $raw : $fallback));

        if ($value === '') {
            return ['show' => false, 'value' => null, 'count' => null, 'url' => null];
        }

        return ['show' => true, 'value' => $value, 'count' => null, 'url' => null];
    }

    /**
     * El NÚMERO de opiniones externas, para ponerlo en su propio slot.
     *
     * `rating_external` prioriza el promedio, así que con un solo slot no había
     * forma de publicar "5.0" y "255" a la vez — que es exactamente el
     * reemplazo del inventado "50K+ viajeros" que pide el informe de contenido
     * real (hallazgo Medio del CRO, 2026-08-02). Con esta fuente, un slot lleva
     * el promedio y otro el respaldo, ambos enlazados a la misma ficha pública.
     */
    private function reviewsExternalCount(): array
    {
        $countRaw = Setting::get('reviews_external_count');
        $urlRaw = Setting::get('reviews_external_url');

        $count = is_numeric($countRaw) ? (int) $countRaw : null;
        $url = (is_string($urlRaw) && trim($urlRaw) !== '') ? trim($urlRaw) : null;

        if ($count === null || $count <= 0) {
            return ['show' => false, 'value' => null, 'count' => null, 'url' => null];
        }

        return ['show' => true, 'value' => number_format($count), 'count' => $count, 'url' => $url];
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
