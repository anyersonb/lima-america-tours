@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string =>
        $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);
@endphp

@extends('layouts.app')

@section('title', $L('Blog de Viajes — Lima América Tours', 'Travel Blog — Lima América Tours', 'Blog de Viagens — Lima América Tours'))
@section('description', $L(
    'Descubre consejos, guías de destinos y experiencias de viaje en Lima, Ica y Cusco.',
    'Discover travel tips, destination guides and travel experiences in Lima, Ica and Cusco.',
    'Descubra dicas, guias de destinos e experiências de viagem em Lima, Ica e Cusco.'
))
@section('header_variant', 'solid')

@section('content')
<div class="lat-page">

    {{-- ============================================================
         HERO INTERNO — imagen de fondo + degradado rojo/oscuro
         ============================================================ --}}
    <section class="lat-page-hero" style="background-image:url('{{ asset('assets/banners/Rectangle 19212.jpg') }}')">
        <div class="lat-wrap">
            <nav class="lat-page-hero__crumb" aria-label="Breadcrumb">
                <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('ui.home') }}</a> &middot; <b>Blog</b>
            </nav>
            <span class="lat-eyebrow">{{ $L('Aprende cosas nuevas', 'Learn something new', 'Aprenda coisas novas') }}</span>
            <h1>{{ $L('Post Recientes', 'Recent Posts', 'Posts Recentes') }}</h1>
            <p class="lat-page-hero__sub">
                {{ $L(
                    'Consejos, guías y experiencias para que disfrutes al máximo tu viaje por el Perú.',
                    'Tips, guides and experiences to help you make the most of your trip to Peru.',
                    'Dicas, guias e experiências para você aproveitar ao máximo sua viagem pelo Peru.'
                ) }}
            </p>
        </div>
    </section>

    {{-- ============================================================
         FILTROS + GRID DE ARTÍCULOS
         ============================================================ --}}
    <section class="lat-wrap" style="padding:46px 24px 70px">

        @if ($categories->isNotEmpty())
            <div class="lat-blog-filters" role="group" aria-label="{{ $L('Filtrar por categoría', 'Filter by category', 'Filtrar por categoria') }}">
                <a href="{{ route('blog.index', ['locale' => $locale]) }}"
                   class="lat-filter {{ ! request('categoria') ? 'is-active' : '' }}">
                    {{ $L('Todos', 'All', 'Todos') }}
                </a>
                @foreach ($categories as $cat)
                    <a href="{{ route('blog.index', ['locale' => $locale, 'categoria' => $cat]) }}"
                       class="lat-filter {{ request('categoria') === $cat ? 'is-active' : '' }}">
                        {{ $cat }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($posts->isEmpty())
            <p class="lat-empty-note">{{ $L('No hay artículos publicados todavía.', 'No articles published yet.', 'Nenhum artigo publicado ainda.') }}</p>
        @else
            <div class="lat-blog-grid">
                @foreach ($posts as $post)
                    <article class="lat-post" itemscope itemtype="https://schema.org/BlogPosting">
                        <img src="{{ $post->cover_image ? asset('storage/' . $post->cover_image) : asset('assets/banners/banner-hero.jpg') }}"
                             alt="{{ $post->title }}" loading="lazy" width="640" height="420" itemprop="image">

                        @if ($post->category)
                            <span class="lat-post__badge">{{ $post->category }}</span>
                        @endif

                        <div class="lat-post__body">
                            <h2 itemprop="headline">
                                <a href="{{ route('blog.show', ['locale' => $locale, 'slug' => $post->slug]) }}">
                                    {{ $post->title }}
                                </a>
                            </h2>

                            <div class="lat-post__meta">
                                @if ($post->reading_minutes)
                                    <span>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                        {{ $post->reading_minutes }} {{ $L('min de lectura', 'min read', 'min de leitura') }}
                                    </span>
                                @endif
                                @if ($post->published_at)
                                    <time datetime="{{ $post->published_at->toIso8601String() }}" itemprop="datePublished">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:inline;vertical-align:-3px;width:15px;height:15px;color:#ff1f2d;margin-right:6px"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>{{ $post->published_at->translatedFormat('d M Y') }}
                                    </time>
                                @endif
                            </div>

                            <a href="{{ route('blog.show', ['locale' => $locale, 'slug' => $post->slug]) }}" class="lat-post__link">
                                {{ $L('Leer más', 'Read more', 'Leia mais') }}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($posts->hasPages())
                <div style="margin-top:40px; display:flex; justify-content:center">
                    {{ $posts->links() }}
                </div>
            @endif
        @endif

        {{-- ============================================================
             CTA FINAL — "¿Listo para vivir tu propia aventura?"
             ============================================================ --}}
        <div class="lat-blog-cta" style="margin-top:44px">
            <div class="lat-blog-cta__left">
                <div class="lat-blog-cta__ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>
                </div>
                <div>
                    <h2>{{ $L('¿Listo para vivir tu propia aventura?', 'Ready to live your own adventure?', 'Pronto para viver sua própria aventura?') }}</h2>
                    <p>{{ $L('Descubre nuestros tours y experiencias únicas en Perú.', 'Discover our tours and unique experiences in Peru.', 'Descubra nossos tours e experiências únicas no Peru.') }}</p>
                </div>
            </div>
            <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-btn lat-btn--red">
                {{ $L('Ver todos los tours', 'See all tours', 'Ver todos os tours') }}
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>
    </section>
</div>
@endsection
