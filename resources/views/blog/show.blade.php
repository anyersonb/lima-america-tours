@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string =>
        $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);
@endphp

@extends('layouts.app')

@section('title', $post->metaTitle ?: $post->title)
@section('description', $post->metaDescription ?: Str::limit(strip_tags($post->excerpt), 160))
@section('og_image', $post->cover_image ? asset('storage/' . $post->cover_image) : null)
@section('header_variant', 'solid')

@push('schema')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "BlogPosting",
            "headline": "{{ addslashes($post->title) }}",
            "image": "{{ $post->cover_image ? asset('storage/' . $post->cover_image) : asset('assets/banners/banner-hero.jpg') }}",
            "datePublished": "{{ $post->published_at?->toIso8601String() }}",
            "dateModified": "{{ $post->updated_at->toIso8601String() }}",
            "author": {
                "@type": "Person",
                "name": "{{ addslashes($post->author_name ?? 'Lima América Tours') }}"
            },
            "publisher": {
                "@type": "Organization",
                "name": "Lima América Tours",
                "logo": {
                    "@type": "ImageObject",
                    "url": "{{ asset('assets/logos/logo-america-original.webp') }}"
                }
            },
            "mainEntityOfPage": {
                "@type": "WebPage",
                "@id": "{{ url()->current() }}"
            },
            "description": "{{ addslashes(Str::limit(strip_tags($post->excerpt), 160)) }}",
            "inLanguage": "{{ $locale }}"
            @if ($post->tags)
            ,"keywords": "{{ implode(', ', $post->tags) }}"
            @endif
        },
        {
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "{{ $L('Inicio', 'Home', 'Início') }}",
                    "item": "{{ route('home', ['locale' => $locale]) }}"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "Blog",
                    "item": "{{ route('blog.index', ['locale' => $locale]) }}"
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "{{ addslashes($post->title) }}",
                    "item": "{{ url()->current() }}"
                }
            ]
        }
    ]
}
</script>
@endpush

@section('content')
<div class="lat-page">

    {{-- ── Breadcrumb ── --}}
    <div class="lat-crumb-bar">
        <div class="lat-wrap">
            <nav class="lat-crumb" aria-label="Breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
                <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="{{ route('home', ['locale' => $locale]) }}"><span itemprop="name">{{ $L('Inicio', 'Home', 'Início') }}</span></a>
                    <meta itemprop="position" content="1">
                </span> &middot;
                <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="{{ route('blog.index', ['locale' => $locale]) }}"><span itemprop="name">Blog</span></a>
                    <meta itemprop="position" content="2">
                </span> &middot;
                <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <b class="clamp-1" itemprop="name" style="display:inline-block; max-width:320px; vertical-align:bottom">{{ $post->title }}</b>
                    <meta itemprop="position" content="3">
                </span>
            </nav>
        </div>
    </div>

    {{-- ── Article ── --}}
    <article itemscope itemtype="https://schema.org/BlogPosting">

        <header class="lat-post-head">
            <div class="lat-wrap">
                @if ($post->category)
                    <a href="{{ route('blog.index', ['locale' => $locale, 'categoria' => $post->category]) }}" class="lat-post-badge">
                        {{ $post->category }}
                    </a>
                @endif

                <h1 class="lat-post-title" itemprop="headline">{{ $post->title }}</h1>

                <div class="lat-post-meta">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
                        <span itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name">{{ $post->author_name ?? 'Lima América Tours' }}</span></span>
                    </span>

                    @if ($post->published_at)
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <time datetime="{{ $post->published_at->toIso8601String() }}" itemprop="datePublished">
                                {{ $post->published_at->translatedFormat('d \d\e F \d\e Y') }}
                            </time>
                        </span>
                    @endif

                    @if ($post->reading_minutes)
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            {{ $post->reading_minutes }} min {{ $L('de lectura', 'read', 'de leitura') }}
                        </span>
                    @endif
                </div>
            </div>
        </header>

        @if ($post->cover_image)
            <div class="lat-wrap">
                <div class="lat-post-cover">
                    <img src="{{ asset('storage/' . $post->cover_image) }}" alt="{{ $post->title }}" itemprop="image" width="896" height="460">
                </div>
            </div>
        @endif

        <div class="lat-wrap">
            <div class="lat-post-body" itemprop="articleBody">
                {!! $post->body !!}
            </div>

            @if (! empty($post->tags))
                <div class="lat-post-tags">
                    <span class="lat-tags-label">{{ $L('Etiquetas:', 'Tags:', 'Etiquetas:') }}</span>
                    @foreach ($post->tags as $tag)
                        <span class="lat-tag-pill">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </article>

    {{-- ── Related posts ── --}}
    @if ($related->isNotEmpty())
        <section style="padding:50px 0 10px" aria-labelledby="related-label">
            <div class="lat-wrap">
                <div class="lat-sec-head" style="margin:0 0 30px; text-align:left; max-width:none">
                    <span class="lat-eyebrow">{{ $L('Sigue leyendo', 'Keep reading', 'Continue lendo') }}</span>
                    <h2 style="font-size:clamp(1.5rem,2.6vw,2rem); margin-top:12px">{{ $L('Artículos relacionados', 'Related articles', 'Artigos relacionados') }}</h2>
                </div>
                <div class="lat-related-grid">
                    @foreach ($related as $rel)
                        <article class="lat-related-card">
                            <a href="{{ route('blog.show', ['locale' => $locale, 'slug' => $rel->slug]) }}" class="lat-related-card__media">
                                <img src="{{ $rel->cover_image ? asset('storage/' . $rel->cover_image) : asset('assets/banners/banner-hero.jpg') }}"
                                     alt="{{ $rel->title }}" loading="lazy" width="400" height="225">
                            </a>
                            <div class="lat-related-card__body">
                                <h3><a href="{{ route('blog.show', ['locale' => $locale, 'slug' => $rel->slug]) }}">{{ $rel->title }}</a></h3>
                                @if ($rel->published_at)
                                    <time datetime="{{ $rel->published_at->toIso8601String() }}">{{ $rel->published_at->translatedFormat('d M Y') }}</time>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ── CTA final ── --}}
    <section style="padding:50px 24px 70px">
        <div class="lat-wrap" style="padding:0">
            <div class="lat-blog-cta">
                <div class="lat-blog-cta__left">
                    <div class="lat-blog-cta__ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>
                    </div>
                    <div>
                        <h2>{{ $L('¿Te animaste a viajar?', 'Ready to travel?', 'Animou-se a viajar?') }}</h2>
                        <p>{{ $L('Reserva tu tour y vive Lima, Ica o Cusco como nunca.', 'Book your tour and experience Lima, Ica or Cusco like never before.', 'Reserve seu tour e experimente Lima, Ica ou Cusco como nunca.') }}</p>
                    </div>
                </div>
                <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-btn lat-btn--red">
                    {{ $L('Ver todos los tours', 'See all tours', 'Ver todos os tours') }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
