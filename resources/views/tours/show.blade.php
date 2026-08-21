@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string => $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);

    $galleryUrls = $tour->gallery_urls;

    $rawItinerary = $tour->{"itinerary_{$locale}"} ?: $tour->itinerary_es ?: [];
    $itinerary = collect($rawItinerary)->map(function ($step) {
        if (! is_array($step)) {
            return ['time' => '', 'title' => (string) $step, 'description' => '', 'image' => ''];
        }
        return [
            'time'        => trim((string) ($step['time'] ?? '')),
            'title'       => trim((string) ($step['title'] ?? '')),
            'description' => trim((string) ($step['description'] ?? '')),
            // B2: campo nuevo del repeater (backend, en paralelo) — path
            // relativo del disco `media` o vacío. Los 26 tours ya cargados
            // no tienen ninguna imagen: ese es el estado por defecto hoy y
            // el bloque debe verse igual de bien sin ella (sin hueco).
            'image'       => trim((string) ($step['image'] ?? '')),
        ];
    })->filter(fn ($s) => $s['title'] !== '')->values();

    $rawIncludes = $tour->{"includes_{$locale}"} ?: $tour->includes_es ?: [];
    $includes = collect($rawIncludes)->filter(fn ($i) => trim((string) $i) !== '')->values();
    if ($includes->isEmpty()) {
        $includes = collect([
            $L('Guía profesional bilingüe (ES/EN)', 'Bilingual professional guide (ES/EN)', 'Guia profissional bilíngue (ES/EN)'),
            $L('Transporte turístico climatizado', 'Air-conditioned tourist transport', 'Transporte turístico com ar-condicionado'),
            $L('Recojo y retorno en zonas seleccionadas', 'Pickup and drop-off in selected areas', 'Busca e retorno em áreas selecionadas'),
            $L('Impuestos y tasas incluidos', 'Taxes and fees included', 'Impostos e taxas incluídos'),
        ]);
    }

    // "No incluye" — se muestra junto a "Incluye" solo cuando el CMS trae datos
    $rawExcludes = $tour->{"excludes_{$locale}"} ?: $tour->excludes_es ?: [];
    $excludes = collect($rawExcludes)->filter(fn ($i) => trim((string) $i) !== '')->values();

    // Preguntas frecuentes (repeater question/answer del CMS) — con fallback a español
    $rawFaqs = $tour->{"faqs_{$locale}"} ?: $tour->faqs_es ?: [];
    $faqs = collect($rawFaqs)
        ->map(fn ($f) => is_array($f) ? [
            'question' => trim((string) ($f['question'] ?? '')),
            'answer'   => trim((string) ($f['answer'] ?? '')),
        ] : null)
        ->filter(fn ($f) => $f && $f['question'] !== '' && $f['answer'] !== '')
        ->values();

    // Oferta especial: precio "antes" tachado + % de descuento, cuando el CMS la tiene activa
    $offerBefore = (float) ($tour->price_before ?? 0);
    $offerNow    = (float) $tour->price;
    $hasOffer    = (bool) $tour->show_offer_badge && $offerBefore > 0 && $offerBefore > $offerNow;
    $offerPct    = $hasOffer ? (int) round((1 - ($offerNow / $offerBefore)) * 100) : null;

    $rawRecommendations = $tour->{"recommendations_{$locale}"} ?: $tour->recommendations_es ?: '';
    $bring = collect(preg_split('/\r\n|\r|\n|,/', (string) $rawRecommendations))
        ->map(fn ($l) => trim($l))
        ->filter(fn ($l) => $l !== '')
        ->values();
    if ($bring->isEmpty()) {
        $bring = collect([
            __('ui.rec_id_document'),
            __('ui.rec_comfortable_clothes'),
            __('ui.rec_sunscreen'),
            __('ui.rec_sunglasses_hat'),
            __('ui.rec_camera'),
            __('ui.rec_water'),
        ]);
    }

    // B3: "Notas importantes" — campo real del CMS (notes_es/en/pt,
    // TourResource::form, Textarea) que hoy se guarda pero no se muestra en
    // ningún lado de la ficha pública. Se suma como panel adicional SOLO si
    // trae contenido (mismo criterio que $bring/$includes: sin dato, sin
    // panel — nunca un hueco vacío).
    $rawNotes = $tour->{"notes_{$locale}"} ?? $tour->notes_es ?? '';
    $notes = collect(preg_split('/\r\n|\r|\n/', (string) $rawNotes))
        ->map(fn ($l) => trim($l))
        ->filter(fn ($l) => $l !== '')
        ->values();

    $related = ($related ?? collect())->take(3);

    // Badge del tour (badge_text/badge_type) — ya se usa en el listado
    // (tours/index.blade.php), la ficha lo ignoraba (docs/qa/ficha-tour.md
    // hallazgo #4/#5 según numeración de esta tarea). Mismo mapeo de color.
    $badgeClass = fn (?string $type) => match ($type) {
        'success' => 'lat-detail-badge--g',
        'warn'    => 'lat-detail-badge--o',
        default   => '',
    };

    $comparisonData = $tour->comparisonData();

    // Respeta el título tal cual lo escribió el editor en el CMS (sin
    // recapitalizar): una transformación previa (mb_convert_case a
    // MB_CASE_TITLE) rompía mayúsculas intencionales (siglas, marcas) en el
    // <title>/SEO de la ficha (ver docs/qa/panel-filament.md hallazgo #7).
    $titleDisplay = $tour->title;

    // ── Datos del mockup de la ficha (docs/rebrand/inventario/spec-02-tour.md).
    // Los tres son opcionales: sin URL de video no hay botón de play, sin
    // imagen de mapa no hay caja de mapa, y sin reseñas reales del tour no
    // hay reseña destacada. Ninguno se rellena con un placeholder.
    $tourVideoUrl = \App\Support\VideoEmbed::normalize($tour->video_url);
    $routeMapUrl = $tour->route_map_image ? \App\Support\ImagePath::url($tour->route_map_image) : null;
    // La reseña destacada es una REAL del propio tour: la marcada como
    // destacada si hay alguna, y si no, la primera del mismo listado que ya
    // se imprime completo más abajo. No es un segundo query ni una cita
    // inventada de ejemplo.
    $tourReviewsList = $tourReviews ?? collect();
    $featuredReview = $tourReviewsList->firstWhere('is_featured', true) ?: $tourReviewsList->first();
@endphp

@section('title', $titleDisplay . ' — ' . __('seo.site_name'))
@section('description', __('seo.tour_description_prefix') . $titleDisplay . __('seo.tour_description_suffix'))
@section('header_variant', 'solid')

