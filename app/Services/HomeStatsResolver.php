<?php

namespace App\Services;

use App\Models\Region;
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
    public const SOURCES = ['manual', 'rating_real', 'reviews_count', 'rating_external', 'reviews_external_count', 'tours_count', 'years_active', 'destinations_count'];

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
     * Defaults de los 4 primeros slots de la banda de /nosotros (el 5to,
     * "Valores", se resuelve aparte en aboutValuesSlot() porque no es
     * elegible por fuente: es el conteo de tags que la misma página ya
     * pinta más arriba, no un dato externo).
     *
     * 2026-08-12 — Fix CRO/medición real: con los defaults viejos (slot 1
     * `years_active`, slot 3 `manual` vacío) la banda solo mostraba 2 de 4
     * tiles ("24 Tours disponibles" + "8 Valores"), con huecos enormes en
     * el grid — no porque falten cifras inventadas, sino porque había datos
     * REALES sin usar: el mismo rating/reseñas que ya publica el hero
     * (`rating_real`/`reviews_count`, 5.0 sobre 18 opiniones) y los destinos
     * con tours publicados (`destinations_count`, hoy 3: Lima/Cusco/Ica).
     * Ningún slot nuevo inventa un número — cada uno se oculta solo si no
     * hay dato, igual que el resto del proyecto (docs/rebrand/
     * LOTE-MOCKUPS-AGO-2026.md, Fix 1).
     */
    private const ABOUT_DEFAULTS = [
        1 => ['source' => 'rating_real', 'value' => '', 'icon' => 'star', 'label' => ['es' => 'Valoración de viajeros', 'en' => 'Traveler rating', 'pt' => 'Avaliação dos viajantes']],
        2 => ['source' => 'reviews_count', 'value' => '', 'icon' => 'group', 'label' => ['es' => 'Opiniones de viajeros', 'en' => 'Traveler reviews', 'pt' => 'Avaliações de viajantes']],
        3 => ['source' => 'tours_count', 'value' => '', 'icon' => 'map', 'label' => ['es' => 'Tours & experiencias', 'en' => 'Tours & experiences', 'pt' => 'Tours & experiências']],
        4 => ['source' => 'destinations_count', 'value' => '', 'icon' => 'pin', 'label' => ['es' => 'Destinos', 'en' => 'Destinations', 'pt' => 'Destinos']],
    ];

    private const ABOUT_VALUES_LABEL = ['es' => 'Valores que nos guían', 'en' => 'Values that guide us', 'pt' => 'Valores que nos guiam'];

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
        'destinations_count' => ['es' => 'Destinos', 'en' => 'Destinations', 'pt' => 'Destinos'],
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
                fn (int $n) => $this->resolveConfigurableSlot('home_stat', $n, $locale, self::DEFAULTS[$n]),
                [1, 2, 3, 4]
            ),
        ];
    }

    /**
     * Banda "Miles de viajeros ya confiaron en nosotros" de /nosotros
     * (about.blade.php). Mismo mecanismo que el hero (`resolve()`), con su
     * propio namespace de Settings (`about_stat_*`) para no pisar los slots
     * del hero: cada franja se configura por separado aunque comparta fuentes.
     *
     * Antes esta banda tenía su PROPIA lógica duplicada en el Blade, con dos
     * defaults inventados (año de fundación → 2015, viajeros felices → 5000)
     * que ningún cliente confirmó — justo lo que la regla del proyecto
     * prohíbe. El bug reportado ("las 4 tarjetas muestran 0") no era ese: el
     * valor real SÍ se calculaba bien, pero solo se pintaba vía una animación
     * JS que arranca el HTML en literal "0"; si el JS no corre (o el
     * IntersectionObserver nunca cruza el umbral), el cero queda fijo. Con
     * este resolver el valor final ya llega resuelto al HTML — la animación
     * es una mejora progresiva, no la única fuente del número.
     *
     * El slot 5 ("Valores que nos guían") no es elegible por fuente como los
     * otros cuatro: es, literalmente, el conteo de los tags de Misión/Visión/
     * Valores que la MISMA página ya pinta más arriba (CMS `Page` slug
     * "nosotros", bloque "stats"), así que se recibe ya calculado desde la
     * vista — inventar una fuente paralela sería el mismo error que se está
     * corrigiendo (dos números que deberían coincidir y podrían divergir).
     */
    public function resolveAboutBand(string $locale, int $valuesCount): array
    {
        $enabledRaw = Setting::get('about_stats_enabled');
        $enabled = $enabledRaw === null ? true : filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN);

        $slots = array_map(
            fn (int $n) => $this->resolveConfigurableSlot('about_stat', $n, $locale, self::ABOUT_DEFAULTS[$n]),
            [1, 2, 3, 4]
        );
        $slots[] = $this->aboutValuesSlot($locale, $valuesCount);

        return [
            'enabled' => $enabled,
            'slots' => $slots,
        ];
    }

    /**
     * Resuelve un slot configurable (fuente + ícono + etiqueta por idioma)
     * bajo el namespace de Settings `{prefix}_{n}_*`. Compartido por el hero
     * (`home_stat`) y la banda de Nosotros (`about_stat`).
     */
    private function resolveConfigurableSlot(string $prefix, int $n, string $locale, array $default): array
    {
        $source = Setting::get("{$prefix}_{$n}_source") ?: $default['source'];
        if (! in_array($source, self::SOURCES, true)) {
            $source = 'manual';
        }

        $chosenIcon = Setting::get("{$prefix}_{$n}_icon");
        $icon = (is_string($chosenIcon) && HeroIcons::exists($chosenIcon)) ? $chosenIcon : $default['icon'];

        // Prioridad: etiqueta escrita en el panel → etiqueta propia de la
        // fuente calculada → etiqueta histórica del slot (solo `manual`).
        $fallbackLabels = self::SOURCE_LABELS[$source] ?? $default['label'];
        $label = Setting::get("{$prefix}_{$n}_label_{$locale}")
            ?: ($fallbackLabels[$locale] ?? $fallbackLabels['es']);

        $resolved = match ($source) {
            'rating_real' => $this->ratingReal($locale),
            'reviews_count' => $this->reviewsCount($locale),
            'rating_external' => $this->ratingExternal(),
            'reviews_external_count' => $this->reviewsExternalCount(),
            'tours_count' => $this->toursCount(),
            'years_active' => $this->yearsActive(),
            'destinations_count' => $this->destinationsCount(),
            default => $this->manual("{$prefix}_{$n}_value", $default['value']),
        };

        return [
            'source' => $source,
            'show' => $resolved['show'],
            'value' => $resolved['value'],
            'label' => $label,
            'icon' => $icon,
            // Solo `rating_external` los usa hoy; el resto siempre viaja null
            // para que el contrato sea el mismo shape en todos los slots.
            'count' => $resolved['count'],
            'url' => $resolved['url'],
        ];
    }

    /** @return array{show: bool, value: ?string, label: string, icon: string, count: ?int, url: ?string} */
    private function aboutValuesSlot(string $locale, int $valuesCount): array
    {
        $label = Setting::get('about_stat_5_label_'.$locale)
            ?: (self::ABOUT_VALUES_LABEL[$locale] ?? self::ABOUT_VALUES_LABEL['es']);
        $chosenIcon = Setting::get('about_stat_5_icon');
        $icon = (is_string($chosenIcon) && HeroIcons::exists($chosenIcon)) ? $chosenIcon : 'heart';

        if ($valuesCount <= 0) {
            return ['source' => 'values_count', 'show' => false, 'value' => null, 'label' => $label, 'icon' => $icon, 'count' => null, 'url' => null];
        }

        return ['source' => 'values_count', 'show' => true, 'value' => (string) $valuesCount, 'label' => $label, 'icon' => $icon, 'count' => $valuesCount, 'url' => null];
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
    private function manual(string $settingKey, string $fallback): array
    {
        $raw = Setting::get($settingKey);
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

    /**
     * Destinos (regiones) con al menos un tour publicado — el MISMO guard
     * automático que ya usan Home y Nosotros para pintar las tarjetas de
     * destino (Region::scopeWithPublishedTours()). Hoy da 3 (Lima, Cusco,
     * Ica); si el cliente da de alta una región nueva y le asigna un tour
     * publicado, el número sube solo, sin tocar código.
     *
     * @return array{show: bool, value: ?string, count: ?int, url: ?string}
     */
    private function destinationsCount(): array
    {
        $count = Region::active()->withPublishedTours()->count();

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

    /**
     * Badge fijo "10+ años" del hero (home.blade.php) y del split "10 años
     * mostrando lo mejor del Perú" (about.blade.php). Corrección
     * 2026-08-11: ambos badges publicaban un número de texto libre
     * ("10+", con "Miles de viajeros descubriendo el Perú" de subtítulo en
     * el de Home) sin pasar por ningún resolvedor — exactamente la cifra
     * sin respaldo que este lote decidió apagar (docs/rebrand/
     * LOTE-MOCKUPS-AGO-2026.md, tabla "Lo que NO se publica") y la MISMA
     * cifra de "viajeros" que ya está oculta en la barra de stats, así que
     * no podía reaparecer aquí como texto suelto.
     *
     * Es DISTINTO de los slots configurables de resolve()/resolveAboutBand():
     * esos 4 slots son tarjetas que el admin puede reasignar a otra fuente
     * (rating, tours, etc.) — este badge es una pieza visual fija que SIEMPRE
     * es "años de experiencia" o no existe. Usa el mismo cálculo
     * (Setting::company_started_year) que el slot 'years_active' para que
     * nunca haya dos números de "años" que puedan divergir entre sí. Sin el
     * dato, se oculta el badge completo — nunca un placeholder.
     *
     * @return array{show: bool, value: ?string}
     */
    public function yearsActiveBadge(): array
    {
        $resolved = $this->yearsActive();

        return ['show' => $resolved['show'], 'value' => $resolved['value']];
    }
}
