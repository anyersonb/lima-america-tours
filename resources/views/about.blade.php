@extends('layouts.app')

@section('title', __('nav.about') . ' — ' . __('seo.site_name'))
@section('description', __('ui.about_meta_description'))
@section('header_variant', 'solid')

@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string => $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);

    // Blocks from CMS (Page model, slug "nosotros") — every key falls back
    // to the mockup's hardcoded text/images when the admin hasn't set it.
    $b = $page?->blocks ?? [];

    $mediaUrl = function ($path): ?string {
        if (is_array($path)) { $path = $path[0] ?? ''; }
        $path = trim((string) $path);
        return ($path !== '' && $path !== '[]' && $path !== '""')
            ? \Illuminate\Support\Facades\Storage::disk('media')->url($path)
            : null;
    };

    $bl = function (string $key, string $es, string $en, string $pt) use ($b, $locale): string {
        $val = trim((string) ($b[$key . '_' . $locale] ?? ''));
        if ($val !== '') {
            return $val;
        }
        return $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);
    };

    // ── Destinos (chips bajo el texto del split) ─────────────────────────
    $defaultDestinations = [
        ['label' => 'Ica', 'icon' => 'pin'],
        ['label' => 'Paracas', 'icon' => 'pin'],
        ['label' => 'Circuito Mágico del Agua', 'icon' => 'pin'],
        ['label' => 'Caral', 'icon' => 'pin'],
        ['label' => 'Pachacámac', 'icon' => 'pin'],
        ['label' => 'Nazca', 'icon' => 'pin'],
        ['label' => $L('Tours gastronómicos', 'Food tours', 'Tours gastronômicos'), 'icon' => 'food'],
    ];
    $destinations = $b['destinations'] ?? $defaultDestinations;

    // ── Misión, Visión y Valores ──────────────────────────────────────────
    $defaultMvv = [
        [
            'icon'  => 'target',
            'title_es' => 'Misión', 'title_en' => 'Mission', 'title_pt' => 'Missão',
            'desc_es' => 'Contribuir a la calidad del desarrollo de la industria turística sobre la base de experiencias memorables, creciendo de forma competitiva y equilibrada.',
            'desc_en' => 'Contribute to the quality development of the tourism industry through memorable experiences, growing in a competitive and balanced way.',
            'desc_pt' => 'Contribuir para a qualidade do desenvolvimento da indústria turística com base em experiências memoráveis, crescendo de forma competitiva e equilibrada.',
        ],
        [
            'icon'  => 'eye',
            'title_es' => 'Visión', 'title_en' => 'Vision', 'title_pt' => 'Visão',
            'desc_es' => 'Convertirnos en una empresa de proyección internacional que impulse el desarrollo turístico y cree oportunidades transformadoras para nuestra comunidad.',
            'desc_en' => 'Become an internationally-minded company that drives tourism development and creates transformative opportunities for our community.',
            'desc_pt' => 'Tornar-nos uma empresa de projeção internacional que impulsione o desenvolvimento turístico e crie oportunidades transformadoras para nossa comunidade.',
        ],
        [
            'icon'  => 'heart',
            'title_es' => 'Valores', 'title_en' => 'Values', 'title_pt' => 'Valores',
            'tags' => ['Responsabilidad', 'Laboriosidad', 'Honestidad', 'Honradez', 'Dignidad', 'Justicia', 'Solidaridad', 'Humanismo'],
            'tags_en' => ['Responsibility', 'Diligence', 'Honesty', 'Integrity', 'Dignity', 'Justice', 'Solidarity', 'Humanism'],
            'tags_pt' => ['Responsabilidade', 'Laboriosidade', 'Honestidade', 'Honradez', 'Dignidade', 'Justiça', 'Solidariedade', 'Humanismo'],
        ],
    ];
    // Repeater real del CMS (PageResource → "Stats band", Misión/Visión/Valores/Equipo).
    // Mismo slot visual que las tarjetas MVV: si el admin cargó items, se usan (con
    // icono asignado por posición, ya que el repeater no tiene campo "icono"); si no,
    // se mantiene el contenido de hoy. "$b['mvv']" queda como fallback legado (campo
    // huérfano sin UI en el admin, nunca poblado, pero se respeta por compatibilidad).
    $mvvIconsByPosition = ['target', 'eye', 'heart'];
    if (!empty($b['stats']) && is_array($b['stats'])) {
        $mvvItems = collect($b['stats'])->values()->map(function ($item, $i) use ($mvvIconsByPosition) {
            return [
                'icon'     => $mvvIconsByPosition[$i] ?? 'target',
                'title_es' => $item['title_es'] ?? '',
                'title_en' => $item['title_en'] ?? '',
                'title_pt' => $item['title_pt'] ?? '',
                'desc_es'  => $item['desc_es'] ?? '',
                'desc_en'  => $item['desc_en'] ?? '',
                'desc_pt'  => $item['desc_pt'] ?? '',
            ];
        })->all();
    } else {
        $mvvItems = $b['mvv'] ?? $defaultMvv;
    }

    // ── Banda de estadísticas — reutiliza HomeStatsResolver ───────────────
    // Antes esta banda calculaba sus propios 4 números aquí mismo, con dos
    // defaults INVENTADOS (año de fundación → 2015, viajeros felices → 5000)
    // que ningún cliente confirmó (docs/rebrand/ESTADO.md §2 y §3) — justo lo
    // que la regla del proyecto prohíbe. El bug real reportado ("las 4
    // tarjetas muestran 0") no era ese: el valor SÍ se calculaba bien, pero
    // solo se pintaba vía la animación JS de más abajo, que arrancaba el
    // HTML en literal "0" — si el JS no corre (o el IntersectionObserver
    // nunca cruza el umbral de visibilidad), el cero queda fijo para
    // siempre. Con el resolver, el valor final ya llega resuelto en el HTML;
    // la animación es una mejora progresiva, no la única fuente del número.
    $valuesCount = count(array_filter($mvvItems[2]['tags'] ?? [], fn ($t) => trim((string) $t) !== ''));
    $aboutStatsBand = app(\App\Services\HomeStatsResolver::class)->resolveAboutBand($locale, $valuesCount);
    $aboutStatsVisible = collect($aboutStatsBand['slots'])->filter(fn ($s) => $s['show'])->values();

    // ── Badge "10+ años en el mercado turístico" del split — corrección
    // 2026-08-11: era un "10+" fijo en el HTML, sin pasar por ningún
    // resolvedor. Mismo mecanismo y mismo dato (Setting company_started_year)
    // que la barra de stats de arriba y el badge del hero de Home
    // (HomeStatsResolver::yearsActiveBadge()): sin el año real de inicio de
    // operaciones, el badge entero se oculta en vez de publicar un "10+" sin
    // respaldo (docs/rebrand/LOTE-MOCKUPS-AGO-2026.md, tabla "Lo que NO se
    // publica"). El <h1>/H2 "10 años mostrando lo mejor del Perú" de esta
    // misma sección NO se toca: es copy de marca, no una cifra medida.
    $splitYearsBadge = app(\App\Services\HomeStatsResolver::class)->yearsActiveBadge();

    // ── Checks "¿Por qué viajar con Lima América Tours?" (card del equipo) ──
    // Lote ago-2026: eran 6 <li> fijos en este Blade (5 evergreen + el
    // horario dinámico). Ahora los 5 evergreen vienen de Configuración →
    // Página "nosotros" → blocks.why_travel_items; el horario sigue aparte
    // (única fuente: Setting::contactHours, ver más abajo) y se agrega al
    // final de la lista en el HTML, nunca dentro de este repeater.
    $defaultWhyTravelChecks = [
        $L('Guías certificados', 'Certified guides', 'Guias certificados'),
        $L('Experiencias auténticas', 'Authentic experiences', 'Experiências autênticas'),
        $L('Grupos pequeños', 'Small groups', 'Grupos pequenos'),
        $L('Atención personalizada', 'Personalized support', 'Atendimento personalizado'),
        $L('Cancelación flexible', 'Flexible cancellation', 'Cancelamento flexível'),
    ];
    $whyTravelChecks = collect(is_array($b['why_travel_items'] ?? null) ? $b['why_travel_items'] : [])
        ->map(fn ($item) => trim((string) ($item['text_' . $locale] ?? ($item['text_es'] ?? ''))))
        ->filter(fn ($text) => $text !== '')
        ->values()
        ->all();
    if (empty($whyTravelChecks)) {
        $whyTravelChecks = $defaultWhyTravelChecks;
    }

    // RUC de la empresa — Setting::companyRuc() ya vive SIN default (ver su
    // docblock): dos RUC contradictorios circulaban antes en footer/Términos
    // y ninguno está confirmado. El sello "Empresa registrada" de la fila de
    // confianza más abajo solo se pinta si este valor existe.
    $companyRucForBadge = \App\Models\Setting::companyRuc();

    // Se usa en la banda de estadísticas Y en el CTA final más abajo — debe
    // quedar definido siempre, sin importar si la banda de arriba se oculta
    // por no tener slots visibles (regression 2026-08-11: "Undefined
    // variable $waNumber" en el CTA cuando la banda se ocultaba entera).
    // Sin fallback a otro número de WhatsApp: ver App\Models\Setting::whatsappNumber().
    $waNumber = \App\Models\Setting::whatsappNumber();

    // Horario real (Configuración → Contacto), reemplaza el "Soporte 24/7" /
    // "Atención 24/7" inventado del mockup en la card de equipo y en el CTA
    // final (docs/rebrand/LOTE-MOCKUPS-AGO-2026.md, tabla "Lo que NO se publica").
    $contactHours = \App\Models\Setting::contactHours($locale);

    // ── Rediseño del hero (mockup `23.53.39.jpeg`, franja oscura de arriba:
    // hero + barra de stats + franja de garantías) ────────────────────────

    // ¿Hay pasarela real cobrando? Mismo helper que ya usa tours/show.blade.php
    // para no prometer "Reserva 100% Segura" mientras las llaves sean de
    // prueba (docs/rebrand/inventario/00-VALIDACION-STAGING.md).
    $onlinePaymentAvailable = \App\Support\OnlinePayment::available();

    // 4 micro-features 2×2 del hero. Repeater con fallback (mismo patrón que
    // $whyTravelChecks arriba): si el panel carga `blocks.hero_features`, se
    // usa; si no, el contenido real ya vetted en la card "¿Por qué viajar…"
    // de más abajo (mismos conceptos, sin duplicar copy inventado del mockup
    // de Lima View). Catálogo cerrado de íconos (App\Support\HeroIcons).
    $defaultHeroFeatures = [
        ['icon' => 'guide', 'title' => $L('Guías Expertos', 'Expert Guides', 'Guias Especializados'), 'sub' => $L('Locales certificados', 'Certified locals', 'Locais certificados')],
        ['icon' => 'clock', 'title' => $L('Experiencias', 'Experiences', 'Experiências'), 'sub' => $L('100% auténticas', '100% authentic', '100% autênticas')],
        ['icon' => 'group', 'title' => $L('Grupos Reducidos', 'Small Groups', 'Grupos Reduzidos'), 'sub' => $L('Atención personalizada', 'Personalized attention', 'Atendimento personalizado')],
        ['icon' => 'shield', 'title' => $L('Seguridad Total', 'Total Safety', 'Segurança Total'), 'sub' => $L('Viaja con confianza', 'Travel with confidence', 'Viaje com confiança')],
    ];
    $heroFeatures = collect(is_array($b['hero_features'] ?? null) ? $b['hero_features'] : [])
        ->map(fn ($item) => [
            'icon'  => (is_string($item['icon'] ?? null) && \App\Support\HeroIcons::exists($item['icon'])) ? $item['icon'] : 'guide',
            'title' => trim((string) ($item['title_' . $locale] ?? ($item['title_es'] ?? ''))),
            'sub'   => trim((string) ($item['sub_' . $locale] ?? ($item['sub_es'] ?? ''))),
        ])
        ->filter(fn ($item) => $item['title'] !== '')
        ->values()
        ->all();
    if (empty($heroFeatures)) {
        $heroFeatures = $defaultHeroFeatures;
    }

    // Botón "Ver Video" del hero de Nosotros — MISMO mecanismo que el de Home
    // (App\Support\VideoEmbed, ya existente, evita duplicar la normalización
    // de URL) pero con su propia clave de Setting y su propio id de modal
    // (dos modales con "heroVideoModal" en la misma página rompen
    // getElementById). Sin URL cargada hoy: el botón no se pinta.
    $aboutHeroVideoUrl = \App\Support\VideoEmbed::normalize(\App\Models\Setting::get('about_hero_video_url'));
    $aboutHeroVideoLabel = $bl('hero_video_label', 'Ver Video', 'Watch Video', 'Ver Vídeo');

    // Tira de avatares de viajeros reales — guard de dato: 0 de 13 testimonios
    // activos tienen avatar cargado hoy (verificado en BD), así que esta
    // consulta siempre llega vacía y la tira queda oculta hasta que existan
    // fotos reales con permiso de uso (docs/rebrand/inventario/01-nosotros-y-menu.md, punto 6/8).
    $testimonialsWithAvatar = \App\Models\Testimonial::active()
        ->whereNotNull('avatar')->where('avatar', '!=', '')
        ->orderByDesc('created_at')
        ->limit(5)
        ->get();

    // ── Collage del hero — 6 fotos REALES del cliente (nunca la que trae
    // marca de agua de cuscoperu.com, ver ESTADO.md §6). Curadas de
    // docs/rebrand/CONTENIDO-REAL-PRODUCCION.md §6.1 ("Las mejores 20"), ya
    // copiadas al disco público con prefijo de fecha. Ancho intrínseco
    // verificado contra el ancho renderizado del mosaico (celdas ≤260px en
    // desktop): el más chico de los 6 mide 467px, siempre por encima.
    $heroCollage = [
        ['file' => '2025-10-IMG_3280.webp', 'alt' => $L('Grupo de viajeros frente a la Catedral de Lima', 'Group of travelers in front of Lima Cathedral', 'Grupo de viajantes em frente à Catedral de Lima')],
        ['file' => '2025-10-1000385828.webp', 'alt' => $L('Viajeros y guía en la Plaza Mayor de Lima', 'Travelers and guide in Lima\'s Plaza Mayor', 'Viajantes e guia na Plaza Mayor de Lima')],
        ['file' => '2025-10-1000385822.webp', 'alt' => $L('Grupo de viajeros sentados en la Plaza Mayor', 'Group of travelers sitting in the Plaza Mayor', 'Grupo de viajantes sentados na Plaza Mayor')],
        ['file' => '2025-10-caption-1.jpg', 'alt' => $L('Viajeros con el cartel de Lima América Tours frente a la Catedral', 'Travelers holding the Lima América Tours sign by the Cathedral', 'Viajantes com a placa da Lima América Tours em frente à Catedral')],
        ['file' => '2025-10-WhatsApp-Image-2022-09-14-at-4.46.19-PM.webp', 'alt' => $L('Grupo de viajeros frente a la Catedral de Lima, 2022', 'Group of travelers in front of Lima Cathedral, 2022', 'Grupo de viajantes em frente à Catedral de Lima, 2022')],
        ['file' => '2025-10-IMG_0686.webp', 'alt' => $L('Guía de Lima América Tours explicando a un grupo en la Plaza Mayor', 'Lima América Tours guide explaining to a group in the Plaza Mayor', 'Guia da Lima América Tours explicando a um grupo na Plaza Mayor')],
    ];
    $heroCollageUrls = collect($heroCollage)->map(fn ($p) => [
        'url' => asset('storage/tours/' . $p['file']),
        'alt' => $p['alt'],
    ]);

    // ── Equipo real (Guide, ruta /nosotros ya lo resuelve: Guide::active()->ordered()).
    // Sin foto por decisión del cliente (ver GuideSeeder) — la tarjeta se resuelve con
    // iniciales, nunca una foto de stock representando a una persona real.
    $aboutGuides = $guides ?? collect();

    // ── Testimonios reales de esta página (Testimonial::active(), 4 más
    // recientes, ya resueltos por la ruta) — franja oscura del mockup 03.
    $aboutTestimonials = $testimonials ?? collect();

    // ── Destinos reales (Region::active()->withPublishedTours(), ya
    // resueltos por la ruta) — mismo guard que el home, hoy 3 (Lima/Cusco/Ica).
    $aboutDestinations = $destinationRegions ?? collect();

    // ── Timeline "Un camino lleno de pasión y compromiso" — el mockup
    // inventa 6 hitos (2014→2024) que nadie confirmó (ESTADO.md: el sitio
    // dice 2024, las fotos llegan a 2022). Se maqueta la sección completa
    // pero el guard es de DATO: solo se pinta si el panel carga hitos reales
    // en el bloque "timeline" de esta página (Page->blocks, mismo mecanismo
    // que "stats"/"mvv" — hoy no existe ese campo en Filament, así que
    // $timelineItems siempre llega vacío y la sección queda oculta hasta que
    // se dé de alta el repeater y el cliente cargue los años reales).
    $timelineItems = is_array($b['timeline'] ?? null) ? array_values($b['timeline']) : [];

    // ── Confianza (item 8, mockup 03) — foto real a sangre + fila de sellos
    // verificables (Google real vía ReviewAggregator, nunca un "5000+" suelto).
    $aboutOverallStats = app(\App\Services\ReviewAggregator::class)->overallStats($locale);
