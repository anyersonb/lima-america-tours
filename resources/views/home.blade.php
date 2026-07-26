@extends('layouts.app')

@section('title', __('seo.home_title'))
@section('description', __('seo.home_description'))
@section('header_variant', 'solid')

@push('schema')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => __('seo.site_name'),
    'url' => url('/' . app()->getLocale()),
    'inLanguage' => app()->getLocale(),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => url('/' . app()->getLocale() . '/tours?q={search_term_string}'),
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string => $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);

    $featuredTours = $featuredTours ?? collect();

    // ── Hero: imagen + título editables por Settings (mismo criterio que antes) ──
    $heroImgSetting = \App\Models\Setting::get('home_hero_image');
    if (is_array($heroImgSetting)) { $heroImgSetting = $heroImgSetting[0] ?? ''; }
    $heroImgSetting = trim((string) $heroImgSetting);
    $heroImgUrl = ($heroImgSetting !== '' && $heroImgSetting !== '[]' && $heroImgSetting !== '""')
        ? \Illuminate\Support\Facades\Storage::disk('media')->url($heroImgSetting)
        : asset('assets/banners/hero-machu-picchu.png');

    $heroTitleDefault = [
        'es' => 'Lima <em>América</em> Tours',
        'en' => 'Lima <em>América</em> Tours',
        'pt' => 'Lima <em>América</em> Tours',
    ];
    $heroTitleRaw = \App\Models\Setting::get('home_hero_title_' . $locale)
        ?: ($heroTitleDefault[$locale] ?? $heroTitleDefault['es']);
    $heroSub = $L(
        'Explora lugares increíbles, vive experiencias únicas y crea recuerdos que durarán para siempre.',
        'Explore incredible places, live unique experiences and create memories that will last forever.',
        'Explore lugares incríveis, viva experiências únicas e crie memórias que vão durar para sempre.'
    );

    // ── Tours destacados: reales, ya calculados por HomeController ──
    $destacados = $featuredTours->take(4);

    $badgeClass = fn (?string $type) => match ($type) {
        'success' => 'lat-dcard__badge--g',
        'warn'    => 'lat-dcard__badge--o',
        default   => '',
    };

    // ── Categorías reales (modelo Category) con conteo e imagen del primer tour ──
    try {
        $categories = \App\Models\Category::active()->orderBy('order')
            ->with(['tours' => fn ($q) => $q->published()->limit(1)])
            ->withCount(['tours' => fn ($q) => $q->published()])
            ->limit(4)->get();
    } catch (\Throwable $e) {
        $categories = collect();
    }
@endphp

@section('content')

