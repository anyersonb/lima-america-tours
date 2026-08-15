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

@php
    // Foto real (2560×1707, muy por encima de cualquier ancho renderizado)
    // en vez del placeholder de 205×123px (`assets/banners/Rectangle
    // 19212.jpg`) que usaba esta misma sección antes — ver "Problema
    // transversal: el kit de imágenes está degradado" en el brief.
    $blogHeroImg = asset('storage/tours/Machu_Picchu_Peru_-_Laslovarga_262-scaled.jpg');

    // WhatsApp real (Setting::whatsappNumber(), sin fallback a un número
    // ajeno) — alimenta el botón "Habla con un asesor" del CTA final; sin
    // número configurado, el botón se oculta en vez de apuntar a un chat
    // que no existe.
    $waNumber = \App\Models\Setting::whatsappNumber();

    // Preserva el término de búsqueda al cambiar de categoría y viceversa
    // (?q= y ?categoria= son combinables, ver BlogController::index).
    $blogFilterParams = fn (?string $categoria = null) => array_filter([
        'locale' => $locale,
        'categoria' => $categoria,
        'q' => $q !== '' ? $q : null,
    ]);

    // Copy del blog administrable desde Configuración → Blog. El blog no
    // tiene fila en `pages` (mismo caso que Home): se resuelve por Setting,
    // no por Page->blocks. Vacío = texto por defecto de este mockup.
    $bs = function (string $key, string $fallback) use ($locale): string {
        $val = trim((string) \App\Models\Setting::get($key . '_' . $locale));
        return $val !== '' ? $val : $fallback;
    };
@endphp

