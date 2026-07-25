@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string => $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);
    $cat = $categoria ?? null;

    $regionTitles = [
        'lima'  => $L('Tours en Lima', 'Tours in Lima', 'Tours em Lima'),
        'ica'   => $L('Tours en Ica', 'Tours in Ica', 'Tours em Ica'),
        'cusco' => $L('Tours en Cusco', 'Tours in Cusco', 'Tours em Cusco'),
        null    => $L('Descubre las mejores experiencias en Lima', 'Discover the best experiences in Lima', 'Descubra as melhores experiências em Lima'),
    ];
    $sectionTitle = $regionTitles[$cat ?? null] ?? $regionTitles[null];

    $tours = $tours ?? collect();
    $categories = $categories ?? collect();

    $badgeClass = fn (?string $type) => match ($type) {
        'success' => 'lat-tcard__badge--g',
        'warn'    => 'lat-tcard__badge--o',
        default   => '',
    };

    // Oferta especial: activa cuando el CMS trae price_before > price y el flag está encendido
    $tourOffer = function ($tour) {
        $before = (float) ($tour->price_before ?? 0);
        $now    = (float) $tour->price;
        if (! $tour->show_offer_badge || $before <= 0 || $before <= $now) {
            return null;
        }
        return (int) round((1 - ($now / $before)) * 100);
    };
@endphp

@section('title', $sectionTitle . ' — ' . __('seo.site_name'))
@section('description', __('ui.tours_meta_description', ['section' => $sectionTitle]))
@section('header_variant', 'solid')

@section('content')
<div class="lat-page">

    {{-- ============================================================
         HERO INTERNO — imagen de fondo + degradado rojo/oscuro
         ============================================================ --}}
    <section class="lat-page-hero" style="background-image:url('{{ asset('assets/banners/banner-hero.jpg') }}')">
        <div class="lat-wrap">
            <nav class="lat-page-hero__crumb" aria-label="Breadcrumb">
                <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('ui.home') }}</a> &middot; <b>{{ __('nav.tours') }}</b>
            </nav>
            <span class="lat-eyebrow">{{ $L('Explora lugares increíbles', 'Explore incredible places', 'Explore lugares incríveis') }}</span>
            <h1>{{ $sectionTitle }}</h1>
            <p class="lat-page-hero__sub">
                {{ $L(
                    'Explora la historia, la cultura y la esencia del Perú con nuestros tours más populares. Guías expertos, cancelación gratuita y confirmación inmediata.',
                    'Explore the history, culture and essence of Peru with our most popular tours. Expert guides, free cancellation and instant confirmation.',
                    'Explore a história, a cultura e a essência do Peru com nossos tours mais populares. Guias especialistas, cancelamento gratuito e confirmação instantânea.'
                ) }}
            </p>
        </div>
    </section>

    {{-- ============================================================
         BUSCADOR + FILTROS + GRID
         ============================================================ --}}
    <section class="lat-wrap" style="padding:46px 24px 80px" id="catalogo">
        <div class="lat-tours-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <label for="tourSearch" class="sr-only">{{ $L('Buscar tour por nombre o destino', 'Search a tour by name or destination', 'Buscar tour por nome ou destino') }}</label>
            <input id="tourSearch" type="text" placeholder="{{ $L('Buscar tour por nombre o destino…', 'Search a tour by name or destination…', 'Buscar tour por nome ou destino…') }}" autocomplete="off">
        </div>

        <div class="lat-toolbar">
            <div class="lat-filters" id="tourFilters" role="group" aria-label="{{ $L('Filtrar por categoría', 'Filter by category', 'Filtrar por categoria') }}">
                <button type="button" class="lat-filter is-active" data-cat="all">{{ $L('Todos', 'All', 'Todos') }}</button>
                @foreach ($categories as $category)
                    @if ($category->tours_count > 0)
                        <button type="button" class="lat-filter" data-cat="{{ $category->slug }}">{{ $category->name }}</button>
                    @endif
                @endforeach
            </div>
            <p class="lat-result-count" id="resultCount">
                <b>{{ $tours->count() }}</b> {{ $tours->count() === 1 ? $L('experiencia disponible', 'experience available', 'experiência disponível') : $L('experiencias disponibles', 'experiences available', 'experiências disponíveis') }}
            </p>
        </div>

        <div class="lat-tours-grid" id="toursGrid">
            @forelse ($tours as $tour)
                @php $pct = $tourOffer($tour); @endphp
                <article class="lat-tcard" data-slug="{{ $tour->slug }}" data-cat="{{ $tour->category?->slug ?? '' }}" data-title="{{ mb_strtolower($tour->title) }}">
                    <a href="{{ route('tours.show', ['locale' => $locale, 'slug' => $tour->slug]) }}" class="lat-tcard__media">
                        <img src="{{ $tour->cover_url }}" alt="{{ $tour->title }}" loading="lazy" width="400" height="300">
                        @if ($tour->badge_text)
                            <span class="lat-tcard__badge {{ $badgeClass($tour->badge_type) }}">{{ $tour->badge_text }}</span>
                        @endif
                        @if ($pct)
                            <span class="lat-tcard__offer">{{ $L('Oferta especial', 'Special offer', 'Oferta especial') }} <span>-{{ $pct }}%</span></span>
                        @endif
                    </a>
                    <button type="button" class="lat-tcard__fav" aria-label="{{ $L('Añadir a favoritos', 'Add to favorites', 'Adicionar aos favoritos') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21.3l-1.5-1.3C5.4 15.4 2 12.3 2 8.5 2 5.4 4.4 3 7.5 3c1.7 0 3.4.8 4.5 2.1C13.1 3.8 14.8 3 16.5 3 19.6 3 22 5.4 22 8.5c0 3.8-3.4 6.9-8.5 11.5L12 21.3z"/></svg>
                    </button>
                    <div class="lat-tcard__body">
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
                        <div class="lat-tcard__feats">
                            @if ($tour->duration)
                                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ $tour->duration }}</span>
                            @endif
                            @if ($tour->language)
                                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/></svg>{{ $tour->language }}</span>
                            @endif
                            @if ($tour->group_type)
                                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.7"/></svg>{{ $tour->group_type }}</span>
                            @endif
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>{{ $L('Cancelación gratuita', 'Free cancellation', 'Cancelamento gratuito') }}</span>
                        </div>
                        <div class="lat-tcard__foot">
                            <div class="lat-tcard__price {{ (float) $tour->price === 0.0 ? 'lat-tcard__price--free' : '' }}">
                                @if ((float) $tour->price === 0.0)
                                    <span class="lat-amt">{{ $L('Gratis', 'Free', 'Gratuito') }}</span>
                                @else
                                    @if ($pct)
                                        <span class="lat-tcard__before">${{ number_format((float) $tour->price_before, 0) }}</span>
                                    @else
                                        <small>{{ $L('Desde', 'From', 'Desde') }}</small>
                                    @endif
                                    <span class="lat-amt">${{ number_format((float) $tour->price, 0) }}</span>
                                    <span class="lat-per">{{ $L('por persona', 'per person', 'por pessoa') }}</span>
                                @endif
                            </div>
                            <a href="{{ route('tours.show', ['locale' => $locale, 'slug' => $tour->slug]) }}" class="lat-btn lat-btn--red">{{ $L('Reservar ahora', 'Book now', 'Reservar agora') }}</a>
                        </div>
                    </div>
                </article>
            @empty
                <p class="lat-empty-note">{{ $L('Muy pronto nuevos tours disponibles.', 'New tours coming soon.', 'Novos tours em breve.') }}</p>
            @endforelse
            <p class="lat-empty-note" id="noResults" style="display:none">{{ $L('No hay tours que coincidan con tu búsqueda.', 'No tours match your search.', 'Nenhum tour corresponde à sua busca.') }}</p>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
