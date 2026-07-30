@extends('layouts.app')

@section('title', __('seo.home_title'))
@section('description', __('seo.home_description'))
@section('header_variant', 'solid')

{{-- El JSON-LD WebSite (con su SearchAction) ya lo emite
     resources/views/seo/jsonld.blade.php — este bloque duplicado se retiró
     el 2026-07-27 (informe SEO: duplicado + apuntaba a /tours?q= que
     TourController::index() ignora). No reintroducir aquí. --}}

@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string => $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);

    $featuredTours = $featuredTours ?? collect();

    // ── Hero: imagen + título editables por Settings (mismo criterio que antes) ──
    $heroImgSetting = \App\Models\Setting::get('home_hero_image');
    if (is_array($heroImgSetting)) { $heroImgSetting = $heroImgSetting[0] ?? ''; }
    $heroImgSetting = trim((string) $heroImgSetting);
    $heroImgIsUploaded = ($heroImgSetting !== '' && $heroImgSetting !== '[]' && $heroImgSetting !== '""');

    // Foto por defecto del hero: panorámica de Machu Picchu 1920×960 que mandó
    // el jefe el 2026-07-29 (reemplaza el PNG de 2.5 MB con pinta de IA). Sigue
    // siendo un FALLBACK: si la clienta sube una foto en Configuración → Home,
    // manda la suya.
    $heroImgUrl = $heroImgIsUploaded
        ? \Illuminate\Support\Facades\Storage::disk('media')->url($heroImgSetting)
        : asset('assets/banners/hero-machu-picchu-pano.jpg');

    // La foto del hero es el LCP del sitio: se sirve en variantes WebP a varios
    // anchos (una vez, cacheadas en disco) en vez del archivo original. Aplica
    // igual a la foto que suba la clienta por el panel y al fallback de
    // repositorio — el disco `media` tiene su raíz en public/media, así que en
    // ambos casos hay una ruta física que GD puede leer.
    $heroImgSourcePath = $heroImgIsUploaded
        ? public_path('media/'.ltrim($heroImgSetting, '/'))
        : public_path('assets/banners/hero-machu-picchu-pano.jpg');

    $heroImg = \App\Support\ResponsiveImage::make($heroImgSourcePath, $heroImgUrl);

    // Alt de la foto del hero: editable, porque la foto también lo es. Si la
    // clienta sube otra imagen, el alt tiene que poder seguirla — un alt fijo
    // describiendo Machu Picchu sobre una foto del Colca es peor que no tenerlo.
    // El default describe la foto que viene por defecto.
    $heroImgAlt = \App\Models\Setting::get('home_hero_image_alt_' . $locale) ?: $L(
        'Ciudadela inca de Machu Picchu entre montañas y nubes, Cusco, Perú',
        'Inca citadel of Machu Picchu among mountains and clouds, Cusco, Peru',
        'Cidadela inca de Machu Picchu entre montanhas e nuvens, Cusco, Peru'
    );

    $heroTitleDefault = [
        'es' => 'Lima <em>América</em> Tours',
        'en' => 'Lima <em>América</em> Tours',
        'pt' => 'Lima <em>América</em> Tours',
    ];
    $heroTitleRaw = \App\Models\Setting::get('home_hero_title_' . $locale)
        ?: ($heroTitleDefault[$locale] ?? $heroTitleDefault['es']);
    $heroSubDefault = $L(
        'Creamos experiencias auténticas que conectan viajeros con la historia, la cultura y la esencia de nuestro país.',
        'We create authentic experiences that connect travelers with the history, culture and essence of our country.',
        'Criamos experiências autênticas que conectam viajantes com a história, a cultura e a essência do nosso país.'
    );
    $heroSub = \App\Models\Setting::get('home_hero_sub_' . $locale) ?: $heroSubDefault;

    // ── Hero (rediseño mockup 2026-07): eyebrow + línea roja + bloque
    // "10+ años" + 4 trust badges — editables por Settings con default en
    // código. Claves nuevas (aún no existen en Filament, ver reporte). ──
    $heroEyebrow = \App\Models\Setting::get('home_hero_eyebrow_' . $locale)
        ?: $L('Somos', 'We are', 'Somos');

    $heroTaglineDefault = $L("10 años mostrando\nlo mejor del Perú", "10 years showcasing\nthe best of Peru", "10 anos mostrando\no melhor do Peru");
    $heroTagline = \App\Models\Setting::get('home_hero_tagline_' . $locale) ?: $heroTaglineDefault;

    $heroYearsNumber = \App\Models\Setting::get('home_hero_years_number') ?: '10+';
    $heroYearsLabel = \App\Models\Setting::get('home_hero_years_label_' . $locale)
        ?: $L('Años de experiencia', 'Years of experience', 'Anos de experiência');
    $heroYearsSub = \App\Models\Setting::get('home_hero_years_sub_' . $locale)
        ?: $L('Miles de viajeros descubriendo el Perú', 'Thousands of travelers discovering Peru', 'Milhares de viajantes descobrindo o Peru');

    $heroTrustDefaults = [
        $L('Guías expertos locales', 'Local expert guides', 'Guias locais especializados'),
        $L('Tours 100% seguros', '100% safe tours', 'Tours 100% seguros'),
        $L('Atención personalizada', 'Personalized support', 'Atendimento personalizado'),
        $L('Mejor precio garantizado', 'Best price guaranteed', 'Melhor preço garantido'),
    ];
    // Íconos: catálogo cerrado (App\Support\HeroIcons), elegidos desde
    // Configuración → Home. Clave desconocida o vacía → ícono del mockup.
    $heroTrustIcons = collect([1, 2, 3, 4])->map(function (int $slot) {
        $chosen = \App\Models\Setting::get("home_hero_trust_{$slot}_icon");

        return \App\Support\HeroIcons::svg(\App\Support\HeroIcons::resolveKey(
            is_string($chosen) ? $chosen : null,
            $slot
        ));
    })->all();

    $heroTrust = collect($heroTrustDefaults)->map(function ($default, $i) use ($locale) {
        $n = $i + 1;
        return \App\Models\Setting::get("home_hero_trust_{$n}_{$locale}") ?: $default;
    });

    // ── Destinos reales para el select del buscador (mismo dataset que usa
    // el resto del home / tours.index — modelo Region, ya cargado por
    // HomeController como $regions). ──
    $heroRegions = $regions ?? collect();

    // ── Sugerencias (datalist) para el campo "¿Qué tour buscas?" — títulos
    // reales y publicados, catálogo pequeño (~26), consulta liviana. ──
    try {
        $heroSearchSuggestions = \App\Models\Tour::published()
            ->orderBy('title_' . $locale)
            ->limit(60)
            ->pluck('title_' . $locale);
    } catch (\Throwable $e) {
        $heroSearchSuggestions = collect();
    }

    // El pill de WhatsApp del hero se retiró el 2026-07-29 (había dos CTAs del
    // mismo canal en la primera pantalla: este y el FAB global). Con él se fueron
    // $heroWaNumber y el campo de texto del panel, para no dejar en Configuración
    // un campo que no pinta nada. El FAB sigue leyendo el número de
    // Setting::whatsappNumber() en layouts/app.blade.php.

    // ── Botón "Ver video" del hero: solo se pinta si el cliente ya cargó la
    // URL en Settings → Home (home_hero_video_url). Sin URL, mejor sin botón
    // que con un CTA muerto (ver reporte: pendiente URL real del cliente).
    //
    // El campo del panel acepta cualquier URL "de compartir" (watch?v=,
    // youtu.be, shorts, vimeo.com/ID) pero el <iframe> SOLO admite el
    // formato embebible (/embed/ID) — la URL de compartir de YouTube da
    // "Refused to display... X-Frame-Options 'sameorigin'". Se normaliza
    // aquí, en un solo lugar, a embebible + youtube-nocookie.com (mejor
    // privacidad, ya permitido en el CSP). Si no matchea ningún patrón
    // conocido, mejor sin botón que con un modal roto. ──
    $heroVideoUrlRaw = trim((string) (\App\Models\Setting::get('home_hero_video_url') ?: ''));
    $heroVideoUrl = '';
    if ($heroVideoUrlRaw !== '') {
        $u = $heroVideoUrlRaw;
        if (preg_match('~youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,})~i', $u, $m)) {
            $heroVideoUrl = 'https://www.youtube-nocookie.com/embed/' . $m[1];
        } elseif (preg_match('~youtube\.com/watch\?[^\s#]*\bv=([A-Za-z0-9_-]{6,})~i', $u, $m)) {
            $heroVideoUrl = 'https://www.youtube-nocookie.com/embed/' . $m[1];
        } elseif (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~i', $u, $m)) {
            $heroVideoUrl = 'https://www.youtube-nocookie.com/embed/' . $m[1];
        } elseif (preg_match('~youtube\.com/shorts/([A-Za-z0-9_-]{6,})~i', $u, $m)) {
            $heroVideoUrl = 'https://www.youtube-nocookie.com/embed/' . $m[1];
        } elseif (preg_match('~player\.vimeo\.com/video/(\d+)~i', $u, $m)) {
            $heroVideoUrl = 'https://player.vimeo.com/video/' . $m[1];
        } elseif (preg_match('~vimeo\.com/(?:channels/[\w-]+/|groups/[\w-]+/videos/)?(\d+)~i', $u, $m)) {
            $heroVideoUrl = 'https://player.vimeo.com/video/' . $m[1];
        }
        // Ningún patrón conocido: $heroVideoUrl queda vacío y el @if de abajo
        // simplemente no pinta el botón "Ver video".
    }

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

    // ── Galería "Descubre la belleza del Perú" — editable en Settings → Home,
    // fallback a fotos de destinos reales ya presentes en storage/app/public/tours
    // para que la sección nunca salga vacía. ──
    // Devuelve la URL pública Y la ruta FÍSICA de la imagen. Las dos, y por
    // separado, a propósito: derivar la ruta del disco parseando la URL parece
    // equivalente y no lo es. En staging la app vive en una subcarpeta
    // (`/staging`), así que `asset()` devuelve `/staging/storage/...` y
    // `public_path()` de ese path apunta a un archivo que no existe. Resultado
    // silencioso: la optimización no se aplicaba y se servían los JPG de 300+ KB
    // tal cual (detectado midiendo en staging, en local funcionaba).
    $galleryResolve = function (string $settingKey, string $fallbackRelative) {
        $val = \App\Models\Setting::get($settingKey);
        if (is_array($val)) { $val = $val[0] ?? ''; }
        $val = trim((string) $val);

        if ($val !== '' && $val !== '[]' && $val !== '""') {
            // Disco `media` → raíz en public/media.
            return [
                'url' => \Illuminate\Support\Facades\Storage::disk('media')->url($val),
                'path' => public_path('media/'.ltrim($val, '/')),
            ];
        }

        // Fallback → disco `public`, cuya raíz es public/storage.
        return [
            'url' => asset('storage/'.$fallbackRelative),
            'path' => public_path('storage/'.$fallbackRelative),
        ];
    };

    $gallerySlots = [
        ['key' => 'home_gallery_img_1', 'fallback' => 'tours/OASIS-DE-HUACACHINA-ISLAS-BALLESTAS-EN-PARACAS-1.jpg', 'alt' => [
            'es' => 'Islas Ballestas y costa de Paracas, Perú', 'en' => 'Ballestas Islands and Paracas coastline, Peru', 'pt' => 'Ilhas Ballestas e litoral de Paracas, Peru',
        ]],
        ['key' => 'home_gallery_img_2', 'fallback' => 'tours/machu-picchu-paquete-de-4-dias-lima-view-tours.jpg', 'alt' => [
            'es' => 'Machu Picchu, Cusco', 'en' => 'Machu Picchu, Cusco', 'pt' => 'Machu Picchu, Cusco',
        ]],
        ['key' => 'home_gallery_img_3', 'fallback' => 'tours/2024-12-MONTANA-1-1.png', 'alt' => [
            'es' => 'Montaña de 7 Colores, Cusco', 'en' => 'Rainbow Mountain, Cusco', 'pt' => 'Montanha de 7 Cores, Cusco',
        ]],
        ['key' => 'home_gallery_img_4', 'fallback' => 'tours/2024-02-Centro-Historico-Lima-01-1.webp', 'alt' => [
            'es' => 'Arquitectura colonial del Centro Histórico de Lima', 'en' => 'Colonial architecture in Lima\'s Historic Center', 'pt' => 'Arquitetura colonial do Centro Histórico de Lima',
        ]],
        ['key' => 'home_gallery_img_5', 'fallback' => 'tours/2024-12-laguna-de-humantay-750x536-1.jpg', 'alt' => [
            'es' => 'Laguna Humantay, Cusco', 'en' => 'Humantay Lagoon, Cusco', 'pt' => 'Lagoa Humantay, Cusco',
        ]],
        ['key' => 'home_gallery_img_6', 'fallback' => 'tours/2024-02-Nazca-02.webp', 'alt' => [
            'es' => 'Líneas de Nazca, Perú', 'en' => 'Nazca Lines, Peru', 'pt' => 'Linhas de Nazca, Peru',
        ]],
    ];

    // Los fallbacks de la galería son fotos de tours importadas de WordPress y
    // algunas pesan 300+ KB en JPG (la de Machu Picchu, 319 KB). Se sirven en
    // WebP a 640, que es de sobra para la tira (cada ítem mide ~400 px de ancho).
    // Las que sube la clienta por el panel ya pasan por ImageOptimizer.
    $galleryImages = collect($gallerySlots)->map(function ($slot) use ($galleryResolve, $locale) {
        $img = $galleryResolve($slot['key'], $slot['fallback']);

        return [
            'url' => \App\Support\ResponsiveImage::make($img['path'], $img['url'], [640], '(min-width: 900px) 400px, 70vw')['src'],
            'alt' => $slot['alt'][$locale] ?? $slot['alt']['es'],
        ];
    });
