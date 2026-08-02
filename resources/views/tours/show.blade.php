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

                            <button type="button" class="lat-gal__more" id="galMoreBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                {{ $L('Ver todas las fotos', 'View all photos', 'Ver todas as fotos') }}
                            </button>
                        @endif
                    </div>

                    @if (count($galleryUrls) > 1)
                        <div class="lat-gal__thumbs">
                            @foreach ($galleryUrls as $i => $img)
                                <button type="button" class="lat-gal__thumb {{ $i === 0 ? 'is-active' : '' }}" data-index="{{ $i }}" aria-label="{{ $L('Ver foto', 'View photo', 'Ver foto') }} {{ $i + 1 }}">
                                    <img src="{{ $img }}" alt="" loading="lazy" width="200" height="150">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

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

                {{-- Barra de info --}}
                <div class="lat-detail-info">
                    @if ($tour->duration)
                        <div class="lat-di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><div><b>{{ $tour->duration }}</b><span>{{ $L('Duración', 'Duration', 'Duração') }}</span></div></div>
                    @endif
                    @if ($tour->group_type)
                        <div class="lat-di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.7"/></svg><div><b>{{ $tour->group_type }}</b><span>{{ $L('Tamaño del grupo', 'Group size', 'Tamanho do grupo') }}</span></div></div>
                    @endif
                    @if ($tour->language)
                        <div class="lat-di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/></svg><div><b>{{ $tour->language }}</b><span>{{ $L('Idiomas', 'Languages', 'Idiomas') }}</span></div></div>
                    @endif
                    @if ($tour->departure_time || $tour->return_time)
                        <div class="lat-di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><div><b>{{ $tour->departure_time }}@if($tour->departure_time && $tour->return_time) / @endif{{ $tour->return_time }}</b><span>{{ $L('Salidas', 'Departures', 'Saídas') }}</span></div></div>
                    @endif
                </div>

                <div class="lat-detail-eyebrow">{{ $tour->category?->name ?? $L('Tour', 'Tour', 'Tour') }}</div>
                <h1 class="lat-detail-title">{{ $titleDisplay }}</h1>
                <div class="lat-detail-rate">
                    @if ($tour->badge_text)
                        <span class="lat-detail-badge {{ $badgeClass($tour->badge_type) }}">{{ $tour->badge_text }}</span>
                    @endif
                    <span class="lat-stars">
                        <span class="lat-stars__s">
                            @for ($i = 0; $i < 5; $i++)
                                <svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>
                            @endfor
                        </span>
                        <span class="lat-stars__rate">{{ number_format((float) $tour->rating, 1) }}</span>
                        <span class="lat-stars__cnt">({{ $tour->reviews_count }} {{ $L('reseñas', 'reviews', 'avaliações') }})</span>
                    </span>
                    <span class="lat-badge-free">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        {{ $L('Cancelación gratuita', 'Free cancellation', 'Cancelamento gratuito') }}
                    </span>
                    @if ($hasOffer)
                        <span class="lat-badge-offer">
                            {{ __('ui.special_offer') }}
                            <span>-{{ $offerPct }}%</span>
                        </span>
                    @endif
                </div>

                {{-- ============================================================
                     TABS (≥1024px) / ACORDEÓN (<1024px) — B3. MISMO marcado
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
                <div class="lat-tabs-wrap" id="tabsWrap">
                    <h2 class="lat-tabs-wrap__mobile-heading">{{ $L('Detalles completos', 'Full details', 'Detalhes completos') }}</h2>

                    <div class="lat-tab-item" data-tab="about">
                        <button type="button" class="lat-tab is-active" data-tab="about" role="tab"
                                id="tabbtn-about" aria-controls="panel-about" aria-selected="true" aria-expanded="true">
                            <span class="lat-tab__label">{{ $L('Acerca del Tour', 'About the Tour', 'Sobre o Tour') }}</span>
                            <svg class="lat-tab__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div class="lat-tab-panel is-active" data-tab="about" id="panel-about" role="tabpanel" aria-labelledby="tabbtn-about">
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
                        </div>
                    </div>

                    <div class="lat-tab-item" data-tab="itin">
                        <button type="button" class="lat-tab" data-tab="itin" role="tab"
                                id="tabbtn-itin" aria-controls="panel-itin" aria-selected="false" aria-expanded="false">
                            <span class="lat-tab__label">{{ $L('Itinerario', 'Itinerary', 'Itinerário') }}</span>
                            <svg class="lat-tab__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div class="lat-tab-panel" data-tab="itin" id="panel-itin" role="tabpanel" aria-labelledby="tabbtn-itin">
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
                                        <h4>{{ $step['title'] }}</h4>
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

                    <div class="lat-tab-item" data-tab="incl">
                        <button type="button" class="lat-tab" data-tab="incl" role="tab"
                                id="tabbtn-incl" aria-controls="panel-incl" aria-selected="false" aria-expanded="false">
                            <span class="lat-tab__label">{{ $L('Qué incluye', "What's included", 'O que inclui') }}</span>
                            <svg class="lat-tab__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div class="lat-tab-panel" data-tab="incl" id="panel-incl" role="tabpanel" aria-labelledby="tabbtn-incl">
                            @if ($excludes->isNotEmpty())
                                <p class="lat-tab-panel__subtitle">{{ $L('Incluye', 'Includes', 'Inclui') }}</p>
                            @endif
                            <ul>
                                @foreach ($includes as $item)
                                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>{{ $item }}</span></li>
                                @endforeach
                            </ul>

                            @if ($excludes->isNotEmpty())
                                <p class="lat-tab-panel__subtitle">{{ $L('No incluye', "Doesn't include", 'Não inclui') }}</p>
                                <ul class="lat-tab-panel__excludes">
                                    @foreach ($excludes as $item)
                                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg><span>{{ $item }}</span></li>
                                    @endforeach
                                </ul>
                            @endif
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
                        <div class="lat-book-secure">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            {{ $L('Pago 100% seguro', '100% secure payment', 'Pagamento 100% seguro') }}
                        </div>
                    </form>
                </div>

                @if ($related->isNotEmpty())
                    <div class="lat-more-tours-card">
                        <h4>{{ $L('Más tours', 'More tours', 'Mais tours') }}</h4>
                        @foreach ($related as $rel)
                            <a class="lat-mini-tour" href="{{ route('tours.show', ['locale' => $locale, 'slug' => $rel->slug]) }}">
                                <div class="lat-mini-tour__img"><img src="{{ $rel->cover_url }}" alt="{{ $rel->title }}" loading="lazy" width="74" height="60"></div>
                                <div>
                                    <h5 class="clamp-2">{{ $rel->title }}</h5>
                                    <div class="lat-mprice">{{ \App\Support\Money::format($rel->price, \App\Support\Money::site()) }}</div>
                                    <span class="lat-stars">
                                        <span class="lat-stars__s"><svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg></span>
                                        <span class="lat-stars__rate">{{ number_format((float) $rel->rating, 1) }}</span>
                                        <span class="lat-stars__cnt">({{ $rel->reviews_count }})</span>
                                    </span>
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
                <div><b>{{ $L('Atención al cliente 24/7', '24/7 customer support', 'Atendimento ao cliente 24/7') }}</b><span>{{ $L('Estamos para ayudarte', "We're here to help", 'Estamos aqui para ajudar') }}</span></div>
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

            if (mqDesktop.matches) {
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
})();
</script>
@endpush
