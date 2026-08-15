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

    // ── Slider administrable del hero (mockup 01-home.jpeg: 4 puntos +
    // flechas ←→, ver docs/rebrand/ESTADO.md "Faltante de alcance, no
    // defecto"). Capa de datos y CMS únicamente — el markup/JS/CSS del
    // slider en sí lo hace el maquetador después, consumiendo $heroSlides.
    //
    // Contrato: colección ORDENADA, nunca vacía. Sin diapositivas activas
    // en `hero_slides`, cae en la MISMA imagen/alt de home_hero_image ya
    // resueltos arriba (single source of truth, ver HeroSlidesResolver).
    $heroSlides = app(\App\Services\HeroSlidesResolver::class)->resolve($locale, $heroImg, $heroImgAlt);

    // La primera diapositiva es el LCP del sitio: se precarga y es la única
    // con fetchpriority alto (regla 2 del contrato). $heroImg/$heroImgAlt
    // pasan a apuntar a ella para que el <img> de fondo y el <link
    // rel="preload"> de más abajo —que ya usaban estas dos variables antes
    // de que existiera el slider— sigan funcionando sin más cambios. Sin
    // diapositivas cargadas esto es un no-op: $heroSlides->first() es
    // exactamente el mismo array que ya se armó arriba.
    $heroFirstSlide = $heroSlides->first();
    $heroImg = [
        'src' => $heroFirstSlide['url'],
        'srcset' => $heroFirstSlide['srcset'],
        'sizes' => $heroFirstSlide['sizes'],
        'width' => $heroFirstSlide['width'],
        'height' => $heroFirstSlide['height'],
    ];
    $heroImgAlt = $heroFirstSlide['alt'];

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

    // El default de este subtítulo AFIRMABA "10 años mostrando lo mejor del Perú"
    // mientras el badge de años de al lado estaba oculto por no tener
    // `company_started_year`: el sitio negaba en un lugar lo que afirmaba en el otro,
    // a 40 píxeles de distancia. Y era un default en código, o sea que se publicaba
    // precisamente cuando nadie había cargado nada — el mismo patrón del fallback que
    // publicó la foto de otro cliente.
    //
    // Ahora la cifra sale del MISMO resolver que el badge y la barra de stats: si el
    // dato existe, aparece en los dos lados a la vez y siempre coincide; si no existe,
    // el texto habla del Perú sin inventar una antigüedad. El Setting
    // `home_hero_tagline_{locale}` sigue mandando por encima de todo esto.
    $heroYearsForTagline = app(\App\Services\HomeStatsResolver::class)->yearsActiveBadge();
    $heroTaglineDefault = $heroYearsForTagline['show']
        ? $L(
            "{$heroYearsForTagline['value']} años mostrando\nlo mejor del Perú",
            "{$heroYearsForTagline['value']} years showcasing\nthe best of Peru",
            "{$heroYearsForTagline['value']} anos mostrando\no melhor do Peru"
        )
        : $L("Mostrando\nlo mejor del Perú", "Showcasing\nthe best of Peru", "Mostrando\no melhor do Peru");
    $heroTagline = \App\Models\Setting::get('home_hero_tagline_' . $locale) ?: $heroTaglineDefault;

    // El badge suelto "10+ años" (mockup de julio, cuando la card de stats
    // todavía no existía) se RETIRÓ el 2026-08-13: medido con
    // company_started_year cargado, caía encima de la card roja de stats
    // (badge x:1201-1354/y:384-469 contra card x:1101-1401/y:295-537 o más
    // si crece — ver reporte del lote). La salida no era moverlo: el dato de
    // años YA es una fila más de esa card, porque HomeStatsResolver::resolve()
    // trae el slot 4 en 'years_active' por defecto (mismo Setting
    // company_started_year, mismo valor "X+" que exponía este badge) — con el
    // dato cargado ya aparecía DOS VECES en pantalla antes de este cambio. No
    // se tocó app/** para esto: $homeStats (más abajo) ya trae esa fila sola,
    // se oculta sola sin el dato, y se adapta de alto sola. Las claves de
    // Settings home_hero_years_label_* quedan sin leer aquí (no se borran del
    // panel: es dato del cliente, no huérfano de nadie).

    // ── Los 4 "trust chips" del hero (guía experto / tours seguros / atención
    // personalizada / mejor precio) quedan RETIRADOS del hero en el rediseño
    // 2026-08 (mockup foto a sangre): esa tarjeta la reemplaza la barra de
    // STATS (4.9 valoración / 50K+ viajeros / 100% cancelación / 10+ años),
    // ver $homeStats más abajo. No se borra el código: Configuración → Home
    // sigue teniendo esos 4 campos de texto + selector de ícono guardados en
    // Settings, así que si algún día se quiere reactivar esta tarjeta (p.ej.
    // en otra pantalla) el dato del cliente sigue ahí y no hay que
    // pedírselo de nuevo. Ver reporte de este lote para más contexto.
    /*
    $heroTrustDefaults = [
        $L('Guías expertos locales', 'Local expert guides', 'Guias locais especializados'),
        $L('Tours 100% seguros', '100% safe tours', 'Tours 100% seguros'),
        $L('Atención personalizada', 'Personalized support', 'Atendimento personalizado'),
        $L('Mejor precio garantizado', 'Best price guaranteed', 'Melhor preço garantido'),
    ];
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
    */

    // ── CTA primario del hero "Reservar Ahora" — texto editable con default
    // en código; URL editable con fallback al catálogo completo. ──
    $heroPrimaryLabel = \App\Models\Setting::get('home_hero_cta_primary_' . $locale)
        ?: $L('Reservar Ahora', 'Book Now', 'Reservar Agora');
    $heroPrimaryUrlSetting = trim((string) (\App\Models\Setting::get('home_hero_cta_primary_url') ?: ''));
    $heroPrimaryHref = $heroPrimaryUrlSetting !== '' ? $heroPrimaryUrlSetting : route('tours.index', ['locale' => $locale]);

    // ── CTA secundario del hero "Ver Tours" (mockup 01-home: outline blanco,
    // junto al rojo "Reservar Ahora") — faltaba por completo, editable con
    // el mismo criterio que el primario. ──
    $heroSecondaryLabel = \App\Models\Setting::get('home_hero_cta_secondary_' . $locale)
        ?: $L('Ver Tours', 'View Tours', 'Ver Tours');
    $heroSecondaryUrlSetting = trim((string) (\App\Models\Setting::get('home_hero_cta_secondary_url') ?: ''));
    $heroSecondaryHref = $heroSecondaryUrlSetting !== '' ? $heroSecondaryUrlSetting : route('tours.index', ['locale' => $locale]);

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

    // Texto del botón "Ver video" — antes venía de __('ui.watch_video')
    // (lang file, no editable desde el panel); ahora es un Setting con el
    // mismo default, para que la clienta pueda cambiar el copy sin pedir
    // un deploy.
    $heroVideoLabel = \App\Models\Setting::get('home_hero_cta_video_' . $locale)
        ?: $L('Ver Video', 'Watch Video', 'Ver Vídeo');

    // ── Tours destacados: reales, ya calculados por HomeController ──
    $destacados = $featuredTours->take(4);

    // ── Ofertas especiales — modelo Offer real, ya calculado por
    // HomeController::fetchOffers() (activas, sin vencer, orden manual,
    // límite 3). Se sube aquí (antes vivía junto a su propia sección, más
    // abajo en el archivo) porque el rediseño 2026-08 las pinta como
    // tarjetas "PROMOCIÓN" DENTRO del hero (A3) y ya no en una sección
    // aparte — la sección "Ofertas especiales" que existía después de
    // "Explora por categoría" se retiró para no repetir las mismas 3
    // tarjetas dos veces en la misma página (ver reporte). ──
    $offers = $offers ?? collect();

    // ── Stats del hero — card roja (Fix 2, lote mockups agosto 2026;
    // antes era una barra clara horizontal fuera de lugar, ver
    // .lat-hero__stats-card en _lat-home.scss) — reemplaza la tarjeta de
    // trust chips del hero (ver nota arriba). Slots numéricos, editables por
    // Settings con default en código; ícono del catálogo cerrado
    // App\Support\HeroIcons (mismo criterio que el resto del hero). ──
    // 2026-08-02: el valor de cada slot ya NO se lee aquí desde Settings. Lo
    // resuelve App\Services\HomeStatsResolver (expuesto por HomeController como
    // $homeStatsResolved), porque cada slot puede venir de un texto libre o
    // calcularse de la base (promedio y nº de reseñas vía ReviewAggregator —
    // la misma mezcla que /resenas, para no publicar dos cifras distintas del
    // mismo dato—, tours publicados, años operando, o un agregado externo con
    // su enlace comprobable). Mientras esto se leyó de Settings, el selector
    // "fuente" del panel no cambiaba nada en pantalla.
    //
    // Contrato: ['enabled' => bool, 'slots' => [['show','value','label','icon',
    // 'count','url','source'], ...]]. `show=false` significa OCULTAR el slot:
    // un dato que no existe no se imprime como "0". `url` solo viene en el
    // agregado externo y convierte la cifra en enlace a la ficha real.
    $statsResolved = $homeStatsResolved ?? null;
    $homeStatsEnabled = $statsResolved['enabled'] ?? true;

    $homeStats = collect($statsResolved['slots'] ?? [])
        ->filter(fn (array $slot): bool => ($slot['show'] ?? false) && ($slot['value'] ?? '') !== '')
        ->map(fn (array $slot): array => [
            'value' => $slot['value'],
            'label' => $slot['label'],
            'icon'  => \App\Support\HeroIcons::svg($slot['icon'] ?? 'star'),
            'url'   => $slot['url'] ?? null,
        ])
        ->values();

    // Si ningún slot tiene dato que mostrar, la card entera sobra.
    if ($homeStats->isEmpty()) {
        $homeStatsEnabled = false;
    }

    // ── Newsletter oscuro con foto (A5), antes del footer. Reutiliza el
    // MISMO endpoint/campos que el newsletter del footer
    // (NewsletterController@subscribe + partials.recaptcha) — el del
    // footer se suprime SOLO en esta página (ver $__env->share más abajo
    // y footer.blade.php) para no dejar dos formularios del mismo canal
    // en la misma pantalla. ──
    $newsEnabledRaw = \App\Models\Setting::get('home_news_enabled');
    $homeNewsEnabled = $newsEnabledRaw === null ? true : filter_var($newsEnabledRaw, FILTER_VALIDATE_BOOLEAN);

    $newsImgSetting = \App\Models\Setting::get('home_news_image');
    if (is_array($newsImgSetting)) { $newsImgSetting = $newsImgSetting[0] ?? ''; }
    $newsImgSetting = trim((string) $newsImgSetting);
    // Igual que home_hero_image: disco `media`, directorio "home" — se
    // resuelve con ImagePath::homeImage() (NO ::url(), que es para el disco
    // `public`/`assets` — con el helper equivocado la URL sale rota).
    // Foto del bloque de cierre (CTA + newsletter comparten superficie desde
    // 2026-08-14). El fallback ya NO es ResponsiveImage::defaultPhotoUrl():
    // esa constante es la MISMA panorámica de Machu Picchu del hero, así que
    // sin foto cargada la home abría y cerraba con la misma imagen — se veía
    // como un error, no como una decisión. El fallback pasa a una foto
    // distinta del catálogo real: Barranco, 1920×1080 — panorámica, de Lima
    // (que es la marca) y con densidad suficiente para leerse bien detrás del
    // velo. Se eligió MIRÁNDOLAS: los otros candidatos anchos del catálogo son
    // fotos de grupo o de bodega, que como fondo a sangre no funcionan.
    // Guard: si el archivo no está en el servidor, vuelve a la de siempre
    // antes que quedar sin fondo.
    $newsFallbackRel = 'tours/2024-02-barranco-timeout.jpg';
    $newsFallbackPath = public_path('storage/'.$newsFallbackRel);

    if ($newsImgSetting !== '' && $newsImgSetting !== '[]' && $newsImgSetting !== '""') {
        $newsImgUrl = \App\Support\ImagePath::homeImage($newsImgSetting);
    } elseif (is_file($newsFallbackPath)) {
        $newsImgUrl = \App\Support\ResponsiveImage::make(
            $newsFallbackPath,
            asset('storage/'.$newsFallbackRel),
            [1600],
            '100vw'
        )['src'];
    } else {
        $newsImgUrl = \App\Support\ResponsiveImage::defaultPhotoUrl(1600);
    }

    $newsEyebrow = \App\Models\Setting::get('home_news_eyebrow_' . $locale)
        ?: $L('Viaja. Explora. Vive.', 'Travel. Explore. Live.', 'Viaje. Explore. Viva.');
    // Fix 1 (lote mockups agosto 2026): este H2 y el de la banda roja
    // .lat-home-cta (más abajo) traían el MISMO texto por defecto ("Tu
    // próxima aventura empieza aquí"), medido como dos <h2> consecutivos
    // idénticos en la página — se leía como un copy-paste sin terminar. La
    // banda roja mantiene esa frase (encaja con su CTA "Ver todos los
    // tours"); este bloque es el formulario de newsletter, así que su
    // titular por defecto pasa a hablar de lo que el formulario realmente
    // hace: suscribirse a ofertas y novedades. Sigue siendo un Setting
    // editable (home_news_title_{locale}); solo cambia el default en código.
    $newsTitle = \App\Models\Setting::get('home_news_title_' . $locale)
        ?: $L('Suscríbete a nuestras ofertas y novedades', 'Subscribe to our deals and updates', 'Inscreva-se em nossas ofertas e novidades');
    $newsSub = \App\Models\Setting::get('home_news_sub_' . $locale)
        ?: $L(
            'Suscríbete a nuestro boletín para recibir noticias, ofertas y promociones especiales.',
            'Subscribe to our newsletter to receive news, deals and special promotions.',
            'Inscreva-se em nossa newsletter para receber notícias, ofertas e promoções especiais.'
        );

    $newsBenefitDefaults = [
        1 => ['icon' => 'tag', 'title' => $L('Ofertas exclusivas', 'Exclusive deals', 'Ofertas exclusivas'), 'text' => $L('Accede a descuentos y promociones especiales.', 'Get access to special discounts and promotions.', 'Tenha acesso a descontos e promoções especiais.')],
        2 => ['icon' => 'map', 'title' => $L('Novedades de viaje', 'Travel updates', 'Novidades de viagem'), 'text' => $L('Recibe inspiración y nuevas experiencias cada semana.', 'Get inspiration and new experiences every week.', 'Receba inspiração e novas experiências toda semana.')],
        3 => ['icon' => 'clock', 'title' => $L('Eventos especiales', 'Special events', 'Eventos especiais'), 'text' => $L('Sé el primero en enterarte de nuestros eventos y lanzamientos.', 'Be the first to know about our events and launches.', 'Seja o primeiro a saber sobre nossos eventos e lançamentos.')],
        4 => ['icon' => 'headset', 'title' => $L('Atención preferente', 'Priority support', 'Atendimento preferencial'), 'text' => $L('Soporte prioritario para suscriptores en todo momento.', 'Priority support for subscribers at all times.', 'Suporte prioritário para assinantes a qualquer momento.')],
    ];
    $newsBenefits = collect([1, 2, 3, 4])->map(function (int $n) use ($newsBenefitDefaults, $locale) {
        $default = $newsBenefitDefaults[$n];
        $chosenIcon = \App\Models\Setting::get("home_news_benefit_{$n}_icon");
        $iconKey = (is_string($chosenIcon) && \App\Support\HeroIcons::exists($chosenIcon)) ? $chosenIcon : $default['icon'];

        return [
            'icon'  => \App\Support\HeroIcons::svg($iconKey),
            'title' => \App\Models\Setting::get("home_news_benefit_{$n}_title_{$locale}") ?: $default['title'],
            'text'  => \App\Models\Setting::get("home_news_benefit_{$n}_text_{$locale}") ?: $default['text'],
        ];
    });

    if ($homeNewsEnabled) {
        // Avisa a footer.blade.php (renderizado después de @yield('content')
        // dentro de layouts/app.blade.php, mismo $__env) que NO repita el
        // formulario de newsletter en esta página — evita dos formularios
        // del mismo canal en la misma pantalla (footer.blade.php lee esto
        // con $__env->shared()).
        $__env->share('lat_hide_footer_newsletter', true);
    }

    $badgeClass = fn (?string $type) => match ($type) {
        'success' => 'lat-dcard__badge--g',
        'warn'    => 'lat-dcard__badge--o',
        default   => '',
    };

    // ── Categorías reales — ya vienen resueltas por HomeController
    // ($homeCategories: Category::active()->withPublishedTours(), guard
    // automático). Antes esta vista volvía a consultar el modelo aquí mismo
    // (duplicando la query del controller) con un bug real: eager-loadear
    // `tours` con `->limit(1)` dentro del closure NO limita "1 por categoría"
    // — Eloquent aplica un único LIMIT a la consulta combinada de las 4
    // categorías, así que solo la PRIMERA se quedaba con foto y las otras 3
    // recibían `tours` vacío y caían todas al mismo fallback genérico (la
    // panorámica del hero) — el defecto "categorías con la misma foto
    // repetida y una en gris" que reporta el brief. Cargar el tour SIN limit
    // (25 tours en total entre las 4 categorías, carga liviana) y tomar
    // `first()` en PHP sí es por-categoría. Verificado contra la BD real:
    // las 4 categorías ya tienen cada una su propia foto distinta.
    $categories = $homeCategories ?? collect();
    if ($categories->isNotEmpty()) {
        $categories->load(['tours' => fn ($q) => $q->published()->orderBy('order')]);
    }

    // Ícono propio por categoría para la tira "encimada" al borde del hero
    // (mockup: foto + ícono + label). Catálogo cerrado de 4 SVG (no hay campo
    // de ícono en el modelo Category) — se asigna por SLUG, con un ícono
    // genérico de respaldo si el cliente crea una 5ª categoría con otro slug.
    // Las claves son los SLUGS REALES de la tabla `categories` (cultural,
    // aventura, gastronomia, otros). Estaban escritas como "tours-culturales",
    // "tours-de-aventura" y "experiencias-culinarias" —el nombre visible
    // convertido a slug, no el slug— así que NINGUNA coincidía y las cuatro
    // categorías caían al ícono de respaldo: cuatro círculos con una cruz,
    // todos iguales, en la tira pegada al hero (visto en staging el
    // 2026-08-14). Se dejan los nombres viejos como alias por si en otro
    // entorno el catálogo se cargó con esos slugs.
    $catStripIcons = [
        'cultural' => '<path d="M4 21h16M5 21V9l7-5 7 5v12M9 21v-6h6v6"/>',
        'aventura' => '<path d="m8 21 4-13 4 13M6 13h12M12 3l2 4h-4l2-4z"/>',
        'gastronomia' => '<path d="M7 2v20M7 2a5 5 0 0 0-5 5v4h5M17 2v20M17 2a5 5 0 0 1 5 5v4a5 5 0 0 1-5 5"/>',
        // "Otros" es una categoría real del catálogo, no un caso residual: le
        // toca su propio ícono (brújula) en vez del genérico.
        'otros' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2.1 5-5 2.1 2.1-5z"/>',

        // Alias por los slugs que asumía la versión anterior.
        'tours-culturales' => '<path d="M4 21h16M5 21V9l7-5 7 5v12M9 21v-6h6v6"/>',
        'tours-de-aventura' => '<path d="m8 21 4-13 4 13M6 13h12M12 3l2 4h-4l2-4z"/>',
        'experiencias-culinarias' => '<path d="M7 2v20M7 2a5 5 0 0 0-5 5v4h5M17 2v20M17 2a5 5 0 0 1 5 5v4a5 5 0 0 1-5 5"/>',
    ];
    // Respaldo para una categoría nueva que cree el cliente: un mapa, que dice
    // "un destino más" sin fingir que sabemos de qué trata.
    $catStripIconDefault = '<path d="m9 4-6 3v13l6-3 6 3 6-3V4l-6 3z"/><path d="M9 4v13M15 7v13"/>';

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
        // El fallback de este slot apuntaba a un asset de LIMA VIEW TOURS (otro
        // cliente, competencia directa): con el Setting vacío, la home publicaba
        // material ajeno. Un default que publica lo que no es del cliente es un
        // defecto, no un pendiente de configuración. Ahora es una foto del propio
        // catálogo (658×903, vertical, sin upscale para el derivado de 640).
        // Lo vigila NoForeignClientAssetsTest.
        ['key' => 'home_gallery_img_2', 'fallback' => 'tours/MACHU-2-DIAS-1.jpg', 'alt' => [
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

    // Subtítulo bajo el H2 de la galería (Fix 6, informe CRO 2026-08-01): el
    // prototipo (WhatsApp Image 2026-07-31 13.09.05(1)) trae una línea bajo
    // "Descubre la belleza del Perú" que el sitio no pintaba. No existía
    // ninguna clave de Settings para esto — se agrega `home_gallery_sub_{locale}`
    // con el mismo patrón que el resto del home (default en código, editable
    // sin deploy). NO se tocó app/**: Setting::get() ya es de uso general.
    $gallerySub = \App\Models\Setting::get('home_gallery_sub_' . $locale)
        ?: $L(
            'Experiencias únicas en destinos increíbles que te enamorarán.',
            'Unique experiences in incredible destinations that will win you over.',
            'Experiências únicas em destinos incríveis que vão te encantar.'
        );

    // ── Testimonios reales (mockup: 3 tarjetas + 1 tarjeta de agregado) ──
    // $realTestimonials ya viene resuelto por HomeController (Testimonial::active(),
    // con nombre+fecha+tour real — NO la mezcla con la API de Google que usa
    // $testimonials, esa no trae fecha confiable). El agregado ("5.0 · 18 opiniones")
    // es la MISMA cuenta que usa /resenas y el hero (ReviewAggregator::overallStats),
    // nunca un número calculado aparte.
    $homeReviewCards = ($realTestimonials ?? collect())->take(3);
    $homeOverallStats = app(\App\Services\ReviewAggregator::class)->overallStats($locale);

    // ── Destinos reales (mockup: "Explora los increíbles destinos del Perú") ──
    // $destinationRegions ya viene resuelto por HomeController (Region::active()
    // ->withPublishedTours(), guard automático). Hoy son 3 (Lima, Cusco, Ica); el
    // grid tiene que verse bien con 3, no con los 6 del mockup.
    $homeDestinations = $destinationRegions ?? collect();
@endphp

{{-- Precarga de la foto del hero (LCP del sitio) = primera diapositiva de
     $heroSlides (ver HeroSlidesResolver más arriba). Mismo src/srcset/sizes
     que el <img> de abajo — si no coincidieran, el navegador descargaría la
     foto dos veces. El @stack('preload') del layout está antes del CSS de
     fuentes para que el preload scanner la vea cuanto antes. --}}
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
         HERO — rediseño 2026-08 (mockup foto a sangre): foto de fondo
         cubriendo TODO el hero en cualquier breakpoint (antes: foto solo
         en la columna/bloque superior, con velo hacia crema en desktop).
         Contenido superpuesto: eyebrow, H1 (marca + tagline roja),
         párrafo, botones "Reservar Ahora" / "Ver Video", card ROJA de stats
         (Fix 2, lote mockups agosto 2026 — junto al título en ≥1024, debajo
         en mobile/tablet; el badge suelto "10+ años" se retiró el
         2026-08-13, ver nota junto a $heroYearsForTagline: la cifra de años
         es hoy una fila más de esta misma card), slider administrable
         (dots + flechas, ver más abajo) y tarjetas de PROMOCIÓN (A3). El
         buscador de 4 campos queda FUERA del hero, ya en la sección
         siguiente (evita solapar la card de stats).
         ============================================================ --}}
    <section class="lat-hero" aria-labelledby="hero-title">
        <div class="lat-hero__bg">
            {{-- LCP del sitio: PRIMERA diapositiva de $heroSlides (contrato de
                 App\Services\HeroSlidesResolver — colección ordenada, nunca
                 vacía; sin diapositivas activas en el CMS cae en la misma foto
                 de siempre). Variantes WebP por ancho (ResponsiveImage), carga
                 prioritaria y NUNCA lazy, con las dimensiones reales del archivo
                 que se sirve para que el navegador reserve la caja exacta. El
                 preload va en el <head> (más arriba, @push('preload')) con el
                 mismo srcset: si difirieran, el navegador bajaría dos fotos.
                 Sigue siendo el mismo <img> con srcset de siempre — SOLO cambia
                 su posicionamiento (capa de fondo en vez de columna), nunca se
                 reemplaza por un background-image de CSS.

                 SLIDER (2026-08-13): con 2+ diapositivas activas, este MISMO
                 <img> es el visor — su src/srcset/sizes/width/height/alt se
                 reemplazan por JS al navegar (mismo patrón que la galería de
                 la ficha de tour, .lat-gal__main-btn en tours/show.blade.php:
                 UN visor, no N <img> apiladas). Consecuencia deliberada: las
                 diapositivas 2..N NUNCA se piden por red hasta que el usuario
                 hace clic/usa el teclado — más fuerte que loading="lazy" (que
                 no defiere nada si el elemento ya está en el viewport al
                 cargar, como es el caso de todo el hero). El array completo
                 (url/srcset/sizes/width/height/alt) viaja una sola vez como
                 JSON en el script de más abajo. Con 1 sola diapositiva (tabla
                 en 1 fila, el estado de hoy) no se pinta ningún control y este
                 <img> quedó IDÉNTICO al de antes de este cambio. --}}
            <img
                id="heroSlideImg"
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
            {{-- Degradado oscuro para contraste AA del texto blanco/rosa sobre la
                 foto (medido: ver notas de contraste en pages/_lat-home.scss). --}}
            <span class="lat-hero__scrim" aria-hidden="true"></span>
        </div>

        <div class="lat-hero__content lat-wrap">
            <div class="lat-hero__text">
                <span class="lat-eyebrow lat-eyebrow--on-dark">{{ $heroEyebrow }}</span>

                {{-- El H1 envuelve marca + subtitular rojo, no solo la marca (motivo
                     de SEO explicado en el commit original: la home es la página con
                     más autoridad del dominio y el H1 debe reforzar el tema). --}}
                <h1 id="hero-title" class="lat-hero__title">
                    <span class="lat-hero__brand">{!! $heroTitleRaw !!}</span>
                    <span class="lat-hero__tagline">{!! nl2br(e($heroTagline)) !!}</span>
                </h1>

                <p class="lat-hero__desc">{{ $heroSub }}</p>

                <div class="lat-hero__actions">
                    <a href="{{ $heroPrimaryHref }}" class="lat-btn lat-btn--red">{{ $heroPrimaryLabel }}</a>
                    <a href="{{ $heroSecondaryHref }}" class="lat-btn lat-btn--outline-white">
                        {{ $heroSecondaryLabel }}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>

                    @if ($heroVideoUrl !== '')
                        <button type="button" class="lat-btn lat-btn--outline-white" id="heroVideoBtn"
                                aria-haspopup="dialog" aria-controls="heroVideoModal">
                            <span class="lat-hero__play-ic" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                            {{ $heroVideoLabel }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- ── Card roja de stats (Fix 2, lote mockups agosto 2026) ──
                 Antes era una barra blanca horizontal encimada al borde
                 INFERIOR del hero (.lat-hero__stats-wrap + .lat-hero-stats +
                 .lat-htc, medida en rgb(255,255,255) con top absoluto 1174
                 contra un hero de 139 a 1367 — muy por debajo del título).
                 El mockup 01-home la pone como una card ROJA flotando a la
                 DERECHA del hero, a la altura del titular, con las filas
                 apiladas e ícono a la izquierda de cada una. Vive ahora
                 DENTRO de .lat-hero__content, como hermana de .lat-hero__text,
                 para que en ≥1024 el flex las ponga lado a lado (ver
                 .lat-hero__content en _lat-home.scss) y en mobile/tablet caiga
                 en flujo normal debajo del texto — nunca tapando título ni
                 CTAs. Misma fuente de datos que antes ($homeStats, ya
                 filtrado por HomeStatsResolver): slots sin dato no se pintan
                 y la card se adapta al alto sola (flex column, sin altura
                 fija). .lat-hero-stats / .lat-htc NO se tocan ni se
                 reutilizan aquí a propósito: about.blade.php las sigue usando
                 tal cual para su propia franja de stats (fuera de alcance de
                 este lote) — namespace nuevo (.lat-hero__stats-card /
                 .lat-hsc__*) para no compartir cascada con esa página. --}}
            @if ($homeStatsEnabled)
                <div class="lat-hero__stats-card" style="--lat-stats-n:{{ $homeStats->count() }}">
                    @foreach ($homeStats as $stat)
                        <div class="lat-hsc__row">
                            <span class="lat-hsc__ic">{!! $stat['icon'] !!}</span>
                            <div class="lat-hsc__text">
                                @if (! empty($stat['url']))
                                    <a class="lat-hsc__value lat-hsc__value--src" href="{{ $stat['url'] }}"
                                       target="_blank" rel="noopener nofollow">{{ $stat['value'] }}</a>
                                @else
                                    <span class="lat-hsc__value">{{ $stat['value'] }}</span>
                                @endif
                                <span class="lat-hsc__label">{{ $stat['label'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Slider del hero (mockup 01-home.jpeg: 4 puntos + flechas ←→) ──
             Solo se pinta con 2+ diapositivas ACTIVAS reales — con 1 sola
             (hoy) no hay nada que navegar y un carrusel de una foto es peor
             que no tener carrusel (contrato de HeroSlidesResolver, regla 1).
             Sin autoplay: se mueve solo con clic, flecha o teclado (← →) con
             el foco dentro de este bloque. $heroSlides completo (con la
             primera) viaja como JSON al script de abajo para no tener que
             volver a pedirle nada a PHP al navegar. --}}
        @if ($heroSlides->count() > 1)
            <div class="lat-hero__slider lat-wrap" id="heroSlider"
                 role="group" aria-roledescription="carousel"
                 aria-label="{{ $L('Diapositivas destacadas', 'Featured slides', 'Slides em destaque') }}">
                <div class="lat-hero__dots">
                    @foreach ($heroSlides as $i => $slide)
                        <button type="button" class="lat-hero__dot {{ $i === 0 ? 'is-active' : '' }}"
                                data-index="{{ $i }}"
                                aria-label="{{ $L('Ir a la diapositiva', 'Go to slide', 'Ir para o slide') }} {{ $i + 1 }}"
                                @if ($i === 0) aria-current="true" @endif></button>
                    @endforeach
                </div>
                <div class="lat-hero__arrows">
                    <button type="button" class="lat-hero__arrow lat-hero__arrow--prev" id="heroPrevBtn"
                            aria-label="{{ $L('Diapositiva anterior', 'Previous slide', 'Slide anterior') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="lat-hero__arrow lat-hero__arrow--next" id="heroNextBtn"
                            aria-label="{{ $L('Siguiente diapositiva', 'Next slide', 'Próximo slide') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
                {{-- Anuncio discreto del cambio de diapositiva para lectores de
                     pantalla — no es visible, no duplica el <img> ni sus alt. --}}
                <span class="sr-only" id="heroSliderLive" aria-live="polite"></span>
            </div>
        @endif

        {{-- ── Tarjetas PROMOCIÓN (A3) — mismas 3 ofertas reales ($offers,
             modelo Offer, HomeController::fetchOffers()) que antes vivían en
             la sección aparte "Ofertas especiales"; esa sección se retiró
             para no repetir las mismas tarjetas dos veces en la página. Si
             no hay ofertas activas, el hero no deja un hueco. ── --}}
        @if ($offers->isNotEmpty())
            {{-- El título de este grupo va oculto a la vista pero presente para
                 lectores de pantalla y para el rastreo: las tarjetas usan <h3> y sin
                 un <h2> que las agrupe la jerarquía salta de H1 a H3 (gate de
                 regresión SEO, 2026-08-12). El mockup no dibuja un título acá, así
                 que se resuelve con sr-only en vez de inventar un encabezado visible. --}}
            <h2 class="sr-only">{{ $L('Promociones vigentes', 'Current promotions', 'Promoções vigentes') }}</h2>
            <div class="lat-wrap lat-hero__promos">
                @foreach ($offers as $offer)
                    @php
                        $offerImg = \App\Support\ImagePath::url($offer->image) ?? \App\Support\ResponsiveImage::defaultPhotoUrl(640);
                        $offerHref = $offer->cta_url
                            ?: ($offer->tour ? route('tours.show', ['locale' => $locale, 'slug' => $offer->tour->slug]) : route('tours.index', ['locale' => $locale]));
                    @endphp
                    <article class="lat-promo">
                        <a href="{{ $offerHref }}" class="lat-promo__media">
                            <img src="{{ $offerImg }}" alt="{{ $offer->title }}" loading="lazy" width="400" height="230">
                        </a>
                        <div class="lat-promo__body">
                            <span class="lat-promo__eyebrow">{{ $L('Promoción', 'Promotion', 'Promoção') }}</span>
                            <a href="{{ $offerHref }}"><h3 class="clamp-2">{{ $offer->title }}</h3></a>
                            @if ($offer->description)
                                <p class="clamp-2">{{ $offer->description }}</p>
                            @endif
                            <div class="lat-promo__foot">
                                @if ($offer->price)
                                    <div class="lat-promo__price">
                                        <small>{{ $L('Desde', 'From', 'Desde') }}</small>
                                        <span>{{ \App\Support\Money::format($offer->price, optional($offer->tour)->currency ?? \App\Support\Money::site()) }}</span>
                                    </div>
                                @endif
                                <a href="{{ $offerHref }}" class="lat-btn-out lat-btn-out--on-dark">{{ $offer->cta_label ?: $L('Leer más', 'Read more', 'Leia mais') }}</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

    </section>

    {{-- ============================================================
         TIRA DE CATEGORÍAS — foto + ícono + label, encimada al borde donde
         arranca el fondo claro (mockup 01-home). El mockup pinta 6 (Aventura/
         Cultura/Naturaleza/Grupos/Experiencias/Relajación, decorativas, sin
         dato real detrás); van las 4 categorías reales con su conteo
         ($homeCategories, guard automático — si el cliente da de baja
         "Otros" la tira baja a 3 columnas sola). Distinta de "Explora por
         categoría" de abajo: esta es la tira compacta pegada al hero, esa es
         la grilla grande con descripción.
         ============================================================ --}}
    @if ($categories->isNotEmpty())
        <section class="lat-cat-strip" aria-label="{{ $L('Categorías de tours', 'Tour categories', 'Categorias de tours') }}">
            <div class="lat-wrap lat-cat-strip__grid" style="--lat-catstrip-n:{{ $categories->count() }}">
                @foreach ($categories as $cat)
                    @php
                        $catStripImg = optional($cat->tours->first())->cover_url ?? \App\Support\ResponsiveImage::defaultPhotoUrl(360);
                        $catStripIcon = $catStripIcons[$cat->slug] ?? $catStripIconDefault;
                    @endphp
                    <a href="{{ route('tours.index', ['locale' => $locale, 'cat' => $cat->slug]) }}" class="lat-cat-strip__item lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms">
                        <img src="{{ $catStripImg }}" alt="" loading="lazy" width="220" height="220">
                        <span class="lat-cat-strip__ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $catStripIcon !!}</svg>
                        </span>
                        <span class="lat-cat-strip__label">{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============================================================
         BUSCADOR — 4 campos reales (doble marco: contenedor oscuro + barra
         clara). Fuera del hero a propósito: nunca debe solaparse con la
         tarjeta de stats que ahora cuelga del borde inferior del hero.
         ============================================================ --}}
    <section class="lat-hero-search-section" aria-label="{{ $L('Buscador de tours', 'Tour search', 'Busca de tours') }}">
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
        </div>
    </section>

    {{-- ============================================================
         MODAL "Ver video" — solo existe en el DOM si hay URL en Settings.
         El iframe NO se crea hasta el clic (evita cargar un player al
         cargar la home y matar el LCP). Fuera del hero a propósito: el
         modal es position:fixed a pantalla completa y no debe heredar
         ningún overflow/stacking del contenedor del hero.
         ============================================================ --}}
    @if ($heroVideoUrl !== '')
        <div class="lat-video-modal" id="heroVideoModal" role="dialog" aria-modal="true"
             aria-label="{{ $heroVideoLabel }}" hidden>
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
    <section class="lat-wrap lat-sec" id="tours" aria-labelledby="destacados-title">
        <div class="lat-sec-head lat-sec-head--home">
            <span class="lat-eyebrow is-center">{{ $L('Explora lugares increíbles', 'Explore incredible places', 'Explore lugares incríveis') }}</span>
            <h2 id="destacados-title">{{ $L('Tours Destacados', 'Featured Tours', 'Tours em Destaque') }}</h2>
            <p>{{ $L('Descubre nuestros tours más populares y vive experiencias inolvidables en los mejores destinos de Perú.', 'Discover our most popular tours and live unforgettable experiences in the best destinations in Peru.', 'Descubra nossos tours mais populares e viva experiências inesquecíveis nos melhores destinos do Peru.') }}</p>
        </div>

        <div class="lat-dest-grid">
            @forelse ($destacados as $tour)
                {{-- Aparición al hacer scroll (encargo 2026-08-15): .lat-reveal
                     nunca oculta nada sin JS (ver _lat-home.scss/reveal.js) —
                     el delay solo escalona el orden dentro de esta grilla,
                     tope de 5 posiciones para no alargar la espera en listas
                     largas. --}}
                <article class="lat-dcard lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms">
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
        {{-- Fix 5(a) — textura decorativa "mapamundi punteado" del prototipo
             (WhatsApp Image 2026-07-31 13.09.05(1)): SVG inline propio (patrón
             de puntos recortado por una silueta orgánica), sin CDN ni imagen de
             terceros. Puramente decorativo: aria-hidden, opacidad muy baja,
             detrás del contenido (z-index 0) y sin tapar texto ni bajar el
             contraste de nada. --}}
        <svg class="lat-gallery__mark" aria-hidden="true" viewBox="0 0 420 420" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="latGalleryDots" width="11" height="11" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="1.6" fill="currentColor"/>
                </pattern>
                <clipPath id="latGalleryBlob">
                    <path d="M70,45 C130,10 225,15 285,55 C345,95 385,155 365,215 C348,270 295,300 235,322 C170,346 100,335 68,292 C38,252 18,198 30,148 C40,105 22,75 70,45 Z"/>
                </clipPath>
            </defs>
            <g clip-path="url(#latGalleryBlob)">
                <rect width="420" height="420" fill="url(#latGalleryDots)"/>
            </g>
        </svg>

        <div class="lat-wrap">
            <div class="lat-sec-head lat-sec-head--home">
                <span class="lat-eyebrow is-center">{{ $L('Galería', 'Gallery', 'Galeria') }}</span>
                <h2 id="gallery-title">{{ $L('Descubre la belleza del Perú', 'Discover the beauty of Peru', 'Descubra a beleza do Peru') }}</h2>
                <p>{{ $gallerySub }}</p>
            </div>
        </div>

        <div class="lat-gallery__strip">
            @foreach ($galleryImages as $photo)
                <div class="lat-gallery__item lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms">
                    <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" loading="lazy" width="400" height="500">
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================================================
         EXPLORA POR CATEGORÍA — reales (modelo Category)
         ============================================================ --}}
    @if ($categories->isNotEmpty())
        <section class="lat-wrap lat-sec" aria-labelledby="cats-title">
            {{-- Encabezado alineado a la IZQUIERDA con enlace a la derecha, no
                 centrado (2026-08-14). La home tenía seis encabezados centrados
                 idénticos uno tras otro — eyebrow, H2, párrafo, siempre igual —
                 y esa regularidad es justo lo que hace que una página se lea
                 como generada. .lat-sec-head-row ya existía para los
                 testimonios; acá se reutiliza tal cual. --}}
            <div class="lat-sec-head-row">
                <div>
                    <span class="lat-eyebrow">{{ $L('Elige tu experiencia', 'Choose your experience', 'Escolha sua experiência') }}</span>
                    <h2 id="cats-title">{{ $L('Explora por categoría', 'Explore by category', 'Explore por categoria') }}</h2>
                    <p class="lat-sec-head-row__sub">{{ $L('Descubre el tipo de aventura que más te gusta: recorridos por la ciudad, sabores peruanos, aventura y culturas milenarias.', 'Discover the kind of adventure you like best: city tours, Peruvian flavors, adventure and ancient cultures.', 'Descubra o tipo de aventura que mais gosta: passeios pela cidade, sabores peruanos, aventura e culturas milenares.') }}</p>
                </div>
                <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-link-arrow">{{ $L('Ver todos los tours', 'View all tours', 'Ver todos os tours') }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </div>

            <div class="lat-cats">
                @foreach ($categories as $cat)
                    @php
                        // Relleno cuando la categoría no tiene ningún tour con foto:
                        // variante de 640 (la tarjeta mide ~340 px), no el archivo
                        // original de 2.5 MB que se usaba antes.
                        $catImg = optional($cat->tours->first())->cover_url ?? \App\Support\ResponsiveImage::defaultPhotoUrl(640);
                    @endphp
                    <a href="{{ route('tours.index', ['locale' => $locale, 'cat' => $cat->slug]) }}" class="lat-cat lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms">
                        <img src="{{ $catImg }}" alt="{{ $cat->name }}" loading="lazy" width="360" height="230">
                        @if ($cat->published_tours_count)
                            <span class="lat-cat__count">{{ $cat->published_tours_count }} {{ $cat->published_tours_count === 1 ? $L('tour', 'tour', 'tour') : $L('tours', 'tours', 'tours') }}</span>
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
         OFERTAS ESPECIALES — retirada de aquí (rediseño 2026-08): las
         mismas 3 tarjetas de $offers ahora se pintan como PROMOCIÓN dentro
         del hero (A3, ver arriba) para no repetirlas dos veces en la misma
         página. $offers sigue siendo el mismo modelo Offer real
         (HomeController::fetchOffers()) — no se tocó ninguna consulta.
         ============================================================ --}}

    {{-- ============================================================
         VIAJA CON CONFIANZA — 5 columnas (mockup: "¿Por qué elegirnos?").
         Título + bajada de cada tarjeta ahora vienen de Configuración → Home
         → "Razones 'Viaja con confianza...'" (Setting home_why_items, un
         repeater que ya existía en el panel pero al que ninguna vista leía
         — hallazgo 2026-08-11). Fallback a las 5 razones de siempre cuando
         el admin no cargó nada. El ícono se asigna por posición (ciclando
         sobre los 5 de diseño): el repeater no guarda ícono, igual que
         blocks.stats en Nosotros.
         ============================================================ --}}
    <section class="lat-wrap lat-why" aria-labelledby="why-title">
        <div class="lat-sec-head lat-sec-head--home">
            <span class="lat-eyebrow is-center">{{ $L('¿Por qué elegirnos?', 'Why choose us?', 'Por que nos escolher?') }}</span>
            <h2 id="why-title">{{ $L('Viaja con confianza y vive la mejor experiencia', 'Travel with confidence and live the best experience', 'Viaje com confiança e viva a melhor experiência') }}</h2>
        </div>

        @php
            $whyIcons = [
                '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.7"/>',
                '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
                '<path d="M20.59 13.41 11 3.83A2 2 0 0 0 9.59 3.24L4 3a1 1 0 0 0-1 1l.24 5.59a2 2 0 0 0 .58 1.41l9.6 9.6a2 2 0 0 0 2.83 0l4.34-4.34a2 2 0 0 0 0-2.85z"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor" stroke="none"/>',
                '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>',
                '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            ];

            $defaultWhyItems = [
                ['title' => $L('Guías certificados', 'Certified guides', 'Guias certificados'), 'text' => $L('Expertos locales apasionados por compartir lo mejor del Perú.', 'Local experts passionate about sharing the best of Peru.', 'Especialistas locais apaixonados por compartilhar o melhor do Peru.')],
                ['title' => $L('Viajes seguros', 'Safe travel', 'Viagens seguras'), 'text' => $L('Tu seguridad es nuestra prioridad en cada tour.', 'Your safety is our priority on every tour.', 'Sua segurança é nossa prioridade em cada tour.')],
                ['title' => $L('Mejor precio garantizado', 'Best price guaranteed', 'Melhor preço garantido'), 'text' => $L('Calidad y experiencia al mejor precio del mercado.', 'Quality and experience at the best market price.', 'Qualidade e experiência ao melhor preço do mercado.')],
                ['title' => $L('Atención personalizada', 'Personalized support', 'Atendimento personalizado'), 'text' => $L('Soporte antes, durante y después de tu viaje.', 'Support before, during and after your trip.', 'Suporte antes, durante e depois da sua viagem.')],
                ['title' => $L('Cancelación flexible', 'Flexible cancellation', 'Cancelamento flexível'), 'text' => $L('Cambia tus planes con flexibilidad y sin complicaciones.', 'Change your plans with flexibility and no hassle.', 'Mude seus planos com flexibilidade e sem complicações.')],
            ];

            $rawWhyItems = \App\Models\Setting::get('home_why_items');
            $whyItemsSrc = is_string($rawWhyItems) ? (json_decode($rawWhyItems, true) ?? []) : (is_array($rawWhyItems) ? $rawWhyItems : []);

            $whyItems = collect($whyItemsSrc)
                ->map(fn ($item) => [
                    'title' => trim((string) ($item['title_' . $locale] ?? ($item['title_es'] ?? ''))),
                    'text'  => trim((string) ($item['desc_' . $locale] ?? ($item['desc_es'] ?? ''))),
                ])
                ->filter(fn ($item) => $item['title'] !== '')
                ->values()
                ->all();

            if (empty($whyItems)) {
                $whyItems = $defaultWhyItems;
            }
        @endphp

        <div class="lat-why__grid">
            @foreach ($whyItems as $i => $why)
                <div class="lat-why__item lat-reveal" style="transition-delay:{{ min($i, 5) * 70 }}ms">
                    <span class="lat-why__ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $whyIcons[$i % count($whyIcons)] !!}</svg></span>
                    <b>{{ $why['title'] }}</b>
                    @if ($why['text'] !== '')
                        <span>{{ $why['text'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================================================
         TESTIMONIOS — reales ($realTestimonials, HomeController), con
         nombre + fecha + tour. 3 tarjetas + 1 tarjeta de agregado (el mismo
         "5.0 · 18 opiniones" del hero, vía ReviewAggregator — nunca un
         número calculado aparte). Sección entera oculta si no hay ninguna
         reseña real cargada (guard, no "0 opiniones" a la vista).
         ============================================================ --}}
    @if ($homeReviewCards->isNotEmpty())
        <section class="lat-wrap lat-sec lat-sec--follow" aria-labelledby="reviews-title">
            <div class="lat-sec-head-row">
                <div>
                    <span class="lat-eyebrow">{{ $L('Lo que dicen nuestros viajeros', 'What our travelers say', 'O que dizem nossos viajantes') }}</span>
                    <h2 id="reviews-title">{{ $L('Miles de viajeros ya vivieron la experiencia', 'Thousands of travelers have already lived the experience', 'Milhares de viajantes já viveram a experiência') }}</h2>
                </div>
                <a href="{{ route('reviews', ['locale' => $locale]) }}" class="lat-link-arrow">{{ $L('Ver todas las reseñas', 'See all reviews', 'Ver todas as avaliações') }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </div>

            <div class="lat-reviews-grid">
                @foreach ($homeReviewCards as $t)
                    @php
                        $tInitial = strtoupper(mb_substr(trim((string) $t->name), 0, 1));
                        $tSrc = app(\App\Services\ReviewAggregator::class)->normalizeSource($t->source);
                        $tSrcLabel = ['google' => 'Google', 'tripadvisor' => 'Tripadvisor', 'trivago' => 'Trivago', 'web' => $L('Nuestra web', 'Our website', 'Nosso site')][$tSrc];
                    @endphp
                    <article class="lat-rcard lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms">
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
                        <span class="lat-rcard__src">{{ $tSrcLabel }}</span>
                    </article>
                @endforeach

                @if (! is_null($homeOverallStats['rating']))
                    <article class="lat-rcard lat-rcard--agg lat-reveal" style="transition-delay:{{ min($homeReviewCards->count(), 5) * 70 }}ms">
                        <span class="lat-rcard__agg-num">{{ number_format($homeOverallStats['rating'], 1) }}<small>/5</small></span>
                        <span class="lat-stars"><span class="lat-stars__s">@for ($i = 0; $i < 5; $i++)<svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.8L12 17.3 5.8 20.9l1.6-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>@endfor</span></span>
                        <span class="lat-rcard__agg-count">
                            {{ $L('Basado en', 'Based on', 'Baseado em') }} {{ number_format($homeOverallStats['count']) }}
                            {{ $L('opiniones', 'reviews', 'avaliações') }}
                        </span>
                    </article>
                @endif
            </div>
        </section>
    @endif

    {{-- ============================================================
         DESTINOS POPULARES — reales ($destinationRegions, HomeController):
         solo regiones activas con al menos un tour publicado, guard
         automático. Hoy son 3 (Lima/Cusco/Ica); el mockup pinta 6, pero acá
         no se inventan Arequipa/Paracas/Puno sin tour detrás.
         ============================================================ --}}
    @if ($homeDestinations->isNotEmpty())
        <section class="lat-wrap lat-sec lat-sec--follow" aria-labelledby="dest-title">
            <div class="lat-sec-head lat-sec-head--home">
                <span class="lat-eyebrow is-center">{{ $L('Destinos populares', 'Popular destinations', 'Destinos populares') }}</span>
                <h2 id="dest-title">{{ $L('Explora los increíbles destinos del Perú', 'Explore the incredible destinations of Peru', 'Explore os incríveis destinos do Peru') }}</h2>
            </div>

            <div class="lat-dest-cards" style="--lat-dest-n:{{ min($homeDestinations->count(), 3) }}">
                @foreach ($homeDestinations as $region)
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
         FAQ (AEO) — se mantiene por su valor SEO; solo renderiza si hay
         preguntas configuradas en Settings. Estilo teal/orange heredado
         del diseño anterior: pendiente de repintar a rojo en un milestone
         posterior de restyle.
         ============================================================ --}}
    <x-faq-section />

    {{-- ============================================================
         CIERRE DE LA HOME — CTA + newsletter sobre UNA sola foto
         (referencia 3 del cliente, 2026-08-14).

         Antes eran dos bandas apiladas: una masa de rojo plano de ~380 px
         de alto y, pegado abajo, otro bloque oscuro con su propia foto. Dos
         superficies distintas para dos mensajes que son el mismo momento
         ("andá al catálogo" / "dejanos tu correo"), y la banda roja era la
         pieza que más gritaba "plantilla" de toda la página.

         La referencia que aprobó el cliente los resuelve sobre una foto
         continua, con el rojo reducido a los botones. Eso hace este
         contenedor: pinta la foto + el velo UNA vez y las dos <section>
         de adentro quedan transparentes.

         Nada de lo que había cambia de dueño: el CTA sigue siendo
         .lat-home-cta y el newsletter .lat-news, con sus mismos textos,
         Settings y endpoint. Si la clienta apaga el newsletter
         (home_news_enabled), el CTA se queda solo sobre la foto y el bloque
         sigue cerrando bien.
         ============================================================ --}}
    {{-- El velo es DENSO a propósito (.74 arriba → .93 abajo). Medido sobre la
         foto real: Barranco es una foto luminosa y con velos más suaves el
         texto blanco caía sobre cielo azul claro y sobre una fachada amarilla.
         Con estos valores la zona más clara del fondo compuesto queda en
         ~rgb(66,64,62) y el blanco mide ~10:1. Si la clienta sube otra foto
         desde Configuración → Home, este velo sigue siendo el peor caso
         razonable; una foto casi blanca habría que volver a medirla. --}}
    <div class="lat-closing"
         style="background-image:linear-gradient(rgba(14,11,10,.74), rgba(14,11,10,.93)), url('{{ $newsImgUrl }}');">

        {{-- Motivo de fondo: geoglifo tipo líneas de Nazca (rectas de la pampa,
             trapecio y espiral), dibujado en SVG y no con una foto ni un asset
             de terceros — mismo criterio que `.lat-gallery__mark`. Venía del
             lote del 13/08 (pedido del jefe sobre una captura) y se conserva
             tal cual: cambia el fondo bajo el que vive, no el dibujo.

             Al fusionarse los dos bloques, la silueta punteada del Perú que
             tenía el newsletter SE RETIRA: dos motivos decorativos distintos
             en la misma superficie compiten entre sí y ninguno se lee. Su
             SVG sigue en el historial si algún día se la quiere en otra
             pantalla. --}}
        <div class="lat-closing__mark" aria-hidden="true">
            <svg viewBox="0 0 420 420" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round">
                {{-- Se probaron dos figuras antes de llegar acá y las dos fallaron en
                     pantalla: un colibrí con alas horizontales se leía como torre de alta
                     tensión, y con alas en V como avión de combate. A este tamaño y con
                     trazo fino, la lectura correcta no es figurativa: lo que dice "Nazca"
                     sin ambigüedad es la GEOMETRÍA de la pampa — un centro radial del que
                     salen rectas larguísimas, trapecios, y la espiral. --}}
                <path d="M296 128 L-30 236" opacity=".85"/>
                <path d="M296 128 L-30 154" opacity=".85"/>
                <path d="M296 128 L20 440" opacity=".7"/>
                <path d="M296 128 L142 448" opacity=".7"/>
                <path d="M296 128 L286 452" opacity=".55"/>
                <path d="M296 128 L444 392" opacity=".55"/>
                <path d="M296 128 L448 76" opacity=".7"/>
                <circle cx="296" cy="128" r="7" opacity=".9"/>
                <path d="M118 30 L86 402 L214 418 L182 34" opacity=".8"/>
                <path d="M112 300 A11 11 0 0 1 134 300 A17 17 0 0 1 100 300 A23 23 0 0 1 146 300 A29 29 0 0 1 88 300" opacity=".9"/>
            </svg>
        </div>

    <section class="lat-home-cta" aria-labelledby="home-cta-title">
        <div class="lat-wrap lat-home-cta__inner lat-reveal">
            <span class="lat-eyebrow is-center">{{ $L('Vive la experiencia', 'Live the experience', 'Viva a experiência') }}</span>
            {{-- La segunda mitad del titular va en itálica, como en las tres
                 referencias. Se imprime sin escapar a propósito y se puede:
                 es texto FIJO de esta vista ($L con literales), no un Setting
                 ni nada que venga de la base — no hay entrada de usuario en
                 esta línea. --}}
            <h2 id="home-cta-title">{!! $L(
                'Tu próxima aventura <em>empieza aquí</em>',
                'Your next adventure <em>starts here</em>',
                'Sua próxima aventura <em>começa aqui</em>'
            ) !!}</h2>
            <p>{{ $L('Explora nuestro catálogo completo y encuentra el tour perfecto para ti.', 'Explore our full catalog and find the perfect tour for you.', 'Explore nosso catálogo completo e encontre o tour perfeito para você.') }}</p>
            <div class="lat-home-cta__actions">
                <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-home-cta__btn">
                    {{ $L('Ver todos los tours', 'View all tours', 'Ver todos os tours') }}
                </a>
            </div>
        </div>
    </section>

    {{-- ── NEWSLETTER — segunda mitad del bloque de cierre. Ya NO trae foto ni
         velo propios: los pinta el contenedor .lat-closing de arriba, una sola
         vez para las dos secciones. Mismo endpoint, mismos campos y mismos
         Settings que antes (NewsletterController@subscribe); el formulario del
         footer se sigue suprimiendo en esta página (ver $__env->share arriba y
         footer.blade.php) para no dejar dos del mismo canal en la pantalla. ── --}}
    @if ($homeNewsEnabled)
        <section class="lat-news" aria-labelledby="news-title">
            <div class="lat-wrap lat-news__inner lat-reveal" style="transition-delay:70ms">
                <div class="lat-news__intro">
                    <span class="lat-eyebrow lat-eyebrow--on-dark">{{ $newsEyebrow }}</span>
                    <h2 id="news-title">{{ $newsTitle }}</h2>
                    <p>{{ $newsSub }}</p>
                </div>

                <div class="lat-news__form">
                    @if (session('newsletter_success'))
                        <p class="lat-news__flash lat-news__flash--ok" role="status">{{ session('newsletter_success') }}</p>
                    @elseif ($errors->has('email') || $errors->has('name'))
                        <p class="lat-news__flash lat-news__flash--err" role="alert">{{ $errors->first('email') ?: $errors->first('name') }}</p>
                    @endif
                    <form action="{{ route('newsletter.subscribe') }}" method="post" id="form-newsletter-home">
                        @csrf
                        <input type="text" name="website" tabindex="-1" autocomplete="off"
                               style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                        {{-- Los íconos dentro de los campos son decorativos
                             (aria-hidden): la etiqueta accesible sigue siendo el
                             <span class="sr-only"> de siempre. --}}
                        <div class="lat-news__fields">
                            <label class="lat-news__field">
                                <span class="sr-only">{{ __('footer.newsletter_name') }}</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <input type="text" name="name" required placeholder="{{ $L('Nombre', 'Name', 'Nome') }}">
                            </label>
                            <label class="lat-news__field">
                                <span class="sr-only">{{ __('footer.newsletter_email') }}</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                                <input type="email" name="email" required placeholder="{{ $L('Correo', 'Email', 'E-mail') }}">
                            </label>
                        </div>
                        @include('partials.recaptcha', ['recaptchaAction' => 'newsletter', 'recaptchaFormId' => 'form-newsletter-home'])
                        <button type="submit" class="lat-btn lat-btn--red" style="width:100%">
                            {{ $L('Quiero suscribirme', 'I want to subscribe', 'Quero me inscrever') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="lat-wrap lat-news__benefits">
                @foreach ($newsBenefits as $benefit)
                    <div class="lat-news__benefit lat-reveal" style="transition-delay:{{ min($loop->index, 5) * 70 }}ms">
                        <span class="lat-news__benefit-ic">{!! $benefit['icon'] !!}</span>
                        <div>
                            <b>{{ $benefit['title'] }}</b>
                            <span>{{ $benefit['text'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    </div>{{-- /.lat-closing --}}

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
    var videoTitle = @json($heroVideoLabel);
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

{{-- ── Slider del hero: navegación (B/2026-08-13) ──────────────────────────
     Mismo patrón que la galería de la ficha de tour (.lat-gal, tours/show.blade.php):
     UN visor (#heroSlideImg) cuyo src/srcset/alt cambian por JS, no N <img>
     apiladas — así las diapositivas 2..N nunca compiten por red al cargar la
     home, solo se piden cuando el usuario navega de verdad. Guard de existencia
     (`if (!slider || !img) return`): con 1 sola diapositiva activa el bloque
     #heroSlider no se imprime (ver home.blade.php más arriba) y este script
     no hace nada — ni un listener queda colgado. --}}
@push('scripts')
<script>
(function () {
    var slider = document.getElementById('heroSlider');
    var img = document.getElementById('heroSlideImg');
    if (!slider || !img) return;

    var slides = @json($heroSlides->values());
    var total = slides.length;
    if (total < 2) return;

    var index = 0;
    var dots = Array.prototype.slice.call(slider.querySelectorAll('.lat-hero__dot'));
    var prevBtn = document.getElementById('heroPrevBtn');
    var nextBtn = document.getElementById('heroNextBtn');
    var live = document.getElementById('heroSliderLive');
    var liveLabel = @json($L('Diapositiva', 'Slide', 'Slide'));
    var liveOf = @json($L('de', 'of', 'de'));

    function render(i) {
        index = ((i % total) + total) % total;
        var s = slides[index];

        img.src = s.url;
        if (s.srcset) {
            img.srcset = s.srcset;
            img.sizes = s.sizes;
        } else {
            img.removeAttribute('srcset');
            img.removeAttribute('sizes');
        }
        img.alt = s.alt;
        if (s.width && s.height) {
            img.width = s.width;
            img.height = s.height;
        }

        dots.forEach(function (d) {
            var isActive = parseInt(d.getAttribute('data-index'), 10) === index;
            d.classList.toggle('is-active', isActive);
            if (isActive) d.setAttribute('aria-current', 'true');
            else d.removeAttribute('aria-current');
        });

        // aria-live discreto: anuncia el CAMBIO, no el estado inicial (por
        // eso este textContent nunca se fija en el primer render de la página).
        if (live) live.textContent = liveLabel + ' ' + (index + 1) + ' ' + liveOf + ' ' + total + ': ' + s.alt;
    }

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            render(parseInt(dot.getAttribute('data-index'), 10));
        });
    });

    if (prevBtn) prevBtn.addEventListener('click', function () { render(index - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { render(index + 1); });

    // Flechas del teclado: solo cuando el foco está DENTRO del slider (un dot
    // o una flecha ya enfocados) — nunca a nivel document, para no robarle
    // ← → a un <select>/<input> de otra parte de la página.
    slider.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft') { e.preventDefault(); render(index - 1); }
        else if (e.key === 'ArrowRight') { e.preventDefault(); render(index + 1); }
    });
})();
</script>
@endpush