(function () {
    var grid = document.getElementById('toursGrid');
    var searchInput = document.getElementById('tourSearch');
    var filtersWrap = document.getElementById('tourFilters');
    var resultCount = document.getElementById('resultCount');
    var noResults = document.getElementById('noResults');
    if (!grid || !filtersWrap) return;

    var cards = Array.prototype.slice.call(grid.querySelectorAll('.lat-tcard'));
    var curCat = 'all';

    var LABELS = {
        one: @json($L('experiencia disponible', 'experience available', 'experiência disponível')),
        many: @json($L('experiencias disponibles', 'experiences available', 'experiências disponíveis')),
    };

    function applyFilters() {
        var q = (searchInput.value || '').trim().toLowerCase();
        var visible = 0;

        cards.forEach(function (card) {
            var matchesCat = curCat === 'all' || card.getAttribute('data-cat') === curCat;
            var matchesQ = !q || card.getAttribute('data-title').indexOf(q) > -1;
            var show = matchesCat && matchesQ;
            card.classList.toggle('is-hidden', !show);
            if (show) visible++;
        });

        if (resultCount) {
            resultCount.innerHTML = '<b>' + visible + '</b> ' + (visible === 1 ? LABELS.one : LABELS.many);
        }
        if (noResults) {
            noResults.style.display = visible === 0 ? '' : 'none';
        }
    }

    filtersWrap.addEventListener('click', function (e) {
        var btn = e.target.closest('.lat-filter');
        if (!btn) return;
        curCat = btn.getAttribute('data-cat');
        filtersWrap.querySelectorAll('.lat-filter').forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
        });
        applyFilters();
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    document.addEventListener('click', function (e) {
        var fav = e.target.closest('.lat-tcard__fav');
        if (fav) {
            e.preventDefault();
            e.stopPropagation();
            fav.classList.toggle('is-on');
        }
    });
})();
</script>
@endpush