@endphp

{{-- Precarga de la foto del hero (LCP del sitio). Mismo src/srcset/sizes que
     el <img> de abajo — si no coincidieran, el navegador descargaría la foto
     dos veces. El @stack('preload') del layout está antes del CSS de fuentes
     para que el preload scanner la vea cuanto antes. --}}
@push('preload')
    <link rel="preload" as="image" href="{{ $heroImg['src'] }}"
          @if ($heroImg['srcset'] !== '')
              imagesrcset="{{ $heroImg['srcset'] }}" imagesizes="{{ $heroImg['sizes'] }}"
          @endif
          fetchpriority="high">
@endpush

@section('content')

<div class="lat-page">

    {{-- ============================================================
         HERO — foto a sangre (columna derecha, altura completa) + texto,
         tarjeta de confianzas y buscador de 4 campos en doble marco.
         Calca de hero.png (mockup aprobado 2026-07).
         ============================================================ --}}
    <section class="lat-hero" aria-labelledby="hero-title">
      <div class="lat-hero__top">
        <div class="lat-hero__media">
            {{-- LCP del sitio: variantes WebP por ancho (ResponsiveImage), carga
                 prioritaria y NUNCA lazy, con las dimensiones reales del archivo
                 que se sirve para que el navegador reserve la caja exacta. El
                 preload va en el <head> (más abajo, @push('preload')) con el
                 mismo srcset: si difirieran, el navegador bajaría dos fotos. --}}
            <img
                src="{{ $heroImg['src'] }}"
                @if ($heroImg['srcset'] !== '')
                    srcset="{{ $heroImg['srcset'] }}"
                    sizes="{{ $heroImg['sizes'] }}"
                @endif
                alt="{{ $heroImgAlt }}"
                @if ($heroImg['width'] && $heroImg['height'])
                    width="{{ $heroImg['width'] }}" height="{{ $heroImg['height'] }}"
                @endif
                loading="eager" fetchpriority="high" decoding="async">
            <span class="lat-hero__media-fade" aria-hidden="true"></span>

            {{-- Fila inferior sobre la foto (mockup): bloque "10+" a la izquierda +
                 WhatsApp/Ver video apilados a la derecha. Los botones comparten
                 clase .lat-btn para que el FAB global de WhatsApp
                 (layouts/app.blade.php) los detecte y se desplace hacia arriba
                 sin solaparse en ningún breakpoint. --}}
            <div class="lat-hero__overlay-row">
                <div class="lat-hero__years">
                    <strong>{{ $heroYearsNumber }}</strong>
                    <span class="lat-hero__years-label">{{ $heroYearsLabel }}</span>
                    <span class="lat-hero__years-sub">{!! nl2br(e($heroYearsSub)) !!}</span>
                </div>

                {{-- El pill de WhatsApp del hero se retiró el 2026-07-29 por
                     decisión del jefe: había DOS CTAs del mismo canal en la
                     primera pantalla (este pill y el FAB flotante global). Queda
                     el FAB, que acompaña en todo el sitio y no compite con el
                     buscador. Consecuencia práctica: este contenedor solo se
                     pinta si hay video cargado — sin él no queda un div vacío
                     empujando layout sobre la foto. --}}
                @if ($heroVideoUrl !== '')
                    <div class="lat-hero__floating-actions">
                        <button type="button" class="lat-btn lat-btn--video" id="heroVideoBtn"
                                aria-haspopup="dialog" aria-controls="heroVideoModal">
                            {{ __('ui.watch_video') }}
                            <span class="lat-btn--video__ic" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="lat-hero__top-content">
            <div class="lat-wrap">
                <div class="lat-hero__text">
                    <span class="lat-eyebrow">{{ $heroEyebrow }}</span>

                    {{-- El H1 envuelve marca + subtitular rojo, no solo la marca.
                         Los dos son elementos visibles y contiguos del mockup y
                         conservan su copy exacto: no hay texto oculto ni cambia
                         una sola medida del diseño. El motivo es de SEO — la home
                         es la página con más autoridad del dominio y un H1 que
                         solo dice el nombre de marca no refuerza el tema por el
                         que compite el sitio; envolviendo también el subtitular,
                         el H1 vuelve a contener Lima / Tours / Perú. La keyword
                         exacta ("Tours en Lima") sigue en el <title>.
                         Los estilos viven en .lat-hero__brand (clase, no
                         etiqueta) justamente para que el texto se vea idéntico
                         sin importar qué etiqueta HTML lo envuelva. --}}
                    <h1 id="hero-title" class="lat-hero__title">
                        <span class="lat-hero__brand">{!! $heroTitleRaw !!}</span>
                        <span class="lat-hero__tagline">{!! nl2br(e($heroTagline)) !!}</span>
                    </h1>

                    <p class="lat-hero__desc">{{ $heroSub }}</p>
                </div>

                {{-- Tarjeta blanca flotante — 4 confianzas (icono + texto centrado) --}}
                <div class="lat-hero-trust">
                    @foreach ($heroTrust as $i => $label)
                        <div class="lat-htc">
                            <span class="lat-htc__ic">{!! $heroTrustIcons[$i] !!}</span>
                            <span class="lat-htc__label">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
      </div>

        {{-- Buscador — 4 campos reales (doble marco: contenedor oscuro + barra clara).
             Fuera de .lat-hero__top a propósito: nunca debe solaparse con el
             bloque rojo "10+" ni con los botones flotantes sobre la foto. --}}
        <div class="lat-wrap">
            <div class="lat-hero__search-wrap">
                    <form class="lat-search" role="search" method="GET" action="{{ route('tours.results', ['locale' => $locale]) }}">
                        <div class="lat-search__field">
                            <label for="s-q">{{ $L('¿Qué tour buscas?', 'What tour are you looking for?', 'Que tour você procura?') }}</label>
                            <div class="lat-search__val">
                                <input id="s-q" type="text" name="q" list="hero-tour-suggestions" autocomplete="off"
                                       placeholder="{{ $L('Ej. City Tour, Machu Picchu…', 'E.g. City Tour, Machu Picchu…', 'Ex. City Tour, Machu Picchu…') }}">
                                <svg class="lat-search__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                            <datalist id="hero-tour-suggestions">
                                @foreach ($heroSearchSuggestions as $title)
                                    <option value="{{ $title }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div class="lat-search__field">
                            <label for="s-dest">{{ $L('Destino', 'Destination', 'Destino') }}</label>
                            <div class="lat-search__val">
                                <select id="s-dest" name="destino">
                                    <option value="">{{ $L('Todos los destinos', 'All destinations', 'Todos os destinos') }}</option>
                                    @foreach ($heroRegions as $region)
                                        <option value="{{ $region->slug }}">{{ $region->name }}</option>
                                    @endforeach
                                </select>
                                <svg class="lat-search__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                        </div>

                        <div class="lat-search__field">
                            <label for="s-date">{{ $L('Fecha', 'Date', 'Data') }}</label>
                            <div class="lat-search__val">
                                {{-- Fecha de salida: FILTRA de verdad contra los días bloqueados
                                     del CMS (BlockedDate) en TourController::search() — las mismas
                                     reglas que respeta la ficha de tour. `min` evita de entrada
                                     elegir una fecha pasada; el calendario nativo no permite
                                     deshabilitar días sueltos, así que un día sin salidas se
                                     explica en la página de resultados. --}}
                                <input id="s-date" type="text" name="fecha" autocomplete="off"
                                       min="{{ now()->toDateString() }}"
                                       placeholder="{{ $L('Selecciona fecha', 'Pick a date', 'Selecione a data') }}"
                                       onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                                <svg class="lat-search__ic-cal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            </div>
                        </div>

                        <div class="lat-search__field">
                            <label for="s-pax">{{ $L('Personas', 'People', 'Pessoas') }}</label>
                            <div class="lat-search__val">
                                <select id="s-pax" name="pax">
                                    @for ($n = 1; $n <= 9; $n++)
                                        <option value="{{ $n }}" @selected($n === 2)>{{ $n }} {{ $n === 1 ? $L('persona', 'person', 'pessoa') : $L('personas', 'people', 'pessoas') }}</option>
                                    @endfor
                                    <option value="10+">10+ {{ $L('personas', 'people', 'pessoas') }}</option>
                                </select>
                                <svg class="lat-search__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                        </div>

                        <div class="lat-search__go">
                            <button type="submit" class="lat-btn lat-btn--red">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                                {{ $L('Buscar Tours', 'Search Tours', 'Buscar Tours') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </section>

    {{-- ============================================================
         MODAL "Ver video" — solo existe en el DOM si hay URL en Settings.
         El iframe NO se crea hasta el clic (evita cargar un player al
         cargar la home y matar el LCP). Fuera de .lat-hero__top a
         propósito: ese contenedor tiene overflow:hidden en mobile y el
         modal es position:fixed a pantalla completa.
         ============================================================ --}}
    @if ($heroVideoUrl !== '')
        <div class="lat-video-modal" id="heroVideoModal" role="dialog" aria-modal="true"
             aria-label="{{ __('ui.watch_video') }}" hidden>
            <div class="lat-video-modal__backdrop" data-video-close></div>
            <div class="lat-video-modal__panel">
                <button type="button" class="lat-video-modal__close" data-video-close
                        aria-label="{{ $L('Cerrar video', 'Close video', 'Fechar vídeo') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
                <div class="lat-video-modal__frame" data-video-frame></div>
            </div>
        </div>
    @endif

    {{-- ============================================================
         TOURS DESTACADOS — reales, ordenados por featured_order/compras
         ============================================================ --}}
    <section class="lat-wrap" style="padding:70px 24px" id="tours" aria-labelledby="destacados-title">
        <div class="lat-sec-head lat-sec-head--home">
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
         GALERÍA — "Descubre la belleza del Perú"
         Tira horizontal edge-to-edge de fotos de destinos, editable en
         Settings → Home → Imágenes del Home → Galería. Fallback a fotos
         reales de storage/app/public/tours si el admin no sube nada
         (nunca sale vacía). Existe en producción (limaamericatours.com,
         sección "Nuestra Galería"); aquí va justo después de la tira de
         garantías y antes de "Explora por categoría".
         ============================================================ --}}
    <section class="lat-gallery" aria-labelledby="gallery-title">
        <div class="lat-wrap">
            <div class="lat-sec-head lat-sec-head--home">
                <span class="lat-eyebrow is-center">{{ $L('Galería', 'Gallery', 'Galeria') }}</span>
                <h2 id="gallery-title">{{ $L('Descubre la belleza del Perú', 'Discover the beauty of Peru', 'Descubra a beleza do Peru') }}</h2>
            </div>
        </div>

        <div class="lat-gallery__strip">
            @foreach ($galleryImages as $photo)
                <div class="lat-gallery__item">
                    <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" loading="lazy" width="400" height="500">
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================================================
         EXPLORA POR CATEGORÍA — reales (modelo Category)
         ============================================================ --}}
    @if ($categories->isNotEmpty())
        <section class="lat-wrap" style="padding:70px 24px" aria-labelledby="cats-title">
            <div class="lat-sec-head lat-sec-head--home">
                <span class="lat-eyebrow is-center">{{ $L('Elige tu experiencia', 'Choose your experience', 'Escolha sua experiência') }}</span>
                <h2 id="cats-title">{{ $L('Explora por categoría', 'Explore by category', 'Explore por categoria') }}</h2>
                <p>{{ $L('Descubre el tipo de aventura que más te gusta: recorridos por la ciudad, sabores peruanos, aventura y culturas milenarias.', 'Discover the kind of adventure you like best: city tours, Peruvian flavors, adventure and ancient cultures.', 'Descubra o tipo de aventura que mais gosta: passeios pela cidade, sabores peruanos, aventura e culturas milenares.') }}</p>
            </div>

            <div class="lat-cats">
                @foreach ($categories as $cat)
                    @php
                        // Relleno cuando la categoría no tiene ningún tour con foto:
                        // variante de 640 (la tarjeta mide ~340 px), no el archivo
                        // original de 2.5 MB que se usaba antes.
                        $catImg = optional($cat->tours->first())->cover_url ?? \App\Support\ResponsiveImage::defaultPhotoUrl(640);
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
            <div class="lat-sec-head lat-sec-head--home">
                <span class="lat-eyebrow is-center">{{ $L('Aprovecha ahora', 'Grab it now', 'Aproveite agora') }}</span>
                <h2 id="offers-title">{{ $L('Ofertas especiales', 'Special offers', 'Ofertas especiais') }}</h2>
                <p>{{ $L('Promociones por tiempo limitado en nuestros tours más populares.', 'Limited-time promotions on our most popular tours.', 'Promoções por tempo limitado em nossos tours mais populares.') }}</p>
            </div>

            <div class="lat-dest-grid">
                @foreach ($offers as $offer)
                    @php
                        $offerImg = \App\Support\ImagePath::url($offer->image) ?? \App\Support\ResponsiveImage::defaultPhotoUrl(640);
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
                                        <span class="lat-amt">{{ \App\Support\Money::format($offer->price, optional($offer->tour)->currency ?? \App\Support\Money::site()) }}</span>
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

    {{-- ============================================================
         CTA FINAL — masa de rojo con la silueta de Sudamérica del logo
         como motivo. Clase propia .lat-home-cta (NO .lat-cta-final, que
         es de about.blade.php / pages/_lat-about.scss, fuera de alcance
         de este lote) para no compartir cascada entre archivos.
         ============================================================ --}}
    <section class="lat-home-cta" aria-labelledby="home-cta-title">
        <div class="lat-home-cta__mark" aria-hidden="true">
            <img src="{{ asset('assets/logos/logo-america-white.webp') }}" alt="" loading="lazy" width="768" height="379">
        </div>
        <div class="lat-wrap lat-home-cta__inner">
            <span class="lat-eyebrow is-center">{{ $L('Vive la experiencia', 'Live the experience', 'Viva a experiência') }}</span>
            <h2 id="home-cta-title">{{ $L('Tu próxima aventura empieza aquí', 'Your next adventure starts here', 'Sua próxima aventura começa aqui') }}</h2>
            <p>{{ $L('Explora nuestro catálogo completo y encuentra el tour perfecto para ti.', 'Explore our full catalog and find the perfect tour for you.', 'Explore nosso catálogo completo e encontre o tour perfeito para você.') }}</p>
            <div class="lat-home-cta__actions">
                <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-home-cta__btn">
                    {{ $L('Ver todos los tours', 'View all tours', 'Ver todos os tours') }}
                </a>
            </div>
        </div>
    </section>