<div class="lat-page">

    {{-- ============================================================
         HERO — imagen de fondo + título + buscador + chips + features
         ============================================================ --}}
    <section class="lat-hero" aria-labelledby="hero-title">
        <div class="lat-hero__bg">
            <img src="{{ $heroImgUrl }}" alt="" loading="eager" fetchpriority="high">
        </div>

        <div class="lat-wrap" style="display:flex; flex-direction:column; width:100%;">
            <div class="lat-hero__inner">
                <h1 id="hero-title">{!! $heroTitleRaw !!}</h1>
                <p class="lat-hero__sub">{{ $heroSub }}</p>

                <form class="lat-search" role="search" method="GET" action="{{ route('tours.results', ['locale' => $locale]) }}">
                    <div class="lat-search__field">
                        <label for="s-dest">{{ $L('Destino', 'Destination', 'Destino') }}</label>
                        <div class="lat-search__val">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <input id="s-dest" type="text" name="q" placeholder="{{ $L('Lima, Ica, Paracas…', 'Lima, Ica, Paracas…', 'Lima, Ica, Paracas…') }}">
                        </div>
                    </div>
                    <div class="lat-search__field">
                        <label for="s-date">{{ $L('Fecha', 'Date', 'Data') }}</label>
                        <div class="lat-search__val">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <input id="s-date" type="text" name="fecha" placeholder="{{ $L('Cuándo viajas', 'When you travel', 'Quando viaja') }}" onfocus="(this.type='date')">
                        </div>
                    </div>
                    <div class="lat-search__field">
                        <label for="s-pax">{{ $L('Pasajeros', 'Travelers', 'Passageiros') }}</label>
                        <div class="lat-search__val">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.7"/></svg>
                            <select id="s-pax" name="pax">
                                <option>1 {{ $L('pasajero', 'traveler', 'passageiro') }}</option>
                                <option selected>2 {{ $L('pasajeros', 'travelers', 'passageiros') }}</option>
                                <option>3 {{ $L('pasajeros', 'travelers', 'passageiros') }}</option>
                                <option>4+ {{ $L('pasajeros', 'travelers', 'passageiros') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="lat-search__go">
                        <button type="submit" class="lat-btn lat-btn--red">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                            {{ __('ui.search') }}
                        </button>
                    </div>
                </form>

                <div class="lat-chips">
                    @php
                        $chips = [
                            [$L('City Tours', 'City Tours', 'City Tours'), 'city'],
                            [$L('Gastronómicos', 'Food tours', 'Gastronômicos'), 'gastro'],
                            [$L('Paracas & Ica', 'Paracas & Ica', 'Paracas & Ica'), 'paracas'],
                            [$L('Nazca', 'Nazca', 'Nazca'), 'nazca'],
                            [__('nav.free_tours'), 'free'],
                        ];
                    @endphp
                    @foreach ($chips as [$label, $q])
                        <a class="lat-chip" href="{{ route('tours.results', ['locale' => $locale, 'q' => $q]) }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            {{-- Fila de features --}}
            <div class="lat-hero-features">
                <div class="lat-hf">
                    <div class="lat-hf__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                    <div><b>{{ $L('Viajes Seguros', 'Safe Travel', 'Viagens Seguras') }}</b><span>{{ $L('Tu seguridad es nuestra prioridad', 'Your safety is our priority', 'Sua segurança é nossa prioridade') }}</span></div>
                </div>
                <div class="lat-hf">
                    <div class="lat-hf__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.7"/></svg></div>
                    <div><b>{{ $L('Guías Expertos', 'Expert Guides', 'Guias Especialistas') }}</b><span>{{ $L('Guías locales profesionales', 'Professional local guides', 'Guias locais profissionais') }}</span></div>
                </div>
                <div class="lat-hf">
                    <div class="lat-hf__ic"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg></div>
                    <div><b>{{ $L('Mejores Precios', 'Best Prices', 'Melhores Preços') }}</b><span>{{ $L('Calidad garantizada al mejor precio', 'Guaranteed quality at the best price', 'Qualidade garantida ao melhor preço') }}</span></div>
                </div>
                <div class="lat-hf">
                    <div class="lat-hf__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg></div>
                    <div><b>{{ $L('Atención 24/7', '24/7 Support', 'Atendimento 24/7') }}</b><span>{{ $L('Estamos siempre para ayudarte', "We're always here to help", 'Estamos sempre aqui para ajudar') }}</span></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         TARJETA BLANCA superpuesta (Destinos / Tours para Todos / Viajeros)
         ============================================================ --}}
    <div class="lat-hero-card-wrap">
        <div class="lat-wrap">
            <div class="lat-hero-card">
                <div class="lat-hc">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <div><b>{{ $L('Destinos Increíbles', 'Amazing Destinations', 'Destinos Incríveis') }}</b><span>{{ $L('Lima y todo Perú', 'Lima and all of Peru', 'Lima e todo o Peru') }}</span></div>
                </div>
                <div class="lat-hc">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <div><b>{{ $L('Tours para Todos', 'Tours for Everyone', 'Tours para Todos') }}</b><span>{{ $L('Aventura • Cultura • Gastronomía', 'Adventure • Culture • Food', 'Aventura • Cultura • Gastronomia') }}</span></div>
                </div>
                <div class="lat-hc">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.7"/></svg>
                    <div><b>{{ $L('Miles de Viajeros', 'Thousands of Travelers', 'Milhares de Viajantes') }}</b><span>{{ $L('Confían en nosotros', 'Trust us', 'Confiam em nós') }}</span></div>
                </div>
                <a class="lat-btn lat-btn--red" href="{{ route('tours.index', ['locale' => $locale]) }}">
                    {{ $L('Ver Tours', 'View Tours', 'Ver Tours') }}
                </a>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TOURS DESTACADOS — reales, ordenados por featured_order/compras
         ============================================================ --}}
    <section class="lat-wrap" style="padding:70px 24px" id="tours" aria-labelledby="destacados-title">
        <div class="lat-sec-head">
            <span class="lat-eyebrow is-center">{{ $L('Explora lugares increíbles', 'Explore incredible places', 'Explore lugares incríveis') }}</span>
            <h2 id="destacados-title">{{ $L('Tours Destacados', 'Featured Tours', 'Tours em Destaque') }}</h2>
            <p>{{ $L('Descubre nuestros tours más populares y vive experiencias inolvidables en los mejores destinos de Perú.', 'Discover our most popular tours and live unforgettable experiences in the best destinations in Peru.', 'Descubra nossos tours mais populares e viva experiências inesquecíveis nos melhores destinos do Peru.') }}</p>
        </div>

        <div class="lat-dest-grid">
            @forelse ($destacados as $tour)
                <article class="lat-dcard">
                    <a href="{{ route('tours.show', ['locale' => $locale, 'slug' => $tour->slug]) }}" class="lat-dcard__media">
                        <img src="{{ $tour->cover_url }}" alt="{{ $tour->title }}" loading="lazy" width="400" height="275">
                        @if ($tour->badge_text)
                            <span class="lat-dcard__badge {{ $badgeClass($tour->badge_type) }}">{{ $tour->badge_text }}</span>
                        @endif
                        <div class="lat-dcard__meta">
                            @if ($tour->duration)
                                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ $tour->duration }}</span>
                            @endif
                            @if ($tour->group_type)
                                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>{{ $tour->group_type }}</span>
                            @endif
                        </div>
                    </a>
                    <div class="lat-dcard__body">
                        <span class="lat-stars">
                            <span class="lat-stars__s">
                                @for ($i = 0; $i < 5; $i++)
                                    <svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>
                                @endfor
                            </span>
                            <span class="lat-stars__rate">{{ number_format((float) $tour->rating, 1) }}</span>
                            <span class="lat-stars__cnt">({{ $tour->reviews_count }})</span>
                        </span>
                        <a href="{{ route('tours.show', ['locale' => $locale, 'slug' => $tour->slug]) }}"><h3 class="clamp-2">{{ $tour->title }}</h3></a>
                        <p class="lat-dcard__desc clamp-2">{{ $tour->description }}</p>
                        <div class="lat-dcard__foot">
                            <div class="lat-dcard__price">
                                <small>{{ $L('Desde', 'From', 'Desde') }}</small>
                                <span class="lat-amt">{{ \App\Support\Money::format($tour->price, $tour->currency) }}</span>
                            </div>
                            <a href="{{ route('tours.show', ['locale' => $locale, 'slug' => $tour->slug]) }}" class="lat-btn-out">{{ $L('Ver Detalles', 'View Details', 'Ver Detalhes') }}</a>
                        </div>
                    </div>
                </article>
            @empty
                <p style="grid-column:1/-1; text-align:center; color:#6f6a63; padding:40px 0;">
                    {{ $L('Muy pronto nuevos tours disponibles.', 'New tours coming soon.', 'Novos tours em breve.') }}
                </p>
            @endforelse
        </div>
    </section>

    {{-- ============================================================
         TIRA DE GARANTÍAS
         ============================================================ --}}
    <div class="lat-guarantee" id="servicios">
        <div class="lat-wrap lat-guarantee__grid">
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div>
                <div><b>{{ $L('Cancelación Gratuita', 'Free Cancellation', 'Cancelamento Gratuito') }}</b><span>{{ $L('Cancela sin penalidad hasta 24 horas antes', 'Cancel penalty-free up to 24 hours before', 'Cancele sem multa até 24 horas antes') }}</span></div>
            </div>
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
                <div><b>{{ $L('Confirmación Inmediata', 'Instant Confirmation', 'Confirmação Imediata') }}</b><span>{{ $L('Reserva fácil y recibe tu confirmación al instante', 'Book easily and get instant confirmation', 'Reserve facilmente e receba confirmação instantânea') }}</span></div>
            </div>
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 10v12M15 5.9 14 10h5.6a2 2 0 0 1 2 2.3l-1.4 8A2 2 0 0 1 18.2 22H7V10l4.5-8a2 2 0 0 1 3.5 1.9z"/></svg></div>
                <div><b>{{ $L('Reservas 100% Seguras', '100% Secure Bookings', 'Reservas 100% Seguras') }}</b><span>{{ $L('Tus datos y pagos están protegidos', 'Your data and payments are protected', 'Seus dados e pagamentos estão protegidos') }}</span></div>
            </div>
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg></div>
                <div><b>{{ $L('Atención Personalizada', 'Personalized Support', 'Atendimento Personalizado') }}</b><span>{{ $L('Te ayudamos a planificar tu mejor experiencia', 'We help you plan your best experience', 'Ajudamos você a planejar sua melhor experiência') }}</span></div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         EXPLORA POR CATEGORÍA — reales (modelo Category)
         ============================================================ --}}
    @if ($categories->isNotEmpty())
        <section class="lat-wrap" style="padding:70px 24px" aria-labelledby="cats-title">
            <div class="lat-sec-head">
                <span class="lat-eyebrow is-center">{{ $L('Elige tu experiencia', 'Choose your experience', 'Escolha sua experiência') }}</span>
                <h2 id="cats-title">{{ $L('Explora por categoría', 'Explore by category', 'Explore por categoria') }}</h2>
                <p>{{ $L('Descubre el tipo de aventura que más te gusta: recorridos por la ciudad, sabores peruanos, aventura y culturas milenarias.', 'Discover the kind of adventure you like best: city tours, Peruvian flavors, adventure and ancient cultures.', 'Descubra o tipo de aventura que mais gosta: passeios pela cidade, sabores peruanos, aventura e culturas milenares.') }}</p>
            </div>

            <div class="lat-cats">
                @foreach ($categories as $cat)
                    @php
                        $catImg = optional($cat->tours->first())->cover_url ?? asset('assets/banners/hero-machu-picchu.png');
                    @endphp
                    <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-cat">
                        <img src="{{ $catImg }}" alt="{{ $cat->name }}" loading="lazy" width="360" height="230">
                        @if ($cat->tours_count)
                            <span class="lat-cat__count">{{ $cat->tours_count }} {{ $cat->tours_count === 1 ? $L('tour', 'tour', 'tour') : $L('tours', 'tours', 'tours') }}</span>
                        @endif
                        <div class="lat-cat__body">
                            <div class="lat-cat__name">{{ $cat->name }}</div>
                            <div class="lat-cat__sub">
                                {{ $cat->description ? \Illuminate\Support\Str::limit($cat->description, 28) : $L('Descúbrelo', 'Discover it', 'Descubra') }}
                                · <b>{{ $L('Ver todos', 'View all', 'Ver todos') }}</b>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============================================================
         OFERTAS ESPECIALES — reales (modelo Offer, admin → Marketing →
         Ofertas). $offers ya viene calculado por HomeController::fetchOffers()
         (activas, sin vencer, orden manual, límite 3); antes se calculaba y
         nunca se pintaba en ningún lugar del sitio (docs/qa/panel-filament.md
         hallazgo #2).
         ============================================================ --}}
    @php $offers = $offers ?? collect(); @endphp
    @if ($offers->isNotEmpty())
        <section class="lat-wrap" style="padding:70px 24px" aria-labelledby="offers-title">
            <div class="lat-sec-head">
                <span class="lat-eyebrow is-center">{{ $L('Aprovecha ahora', 'Grab it now', 'Aproveite agora') }}</span>
                <h2 id="offers-title">{{ $L('Ofertas especiales', 'Special offers', 'Ofertas especiais') }}</h2>
                <p>{{ $L('Promociones por tiempo limitado en nuestros tours más populares.', 'Limited-time promotions on our most popular tours.', 'Promoções por tempo limitado em nossos tours mais populares.') }}</p>
            </div>

            <div class="lat-dest-grid">
                @foreach ($offers as $offer)
                    @php
                        $offerImg = \App\Support\ImagePath::url($offer->image) ?? asset('assets/banners/hero-machu-picchu.png');
                        $offerHref = $offer->cta_url
                            ?: ($offer->tour ? route('tours.show', ['locale' => $locale, 'slug' => $offer->tour->slug]) : route('tours.index', ['locale' => $locale]));
                    @endphp
                    <article class="lat-dcard">
                        <a href="{{ $offerHref }}" class="lat-dcard__media">
                            <img src="{{ $offerImg }}" alt="{{ $offer->title }}" loading="lazy" width="400" height="275">
                        </a>
                        <div class="lat-dcard__body">
                            <a href="{{ $offerHref }}"><h3 class="clamp-2">{{ $offer->title }}</h3></a>
                            @if ($offer->description)
                                <p class="lat-dcard__desc clamp-2">{{ $offer->description }}</p>
                            @endif
                            <div class="lat-dcard__foot">
                                @if ($offer->price)
                                    <div class="lat-dcard__price">
                                        <small>{{ $L('Desde', 'From', 'Desde') }}</small>
                                        {{-- Offer no siempre tiene tour_id (promos genéricas); el negocio
                                             opera en soles (PEN), así que ese es el fallback razonable
                                             cuando no hay tour vinculado del que heredar la moneda. --}}
                                        <span class="lat-amt">{{ \App\Support\Money::format($offer->price, optional($offer->tour)->currency ?? 'PEN') }}</span>
                                    </div>
                                @endif
                                <a href="{{ $offerHref }}" class="lat-btn-out">{{ $offer->cta_label }}</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============================================================
         FAQ (AEO) — se mantiene por su valor SEO; solo renderiza si hay
         preguntas configuradas en Settings. Estilo teal/orange heredado
         del diseño anterior: pendiente de repintar a rojo en un milestone
         posterior de restyle.
         ============================================================ --}}
    <x-faq-section />

</div>

@endsection

@push('schema')
@include('partials.faq-schema')
@endpush
