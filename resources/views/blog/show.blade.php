@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string =>
        $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);

    // ── Datos del mockup del artículo (docs/rebrand/inventario/spec-03-blog.md).
    // Todos son opcionales y HOY están vacíos en los 10 posts publicados: cada
    // bloque se oculta por dato, nunca con un placeholder ni una cifra
    // inventada (mismo criterio que el hero de Nosotros y la barra de stats).
    $features = $post->feature_cards;                 // tarjeta de hasta 4 bloques sobre el hero
    $videoUrl = $post->video_embed_url;               // botón de play sobre la foto
    $quote = $post->quote_text;                       // cita destacada
    $quoteBy = $post->quote_attribution;

    // Firma: manda el guía real si está asignado; si no, los campos sueltos
    // author_* (ver BlogPost::getSignature*Attribute()). El check de
    // verificado sale de tener un Guide real, no de un booleano editable.
    $signName = $post->signature_name ?: (\App\Models\Setting::get('site_name') ?: 'Lima América Tours');
    $signRole = $post->signature_role;
    $signPhoto = $post->signature_photo_url;
    $signVerified = $post->signature_is_verified;

    // Cifra del sidebar: el agregado REAL de reseñas, el mismo que publica
    // /resenas y el hero de Nosotros. El mockup dice "+2,500 viajeros ya lo
    // vivieron" y esa cifra no existe en ninguna tabla — no se publica.
    $overall = app(\App\Services\ReviewAggregator::class)->overallStats($locale);
@endphp

@extends('layouts.app')

@section('title', $post->metaTitle ?: $post->title)
@section('description', $post->metaDescription ?: Str::limit(strip_tags($post->excerpt), 160))
@section('og_image', $post->cover_url)
@section('header_variant', 'solid')

