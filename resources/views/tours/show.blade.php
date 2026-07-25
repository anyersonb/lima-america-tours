@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string => $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);

    $galleryUrls = $tour->gallery_urls;

    $rawItinerary = $tour->{"itinerary_{$locale}"} ?: $tour->itinerary_es ?: [];
    $itinerary = collect($rawItinerary)->map(function ($step) {
        if (! is_array($step)) {
            return ['time' => '', 'title' => (string) $step, 'description' => ''];
        }
        return [
            'time'        => trim((string) ($step['time'] ?? '')),
            'title'       => trim((string) ($step['title'] ?? '')),
            'description' => trim((string) ($step['description'] ?? '')),
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

    $related = ($related ?? collect())->take(3);

    $titleDisplay = (function ($raw) {
        $stop = ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'o', 'u', 'e', 'a', 'al', 'en', 'con', 'para', 'por', 'un', 'una'];
        $words = preg_split('/\s+/u', mb_strtolower(trim($raw), 'UTF-8'));
        $out = [];
        foreach ($words as $i => $w) {
            if ($w === '') continue;
            $out[] = ($i > 0 && in_array($w, $stop, true)) ? $w : mb_convert_case($w, MB_CASE_TITLE, 'UTF-8');
        }
        return implode(' ', $out);
    })($tour->title);
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
            'priceCurrency' => $tour->currency ?: 'USD',
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
<div class="lat-page">

    {{-- ============================================================
         BREADCRUMB
         ============================================================ --}}
    <div class="lat-crumb-bar">
        <div class="lat-wrap">
            <nav class="lat-crumb" aria-label="Breadcrumb">
                <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('ui.home') }}</a> &middot;
                <a href="{{ route('tours.index', ['locale' => $locale]) }}">{{ __('nav.tours') }}</a> &middot;
                <b>{{ $titleDisplay }}</b>
            </nav>
        </div>
    </div>

    {{-- ============================================================
         GALERÍA + CONTENIDO + CAJA DE RESERVA
         ============================================================ --}}
    <section class="lat-wrap" style="padding:0 24px">
        <div class="lat-detail-grid">

            <div class="lat-detail-main">
                {{-- Galería --}}
                <div class="lat-gal">
                    <div class="lat-gal__main">
                        <img id="galMain" src="{{ $galleryUrls[0] ?? $tour->cover_url }}" alt="{{ $titleDisplay }}" width="860" height="452">
                        @if (count($galleryUrls) > 1)
                            <button type="button" class="lat-gal__more" id="galMoreBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                {{ $L('Ver todas las fotos', 'View all photos', 'Ver todas as fotos') }}
                            </button>
                        @endif
                    </div>
                    @foreach ($galleryUrls as $i => $img)
                        <button type="button" class="lat-gal__thumb {{ $i === 0 ? 'is-active' : '' }}" data-img="{{ $img }}">
                            <img src="{{ $img }}" alt="" loading="lazy" width="200" height="150">
                        </button>
                    @endforeach
                </div>

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

                {{-- Tabs --}}
                <div class="lat-tabs" role="tablist">
                    <button type="button" class="lat-tab is-active" data-tab="about" role="tab">{{ $L('Acerca del Tour', 'About the Tour', 'Sobre o Tour') }}</button>
                    <button type="button" class="lat-tab" data-tab="itin" role="tab">{{ $L('Itinerario', 'Itinerary', 'Itinerário') }}</button>
                    <button type="button" class="lat-tab" data-tab="incl" role="tab">{{ $L('Qué incluye', "What's included", 'O que inclui') }}</button>
                    <button type="button" class="lat-tab" data-tab="bring" role="tab">{{ $L('Qué llevar', 'What to bring', 'O que levar') }}</button>
                </div>

                <div class="lat-tab-panel is-active" data-tab="about">
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

                <div class="lat-tab-panel" data-tab="itin">
                    @forelse ($itinerary as $i => $step)
                        <div class="lat-itin-step">
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

                <div class="lat-tab-panel" data-tab="incl">
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

                <div class="lat-tab-panel" data-tab="bring">
                    <ul>
                        @foreach ($bring as $item)
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
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
            </div>

            {{-- ============================================================
                 CAJA DE RESERVA (sticky) + MÁS TOURS
                 ============================================================ --}}
            <aside class="lat-book">
                <div class="lat-book-card">
                    <div class="lat-book-head">
                        @if ($hasOffer)
                            <div class="lat-book-head__offer">
                                <span class="lat-book-head__before">${{ number_format($offerBefore, 0) }}</span>
                                <span class="lat-book-head__pct">-{{ $offerPct }}%</span>
                            </div>
                        @endif
                        <small>{{ $L('Desde', 'From', 'Desde') }}</small>
                        <div class="lat-amt">${{ number_format((float) $tour->price, 0) }}<span> {{ $L('por persona', 'per person', 'por pessoa') }}</span></div>
                    </div>
                    <form class="lat-book-body" method="POST" action="{{ route('cart.store', ['locale' => $locale]) }}">
                        @csrf
                        <input type="hidden" name="tour_id" value="{{ $tour->id }}">
                        <input type="hidden" name="children" value="0">
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
                            <div class="lat-book-field">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                <input id="bkDate" type="date" name="travel_date" min="{{ now()->addDay()->toDateString() }}" required>
                            </div>
                        </div>
                        <div class="lat-book-total">
                            <span class="lat-lbl">{{ $L('Precio total', 'Total price', 'Preço total') }}</span>
                            <span class="lat-tot" id="bkTotal">{{ $tour->currency ?: 'USD' }} ${{ number_format((float) $tour->price * 2, 2) }}</span>
                        </div>
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
                                    <div class="lat-mprice">${{ number_format((float) $rel->price, 0) }}</div>
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

</div>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.querySelector('.lat-page');
    if (!root) return;

    // Tabs
    root.querySelectorAll('.lat-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var name = tab.getAttribute('data-tab');
            root.querySelectorAll('.lat-tab').forEach(function (t) { t.classList.toggle('is-active', t === tab); });
            root.querySelectorAll('.lat-tab-panel').forEach(function (p) { p.classList.toggle('is-active', p.getAttribute('data-tab') === name); });
        });
    });

    // Galería: miniaturas
    var galMain = document.getElementById('galMain');
    root.querySelectorAll('.lat-gal__thumb').forEach(function (th) {
        th.addEventListener('click', function () {
            if (galMain) galMain.src = th.getAttribute('data-img');
            root.querySelectorAll('.lat-gal__thumb').forEach(function (t) { t.classList.toggle('is-active', t === th); });
        });
    });

    // Total automático: pax × precio
    var pax = document.getElementById('bkPax');
    var total = document.getElementById('bkTotal');
    if (pax && total) {
        var price = parseFloat(pax.getAttribute('data-price')) || 0;
        var currency = @json($tour->currency ?: 'USD');
        var update = function () {
            total.textContent = currency + ' $' + (price * (parseInt(pax.value, 10) || 1)).toFixed(2);
        };
        pax.addEventListener('change', update);
        update();
    }
})();
</script>
@endpush