</div>

@endsection

@push('schema')
@include('partials.faq-schema')
@endpush

@if ($heroVideoUrl !== '')
@push('scripts')
<script>
(function () {
    var btn = document.getElementById('heroVideoBtn');
    var modal = document.getElementById('heroVideoModal');
    if (!btn || !modal) return;

    var frame = modal.querySelector('[data-video-frame]');
    var closers = modal.querySelectorAll('[data-video-close]');
    var videoUrl = @json($heroVideoUrl);
    var videoTitle = @json(__('ui.watch_video'));
    var lastFocused = null;

    function focusableEls() {
        return Array.prototype.slice
            .call(modal.querySelectorAll('button, [href], iframe, [tabindex]:not([tabindex="-1"])'))
            .filter(function (el) { return el.offsetParent !== null; });
    }

    function onKeydown(e) {
        if (e.key === 'Escape' || e.key === 'Esc') { close(); return; }
        if (e.key !== 'Tab') return;
        var els = focusableEls();
        if (!els.length) return;
        var first = els[0], last = els[els.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }

    function open() {
        lastFocused = document.activeElement;

        // El iframe recién se crea aquí — al abrir el modal — para que el
        // recurso de video nunca aparezca en la red mientras carga la home.
        var iframe = document.createElement('iframe');
        iframe.src = videoUrl;
        iframe.title = videoTitle;
        iframe.allow = 'autoplay; fullscreen; picture-in-picture';
        iframe.allowFullscreen = true;
        iframe.setAttribute('frameborder', '0');
        frame.innerHTML = '';
        frame.appendChild(iframe);

        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', onKeydown);

        var els = focusableEls();
        (els[0] || modal).focus();
    }

    function close() {
        modal.hidden = true;
        document.body.style.overflow = '';
        frame.innerHTML = ''; // corta el video al cerrar, no solo lo oculta
        document.removeEventListener('keydown', onKeydown);
        if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    }

    btn.addEventListener('click', open);
    closers.forEach(function (el) { el.addEventListener('click', close); });
})();
</script>
@endpush
@endif