@push('schema')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "BlogPosting",
            "headline": "{{ addslashes($post->title) }}",
            "image": "{{ $post->cover_url }}",
            "datePublished": "{{ $post->published_at?->toIso8601String() }}",
            "dateModified": "{{ $post->updated_at->toIso8601String() }}",
            "author": {
                "@type": "Person",
                "name": "{{ addslashes($signName) }}"
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
                }
                @if ($post->category)
                ,{
                    "@type": "ListItem",
                    "position": 3,
                    "name": "{{ addslashes($post->category) }}",
                    "item": "{{ route('blog.index', ['locale' => $locale, 'categoria' => $post->category]) }}"
                }
                @endif
                ,{
                    "@type": "ListItem",
                    "position": {{ $post->category ? 4 : 3 }},
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

    {{-- ── Breadcrumb — 4 niveles con separador "›" (spec-03-blog.md §10).
         El nivel de categoría solo aparece si el post la tiene cargada. ── --}}
    <div class="lat-crumb-bar">
        <div class="lat-wrap">
            <nav class="lat-crumb lat-crumb--chevron" aria-label="Breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
                <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="{{ route('home', ['locale' => $locale]) }}"><span itemprop="name">{{ $L('Inicio', 'Home', 'Início') }}</span></a>
                    <meta itemprop="position" content="1">
                </span>
                <span class="lat-crumb__sep" aria-hidden="true">&rsaquo;</span>
                <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="{{ route('blog.index', ['locale' => $locale]) }}"><span itemprop="name">Blog</span></a>
                    <meta itemprop="position" content="2">
                </span>
                @if ($post->category)
                    <span class="lat-crumb__sep" aria-hidden="true">&rsaquo;</span>
                    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <a itemprop="item" href="{{ route('blog.index', ['locale' => $locale, 'categoria' => $post->category]) }}"><span itemprop="name">{{ $post->category }}</span></a>
                        <meta itemprop="position" content="3">
                    </span>
                @endif
                <span class="lat-crumb__sep" aria-hidden="true">&rsaquo;</span>
                <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <b class="clamp-1" itemprop="name" style="display:inline-block; max-width:320px; vertical-align:bottom">{{ $post->title }}</b>
                    <meta itemprop="position" content="{{ $post->category ? 4 : 3 }}">
                </span>
            </nav>
        </div>
    </div>

    {{-- ── Article ── --}}
    <article itemscope itemtype="https://schema.org/BlogPosting">

        {{-- ============================================================
             HERO SPLIT (spec-03-blog.md §3)
             NO es "foto de fondo con texto encima": son dos capas
             independientes. La foto va a sangre hasta el borde del viewport
             con una máscara de opacidad en su borde izquierdo (mask-image),
             y el texto vive en una columna de ancho FIJO que nunca se monta
             sobre la parte opaca de la foto. Ese ancho fijo es la regla no
             negociable de la spec (§3.3): si la columna se vuelve fluida, el
             texto termina sobre la foto a plena opacidad y el contraste cae
             a ~1:1 en cualquier idioma con títulos largos.
             Abajo de 1024px la composición se apila: texto en banda clara +
             foto en banda horizontal debajo, que es el patrón que esta
             pantalla ya tenía construido y probado.
             ============================================================ --}}
        <header class="lat-post-hero">
            <div class="lat-wrap lat-post-hero__grid">
                <div class="lat-post-hero__text">
                    @if ($post->category)
                        <a href="{{ route('blog.index', ['locale' => $locale, 'categoria' => $post->category]) }}" class="lat-post-eyebrow">
                            <span class="lat-post-eyebrow__ic" aria-hidden="true">{!! \App\Support\HeroIcons::svg('tag') !!}</span>
                            {{ $post->category }}
                        </a>
                    @endif

                    <h1 class="lat-post-title" itemprop="headline">{{ $post->title }}</h1>

                    {{-- La bajada ya existía en BD y solo se usaba para la
                         meta description; el mockup la pide visible. --}}
                    @if (filled($post->excerpt))
                        <p class="lat-post-lead">{{ strip_tags($post->excerpt) }}</p>
                    @endif

                    {{-- ── Línea de firma (spec §5.1): un solo bloque, con el
                         sub-grupo de autor arriba y los metadatos debajo.
                         Sin foto cargada no se reserva el hueco del avatar. --}}
                    <div class="lat-post-sign">
                        <div class="lat-post-sign__who">
                            @if ($signPhoto)
                                <img class="lat-post-sign__avatar" src="{{ $signPhoto }}" alt="" loading="lazy" width="44" height="44">
                            @endif
                            <span class="lat-post-sign__name" itemprop="author" itemscope itemtype="https://schema.org/Person">
                                {{ $L('Por', 'By', 'Por') }} <b itemprop="name">{{ $signName }}</b>@if ($signRole)<span class="lat-post-sign__role"> – {{ $signRole }}</span>@endif@if ($signVerified)<span class="lat-post-sign__check" title="{{ $L('Autor verificado del equipo', 'Verified team author', 'Autor verificado da equipe') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg><span class="sr-only">{{ $L('Autor verificado del equipo', 'Verified team author', 'Autor verificado da equipe') }}</span></span>@endif
                            </span>
                        </div>

                        <div class="lat-post-sign__meta">
                            @if ($post->published_at)
                                <span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                    <time datetime="{{ $post->published_at->toIso8601String() }}" itemprop="datePublished">
                                        {{ $post->published_at->translatedFormat('d M Y') }}
                                    </time>
                                </span>
                            @endif

                            @if ($post->reading_minutes)
                                <span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    {{ $post->reading_minutes }} min {{ $L('de lectura', 'read', 'de leitura') }}
                                </span>
                            @endif

                            @if ($post->category)
                                <span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4 11 3.8a2 2 0 0 0-1.4-.6L4 3a1 1 0 0 0-1 1l.2 5.6a2 2 0 0 0 .6 1.4l9.6 9.6a2 2 0 0 0 2.8 0l4.4-4.3a2 2 0 0 0 0-2.9z"/><circle cx="7.5" cy="7.5" r="1"/></svg>
                                    {{ $post->category }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="lat-post-hero__media">
                <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" itemprop="image"
                     width="1016" height="480" fetchpriority="high">

                @if ($videoUrl)
                    <button type="button" class="lat-post-hero__play" id="postVideoBtn"
                            aria-haspopup="dialog" aria-controls="postVideoModal"
                            aria-label="{{ $L('Ver video del artículo', 'Watch article video', 'Ver vídeo do artigo') }}">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                    </button>
                @endif
            </div>
        </header>

        {{-- ── Tarjeta de features montada sobre el borde inferior del hero
             (spec §2). El solape es margin-top negativo contra el
             padding-bottom del hero, NO position:absolute: así el documento
             sigue midiendo alto real y nada se superpone al scrollear.
             Se imprime solo si el post tiene bloques cargados — hoy ninguno
             los tiene, así que el hero cierra normal y no queda un hueco. ── --}}
        @if (! empty($features))
            <div class="lat-wrap">
                <div class="lat-post-features" data-count="{{ count($features) }}">
                    @foreach ($features as $feat)
                        <div class="lat-post-features__item">
                            @if ($feat['icon'] && \App\Support\HeroIcons::exists($feat['icon']))
                                <span class="lat-post-features__ic" aria-hidden="true">{!! \App\Support\HeroIcons::svg($feat['icon']) !!}</span>
                            @endif
                            <div>
                                <b>{{ $feat['title'] }}</b>
                                @if ($feat['text'])
                                    <span>{{ $feat['text'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── Grid mayor: cuerpo + sidebar (spec §4). Las dos columnas
             arrancan en la misma fila (align-items:start) y el sidebar es
             sticky desde 1024px, mismo criterio que el sidebar de la ficha
             de tour. Abajo de 1024px el sidebar se apila debajo del cuerpo
             completo. ── --}}
        <div class="lat-wrap lat-post-layout">
            <div class="lat-post-main">
                <div class="lat-post-body" itemprop="articleBody">
                    {!! $post->body !!}
                </div>

                {{-- Cita destacada del campo estructurado. El editor además
                     puede seguir pegando un blockquote dentro del cuerpo: el
                     SCSS le da el mismo tratamiento (comillas grandes, sin
                     itálica) para que las dos vías se vean igual. --}}
                @if ($quote)
                    <blockquote class="lat-post-quote">
                        <p>{{ $quote }}</p>
                        @if ($quoteBy)
                            <cite>{{ $quoteBy }}</cite>
                        @endif
                    </blockquote>
                @endif

                @if (! empty($post->tags))
                    <div class="lat-post-tags">
                        <span class="lat-tags-label">{{ $L('Etiquetas:', 'Tags:', 'Etiquetas:') }}</span>
                        @foreach ($post->tags as $tag)
                            <span class="lat-tag-pill">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <aside class="lat-post-aside" aria-labelledby="post-cta-title">
                <div class="lat-post-cta">
                    <span class="lat-post-cta__eyebrow" id="post-cta-title">{{ $L('¿Listo para vivirlo?', 'Ready to live it?', 'Pronto para viver isso?') }}</span>
                    <p>{{ $L('Reserva tu experiencia con nosotros y déjate sorprender por el Perú.', 'Book your experience with us and let Peru surprise you.', 'Reserve sua experiência com a gente e deixe o Peru surpreender você.') }}</p>

                    <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-btn lat-btn--red">
                        {{ $L('Ver experiencias', 'See experiences', 'Ver experiências') }}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>

                    {{-- El mockup pone acá "+2.500 viajeros ya lo vivieron".
                         Va el agregado real de reseñas (el mismo de /resenas)
                         y, si todavía no hay ninguna, no va ninguna cifra. --}}
                    @if ($overall['count'] > 0)
                        <p class="lat-post-cta__proof">
                            @php $overallRating = number_format((float) $overall['rating'], 1); @endphp
                            {{ $L(
                                $overall['count'].' opiniones reales de viajeros · '.$overallRating.' de valoración',
                                $overall['count'].' real traveler reviews · '.$overallRating.' rating',
                                $overall['count'].' opiniões reais de viajantes · '.$overallRating.' de avaliação'
                            ) }}
                        </p>
                    @endif
                </div>
            </aside>
        </div>
    </article>

    {{-- ── Related posts ── --}}
    @if ($related->isNotEmpty())
        <section class="lat-post-related" aria-labelledby="related-label">
            <div class="lat-wrap">
                <div class="lat-sec-head" style="margin:0 0 30px; text-align:left; max-width:none">
                    <span class="lat-eyebrow">{{ $L('Sigue leyendo', 'Keep reading', 'Continue lendo') }}</span>
                    <h2 id="related-label" style="font-size:clamp(1.5rem,2.6vw,2rem); margin-top:12px">{{ $L('Artículos relacionados', 'Related articles', 'Artigos relacionados') }}</h2>
                </div>
                <div class="lat-related-grid">
                    @foreach ($related as $rel)
                        <article class="lat-related-card">
                            <a href="{{ route('blog.show', ['locale' => $locale, 'slug' => $rel->slug]) }}" class="lat-related-card__media">
                                <img src="{{ $rel->cover_url }}"
                                     alt="{{ $rel->title }}" loading="lazy" width="400" height="225">
                                {{-- Badge de categoría encimado + minutos de lectura:
                                     mismos dos campos que ya usa .lat-blog-card del
                                     listado, que a esta variante le faltaban. --}}
                                @if ($rel->category)
                                    <span class="lat-related-card__badge">{{ $rel->category }}</span>
                                @endif
                            </a>
                            <div class="lat-related-card__body">
                                <h3><a href="{{ route('blog.show', ['locale' => $locale, 'slug' => $rel->slug]) }}">{{ $rel->title }}</a></h3>
                                <div class="lat-related-card__meta">
                                    @if ($rel->published_at)
                                        <time datetime="{{ $rel->published_at->toIso8601String() }}">{{ $rel->published_at->translatedFormat('d M Y') }}</time>
                                    @endif
                                    @if ($rel->reading_minutes)
                                        <span>{{ $rel->reading_minutes }} min</span>
                                    @endif
                                </div>
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

    <x-video-modal id="postVideoModal" :url="$videoUrl"
                   :label="$L('Ver video del artículo', 'Watch article video', 'Ver vídeo do artigo')" />
</div>
@endsection