@push('schema')
@php
    $canonicalUrl = route('tours.show', ['locale' => $locale, 'slug' => $tour->slug]);
    $schema = array_filter([
        '@context'    => 'https://schema.org',
        '@type'       => ['Product', 'TouristTrip'],
        '@id'         => $canonicalUrl . '#tour',
        'name'        => $tour->title,
        'description' => \Illuminate\Support\Str::limit(strip_tags((string) ($tour->description_es ?: $titleDisplay)), 300),
        'url'         => $canonicalUrl,
        'image'       => $galleryUrls ?: [$tour->cover_url],
        'inLanguage'  => $locale,
        'provider'    => [
            '@type' => 'TravelAgency',
            'name'  => 'Lima América Tours',
            'url'   => rtrim(config('app.url'), '/'),
        ],
        'aggregateRating' => $tour->rating ? [
            '@type'       => 'AggregateRating',
            'ratingValue' => max(1, min(5, round((float) $tour->rating, 1))),
            'reviewCount' => $tour->reviews_count ?: 1,
            'bestRating'  => 5,
            'worstRating' => 1,
        ] : null,
        'offers' => array_filter([
            '@type'         => 'Offer',
            'price'         => (float) $tour->price,
            'priceCurrency' => \App\Support\Money::site(),
            'availability'  => 'https://schema.org/InStock',
            'url'           => $canonicalUrl,
        ]),
    ], fn ($v) => $v !== null);
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>
@endpush