@section('content')
<div class="lat-page">

    {{-- ============================================================
         HERO — foto a la derecha con degradado hacia la izquierda,
         eyebrow rojo, H1, bajada y buscador pill real.
         ============================================================ --}}
    <section class="lat-page-hero lat-blog-hero" style="background-image:url('{{ $blogHeroImg }}')">
        <div class="lat-wrap">
            <span class="lat-eyebrow lat-eyebrow--on-dark">{{ $bs('blog_hero_eyebrow', $L('Inspírate para viajar', 'Get inspired to travel', 'Inspire-se para viajar')) }}</span>
            <h1>{{ $bs('blog_hero_title', $L('Blog de viajes', 'Travel blog', 'Blog de viagens')) }}</h1>
            <p class="lat-page-hero__sub">
                {{ $bs('blog_hero_sub', $L(
                    'Consejos, guías y experiencias para que disfrutes al máximo tu aventura por el Perú.',
                    'Tips, guides and experiences to help you make the most of your adventure in Peru.',
                    'Dicas, guias e experiências para você aproveitar ao máximo sua aventura pelo Peru.'
                )) }}
            </p>

            {{-- Buscador real: BlogController@index ya filtra por ?q= en
                 título y extracto (ES + el idioma actual). Combinable con
                 ?categoria= vía el input oculto. --}}
            @php
                $blogSearchPlaceholder = $bs('blog_search_placeholder', $L('Buscar artículos, destinos o consejos…', 'Search articles, destinations or tips…', 'Buscar artigos, destinos ou dicas…'));
            @endphp
            <form class="lat-blog-search" role="search" method="GET" action="{{ route('blog.index', ['locale' => $locale]) }}">
                @if (request()->filled('categoria'))
                    <input type="hidden" name="categoria" value="{{ request('categoria') }}">
                @endif
                <svg class="lat-blog-search__ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <label for="blog-q" class="sr-only">{{ $blogSearchPlaceholder }}</label>
                <input id="blog-q" type="search" name="q" value="{{ $q }}" autocomplete="off"
                       placeholder="{{ $blogSearchPlaceholder }}">
                <button type="submit" class="sr-only">{{ $L('Buscar', 'Search', 'Buscar') }}</button>
            </form>
        </div>
    </section>

    {{-- ============================================================
         CUERPO OSCURO — toolbar + filtros + grid + CTA final. Tema
         oscuro de ESTA página (no del layout compartido): ver comentario
         de _lat-blog.scss.
         ============================================================ --}}
    <section class="lat-blog-body">
        <div class="lat-wrap">

            <div class="lat-blog-toolbar">
                <h2 class="lat-blog-toolbar__title">{{ $bs('blog_toolbar_title', $L('Explora nuestros artículos', 'Explore our articles', 'Explore nossos artigos')) }}</h2>

                @if ($categories->isNotEmpty())
                    <div class="lat-blog-filters" role="group" aria-label="{{ $L('Filtrar por categoría', 'Filter by category', 'Filtrar por categoria') }}">
                        <a href="{{ route('blog.index', $blogFilterParams()) }}"
                           class="lat-blog-filter {{ ! request('categoria') ? 'is-active' : '' }}">
                            {{ $L('Todos', 'All', 'Todos') }}
                        </a>
                        @foreach ($categories as $cat)
                            <a href="{{ route('blog.index', $blogFilterParams($cat)) }}"
                               class="lat-blog-filter {{ request('categoria') === $cat ? 'is-active' : '' }}">
                                {{ $cat }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($posts->isEmpty())
                <p class="lat-empty-note--blog">
                    {{ $q !== '' || request()->filled('categoria')
                        ? $L('No encontramos artículos con esa búsqueda.', 'We couldn\'t find any articles matching that search.', 'Não encontramos artigos com essa busca.')
                        : $L('No hay artículos publicados todavía.', 'No articles published yet.', 'Nenhum artigo publicado ainda.') }}
                </p>
            @else
                {{-- El mockup pinta 6 tarjetas pero el catálogo publicado no se
                     recorta a 6: se pintan TODAS las de $posts (paginador de
                     BlogController@index, sin slice adicional aquí). --}}
                <div class="lat-blog-grid">
                    @foreach ($posts as $post)
                        @php
                            // Fila "autor · fecha · N min" — solo los datos que
                            // el post realmente tiene (brief: nunca una fila de
                            // autor vacía cuando el post no tiene autor cargado).
                            $metaParts = [];
                            if ($post->author_name) {
                                $metaParts[] = ['type' => 'text', 'value' => $post->author_name];
                            }
                            if ($post->published_at) {
                                $metaParts[] = ['type' => 'time', 'value' => $post->published_at];
                            }
                            if ($post->reading_minutes) {
                                $metaParts[] = ['type' => 'text', 'value' => $post->reading_minutes . ' ' . $L('min', 'min', 'min')];
                            }
                        @endphp
                        <article class="lat-blog-card lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms" itemscope itemtype="https://schema.org/BlogPosting">
                            <a href="{{ route('blog.show', ['locale' => $locale, 'slug' => $post->slug]) }}" class="lat-blog-card__media">
                                <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" loading="lazy" width="640" height="464" itemprop="image">
                                @if ($post->category)
                                    <span class="lat-blog-card__badge">{{ $post->category }}</span>
                                @endif
                            </a>

                            <div class="lat-blog-card__body">
                                <h3 class="clamp-2" itemprop="headline">
                                    <a href="{{ route('blog.show', ['locale' => $locale, 'slug' => $post->slug]) }}">
                                        {{ $post->title }}
                                    </a>
                                </h3>

                                <div class="lat-blog-card__foot">
                                    @if (! empty($metaParts))
                                        <div class="lat-blog-card__meta">
                                            @foreach ($metaParts as $part)
                                                @if ($part['type'] === 'time')
                                                    <time datetime="{{ $part['value']->toIso8601String() }}" itemprop="datePublished">{{ $part['value']->translatedFormat('d M, Y') }}</time>
                                                @else
                                                    <span>{{ $part['value'] }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    <a href="{{ route('blog.show', ['locale' => $locale, 'slug' => $post->slug]) }}" class="lat-blog-card__arrow"
                                       aria-label="{{ $L('Leer', 'Read', 'Ler') }}: {{ $post->title }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($posts->hasPages())
                    <div class="lat-blog-pager">
                        {{ $posts->links() }}
                    </div>
                @endif
            @endif

            {{-- ============================================================
                 CTA FINAL — "¿Listo para vivir tu propia historia?"
                 ============================================================ --}}
            <div class="lat-blog-cta">
                <div class="lat-blog-cta__left">
                    <div class="lat-blog-cta__ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>
                    </div>
                    <div>
                        <h2>{{ $bs('blog_cta_title', $L('¿Listo para vivir tu propia historia?', 'Ready to live your own story?', 'Pronto para viver sua própria história?')) }}</h2>
                        <p>{{ $bs('blog_cta_desc', $L('Inspírate, planea y reserva tu próxima aventura con nosotros.', 'Get inspired, plan and book your next adventure with us.', 'Inspire-se, planeje e reserve sua próxima aventura conosco.')) }}</p>
                    </div>
                </div>
                <div class="lat-blog-cta__actions">
                    <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-btn lat-btn--red">
                        {{ $bs('blog_cta_btn_primary', $L('Ver tours disponibles', 'See available tours', 'Ver tours disponíveis')) }}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                    @if ($waNumber)
                        <span class="lat-blog-cta__divider" aria-hidden="true"></span>
                        <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener noreferrer" class="lat-btn lat-btn--outline-white">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" width="18" height="18"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24z"/></svg>
                            {{ $bs('blog_cta_btn_wa', $L('Habla con un asesor', 'Talk to an advisor', 'Fale com um consultor')) }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