@endphp

@section('content')
<div class="lat-page">

    {{-- ============================================================
         HERO INTERNO — texto + collage de 6 fotos reales (mockup
         02-nosotros-parte1). Antes esta sección era una foto de fondo a
         sangre (`Rectangle 19215.jpg`, marca de agua de cuscoperu.com — ver
         ESTADO.md §6, foto de otro que no puede publicarse). Fondo oscuro
         sólido (sin foto) + el collage de la derecha, con fotos reales del
         cliente ya verificadas contra el ancho renderizado.
         ============================================================ --}}
    <section class="lat-about-hero">
        <div class="lat-wrap lat-about-hero__grid">
            <div class="lat-about-hero__text">
                <nav class="lat-about-hero__crumb" aria-label="Breadcrumb">
                    <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('ui.home') }}</a> &middot; <b>{{ __('nav.about') }}</b>
                </nav>

                {{-- Badge de marca (mockup, patrón de Lima View — copy propio de
                     Lima América Tours). Estático, no editable, igual que el logo
                     del header: es identidad de marca, no copy de campaña. --}}
                <span class="lat-hero-badge">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    {{ $L('Lima América Tours', 'Lima América Tours', 'Lima América Tours') }}
                </span>

                <span class="lat-eyebrow lat-eyebrow--on-dark">{{ $bl('hero_eyebrow', 'Nuestra historia', 'Our story', 'Nossa história') }}</span>
                <h1>{{ $bl('hero_title', 'Más de 10 años mostrando lo mejor del Perú', 'More than 10 years showcasing the best of Peru', 'Mais de 10 anos mostrando o melhor do Peru') }}</h1>
                <p class="lat-about-hero__sub">
                    {{ $bl('hero_lead',
                        'En Lima América Tours compartimos nuestra pasión por el Perú a través de experiencias auténticas, memorables y llenas de cultura. No eres un turista, eres nuestro invitado.',
                        'At Lima América Tours we share our passion for Peru through authentic, memorable experiences full of culture. You are not a tourist, you are our guest.',
                        'Na Lima América Tours compartilhamos nossa paixão pelo Peru por meio de experiências autênticas, memoráveis e cheias de cultura. Você não é um turista, é nosso convidado.'
                    ) }}
                </p>

                {{-- 4 micro-features 2×2 (mockup). Repeater con fallback,
                     $heroFeatures resuelto arriba. --}}
                <div class="lat-hero-features">
                    @foreach ($heroFeatures as $feat)
                        <div class="lat-hero-features__item">
                            <span class="lat-hero-features__ic">{!! \App\Support\HeroIcons::svg($feat['icon']) !!}</span>
                            <div>
                                <b>{{ $feat['title'] }}</b>
                                <span>{{ $feat['sub'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="lat-about-hero__actions">
                    <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-btn lat-btn--red">{{ $L('Explorar Tours', 'Explore Tours', 'Explorar Tours') }}</a>
                    @if ($aboutHeroVideoUrl !== null)
                        <button type="button" class="lat-btn lat-btn--outline-white" id="aboutHeroVideoBtn"
                                aria-haspopup="dialog" aria-controls="aboutHeroVideoModal">
                            <span class="lat-hero__play-ic" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                            {{ $aboutHeroVideoLabel }}
                        </button>
                    @endif
                </div>

                {{-- Tira de avatares + rating — SOLO si hay testimonios con foto
                     real cargada (hoy 0 de 13: la sección queda oculta por dato,
                     no por comentario en el HTML). --}}
                @if ($testimonialsWithAvatar->isNotEmpty())
                    <div class="lat-avatar-strip">
                        <span class="lat-avatar-strip__stack">
                            @foreach ($testimonialsWithAvatar as $t)
                                <img src="{{ $t->avatar }}" alt="" loading="lazy" width="36" height="36">
                            @endforeach
                        </span>
                        <span class="lat-avatar-strip__text">
                            {{-- El mockup dice "Miles de viajeros". No hay tal cifra en
                                 ninguna tabla. Hoy este bloque no se pinta (0 de 13
                                 testimonios tienen avatar), pero en cuanto el cliente
                                 suba UNA foto se publicaría el "miles": un default que
                                 espera para mentir sigue siendo un defecto. Va el
                                 agregado real, que es el mismo que ya usa la barra de
                                 stats de abajo. --}}
                            {{ $aboutOverallStats['count'] > 0
                                ? $L(
                                    $aboutOverallStats['rating'].' de valoración en '.$aboutOverallStats['count'].' opiniones de viajeros',
                                    $aboutOverallStats['rating'].' rating from '.$aboutOverallStats['count'].' traveler reviews',
                                    $aboutOverallStats['rating'].' de avaliação em '.$aboutOverallStats['count'].' opiniões de viajantes'
                                  )
                                : $L('Viajeros que ya vivieron la experiencia Lima América', 'Travelers who already lived the Lima América experience', 'Viajantes que já viveram a experiência Lima América') }}
                            <span class="lat-stars"><span class="lat-stars__s">@for ($i = 0; $i < 5; $i++)<svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>@endfor</span></span>
                        </span>
                    </div>
                @endif
            </div>

            {{-- Collage de 4 fotos (mockup: 1 grande + 3 en fila) — de las 6 ya
                 curadas. La foto grande (slot #3, `caption-1.jpg`, 700×500) es
                 la ÚNICA de las 6 con ancho intrínseco suficiente para el
                 recuadro ancho de arriba: medido en el navegador a 1440, el
                 slot #0 (467×622, retrato) rendía a 665px de ancho — se veía
                 borroso (`__images()`, ratio 0.7). Las otras 3 (622-660px de
                 ancho) sobran para el recuadro angosto de abajo. --}}
            <div class="lat-hero-collage" aria-label="{{ $L('Fotos de nuestros viajes', 'Photos from our trips', 'Fotos das nossas viagens') }}">
                <div class="lat-hero-collage__main">
                    <img src="{{ $heroCollageUrls[3]['url'] }}" alt="{{ $heroCollageUrls[3]['alt'] }}" loading="eager" width="700" height="500">
                </div>
                <div class="lat-hero-collage__row">
                    @foreach ($heroCollageUrls->only([0, 1, 2]) as $photo)
                        <div class="lat-hero-collage__item">
                            <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" loading="lazy" width="260" height="260">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Modal "Ver video" del hero de Nosotros — mismo mecanismo que Home
         (home.blade.php:878-890, 1394-1456), id propio para no chocar con
         #heroVideoModal si ambas páginas comparten alguna vez el mismo DOM
         (no es el caso hoy, pero un id fijo duplicado es un bug esperando
         pasar). Solo existe en el DOM si hay URL válida cargada. --}}
    @if ($aboutHeroVideoUrl !== null)
        <div class="lat-video-modal" id="aboutHeroVideoModal" role="dialog" aria-modal="true"
             aria-label="{{ $aboutHeroVideoLabel }}" hidden>
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

    {{-- ── Barra de stats compacta encimada al borde del hero (mockup 02) —
         reutiliza el MISMO $aboutStatsVisible que la banda "Miles de
         viajeros" de más abajo (misma fuente real, HomeStatsResolver::
         resolveAboutBand): nunca dos cifras distintas para el mismo dato,
         solo dos vistas distintas de la misma resolución. ── --}}
    @if ($aboutStatsBand['enabled'] && $aboutStatsVisible->isNotEmpty())
        <div class="lat-wrap lat-about-hero__stats-wrap">
            <div class="lat-hero-stats lat-hero-stats--dark" style="--lat-stats-n:{{ $aboutStatsVisible->count() }}">
                @foreach ($aboutStatsVisible as $slot)
                    <div class="lat-htc">
                        <span class="lat-htc__ic">{!! \App\Support\HeroIcons::svg($slot['icon']) !!}</span>
                        <span class="lat-hstat__value">{{ $slot['value'] }}</span>
                        <span class="lat-htc__label">{{ $slot['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ============================================================
         FRANJA ROJA DE GARANTÍAS (mockup) — reskin de .lat-guarantee/.lat-gt
         (mismo componente de Home y tours/show.blade.php), fondo rojo sólido
         vía el modificador .lat-guarantee--red. "Atención 24/7" → horario
         real (Setting::contactHours, ya calculado en $contactHours arriba);
         "Reserva 100% Segura" bifurca su copy con OnlinePayment::available(),
         igual que la ficha de tour (docs/rebrand/LOTE-MOCKUPS-AGO-2026.md,
         tabla "Lo que NO se publica"). "Mejor Precio Garantizado" se
         conserva: ya está publicado hoy en Home y en la ficha de tour, no es
         una promesa nueva de esta página.
         ============================================================ --}}
    <div class="lat-guarantee lat-guarantee--red">
        <div class="lat-wrap lat-guarantee__grid">
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg></div>
                <div>
                    @if ($onlinePaymentAvailable)
                        <b>{{ $bl('guarantee_secure_title', 'Reserva 100% Segura', '100% Secure Booking', 'Reserva 100% Segura') }}</b>
                        <span>{{ $bl('guarantee_secure_sub', 'Tus datos protegidos', 'Your data protected', 'Seus dados protegidos') }}</span>
                    @else
                        <b>{{ $bl('guarantee_secure_title_alt', 'Reserva sin cobro inmediato', 'Booking, no charge yet', 'Reserva sem cobrança imediata') }}</b>
                        <span>{{ $bl('guarantee_secure_sub_alt', 'Confirmamos por WhatsApp o correo', 'Confirmed by WhatsApp or email', 'Confirmamos por WhatsApp ou e-mail') }}</span>
                    @endif
                </div>
            </div>
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div>
                <div>
                    <b>{{ $bl('guarantee_flex_title', 'Cancelación Flexible', 'Flexible Cancellation', 'Cancelamento Flexível') }}</b>
                    <span>{{ $bl('guarantee_flex_sub', 'Sin cargos ocultos', 'No hidden fees', 'Sem taxas ocultas') }}</span>
                </div>
            </div>
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg></div>
                <div>
                    <b>{{ $bl('guarantee_price_title', 'Mejor Precio Garantizado', 'Best Price Guaranteed', 'Melhor Preço Garantido') }}</b>
                    <span>{{ $bl('guarantee_price_sub', 'Calidad al mejor precio', 'Quality at the best price', 'Qualidade ao melhor preço') }}</span>
                </div>
            </div>
            <div class="lat-gt">
                <div class="lat-gt__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg></div>
                <div>
                    <b>{{ $L('Atención al cliente', 'Customer support', 'Atendimento ao cliente') }}</b>
                    <span>{{ $contactHours ?: $bl('guarantee_support_fallback', 'Estamos para ayudarte', "We're here to help", 'Estamos aqui para ajudar') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         SPLIT — imagen + "10 años mostrando lo mejor del Perú"
         ============================================================ --}}
    <section class="lat-wrap" style="padding:64px 24px" aria-labelledby="about-split-title">
        <div class="lat-split">
            <div class="lat-split__media">
                {{-- Antes: `Rectangle 19216.jpg` — uno de los 12 placeholders de
                     205×123px reales estirados a ancho completo (ver brief, "problema
                     transversal del kit de imágenes"): se veía borroso sin importar el
                     tamaño de pantalla. Reemplazado por una foto real del cliente
                     (1279×853, de sobra sobre los ~640px renderizados de esta columna). --}}
                <img src="{{ $mediaUrl($b['img_split'] ?? null) ?? asset('storage/tours/2025-12-caption-9.jpg') }}"
                     alt="{{ $L('Guía de Lima América Tours explicando a un grupo de viajeros', 'Lima América Tours guide explaining to a group of travelers', 'Guia da Lima América Tours explicando a um grupo de viajantes') }}"
                     loading="lazy" width="1279" height="853">
                @if ($splitYearsBadge['show'])
                <div class="lat-split__badge">
                    <b>{{ $splitYearsBadge['value'] }}</b>
                    <span>{{ $L('años en el mercado turístico', 'years in the tourism market', 'anos no mercado turístico') }}</span>
                </div>
                @endif
            </div>

            <div class="lat-split__body">
                <span class="lat-eyebrow">{{ $bl('split_eyebrow', 'Agencia de turismo', 'Tourism agency', 'Agência de turismo') }}</span>
                <h2 id="about-split-title" class="lat-split-title">
                    {{ $bl('split_heading', '10 años mostrando lo mejor del Perú', '10 years showing the best of Peru', '10 anos mostrando o melhor do Peru') }}
                </h2>

                <p>
                    {{ $bl('split_body_1',
                        'Bienvenidos a Lima América Tours, una agencia de viajes fundada por emprendedores limeños apasionados por el turismo. Diseñamos experiencias auténticas de la mano de guías locales, con tours propios en Lima, Ica y Cusco, para compartir contigo las maravillas que ofrece el Perú.',
                        'Welcome to Lima América Tours, a travel agency founded by entrepreneurs from Lima passionate about tourism. We design authentic experiences with local guides, running our own tours in Lima, Ica and Cusco, to share with you the wonders that Peru offers.',
                        'Bem-vindo à Lima América Tours, uma agência de viagens fundada por empreendedores limenhos apaixonados por turismo. Criamos experiências autênticas com guias locais, com tours próprios em Lima, Ica e Cusco, para compartilhar com você as maravilhas que o Peru oferece.'
                    ) }}
                </p>
                <p>
                    {{ $bl('split_body_2',
                        'Queremos mostrar al mundo la diversidad de destinos que posee nuestro país, brindando siempre un servicio personalizado y de alta calidad: city tours, excursiones y experiencias gastronómicas que resaltan lo mejor de nuestra cultura.',
                        'We want to show the world the diversity of destinations our country has, always offering a personalized, high-quality service: city tours, excursions and gastronomic experiences that highlight the best of our culture.',
                        'Queremos mostrar ao mundo a diversidade de destinos que nosso país possui, oferecendo sempre um serviço personalizado e de alta qualidade: city tours, excursões e experiências gastronômicas que destacam o melhor de nossa cultura.'
                    ) }}
                </p>
                <p class="lat-split__highlight">
                    {{ $bl('split_highlight',
                        '¡No esperes más y vive una experiencia inolvidable en el Perú!',
                        "Don't wait any longer and live an unforgettable experience in Peru!",
                        'Não espere mais e viva uma experiência inesquecível no Peru!'
                    ) }}
                </p>

                <div class="lat-split__tags">
                    @foreach ($destinations as $dest)
                        <span class="lat-tag">
                            @if (($dest['icon'] ?? 'pin') === 'food')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 2v20M7 2a5 5 0 0 0-5 5v4h5M17 2v20M17 2a5 5 0 0 1 5 5v4a5 5 0 0 1-5 5"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            @endif
                            {{ $dest['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         EQUIPO — guías reales (Guide, mockup: "Guías locales, amigos y
         amantes de nuestra cultura"). Sección oculta si no hay ningún guía
         activo (guard, mismo criterio que el resto del sitio). SIN FOTO a
         propósito (ver GuideSeeder): la tarjeta se resuelve con iniciales en
         un marco tipográfico, nunca con una foto de stock ni la cara de otra
         persona. Instagram/WhatsApp solo si el guía tiene el handle cargado.
         ============================================================ --}}
    @if ($aboutGuides->isNotEmpty())
        <section class="lat-wrap" style="padding:20px 24px 70px" aria-labelledby="about-team-title">
            <div class="lat-sec-head" style="margin-bottom:32px">
                <span class="lat-eyebrow is-center">{{ $L('Conoce a nuestro equipo', 'Meet our team', 'Conheça nossa equipe') }}</span>
                <h2 id="about-team-title">{{ $L('Guías locales, amigos y amantes de nuestra cultura', 'Local guides, friends and lovers of our culture', 'Guias locais, amigos e amantes de nossa cultura') }}</h2>
            </div>

            <div class="lat-team-layout">
                <div class="lat-team-grid">
                    @foreach ($aboutGuides as $guide)
                        @php $gInitial = strtoupper(mb_substr(trim($guide->name), 0, 1)); @endphp
                        <article class="lat-team-card lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms">
                            @if ($guide->photo_url)
                                <img src="{{ $guide->photo_url }}" alt="{{ $guide->name }}" class="lat-team-card__photo" loading="lazy" width="200" height="200">
                            @else
                                {{-- Sin retrato confirmado (docs/rebrand/ESTADO.md §4): marco
                                     tipográfico con inicial, nunca una cara que no es la suya. --}}
                                <span class="lat-team-card__initial" aria-hidden="true">{{ $gInitial }}</span>
                            @endif
                            <b>{{ $guide->name }}</b>
                            <span class="lat-team-card__role">{{ $guide->role }}</span>
                            @if ($guide->bio)
                                <p>{{ $guide->bio }}</p>
                            @endif
                            @if ($guide->highlight)
                                <p class="lat-team-card__highlight">{{ $guide->highlight }}</p>
                            @endif
                            @if ($guide->instagram_url || $guide->whatsapp_url)
                                <div class="lat-team-card__social">
                                    @if ($guide->instagram_url)
                                        <a href="{{ $guide->instagram_url }}" target="_blank" rel="noopener" aria-label="Instagram — {{ $guide->name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1" fill="currentColor" stroke="none"/></svg>
                                        </a>
                                    @endif
                                    @if ($guide->whatsapp_url)
                                        <a href="{{ $guide->whatsapp_url }}" target="_blank" rel="noopener" aria-label="WhatsApp — {{ $guide->name }}">
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24z"/></svg>
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>

                <aside class="lat-team-why">
                    <h3>{{ $L('¿Por qué viajar con Lima América Tours?', 'Why travel with Lima América Tours?', 'Por que viajar com a Lima América Tours?') }}</h3>
                    <ul>
                        @foreach ($whyTravelChecks as $check)
                            <li>{{ $check }}</li>
                        @endforeach
                        {{-- El horario NUNCA se carga aquí: única fuente en
                             Configuración → Contacto (Setting::contactHours). --}}
                        @if ($contactHours)
                            <li>{{ $contactHours }}</li>
                        @endif
                    </ul>
                    <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-btn lat-btn--red" style="width:100%">{{ $L('Reservar Ahora', 'Book Now', 'Reservar Agora') }}</a>
                </aside>
            </div>
        </section>
    @endif

    {{-- ============================================================
         TESTIMONIOS — franja OSCURA (mockup 03, item 4), reales
         ($testimonials → $aboutTestimonials, Testimonial::active(), 4 más
         recientes, ya resueltos por la ruta /nosotros). Oculta si no hay
         ninguno activo.
         ============================================================ --}}
    @if ($aboutTestimonials->isNotEmpty())
        <section class="lat-about-reviews" aria-labelledby="about-reviews-title">
            <div class="lat-wrap">
                <div class="lat-sec-head-row" style="justify-content:center; text-align:center; flex-direction:column; align-items:center">
                    <span class="lat-eyebrow is-center">{{ $L('Lo que dicen nuestros viajeros', 'What our travelers say', 'O que dizem nossos viajantes') }}</span>
                    <h2 id="about-reviews-title" style="color:#fff">{{ $L('Experiencias que hablan por nosotros', 'Experiences that speak for themselves', 'Experiências que falam por nós') }}</h2>
                </div>

                <div class="lat-reviews-grid" style="margin-top:32px">
                    @foreach ($aboutTestimonials as $t)
                        @php
                            $tInitial = strtoupper(mb_substr(trim($t->name), 0, 1));
                            $tSrc = app(\App\Services\ReviewAggregator::class)->normalizeSource($t->source);
                            $tSrcLabel = ['google' => 'Google', 'tripadvisor' => 'Tripadvisor', 'trivago' => 'Trivago', 'web' => $L('Nuestra web', 'Our website', 'Nosso site')][$tSrc];
                        @endphp
                        <article class="lat-rcard lat-rcard--dark lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms">
                            <div class="lat-rcard__head">
                                @if (!empty($t->avatar))
                                    <img src="{{ $t->avatar }}" alt="" class="lat-rcard__avatar" loading="lazy" width="44" height="44">
                                @else
                                    <span class="lat-rcard__avatar lat-rcard__avatar--fallback" aria-hidden="true">{{ $tInitial }}</span>
                                @endif
                                <div>
                                    <b>{{ $t->name }}</b>
                                    <span>{{ $t->country ?: $t->displayDate()?->translatedFormat('M Y') }}</span>
                                </div>
                            </div>
                            <span class="lat-stars"><span class="lat-stars__s">@for ($i = 0; $i < 5; $i++)<svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>@endfor</span></span>
                            <p class="lat-rcard__quote clamp-3">{{ $t->quote }}</p>
                            <span class="lat-rcard__src lat-rcard__src--dark">{{ $tSrcLabel }}</span>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================================
         DESTINOS — fondo claro (mockup 03, item 5), reales
         ($destinationRegions → $aboutDestinations, guard automático).
         Mismas tarjetas que el home (.lat-dest-card), reutilizadas.
         ============================================================ --}}
    @if ($aboutDestinations->isNotEmpty())
        <section class="lat-wrap" style="padding:70px 24px" aria-labelledby="about-dest-title">
            <div class="lat-sec-head lat-sec-head--home">
                <span class="lat-eyebrow is-center">{{ $L('Nuestros destinos', 'Our destinations', 'Nossos destinos') }}</span>
                <h2 id="about-dest-title">{{ $L('Explora los increíbles destinos del Perú', 'Explore the incredible destinations of Peru', 'Explore os incríveis destinos do Peru') }}</h2>
            </div>

            <div class="lat-dest-cards" style="--lat-dest-n:{{ min($aboutDestinations->count(), 3) }}">
                @foreach ($aboutDestinations as $region)
                    <a href="{{ route('tours.category', ['locale' => $locale, 'categoria' => $region->slug]) }}" class="lat-dest-card lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms">
                        <img src="{{ $region->image_url ?? \App\Support\ResponsiveImage::defaultPhotoUrl(640) }}" alt="{{ $region->name }}" loading="lazy" width="360" height="440">
                        <div class="lat-dest-card__body">
                            <b>{{ $region->name }}</b>
                            <span>{{ $region->description ? \Illuminate\Support\Str::limit($region->description, 32) : ($region->published_tours_count . ' ' . ($region->published_tours_count === 1 ? $L('tour', 'tour', 'tour') : $L('tours', 'tours', 'tours'))) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="lat-dest-cta">
                <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-btn lat-btn--red">{{ $L('Ver todos los destinos', 'View all destinations', 'Ver todos os destinos') }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </div>
        </section>
    @endif

    {{-- ============================================================
         TIMELINE — "Un camino lleno de pasión y compromiso" (mockup 03,
         item 6). El mockup inventa 6 hitos 2014→2024; el guard es de DATO
         ($timelineItems, definido arriba en el bloque de variables): sin hitos reales cargados por el
         cliente, la sección entera no se pinta. Nada de un comentario HTML
         disfrazando el apagado.
         ============================================================ --}}
    @if (count($timelineItems) > 0)
        <section class="lat-timeline" aria-labelledby="about-timeline-title">
            <div class="lat-wrap">
                <div class="lat-sec-head">
                    <span class="lat-eyebrow is-center">{{ $L('Nuestra historia', 'Our story', 'Nossa história') }}</span>
                    <h2 id="about-timeline-title" style="color:#fff">{{ $L('Un camino lleno de pasión y compromiso', 'A path full of passion and commitment', 'Um caminho cheio de paixão e comprometimento') }}</h2>
                </div>
                <div class="lat-timeline__row">
                    @foreach ($timelineItems as $hito)
                        <div class="lat-timeline__item lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms">
                            <span class="lat-timeline__dot" aria-hidden="true"></span>
                            <b>{{ $hito['year'] ?? '' }}</b>
                            <p>{{ $hito['text_' . $locale] ?? $hito['text_es'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================================
         MISIÓN, VISIÓN Y VALORES — sobre foto oscura, acento DORADO
         (mockup 03-nosotros-parte2). Antes: tarjetas claras sobre $lat-paper
         (mismatch de tema — el mockup la pinta sobre foto de noche con
         tarjetas "glass" y borde dorado). Los 8 valores reales del CMS no
         cambian ($mvvItems ya los trae); solo cambia la piel visual.
         Contraste del dorado sobre esta sección medido con Playwright
         (muestreo de píxeles reales, no la fórmula a ciegas) — ver informe.
         ============================================================ --}}
    <section class="lat-mvv-photo" aria-labelledby="about-mvv-title"
              style="background-image:url('{{ asset('storage/tours/Machu_Picchu_Peru_-_Laslovarga_262-scaled.jpg') }}')">
        <div class="lat-mvv-photo__scrim" aria-hidden="true"></div>
        <div class="lat-wrap lat-mvv-photo__inner">
            <div class="lat-sec-head">
                <span class="lat-eyebrow-lines">{{ $L('Nuestro propósito', 'Our purpose', 'Nosso propósito') }}</span>
                <h2 id="about-mvv-title" style="color:#fff">{{ $L('Misión, visión y valores', 'Mission, vision and values', 'Missão, visão e valores') }}</h2>
                <p style="color:rgba(255,255,255,.82)">{{ $L('Coherencia, propósito y pasión por lo que hacemos.', 'Coherence, purpose and passion for what we do.', 'Coerência, propósito e paixão pelo que fazemos.') }}</p>
            </div>

            <div class="lat-mvv-gold">
                @foreach ($mvvItems as $item)
                    <div class="lat-mvv-gold-card lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms">
                        <div class="lat-mvv-gold-card__ic">
                            @switch($item['icon'] ?? 'target')
                                @case('eye')
                                    {{-- Montaña con bandera (Visión) --}}
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m4 20 6-11 4 7 2-3 4 7H4z"/><path d="M14 4v6"/><path d="M14 4l5 2-5 2"/></svg>
                                    @break
                                @case('heart')
                                    {{-- Corazón en mano (Valores) --}}
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8.6c-1-1.6-2.7-2.6-4.5-2.1A3.6 3.6 0 0 0 5 10c0 3 3 5.4 7 8.6 4-3.2 7-5.6 7-8.6a3.6 3.6 0 0 0-2.5-3.5c-1.8-.5-3.5.5-4.5 2.1z"/><path d="M3 20c1.2-1.6 3-2 5-1"/></svg>
                                    @break
                                @default
                                    {{-- Brújula (Misión) --}}
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m14.5 9.5-2 5-3 1 2-5 3-1z"/></svg>
                            @endswitch
                        </div>

                        <h3>{{ $item['title_' . $locale] ?? $item['title_es'] ?? '' }}</h3>

                        @if (! empty($item['tags']))
                            @php $tagList = $item['tags_' . $locale] ?? $item['tags'] ?? []; @endphp
                            <div class="lat-mvv-gold-card__tags">
                                @foreach ($tagList as $tag)
                                    <span class="lat-mvv-gold-tag">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @else
                            <p>{{ $item['desc_' . $locale] ?? $item['desc_es'] ?? '' }}</p>
                        @endif

                        <span class="lat-mvv-gold-card__rule" aria-hidden="true"></span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================
         BANDA DE ESTADÍSTICAS — "Miles de viajeros ya confiaron en nosotros"
         (mockup 03, item 8). Regla del proyecto: sin dato real la tarjeta se
         oculta, y si no queda ninguna, la franja entera desaparece (nunca un
         "0" de relleno). Se agrega la fila de confianza (Google real vía
         ReviewAggregator — la MISMA cuenta del hero y de /resenas, nunca un
         "5,000 viajeros" suelto — más TripAdvisor y "empresa registrada") y
         una foto real a sangre a la derecha, tal como pide el mockup.
         ============================================================ --}}
    @if ($aboutStatsBand['enabled'] && $aboutStatsVisible->isNotEmpty())
    <section class="lat-stats-band" aria-labelledby="about-stats-title">
        <div class="lat-wrap lat-stats-band__grid lat-stats-band__grid--trust">
            <div class="lat-stats-band__col">
                <div class="lat-stats-band__intro">
                    <span class="lat-eyebrow">{{ $L('Confianza que se construye', 'Trust that builds', 'Confiança que se constrói') }}</span>
                    <h2 id="about-stats-title">
                        {{ $L('Miles de viajeros ya confiaron en nosotros', 'Thousands of travelers have already trusted us', 'Milhares de viajantes já confiaram em nós') }}
                    </h2>
                    <p>
                        {{ $L(
                            'Cada tour es una historia. Estos números reflejan una década acompañando a viajeros de todo el mundo por el Perú.',
                            'Every tour is a story. These numbers reflect a decade accompanying travelers from all over the world across Peru.',
                            'Cada tour é uma história. Esses números refletem uma década acompanhando viajantes do mundo todo pelo Peru.'
                        ) }}
                    </p>
                    @if ($waNumber)
                    <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener noreferrer" class="lat-btn lat-btn--wa">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" width="18" height="18"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24z"/></svg>
                        {{ $L('Habla con nosotros', 'Talk to us', 'Fale conosco') }}
                    </a>
                    @endif
                </div>

                <div class="lat-stat-tiles" data-stats-tiles>
                    @foreach ($aboutStatsVisible as $slot)
                        @php
                            // data-count-to necesita un entero puro para la animación JS
                            // de más abajo. Antes se usaba $slot['count'] como target de la
                            // animación, pero ese campo NO siempre es "el número que se ve
                            // en pantalla": en rating_real/rating_external, 'count' es el
                            // respaldo (cantidad de reseñas), no el rating mostrado. Con
                            // rating_real ahora en esta banda (fix 2026-08-12, slot
                            // "Valoración de viajeros" = "5.0"), animar hacia 'count' hacía
                            // que la tarjeta terminara mostrando "18" en vez de "5.0".
                            // Regla: solo se anima cuando el VALOR mostrado es un entero
                            // puro (con "+" opcional, ej. "24", "10+"); cualquier otro
                            // formato (decimales, etc.) se queda con el valor estático que
                            // ya llegó resuelto del servidor — la animación es una mejora
                            // progresiva, nunca la fuente del número.
                            $slotValueStr = (string) $slot['value'];
                            $slotIsPlainInteger = (bool) preg_match('/^\d+\+?$/', $slotValueStr);
                            $slotCount = $slotIsPlainInteger ? (int) preg_replace('/\D+/', '', $slotValueStr) : null;
                            $slotSuffix = $slotIsPlainInteger ? preg_replace('/[\d,\.\s]+/', '', $slotValueStr) : null;
                        @endphp
                        <div class="lat-stat-tile">
                            @if (! is_null($slotCount))
                                <strong data-count-to="{{ $slotCount }}" data-suffix="{{ $slotSuffix }}">{{ $slot['value'] }}</strong>
                            @else
                                <strong>{{ $slot['value'] }}</strong>
                            @endif
                            <span>{{ $slot['label'] }}</span>
                        </div>
                    @endforeach
                </div>

                @if (! is_null($aboutOverallStats['rating']))
                    <div class="lat-trust-row">
                        <span class="lat-trust-row__item">
                            <b>{{ number_format($aboutOverallStats['rating'], 1) }}</b>
                            <span class="lat-stars"><span class="lat-stars__s">@for ($i = 0; $i < 5; $i++)<svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>@endfor</span></span>
                            {{ $bl('trust_label_google', 'Reseñas de Google', 'Google Reviews', 'Avaliações no Google') }}
                        </span>
                        <span class="lat-trust-row__item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="8.5" cy="12" r="2.2"/><circle cx="15.5" cy="12" r="2.2"/></svg>
                            {{ $bl('trust_label_tripadvisor', 'Presencia en Tripadvisor', 'Present on Tripadvisor', 'Presença no Tripadvisor') }}
                        </span>
                        @if ($companyRucForBadge)
                        <span class="lat-trust-row__item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 3 6v6c0 5 4 8.5 9 10 5-1.5 9-5 9-10V6l-9-4z"/></svg>
                            {{ $bl('trust_label_company', 'Empresa registrada', 'Registered company', 'Empresa registrada') }}
                        </span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="lat-stats-band__photo">
                <img src="{{ asset('storage/tours/2025-12-caption-8.jpg') }}"
                     alt="{{ $L('Familia de viajeros disfrutando un tour de Lima América Tours', 'Family of travelers enjoying a Lima América Tours tour', 'Família de viajantes aproveitando um tour da Lima América Tours') }}"
                     loading="lazy" width="800" height="1000">
            </div>
        </div>
    </section>
    @endif

    {{-- ============================================================
         CTA FINAL — "Empecemos a planear"

         Fondo fotográfico desde 2026-08-14 (pedido del jefe): antes era un
         degradado ink plano. La foto es la Plaza Mayor de Lima al atardecer,
         **traída del WordPress de producción** (wp-content/uploads/2024/02/,
         1600×900), o sea material del propio cliente y no de un banco.

         Por qué esta y no otra: se revisaron las nueve imágenes apaisadas
         (ratio ≥ 1.7) que tiene la biblioteca de producción, MIRÁNDOLAS una por
         una. La de Huacachina al atardecer era la más parecida a la referencia
         que aprobó el cliente, pero trae una marca de agua de "CuscoPeru.com"
         incrustada: publicarla sería poner el logo de un tercero en el sitio.
         La panorámica de Miraflores tiene el cielo demasiado claro para texto
         blanco. Esta es apaisada, cálida, sin marca de agua, y es Lima — que es
         la marca. Tampoco repite ninguna de las otras dos fotos grandes del
         sitio (Machu Picchu en el hero, Barranco en el cierre de la home).

         Se sirve por ResponsiveImage (WebP a 1600) igual que el resto de fondos
         del sitio: el JPG original pesa 448 KB.
         ============================================================ --}}
    @php
        $aboutCtaBg = \App\Support\ResponsiveImage::make(
            public_path('assets/banners/plaza-mayor-lima-atardecer.jpg'),
            asset('assets/banners/plaza-mayor-lima-atardecer.jpg'),
            [1600],
            '100vw'
        )['src'];
    @endphp
    <section class="lat-cta-final" aria-labelledby="about-cta-title"
             style="background-image:linear-gradient(rgba(14,11,10,.78), rgba(14,11,10,.9)), url('{{ $aboutCtaBg }}');">
        <div class="lat-wrap">
            <span class="lat-eyebrow is-center" style="color:#ffd7d9">
                {{ $L('Empecemos a planear', "Let's start planning", 'Vamos começar a planejar') }}
            </span>
            <h2 id="about-cta-title">
                {{ $L('Déjanos ser tu guía en tu próxima aventura', 'Let us be your guide on your next adventure', 'Deixe-nos ser seu guia na sua próxima aventura') }}
            </h2>
            <p>
                {{ $L('Viajar es descubrir, aprender y vivir. Cuéntanos a dónde quieres ir.', 'To travel is to discover, learn and live. Tell us where you want to go.', 'Viajar é descobrir, aprender e viver. Conte-nos para onde você quer ir.') }}
            </p>
            <div class="lat-cta-final__actions">
                @if ($waNumber)
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener noreferrer" class="lat-btn lat-btn--wa">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" width="18" height="18"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24z"/></svg>
                    {{ $L('Escríbenos por WhatsApp', 'Message us on WhatsApp', 'Escreva-nos pelo WhatsApp') }}
                </a>
                @endif
                <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-btn lat-btn--white">
                    {{ $L('Ver tours', 'View tours', 'Ver tours') }}
                </a>
            </div>

            {{-- 4 features del CTA (mockup, item 9). "Atención 24/7" → horario
                 real; "Pago seguro / Diversos métodos de pago" fuera (tabla "Lo
                 que NO se publica" del brief: no hay pasarela activa hoy). --}}
            <div class="lat-cta-final__features">
                <div class="lat-cta-final__feature lat-reveal" style="transition-delay:0ms">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <div>
                        <b>{{ $contactHours ?: $bl('cta_feat1_fallback', 'Escríbenos cuando quieras', 'Message us anytime', 'Escreva quando quiser') }}</b>
                        <span>{{ $bl('cta_feat1_label', 'Horario de atención', 'Support hours', 'Horário de atendimento') }}</span>
                    </div>
                </div>
                <div class="lat-cta-final__feature lat-reveal" style="transition-delay:70ms">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.7"/></svg>
                    <div>
                        <b>{{ $bl('cta_feat2_title', 'Viajes 100% personalizados', '100% personalized trips', 'Viagens 100% personalizadas') }}</b>
                        <span>{{ $bl('cta_feat2_desc', 'Hechos a tu medida', 'Made just for you', 'Feitas sob medida') }}</span>
                    </div>
                </div>
                <div class="lat-cta-final__feature lat-reveal" style="transition-delay:140ms">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <div>
                        <b>{{ $bl('cta_feat3_title', 'Seguridad y confianza', 'Security and trust', 'Segurança e confiança') }}</b>
                        <span>{{ $bl('cta_feat3_desc', 'Tu tranquilidad es lo primero', 'Your peace of mind comes first', 'Sua tranquilidade em primeiro lugar') }}</span>
                    </div>
                </div>
                <div class="lat-cta-final__feature lat-reveal" style="transition-delay:210ms">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <div>
                        <b>{{ $bl('cta_feat4_title', 'Cancelación flexible', 'Flexible cancellation', 'Cancelamento flexível') }}</b>
                        <span>{{ $bl('cta_feat4_desc', 'Cambia tus planes sin complicaciones', 'Change your plans hassle-free', 'Mude seus planos sem complicações') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
(function () {
    var tiles = document.querySelector('[data-stats-tiles]');
    if (!tiles) return;

    var counters = Array.prototype.slice.call(tiles.querySelectorAll('[data-count-to]'));
    var animated = false;

    function animate() {
        if (animated) return;
        animated = true;

        counters.forEach(function (el) {
            var target = parseInt(el.getAttribute('data-count-to'), 10) || 0;
            var suffix = el.getAttribute('data-suffix') || '';
            var duration = 1200;
            var start = null;

            // El HTML ya trae el valor real resuelto en servidor (regla del
            // proyecto: nunca un "0" de relleno). Esto es una mejora
            // progresiva SOLO — el reset a "0" ocurre aquí, justo antes de
            // animar, nunca en el marcado inicial. Si este script no llega a
            // correr (o el IntersectionObserver nunca cruza el umbral), el
            // usuario sigue viendo el número correcto en vez de un cero fijo.
            el.textContent = '0' + suffix;

            function step(ts) {
                if (!start) start = ts;
                var progress = Math.min((ts - start) / duration, 1);
                var value = Math.floor(progress * target);
                el.textContent = value + suffix;
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = target + suffix;
                }
            }
            requestAnimationFrame(step);
        });
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animate();
                    observer.disconnect();
                }
            });
        }, { threshold: 0.4 });
        observer.observe(tiles);
    } else {
        animate();
    }
})();
</script>
@endpush

@if ($aboutHeroVideoUrl !== null)
@push('scripts')
<script>
(function () {
    // Mismo mecanismo que el modal "Ver video" de Home
    // (home.blade.php:1394-1456), con ids propios (#aboutHeroVideoBtn /
    // #aboutHeroVideoModal) para no colisionar con los de Home.
    var btn = document.getElementById('aboutHeroVideoBtn');
    var modal = document.getElementById('aboutHeroVideoModal');
    if (!btn || !modal) return;

    var frame = modal.querySelector('[data-video-frame]');
    var closers = modal.querySelectorAll('[data-video-close]');
    var videoUrl = @json($aboutHeroVideoUrl);
    var videoTitle = @json($aboutHeroVideoLabel);
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
        frame.innerHTML = '';
        document.removeEventListener('keydown', onKeydown);
        if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    }

    btn.addEventListener('click', open);
    closers.forEach(function (el) { el.addEventListener('click', close); });
})();
</script>
@endpush
@endif