@section('content')
<div class="lat-page lat-tour-page">

    {{-- ============================================================
         BREADCRUMB
         ============================================================ --}}
    <div class="lat-crumb-bar">
        <div class="lat-wrap">
            <nav class="lat-crumb" aria-label="Breadcrumb">
                <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('ui.home') }}</a>
                <span class="lat-crumb__sep" aria-hidden="true">&middot;</span>
                <a href="{{ route('tours.index', ['locale' => $locale]) }}">{{ __('nav.tours') }}</a>
                <span class="lat-crumb__sep" aria-hidden="true">&middot;</span>
                {{-- La categoría entra acá porque salió del hero: el mockup no
                     tiene eyebrow sobre la foto, pero el dato no se tira. --}}
                @if ($tour->category?->name)
                    <a href="{{ route('tours.category', ['locale' => $locale, 'categoria' => $tour->category->slug]) }}">{{ $tour->category->name }}</a>
                    <span class="lat-crumb__sep" aria-hidden="true">&middot;</span>
                @endif
                <b class="lat-crumb__current" title="{{ $titleDisplay }}">{{ $titleDisplay }}</b>
            </nav>
        </div>
    </div>

    {{-- ============================================================
         GALERÍA + CONTENIDO + CAJA DE RESERVA
         ============================================================ --}}
    <section class="lat-wrap" style="padding:0 24px">
        <div class="lat-detail-grid">

            <div class="lat-detail-main">
                {{-- ============================================================
                     GALERÍA — slider (swipe + flechas + paginación) con
                     miniaturas y lightbox (B1). $galleryUrls es el mismo
                     array de siempre; solo cambia cómo se navega y se pinta
                     en grande. Un único <img id="galMain"> cuyo `src` cambia
                     al navegar (no un track con N <img>): más simple, cero
                     riesgo de que las imágenes 2..N compitan por LCP con la
                     primera, y sigue siendo swipeable/con flechas/paginación.
                     ============================================================ --}}
                {{-- ============================================================
                     HERO COMPUESTO — mockup `WhatsApp Image 2026-08-17 at
                     23.53.40.jpeg`, estructura en docs/rebrand/inventario/spec-02-tour.md §3.

                     No es una galería nueva: es el MISMO slider (flechas, swipe,
                     lightbox, mismo JS) con tres capas encimadas —ribbon, título
                     y botón de video— y la barra de 5 datos pegada al borde
                     inferior de la foto, ya no como tarjeta blanca flotando
                     debajo.

                     El scrim oscuro no es un degradado genérico: es el mismo
                     refuerzo ya verificado del hero del blog. El título mide
                     ≈4,75:1 sobre la zona más clara de la foto del mockup (la
                     fachada iluminada), que pasa el 3:1 de texto grande pero con
                     margen angosto — y ese margen depende de la foto que cargue
                     el cliente. Si se cambia la portada, hay que volver a medir.

                     La paginación de puntos se oculta dentro del hero: el mockup
                     no la tiene y competía por la franja inferior con el título y
                     la barra. La tira de miniaturas, que ahora va debajo del hero
                     completo, cumple la misma función y sigue siendo clicable.
                     ============================================================ --}}
                <div class="lat-tour-hero">
                    <div class="lat-gal">
                        <div class="lat-gal__main" id="galMain__wrap">
                            <button type="button" class="lat-gal__main-btn" id="galMainBtn"
                                    aria-label="{{ $L('Ampliar foto', 'Enlarge photo', 'Ampliar foto') }}">
                                <img id="galMain" src="{{ $galleryUrls[0] ?? $tour->cover_url }}" alt="{{ $titleDisplay }}" width="860" height="452" fetchpriority="high">
                            </button>

                            @if (count($galleryUrls) > 1)
                                <button type="button" class="lat-gal__nav lat-gal__nav--prev" id="galPrev" aria-label="{{ $L('Foto anterior', 'Previous photo', 'Foto anterior') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                </button>
                                <button type="button" class="lat-gal__nav lat-gal__nav--next" id="galNext" aria-label="{{ $L('Foto siguiente', 'Next photo', 'Próxima foto') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                </button>

                                <div class="lat-gal__dots" id="galDots" aria-hidden="true">
                                    @foreach ($galleryUrls as $i => $img)
                                        <button type="button" class="lat-gal__dot {{ $i === 0 ? 'is-active' : '' }}" data-index="{{ $i }}" aria-label="{{ $L('Foto', 'Photo', 'Foto') }} {{ $i + 1 }}"></button>
                                    @endforeach
                                </div>

                            @endif

                            {{-- Ribbon: el mismo badge del CMS (badge_text/badge_type)
                                 que hasta ahora salía en línea junto al rating, con el
                                 patrón de ribbon absoluto que ya usa la tarjeta del
                                 listado. --}}
                            @if ($tour->badge_text)
                                <span class="lat-tour-hero__ribbon {{ $badgeClass($tour->badge_type) }}">{{ $tour->badge_text }}</span>
                            @endif

                            {{-- Botón "Ver video": se imprime solo si el tour tiene la
                                 URL cargada en el CMS. Reutiliza el componente del
                                 modal, no una tercera copia del script. --}}
                            @if ($tourVideoUrl)
                                <button type="button" class="lat-tour-hero__video" id="tourVideoBtn"
                                        aria-haspopup="dialog" aria-controls="tourVideoModal">
                                    <span class="lat-tour-hero__video-ic" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                    </span>
                                    {{ $L('Ver video', 'Watch video', 'Ver vídeo') }}
                                </button>
                            @endif

                            {{-- Título y rating encimados sobre el scrim. El bloque no
                                 recibe eventos (pointer-events:none) para no robarle
                                 el clic de ampliar foto ni el swipe al visor; los
                                 hijos interactivos los recuperan uno por uno. --}}
                            <div class="lat-tour-hero__overlay">
                                <h1 class="lat-detail-title">{{ $titleDisplay }}</h1>

                                <div class="lat-detail-rate">
                                    {{-- El rating por tour solo se pinta si hay reseñas que lo
                                         sostengan. La columna `rating` viene sembrada en 4.8 para los
                                         24 tours y `reviews_count` en 0: publicar "4.8 (0 reseñas)" es
                                         una cifra sin respaldo, igual que inventarla
                                         (docs/rebrand/inventario/00-VALIDACION-STAGING.md). Cuando el
                                         cliente cargue reseñas reales, el bloque aparece solo. --}}
                                    @if ((int) $tour->reviews_count > 0 && (float) $tour->rating > 0)
                                        <span class="lat-stars">
                                            <span class="lat-stars__s">
                                                @for ($i = 0; $i < 5; $i++)
                                                    <svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>
                                                @endfor
                                            </span>
                                            <span class="lat-stars__rate">{{ number_format((float) $tour->rating, 1) }}</span>
                                            <span class="lat-stars__cnt">({{ $tour->reviews_count }} {{ $L('reseñas', 'reviews', 'avaliações') }})</span>
                                        </span>
                                    @endif

                                    @if ($tour->region?->name)
                                        <span class="lat-tour-hero__place">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-6.5 7-11.5a7 7 0 1 0-14 0C5 14.5 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                                            {{ $tour->region->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Barra oscura de 5 datos, pegada al borde inferior de la foto:
                         ya no es una tarjeta blanca con su propio radio flotando
                         debajo. "Salidas" sale de la barra (el mockup no lo pide) y
                         entra "Dificultad", más la cancelación, que hasta ahora vivía
                         como pastilla verde suelta al lado del rating. El copy de la
                         cancelación es el MISMO que el sitio ya publica en la franja
                         de garantías y en la barra fija móvil — no es una promesa
                         nueva. --}}
                    <div class="lat-detail-info lat-detail-info--dark">
                        @if ($tour->duration)
                            <div class="lat-di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><div><b>{{ $tour->duration }}</b><span>{{ $L('Duración', 'Duration', 'Duração') }}</span></div></div>
                        @endif
                        @if ($tour->group_type)
                            {{-- Relabel del hallazgo #7 del acta de staging: el valor de
                                 `group_type` es "Grupal / Privado", no un tamaño. --}}
                            <div class="lat-di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.7"/></svg><div><b>{{ $tour->group_type }}</b><span>{{ $L('Opciones', 'Options', 'Opções') }}</span></div></div>
                        @endif
                        @if ($tour->language)
                            <div class="lat-di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/></svg><div><b>{{ $tour->language }}</b><span>{{ $L('Idiomas', 'Languages', 'Idiomas') }}</span></div></div>
                        @endif
                        @if ($tour->difficulty)
                            <div class="lat-di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h16M7 20V9l5-5 5 5v11"/></svg><div><b>{{ $tour->difficulty }}</b><span>{{ $L('Dificultad', 'Difficulty', 'Dificuldade') }}</span></div></div>
                        @endif
                        <div class="lat-di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><div><b>{{ __('ui.free_cancellation') }}</b><span>{{ __('ui.cancellation_24h') }}</span></div></div>
                    </div>
                </div>

                {{-- Tira de miniaturas: sale de dentro de `.lat-gal` (iba pegada al
                     visor) y pasa a ir DESPUÉS del hero completo, como en el mockup.
                     El JS no cambia: sigue enganchando por `.lat-gal__thumb`. --}}
                @if (count($galleryUrls) > 1)
                    @php $thumbCap = 8; $thumbExtra = count($galleryUrls) - $thumbCap; @endphp
                    <div class="lat-gal__thumbs">
                        @foreach ($galleryUrls as $i => $img)
                            @if ($i >= $thumbCap) @break @endif

                            @if ($i === $thumbCap - 1 && $thumbExtra > 0)
                                {{-- Última casilla "+N": el mockup la usa como puerta al
                                     visor completo. Imprime el número real de fotos que
                                     faltan, no un texto fijo. --}}
                                <button type="button" class="lat-gal__thumb lat-gal__thumb--more" id="galMoreThumb"
                                        aria-label="{{ $L('Ver las '.count($galleryUrls).' fotos', 'View all '.count($galleryUrls).' photos', 'Ver as '.count($galleryUrls).' fotos') }}">
                                    <img src="{{ $img }}" alt="" loading="lazy" width="200" height="150">
                                    <span class="lat-gal__thumb-more">
                                        <b>+{{ $thumbExtra }}</b>
                                        <span>{{ $L('Ver más fotos', 'More photos', 'Ver mais fotos') }}</span>
                                    </span>
                                </button>
                            @else
                                <button type="button" class="lat-gal__thumb {{ $i === 0 ? 'is-active' : '' }}" data-index="{{ $i }}" aria-label="{{ $L('Ver foto', 'View photo', 'Ver foto') }} {{ $i + 1 }}">
                                    <img src="{{ $img }}" alt="" loading="lazy" width="200" height="150">
                                </button>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- LIGHTBOX — overlay a pantalla completa; la imagen grande se
                     carga diferida (sin `src` hasta el primer clic, igual que
                     el iframe del modal "Ver video" del home) para no penalizar
                     el LCP de la ficha. --}}
                @if (count($galleryUrls) > 0)
                    <div class="lat-lightbox" id="galLightbox" role="dialog" aria-modal="true"
                         aria-label="{{ $L('Galería de fotos', 'Photo gallery', 'Galeria de fotos') }}" hidden>
                        <div class="lat-lightbox__backdrop" data-lb-close></div>
                        <button type="button" class="lat-lightbox__close" data-lb-close aria-label="{{ $L('Cerrar', 'Close', 'Fechar') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                        @if (count($galleryUrls) > 1)
                            <button type="button" class="lat-lightbox__nav lat-lightbox__nav--prev" id="lbPrev" aria-label="{{ $L('Foto anterior', 'Previous photo', 'Foto anterior') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <button type="button" class="lat-lightbox__nav lat-lightbox__nav--next" id="lbNext" aria-label="{{ $L('Foto siguiente', 'Next photo', 'Próxima foto') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        @endif
                        <div class="lat-lightbox__stage">
                            <img id="lbImg" alt="" loading="lazy" decoding="async">
                        </div>
                        @if (count($galleryUrls) > 1)
                            <div class="lat-lightbox__count" id="lbCount" aria-live="polite"></div>
                        @endif
                    </div>
                @endif

                {{-- ============================================================
                     FILA DE 3 CAJAS — "Lo más destacado" / mapa / reseña
                     destacada (spec-02-tour.md §5). Va entre las miniaturas y el
                     bloque de Descripción/Incluye, como en el mockup.

                     Ninguna de las tres inventa contenido: los destacados son los
                     primeros pasos del itinerario que ya carga el CMS, el mapa es
                     la imagen que sube el cliente (`tours.route_map_image`) y la
                     reseña es una real del propio tour. Cada caja que no tenga
                     dato no se imprime, y si no queda ninguna, la fila entera
                     desaparece — no hay tarjetas vacías de relleno.
                     ============================================================ --}}
                @php
                    // Cuántas cajas hay REALMENTE: de esto salen las columnas del
                    // grid. Medido en staging: con una sola caja cargada (este tour
                    // no tiene mapa ni reseñas) la grilla de 3 columnas la dejaba
                    // sola con dos tercios vacíos.
                    $boxCount = ($itinerary->isNotEmpty() ? 1 : 0) + ($routeMapUrl ? 1 : 0) + ($featuredReview ? 1 : 0);
                @endphp
                @if ($boxCount > 0)
                    <div class="lat-tour-boxes" data-count="{{ $boxCount }}">
                        @if ($itinerary->isNotEmpty())
                            <div class="lat-tbox">
                                <h2 class="lat-tbox__title">{{ $L('Lo más destacado', 'Highlights', 'Os destaques') }}</h2>
                                <ul class="lat-tbox__list">
                                    @foreach ($itinerary->take(4) as $step)
                                        <li>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>
                                            <span>{{ $step['title'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($routeMapUrl)
                            <div class="lat-tbox lat-tbox--map">
                                <button type="button" class="lat-tbox__map" id="mapBtn"
                                        aria-haspopup="dialog" aria-controls="mapLightbox">
                                    <img src="{{ $routeMapUrl }}" alt="{{ $L('Mapa del recorrido de ', 'Route map of ', 'Mapa do percurso de ') }}{{ $titleDisplay }}" loading="lazy" width="420" height="240">
                                    <span class="lat-tbox__map-cta">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        {{ $L('Ver mapa del recorrido', 'View route map', 'Ver mapa do percurso') }}
                                    </span>
                                </button>
                            </div>
                        @endif

                        @if ($featuredReview)
                            <div class="lat-tbox lat-tbox--quote">
                                <span class="lat-tbox__mark" aria-hidden="true">&ldquo;</span>
                                <p class="lat-tbox__quote">{{ $featuredReview->quote }}</p>
                                <div class="lat-tbox__author">
                                    @if ($featuredReview->avatar)
                                        <img src="{{ $featuredReview->avatar }}" alt="" loading="lazy" width="40" height="40">
                                    @endif
                                    <div>
                                        <b>{{ $featuredReview->name }}</b>
                                        @if ($featuredReview->country)
                                            <span>{{ $featuredReview->country }}</span>
                                        @endif
                                    </div>
                                    @if ((float) $featuredReview->rating > 0)
                                        <span class="lat-stars">
                                            <span class="lat-stars__s">
                                                @for ($i = 0; $i < 5; $i++)
                                                    <svg viewBox="0 0 24 24" class="{{ $i < round((float) $featuredReview->rating) ? '' : 'is-empty' }}"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>
                                                @endfor
                                            </span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ============================================================
                     DESCRIPCIÓN + INCLUYE — siempre visibles, a dos columnas.

                     Decisión de Anyerson (2026-08-21), que era la que la spec
                     dejaba abierta: estos dos salen del sistema de tabs y quedan
                     siempre a la vista, como en el mockup, y los otros tres
                     paneles (Itinerario, Qué llevar, Información importante) bajan
                     al acordeón de más abajo. No se pierde contenido: los 24 tours
                     publicados tienen itinerario —hasta 3.569 caracteres— y el
                     mockup simplemente no lo maquetó.
                     ============================================================ --}}
                <div class="lat-tour-cols">
                    <section class="lat-tour-col" aria-labelledby="tour-desc-title">
                        <h2 class="lat-tour-col__title" id="tour-desc-title">{{ $L('Descripción', 'Description', 'Descrição') }}</h2>

                        @if ($tour->description)
                            @foreach (explode("\n", $tour->description) as $para)
                                @continue(trim($para) === '')
                                <p>{{ $para }}</p>
                            @endforeach
                        @endif

                        <div class="lat-detail-highlights">
                            <span class="lat-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>{{ $L('Guía profesional', 'Professional guide', 'Guia profissional') }}</span>
                            <span class="lat-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>{{ $L('Experiencia auténtica', 'Authentic experience', 'Experiência autêntica') }}</span>
                            <span class="lat-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>{{ $L('Asistencia personalizada', 'Personalized support', 'Atendimento personalizado') }}</span>
                        </div>
                    </section>

                    <section class="lat-tour-col" aria-labelledby="tour-incl-title">
                        <h2 class="lat-tour-col__title" id="tour-incl-title">{{ $L('Incluye', 'Includes', 'Inclui') }}</h2>

                        <ul class="lat-tour-col__list">
                            @foreach ($includes as $item)
                                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>

                        @if ($excludes->isNotEmpty())
                            <p class="lat-tour-col__subtitle">{{ $L('No incluye', "Doesn't include", 'Não inclui') }}</p>
                            <ul class="lat-tour-col__list lat-tour-col__list--no">
                                @foreach ($excludes as $item)
                                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg><span>{{ $item }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>

                {{-- ============================================================
                     ACORDEÓN de Itinerario / Qué llevar / Información
                     importante — en TODOS los anchos.

                     Antes esto era un sistema de 5 tabs (tabs ≥1024px,
                     acordeón por debajo). Descripción e Incluye salieron de
                     acá y ahora están siempre visibles a dos columnas, como
                     pide el mockup; los tres paneles que el mockup no maquetó
                     se quedan acá, plegados, porque su contenido existe en las
                     24 fichas publicadas y no se tira.

                     El marcado no cambió: los mismos `.lat-tab-item` con
                     role="tab"/aria-expanded y el mismo JS, que ahora ve el
                     modificador --accordion y trata cada clic como acordeón
                     independiente sin mirar el ancho. MISMO marcado
                     para ambos: cada `.lat-tab-item` empareja un botón con su
                     panel (nunca se duplica el contenido de ningún panel). En
                     desktop, CSS (`display:contents` + `order` en
                     pages/_lat-tour.scss) saca visualmente los botones de su
                     wrapper y los agrupa en una barra horizontal de tabs; en
                     mobile cada par queda apilado en flujo normal (botón =
                     cabecera del acordeón, con chevron). El JS (al final del
                     archivo) decide, según el ancho actual, si un clic actúa
                     como "tab exclusivo" o como "acordeón independiente".
                     ============================================================ --}}
                <div class="lat-tabs-wrap lat-tabs-wrap--accordion" id="tabsWrap">
                    <h2 class="lat-tabs-wrap__mobile-heading">{{ $L('Detalles completos', 'Full details', 'Detalhes completos') }}</h2>

                    <div class="lat-tab-item" data-tab="itin">
                        <button type="button" class="lat-tab is-active" data-tab="itin" role="tab"
                                id="tabbtn-itin" aria-controls="panel-itin" aria-selected="true" aria-expanded="true">
                            <span class="lat-tab__label">{{ $L('Itinerario', 'Itinerary', 'Itinerário') }}</span>
                            <svg class="lat-tab__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div class="lat-tab-panel is-active" data-tab="itin" id="panel-itin" role="tabpanel" aria-labelledby="tabbtn-itin">
                            @forelse ($itinerary as $i => $step)
                                @php $stepImgUrl = $step['image'] !== '' ? \App\Support\ImagePath::url($step['image']) : null; @endphp
                                <div class="lat-itin-step {{ $stepImgUrl ? 'has-image' : '' }}">
                                    @if ($stepImgUrl)
                                        <img class="lat-itin-step__img" src="{{ $stepImgUrl }}" alt="{{ $step['title'] }}" loading="lazy" width="100" height="100">
                                    @endif
                                    <div class="lat-itin-step__num">{{ $i + 1 }}</div>
                                    <div>
                                        @if ($step['time'])
                                            <span class="lat-itin-step__time">{{ $step['time'] }}</span>
                                        @endif
                                        <h3>{{ $step['title'] }}</h3>
                                        @if ($step['description'])
                                            <p>{{ $step['description'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p>{{ $L('El itinerario detallado de este tour estará disponible próximamente.', "This tour's detailed itinerary will be available soon.", 'O itinerário detalhado deste tour estará disponível em breve.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="lat-tab-item" data-tab="bring">
                        <button type="button" class="lat-tab" data-tab="bring" role="tab"
                                id="tabbtn-bring" aria-controls="panel-bring" aria-selected="false" aria-expanded="false">
                            <span class="lat-tab__label">{{ $L('Qué llevar', 'What to bring', 'O que levar') }}</span>
                            <svg class="lat-tab__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div class="lat-tab-panel" data-tab="bring" id="panel-bring" role="tabpanel" aria-labelledby="tabbtn-bring">
                            <ul>
                                @foreach ($bring as $item)
                                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>{{ $item }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    @if ($notes->isNotEmpty())
                        <div class="lat-tab-item" data-tab="notes">
                            <button type="button" class="lat-tab" data-tab="notes" role="tab"
                                    id="tabbtn-notes" aria-controls="panel-notes" aria-selected="false" aria-expanded="false">
                                <span class="lat-tab__label">{{ $L('Información importante', 'Important information', 'Informação importante') }}</span>
                                <svg class="lat-tab__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div class="lat-tab-panel" data-tab="notes" id="panel-notes" role="tabpanel" aria-labelledby="tabbtn-notes">
                                @foreach ($notes as $line)
                                    <p>{{ $line }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Preguntas frecuentes: solo si el tour tiene FAQs cargadas en el CMS --}}
                @if ($faqs->isNotEmpty())
                    <div class="lat-faq" aria-labelledby="faq-title">
                        <h2 id="faq-title" class="lat-faq__title">{{ $L('Preguntas frecuentes', 'Frequently asked questions', 'Perguntas frequentes') }}</h2>
                        @foreach ($faqs as $i => $faq)
                            <details class="lat-faq-item" {{ $i === 0 ? 'open' : '' }}>
                                <summary>
                                    <span>{{ $faq['question'] }}</span>
                                    <span class="lat-faq-item__ic" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                    </span>
                                </summary>
                                <p class="lat-faq-item__a">{{ $faq['answer'] }}</p>
                            </details>
                        @endforeach
                    </div>
                @endif

                {{-- Bloque comparativo convencional vs. premium: solo cuando el CMS lo
                     tiene activo y con datos (Tour::comparisonData() ya valida ambas
                     condiciones y devuelve null en caso contrario). Hallazgo #2. --}}
                @if ($comparisonData)
                    <div class="lat-detail-comparison">
                        <x-tour-comparison :data="$comparisonData" variant="d" />
                    </div>
                @endif

                {{-- Reseñas aprobadas del propio tour ($tourReviews, testimonials()
                     filtrados por is_active en el controlador). Solo el listado de
                     lectura: el formulario de envío queda fuera de esta corrección
                     (ver docs/qa/BACKLOG-CONTENIDO.md). Hallazgo #3. --}}
                @if (($tourReviews ?? collect())->isNotEmpty())
                    <div class="lat-reviews" aria-labelledby="reviews-title">
                        <h2 id="reviews-title" class="lat-reviews__title">{{ $L('Reseñas de viajeros', 'Traveler reviews', 'Avaliações de viajantes') }}</h2>
                        <div class="lat-reviews__list">
                            @foreach ($tourReviews as $review)
                                <article class="lat-review">
                                    <div class="lat-review__head">
                                        <span class="lat-review__name">{{ $review->name }}</span>
                                        @if ($review->created_at)
                                            <span class="lat-review__date">{{ $review->created_at->translatedFormat('d M Y') }}</span>
                                        @endif
                                    </div>
                                    <span class="lat-stars">
                                        <span class="lat-stars__s">
                                            @for ($i = 0; $i < 5; $i++)
                                                <svg viewBox="0 0 24 24" class="{{ $i < round((float) $review->rating) ? '' : 'is-empty' }}"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>
                                            @endfor
                                        </span>
                                    </span>
                                    <p class="lat-review__comment">{{ $review->quote }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ── Caja "¿Tienes dudas?" (spec-02-tour.md §6): último bloque de
                     la columna principal, no de la franja de garantías. El número
                     sale de Setting::whatsappNumber(), el mismo que ya usa Contacto
                     y el botón flotante — si no hay número cargado, la caja no se
                     imprime en lugar de dejar un botón que no lleva a ninguna
                     parte. ── --}}
                @php $tourWhatsapp = \App\Models\Setting::whatsappNumber(); @endphp
                @if ($tourWhatsapp)
                    <div class="lat-tour-help">
                        <div class="lat-tour-help__left">
                            <span class="lat-tour-help__ic" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.7"/><circle cx="12" cy="17" r=".6" fill="currentColor"/></svg>
                            </span>
                            <div>
                                <b>{{ $L('¿Tienes dudas?', 'Any questions?', 'Tem dúvidas?') }}</b>
                                <span>{{ $L('Escríbenos por WhatsApp, estamos listos para ayudarte.', 'Message us on WhatsApp, we are ready to help.', 'Escreva pelo WhatsApp, estamos prontos para ajudar.') }}</span>
                            </div>
                        </div>
                        <a class="lat-btn lat-btn--wa" href="https://wa.me/{{ $tourWhatsapp }}" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.6.2-.2.3-.7.9-.9 1.1-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.4-1.5-.9-.8-1.5-1.8-1.6-2.1-.2-.3 0-.4.1-.6l.5-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.2-.6-1.5-.9-2-.2-.5-.4-.5-.6-.5h-.5c-.2 0-.5.1-.7.3-.3.3-1 .9-1 2.3s1 2.7 1.2 2.9c.1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3zM12 2a10 10 0 0 0-8.6 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2z"/></svg>
                            {{ $L('Contactar por WhatsApp', 'Contact on WhatsApp', 'Falar no WhatsApp') }}
                        </a>
                    </div>
                @endif
            </div>

            {{-- ============================================================
                 CAJA DE RESERVA (sticky) + MÁS TOURS
                 ============================================================ --}}
            <aside class="lat-book">
                <div class="lat-book-card">
                    <div class="lat-book-head">
                        @if ($hasOffer)
                            <div class="lat-book-head__offer">
                                <span class="lat-book-head__before">{{ \App\Support\Money::format($offerBefore, \App\Support\Money::site()) }}</span>
                                <span class="lat-book-head__pct">-{{ $offerPct }}%</span>
                            </div>
                        @endif
                        <small>{{ $L('Desde', 'From', 'Desde') }}</small>
                        <div class="lat-amt">{{ \App\Support\Money::format($tour->price, \App\Support\Money::site()) }}<span> {{ $L('por persona', 'per person', 'por pessoa') }}</span></div>
                    </div>
                    <form class="lat-book-body" method="POST" action="{{ route('cart.store', ['locale' => $locale]) }}">
                        @csrf
                        <input type="hidden" name="tour_id" value="{{ $tour->id }}">
                        <input type="hidden" name="children" value="0">

                        {{-- El backend ya rechaza fechas bloqueadas (CartController@store,
                             defensa en profundidad), pero el mensaje nunca se mostraba
                             (hallazgo #1a): withErrors(['travel_date' => ...]) llega en la
                             sesión tras el redirect()->back(), y $errors nunca se leía aquí. --}}
                        @if ($errors->any())
                            <div class="lat-book-error" role="alert">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
                                <div>
                                    @foreach ($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <label for="bkPax">{{ $L('Número de pasajeros', 'Number of travelers', 'Número de passageiros') }}</label>
                            <div class="lat-book-field">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.7"/></svg>
                                <select id="bkPax" name="adults" data-price="{{ (float) $tour->price }}">
                                    @for ($p = 1; $p <= 8; $p++)
                                        <option value="{{ $p }}" {{ $p === 2 ? 'selected' : '' }}>{{ $p }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="bkDate">{{ $L('Seleccionar fecha', 'Select a date', 'Selecionar data') }}</label>
                            <div class="lat-book-field" id="bkDateField">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                <input id="bkDate" type="date" name="travel_date" min="{{ now()->addDay()->toDateString() }}" required aria-describedby="bkDateError">
                            </div>
                            <p class="lat-field-error" id="bkDateError" hidden>{{ __('booking.date_blocked') }}</p>
                        </div>
                        <div class="lat-book-total">
                            <span class="lat-lbl">{{ $L('Precio total', 'Total price', 'Preço total') }}</span>
                            <span class="lat-tot" id="bkTotal">{{ \App\Support\Money::format((float) $tour->price * 2, \App\Support\Money::site(), 2) }}</span>
                        </div>
                        @if (! is_null($tour->booking_advance_hours))
                            <div class="lat-book-advance">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                <span>{{ $L("Reserva con {$tour->booking_advance_hours} horas de anticipación", "Book at least {$tour->booking_advance_hours} hours in advance", "Reserve com {$tour->booking_advance_hours} horas de antecedência") }}</span>
                            </div>
                        @endif
                        <button type="submit" class="lat-btn lat-btn--red" style="width:100%">{{ $L('Reservar ahora', 'Book now', 'Reservar agora') }}</button>
                        {{-- "Pago 100% seguro" solo si de verdad hay una pasarela que
                             pueda cobrar. Hoy las llaves de Culqi y PayPal son de
                             prueba, así que la reserva se confirma por WhatsApp/correo
                             y no hay cobro inmediato: prometer un pago seguro que no
                             existe es peor que no prometer nada. La bandera la calcula
                             App\Support\OnlinePayment. --}}
                        <div class="lat-book-secure">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            @if ($onlinePayment)
                                {{ $L('Pago 100% seguro', '100% secure payment', 'Pagamento 100% seguro') }}
                            @else
                                {{ $L('Sin cobro ahora: confirmamos tu reserva por WhatsApp o correo', 'No charge now: we confirm your booking by WhatsApp or email', 'Sem cobrança agora: confirmamos sua reserva por WhatsApp ou e-mail') }}
                            @endif
                        </div>
                    </form>
                </div>

                @if ($related->isNotEmpty())
                    <div class="lat-more-tours-card">
                        <h3>{{ $L('Más tours', 'More tours', 'Mais tours') }}</h3>
                        @foreach ($related as $rel)
                            <a class="lat-mini-tour" href="{{ route('tours.show', ['locale' => $locale, 'slug' => $rel->slug]) }}">
                                <div class="lat-mini-tour__img"><img src="{{ $rel->cover_url }}" alt="{{ $rel->title }}" loading="lazy" width="74" height="60"></div>
                                <div>
                                    <h4 class="clamp-2">{{ $rel->title }}</h4>
                                    <div class="lat-mprice">{{ \App\Support\Money::format($rel->price, \App\Support\Money::site()) }}</div>
                                    @if ((int) $rel->reviews_count > 0 && (float) $rel->rating > 0)
                                    <span class="lat-stars">
                                        <span class="lat-stars__s"><svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg></span>
                                        <span class="lat-stars__rate">{{ number_format((float) $rel->rating, 1) }}</span>
                                        <span class="lat-stars__cnt">({{ $rel->reviews_count }})</span>
                                    </span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    </section>

    {{-- ============================================================
         FRANJA DE GARANTÍAS (reutiliza .lat-guarantee/.lat-gt de Home)
         ============================================================ --}}
    <div class="lat-guarantee">
        <div class="lat-wrap lat-guarantee__grid">
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg></div>
                <div><b>{{ $L('Cancelación gratuita', 'Free cancellation', 'Cancelamento gratuito') }}</b><span>{{ $L('Hasta 24 horas antes del tour', 'Up to 24 hours before the tour', 'Até 24 horas antes do tour') }}</span></div>
            </div>
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div>
                <div><b>{{ $L('Reserva flexible', 'Flexible booking', 'Reserva flexível') }}</b><span>{{ $L('Cambia la fecha sin costo adicional', 'Change the date at no extra cost', 'Mude a data sem custo adicional') }}</span></div>
            </div>
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg></div>
                <div><b>{{ $L('Mejor precio garantizado', 'Best price guaranteed', 'Melhor preço garantido') }}</b><span>{{ $L('Sin cargos ocultos', 'No hidden fees', 'Sem taxas ocultas') }}</span></div>
            </div>
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg></div>
                <div><b>{{ $L('Atención al cliente', 'Customer support', 'Atendimento ao cliente') }}</b><span>{{ $contactHours ?? $L('Estamos para ayudarte', "We're here to help", 'Estamos aqui para ajudar') }}</span></div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         BARRA FIJA INFERIOR (B4) — solo visible <1024px (CSS). El precio
         respeta el formateo/moneda del sitio y refleja la oferta tachada
         igual que la caja de reserva. El botón NO envía el formulario: hace
         scroll a la caja de reserva y enfoca el primer campo (la reserva
         necesita fecha y pasajeros). Comparte clase .lat-btn con el resto
         del sitio para que el FAB de WhatsApp (layouts/app.blade.php) la
         detecte y no se solape (.lat-sticky-book ya está en su
         CONTROL_SELECTOR).
         ============================================================ --}}
    <div class="lat-sticky-book" id="stickyBook">
        <div class="lat-sticky-book__trust">
            <span>{{ $L('Cancelación gratuita: hasta 24 h', 'Free cancellation: up to 24 h', 'Cancelamento gratuito: até 24 h') }}</span>
            <span>{{ $L('Reserva ahora y paga después', 'Book now, pay later', 'Reserve agora e pague depois') }}</span>
        </div>
        <div class="lat-sticky-book__row">
            <div class="lat-sticky-book__price">
                @if ($hasOffer)
                    <small class="lat-sticky-book__before">{{ \App\Support\Money::format($offerBefore, \App\Support\Money::site()) }}</small>
                @endif
                <span class="lat-sticky-book__amt">{{ \App\Support\Money::format($tour->price, \App\Support\Money::site()) }}</span>
                <small>{{ $L('por persona', 'per person', 'por pessoa') }}</small>
            </div>
            <button type="button" class="lat-btn lat-btn--red" id="stickyBookBtn">
                {{ $L('Reserva ahora', 'Book now', 'Reserve agora') }}
            </button>
        </div>
    </div>

    {{-- Modal del video del hero: componente compartido, no una copia del
         script. Si el tour no tiene video_url, el componente no imprime nada. --}}
    <x-video-modal id="tourVideoModal" :url="$tourVideoUrl"
                   :label="$L('Ver video del tour', 'Watch tour video', 'Ver vídeo do tour')" />

    {{-- Visor del mapa del recorrido. Reutiliza el mismo componente visual
         del lightbox de la galería (mismas clases, mismo aspecto) con ids
         propios: son dos diálogos distintos en la misma página y compartir
         id rompería getElementById. La imagen del mapa ya está cargada en la
         caja, así que acá no hay carga diferida que administrar. --}}
    @if ($routeMapUrl)
        <div class="lat-lightbox" id="mapLightbox" role="dialog" aria-modal="true"
             aria-label="{{ $L('Mapa del recorrido', 'Route map', 'Mapa do percurso') }}" hidden>
            <div class="lat-lightbox__backdrop" data-map-close></div>
            <button type="button" class="lat-lightbox__close" data-map-close aria-label="{{ $L('Cerrar', 'Close', 'Fechar') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
            <div class="lat-lightbox__stage">
                <img src="{{ $routeMapUrl }}" alt="{{ $L('Mapa del recorrido de ', 'Route map of ', 'Mapa do percurso de ') }}{{ $titleDisplay }}" loading="lazy" decoding="async">
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.querySelector('.lat-page');
    if (!root) return;

    // ── TABS (≥1024px) / ACORDEÓN (<1024px) — B3 ───────────────────────
    // MISMO marcado para ambos modos (ver comentario en el HTML): lo único
    // que cambia es cómo se interpreta un clic. `mq` decide en cada click
    // cuál de los dos modos aplica — nunca se lee un valor cacheado viejo.
    var mqDesktop = window.matchMedia('(min-width: 1024px)');
    // Con el modificador --accordion (los 3 paneles que quedaron: itinerario,
    // qué llevar e información importante) el ancho deja de importar: siempre
    // es acordeón independiente, en desktop también. Es la decisión de
    // Anyerson del 2026-08-21 sobre el mockup de la ficha.
    var forceAccordion = !!root.querySelector('.lat-tabs-wrap--accordion');
    var tabButtons = Array.prototype.slice.call(root.querySelectorAll('.lat-tab'));
    var tabPanels = Array.prototype.slice.call(root.querySelectorAll('.lat-tab-panel'));

    function setTabState(btn, panel, active) {
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
        btn.setAttribute('aria-expanded', active ? 'true' : 'false');
        if (panel) panel.classList.toggle('is-active', active);
    }

    function panelFor(name) {
        return tabPanels.filter(function (p) { return p.getAttribute('data-tab') === name; })[0] || null;
    }

    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var name = btn.getAttribute('data-tab');
            var panel = panelFor(name);

            if (mqDesktop.matches && !forceAccordion) {
                // Tabs exclusivos: solo el clicado queda activo.
                tabButtons.forEach(function (b) { setTabState(b, panelFor(b.getAttribute('data-tab')), b === btn); });
            } else {
                // Acordeón independiente: alterna SOLO este panel.
                setTabState(btn, panel, !btn.classList.contains('is-active'));
            }
        });
    });

    // Al cruzar el punto de corte 1024px, normaliza el estado para no dejar
    // paneles huérfanos: si venías de móvil con 2+ paneles abiertos (válido
    // en acordeón) y la pantalla pasa a desktop (tabs exclusivos), deja
    // activo solo el primero que ya estaba abierto — nunca los deja todos
    // ocultos ni todos visibles a la vez.
    mqDesktop.addEventListener('change', function (e) {
        if (forceAccordion) return; // en modo acordeón fijo no hay nada que normalizar
        if (!e.matches) return; // mobile→desktop es el único caso que necesita normalizar
        var activeButtons = tabButtons.filter(function (b) { return b.classList.contains('is-active'); });
        var keep = activeButtons[0] || tabButtons[0];
        tabButtons.forEach(function (b) { setTabState(b, panelFor(b.getAttribute('data-tab')), b === keep); });
    });

    // ── GALERÍA: slider (B1) ────────────────────────────────────────────
    var galUrls = @json($galleryUrls);
    var galMain = document.getElementById('galMain');
    var galIndex = 0;

    function galShow(i) {
        if (!galUrls.length) return;
        galIndex = ((i % galUrls.length) + galUrls.length) % galUrls.length; // wrap circular
        if (galMain) galMain.src = galUrls[galIndex];
        root.querySelectorAll('.lat-gal__thumb').forEach(function (t) {
            t.classList.toggle('is-active', parseInt(t.getAttribute('data-index'), 10) === galIndex);
        });
        root.querySelectorAll('.lat-gal__dot').forEach(function (d) {
            d.classList.toggle('is-active', parseInt(d.getAttribute('data-index'), 10) === galIndex);
        });
    }

    root.querySelectorAll('.lat-gal__thumb').forEach(function (th) {
        th.addEventListener('click', function () { galShow(parseInt(th.getAttribute('data-index'), 10)); });
    });

    var galPrevBtn = document.getElementById('galPrev');
    var galNextBtn = document.getElementById('galNext');
    if (galPrevBtn) galPrevBtn.addEventListener('click', function () { galShow(galIndex - 1); });
    if (galNextBtn) galNextBtn.addEventListener('click', function () { galShow(galIndex + 1); });

    root.querySelectorAll('.lat-gal__dot').forEach(function (dot) {
        dot.addEventListener('click', function () { galShow(parseInt(dot.getAttribute('data-index'), 10)); });
    });

    // Swipe táctil en el visor principal.
    var galMainWrap = document.getElementById('galMain__wrap');
    if (galMainWrap) {
        var touchStartX = null;
        galMainWrap.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].clientX;
        }, { passive: true });
        galMainWrap.addEventListener('touchend', function (e) {
            if (touchStartX === null) return;
            var dx = e.changedTouches[0].clientX - touchStartX;
            touchStartX = null;
            if (Math.abs(dx) < 40) return; // umbral: evita disparar con un tap
            if (dx < 0) galShow(galIndex + 1); else galShow(galIndex - 1);
        }, { passive: true });
    }

    // ── LIGHTBOX (B1) ────────────────────────────────────────────────────
    var lightbox = document.getElementById('galLightbox');
    if (lightbox && galUrls.length) {
        var lbImg = document.getElementById('lbImg');
        var lbCount = document.getElementById('lbCount');
        var lbPrevBtn = document.getElementById('lbPrev');
        var lbNextBtn = document.getElementById('lbNext');
        var lbClosers = lightbox.querySelectorAll('[data-lb-close]');
        var lbIndex = 0;
        var lbLastFocused = null;
        var lbSavedScrollY = 0;

        function lbRender() {
            // `loading="lazy"` + src solo asignado aquí: la foto grande nunca
            // se descarga hasta que el usuario abre el lightbox (no penaliza
            // el LCP de la ficha).
            lbImg.src = galUrls[lbIndex];
            lbImg.alt = @json($titleDisplay) + ' — ' + (lbIndex + 1);
            if (lbCount) lbCount.textContent = (lbIndex + 1) + ' / ' + galUrls.length;
        }

        function lbFocusables() {
            return Array.prototype.slice
                .call(lightbox.querySelectorAll('button, [href], [tabindex]:not([tabindex="-1"])'))
                .filter(function (el) { return el.offsetParent !== null; });
        }

        function lbKeydown(e) {
            if (e.key === 'Escape' || e.key === 'Esc') { lbClose(); return; }
            if (e.key === 'ArrowLeft') { lbShow(lbIndex - 1); return; }
            if (e.key === 'ArrowRight') { lbShow(lbIndex + 1); return; }
            if (e.key !== 'Tab') return;
            var els = lbFocusables();
            if (!els.length) return;
            var first = els[0], last = els[els.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }

        function lbShow(i) {
            lbIndex = ((i % galUrls.length) + galUrls.length) % galUrls.length;
            lbRender();
        }

        function lbOpen(startIndex) {
            lbLastFocused = document.activeElement;
            lbSavedScrollY = window.scrollY;
            lbIndex = startIndex || 0;
            lbRender();
            lightbox.hidden = false;
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', lbKeydown);
            var els = lbFocusables();
            (els[0] || lightbox).focus();
        }

        function lbClose() {
            lightbox.hidden = true;
            document.body.style.overflow = '';
            window.scrollTo(0, lbSavedScrollY); // restaura la posición exacta de scroll
            document.removeEventListener('keydown', lbKeydown);
            lbImg.removeAttribute('src'); // corta la carga si seguía en curso
            if (lbLastFocused && typeof lbLastFocused.focus === 'function') lbLastFocused.focus();
        }

        var galMainBtn = document.getElementById('galMainBtn');
        if (galMainBtn) galMainBtn.addEventListener('click', function () { lbOpen(galIndex); });

        // Bug existente (brief): #galMoreBtn no tenía listener — no hacía nada.
        var galMoreBtn = document.getElementById('galMoreBtn');
        if (galMoreBtn) galMoreBtn.addEventListener('click', function () { lbOpen(0); });

        // Última miniatura "+N" (reemplazó a la pastilla sobre la foto): abre el
        // visor en la primera foto que la tira no alcanza a mostrar, no en la 1.
        var galMoreThumb = document.getElementById('galMoreThumb');
        if (galMoreThumb) galMoreThumb.addEventListener('click', function () { lbOpen(Math.min(7, galUrls.length - 1)); });

        if (lbPrevBtn) lbPrevBtn.addEventListener('click', function () { lbShow(lbIndex - 1); });
        if (lbNextBtn) lbNextBtn.addEventListener('click', function () { lbShow(lbIndex + 1); });
        lbClosers.forEach(function (el) { el.addEventListener('click', lbClose); });
    }

    // ── BARRA FIJA INFERIOR (B4) — scroll a la caja de reserva + foco en el
    // primer campo. No envía el formulario: la reserva necesita fecha y
    // pasajeros, que el usuario todavía no eligió. ──
    var stickyBookBtn = document.getElementById('stickyBookBtn');
    var bookCard = document.querySelector('.lat-book-card');
    if (stickyBookBtn && bookCard) {
        stickyBookBtn.addEventListener('click', function () {
            bookCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            var firstField = document.getElementById('bkPax');
            if (firstField) {
                window.setTimeout(function () { firstField.focus(); }, 420);
            }
        });
    }

    // Total automático: pax × precio
    var pax = document.getElementById('bkPax');
    var total = document.getElementById('bkTotal');
    if (pax && total) {
        var price = parseFloat(pax.getAttribute('data-price')) || 0;
        var currency = @json(\App\Support\Money::prefix(\App\Support\Money::site()));
        var update = function () {
            total.textContent = currency + (price * (parseInt(pax.value, 10) || 1)).toFixed(2);
        };
        pax.addEventListener('change', update);
        update();
    }

    // Fechas bloqueadas (hallazgo #1b): el <input type="date"> nativo no
    // tiene forma de "deshabilitar" fechas/días sueltos, así que se valida
    // en vivo contra las mismas listas que el backend usa para rechazar la
    // reserva (BlockedDate::blockedDatesFor/blockedWeekdaysFor), min=hoy+1
    // ya viene del atributo `min`. Si la fecha elegida está bloqueada: se
    // marca el campo, se muestra el mensaje inline y se bloquea el envío
    // (setCustomValidity + preventDefault), sin esperar el viaje al server.
    var dateInput = document.getElementById('bkDate');
    var dateField = document.getElementById('bkDateField');
    var dateError = document.getElementById('bkDateError');
    if (dateInput) {
        var blockedDates = @json($blockedDates ?? []);
        var blockedWeekdays = @json($blockedWeekdays ?? []);
        var blockedMessage = @json(__('booking.date_blocked'));

        var isDateBlocked = function (value) {
            if (!value) return false;
            if (blockedDates.indexOf(value) !== -1) return true;
            var parts = value.split('-');
            var d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            return blockedWeekdays.indexOf(d.getDay()) !== -1;
        };

        var validateDate = function () {
            var blocked = isDateBlocked(dateInput.value);
            if (dateField) dateField.classList.toggle('is-invalid', blocked);
            if (dateError) dateError.hidden = !blocked;
            dateInput.setCustomValidity(blocked ? blockedMessage : '');
            return !blocked;
        };

        dateInput.addEventListener('input', validateDate);
        dateInput.addEventListener('change', validateDate);

        var bookForm = dateInput.closest('form');
        if (bookForm) {
            bookForm.addEventListener('submit', function (e) {
                if (!validateDate()) {
                    e.preventDefault();
                    dateInput.reportValidity();
                }
            });
        }

        validateDate();
    }

    // ── MAPA DEL RECORRIDO — visor propio ────────────────────────────────
    // Mismo comportamiento que el lightbox de la galería (Escape cierra, el
    // scroll del fondo se bloquea, el foco vuelve al botón que lo abrió) pero
    // con sus propios ids: dos diálogos en la misma página no pueden compartir
    // id ni handlers. No hay carga diferida acá porque la imagen del mapa ya
    // está visible en su caja.
    var mapBtn = document.getElementById('mapBtn');
    var mapBox = document.getElementById('mapLightbox');
    if (mapBtn && mapBox) {
        var mapClosers = mapBox.querySelectorAll('[data-map-close]');
        var mapLastFocused = null;

        var mapKeydown = function (e) {
            if (e.key === 'Escape' || e.key === 'Esc') mapClose();
        };

        var mapOpen = function () {
            mapLastFocused = document.activeElement;
            mapBox.hidden = false;
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', mapKeydown);
            var closeBtn = mapBox.querySelector('.lat-lightbox__close');
            (closeBtn || mapBox).focus();
        };

        function mapClose() {
            mapBox.hidden = true;
            document.body.style.overflow = '';
            document.removeEventListener('keydown', mapKeydown);
            if (mapLastFocused && typeof mapLastFocused.focus === 'function') mapLastFocused.focus();
        }

        mapBtn.addEventListener('click', mapOpen);
        Array.prototype.forEach.call(mapClosers, function (el) { el.addEventListener('click', mapClose); });
    }
})();
</script>
@endpush
