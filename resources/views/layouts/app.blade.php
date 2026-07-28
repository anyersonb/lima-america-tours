@php
    $locale = app()->getLocale();
    $altLocale = $locale === 'es' ? 'en' : 'es';
    $pathWithoutLocale = ltrim(preg_replace('#^/?(es|en|pt)(/|$)#', '', request()->path()), '/');
    $settings = $siteSettings ?? [];

    $siteName = $settings['site_name'] ?? __('seo.site_name');
    $defaultTitle = $settings['seo_default_title'] ?? __('seo.default_title');
    $defaultDescription = $settings['seo_default_description'] ?? __('seo.default_description');
    $defaultOgImage = $settings['seo_og_image'] ?? null;

    $title = trim($__env->yieldContent('title'));
    $description = trim($__env->yieldContent('description'));

    // Per-tour SEO (Tour::seo_title/seo_description/seo_image/seo_keywords) wins
    // over the generic @section('title'/'description') that tours/show.blade.php
    // always sets, when TourController@show passes it (docs/qa/ficha-tour.md
    // hallazgo #4). Any other view simply doesn't define these and falls back
    // to the existing @section/default behavior below.
    if (! empty($seoTitle)) { $title = $seoTitle; }
    if (! empty($seoDescription)) { $description = $seoDescription; }

    if ($title === '') { $title = $defaultTitle; }
    if ($description === '') { $description = $defaultDescription; }

    $ogImage = trim($__env->yieldContent('og_image'));
    if (! empty($seoImage)) { $ogImage = $seoImage; }
    if ($ogImage === '') {
        $ogImage = $defaultOgImage
            ? (\Illuminate\Support\Str::startsWith($defaultOgImage, ['http', '/']) ? $defaultOgImage : asset($defaultOgImage))
            : asset('assets/banners/banner-hero.jpg');
    }

    $gaId = $settings['seo_google_analytics_id'] ?? null;
    $gtmId = $settings['seo_gtm_id'] ?? null;
    $fbPixel = $settings['seo_facebook_pixel'] ?? null;
    $googleVerify = $settings['seo_google_site_verification'] ?? null;
    $bingVerify = $settings['seo_bing_site_verification'] ?? null;

    // Cookie consent — when banner is disabled by admin, analytics loads without requiring consent
    $cookieBannerEnabled = (bool) \App\Models\Setting::get('cookie_banner_enabled', true);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="ltr" class="no-js">
<head>
    <meta charset="UTF-8">
    <script>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js');</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    {{-- Evita el "flash" de elementos Alpine (menú/drawer) antes de inicializar --}}
    <style>[x-cloak]{display:none!important}</style>

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    @php
        // Some views (checkout/*.blade.php) need to force noindex,nofollow
        // regardless of the global NOINDEX config/environment — they set
        // @section('robots', ...) instead of printing their own
        // <meta name="robots"> tag, which used to duplicate this one
        // (SEO S-06: 2 <meta name="robots"> in the same <head>).
        //
        // Non-production environments (local/staging/testing) are ALWAYS
        // forced to noindex,nofollow, on top of the manual config('app.noindex')
        // override that can also force it in production. This keeps staging
        // out of search results without requiring anyone to remember to set
        // NOINDEX=true there. Production behavior is unchanged.
        $robotsOverride = trim($__env->yieldContent('robots'));
        $forceNoindex = config('app.noindex') || ! app()->environment('production');
    @endphp
    @if ($robotsOverride !== '')
        <meta name="robots" content="{{ $robotsOverride }}">
    @elseif ($forceNoindex)
        <meta name="robots" content="noindex,nofollow">
    @else
        <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    @endif
    <meta name="theme-color" content="#CB101E">
    <meta name="author" content="{{ $siteName }}">
    @php
        // Per-tour keywords (Tour::seo_keywords) win over the site-wide default
        // (docs/qa/ficha-tour.md hallazgo #4); other views keep the site default.
        $keywordsContent = ! empty($seoKeywords) ? $seoKeywords : ($settings['seo_default_keywords'] ?? null);
    @endphp
    @if ($keywordsContent)
        <meta name="keywords" content="{{ $keywordsContent }}">
    @endif
    @if ($googleVerify)
        <meta name="google-site-verification" content="{{ $googleVerify }}">
    @endif
    @if ($bingVerify)
        <meta name="msvalidate.01" content="{{ $bingVerify }}">
    @endif

    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="es"      href="{{ url('/es/' . $pathWithoutLocale) }}">
    <link rel="alternate" hreflang="en"      href="{{ url('/en/' . $pathWithoutLocale) }}">
    <link rel="alternate" hreflang="pt"      href="{{ url('/pt/' . $pathWithoutLocale) }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/es/' . $pathWithoutLocale) }}">

    {{-- GEO meta tags (solo si hay coordenadas configuradas) --}}
    @php
        $geoRegionCode = $settings['geo_region_code'] ?? 'PE-LIM';
        $geoPlacename  = ($settings['geo_city'] ?? 'Lima') . ', ' . ($settings['geo_country'] ?? 'PE');
        $geoLat        = $settings['geo_latitude']  ?? null;
        $geoLong       = $settings['geo_longitude'] ?? null;
    @endphp
    <meta name="geo.region"    content="{{ $geoRegionCode }}">
    <meta name="geo.placename" content="{{ $geoPlacename }}">
    @if ($geoLat && $geoLong)
        <meta name="geo.position" content="{{ $geoLat }};{{ $geoLong }}">
        <meta name="ICBM"         content="{{ $geoLat }}, {{ $geoLong }}">
    @endif

    <meta property="og:type" content="website">
    @php
        $ogLocaleMap = ['es' => 'es_PE', 'en' => 'en_US', 'pt' => 'pt_BR'];
        $ogLocale    = $ogLocaleMap[$locale] ?? 'es_PE';
    @endphp
    <meta property="og:locale" content="{{ $ogLocale }}">
    @foreach ($ogLocaleMap as $l => $ol)
        @if ($l !== $locale)
            <meta property="og:locale:alternate" content="{{ $ol }}">
        @endif
    @endforeach
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Albert+Sans:wght@300;400;500;600;700&family=Hedvig+Letters+Serif:opsz@12..24&family=Instrument+Serif:ital@0;1&family=Raleway:wght@400;500;600;700;800&family=Roboto:wght@400;500;700&display=swap">

    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">

    <x-jsonld />
    @stack('schema')

    {{--
        =====================================================================
        ANALYTICS — Google Consent Mode v2 + Facebook Pixel gating
        =====================================================================
        When $cookieBannerEnabled is true:
          - GTM/GA4 load with consent default = denied (Consent Mode v2).
            They fire only conversion/analytics events after the user grants
            consent via lvt-consent-granted or a stored 'granted' value.
          - Facebook Pixel is NOT initialized at all until consent is granted.

        When $cookieBannerEnabled is false (admin disabled the banner):
          - Everything loads unconditionally, exactly as before.

        Reference: https://developers.google.com/tag-platform/security/guides/consent
        =====================================================================
    --}}
    @if ($gtmId || $gaId)
        @if ($cookieBannerEnabled)
            {{-- Step 1: initialize dataLayer and set Consent Mode v2 DEFAULTS to denied
                 This must happen BEFORE the GTM/gtag scripts load. --}}
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                // Default: all consent types denied until user explicitly accepts
                gtag('consent', 'default', {
                    ad_storage:            'denied',
                    analytics_storage:     'denied',
                    ad_user_data:          'denied',
                    ad_personalization:    'denied',
                    wait_for_update:       500
                });
            </script>
        @else
            {{-- Banner disabled: initialize dataLayer without consent restrictions --}}
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
            </script>
        @endif

        {{-- Step 2: load GTM (it reads the consent state set above) --}}
        @if ($gtmId)
            <script>
                (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmId }}');
            </script>
        @endif

        {{-- Step 3: load GA4 (inherits consent state from dataLayer above) --}}
        @if ($gaId)
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
            <script>
                gtag('js', new Date());
                gtag('config', '{{ $gaId }}', { anonymize_ip: true });
            </script>
        @endif

        {{-- Step 4: consent UPDATE logic — runs on page load and on user accept event.
             When banner is enabled: update consent to 'granted' if already stored or
             when the user clicks Accept (lvt-consent-granted event).
             When banner is disabled: skip (already loaded without restriction). --}}
        @if ($cookieBannerEnabled)
            <script>
                (function () {
                    function grantConsent() {
                        gtag('consent', 'update', {
                            ad_storage:         'granted',
                            analytics_storage:  'granted',
                            ad_user_data:       'granted',
                            ad_personalization: 'granted'
                        });
                    }
                    // If user already accepted in a previous session, update immediately
                    if (localStorage.getItem('lvt_cookie_consent') === 'granted') {
                        grantConsent();
                    }
                    // Listen for Accept click fired by the cookie banner component
                    window.addEventListener('lvt-consent-granted', grantConsent);
                })();
            </script>
        @endif
    @endif

    {{--
        Facebook Pixel — no Consent Mode API; must NOT fire until consent is given.
        When banner is enabled: register a loader function and call it only on consent.
        When banner is disabled: load unconditionally as before.
    --}}
    @if ($fbPixel)
        @if ($cookieBannerEnabled)
            <script>
                (function () {
                    function loadFbPixel() {
                        if (window._fbPixelLoaded) return;
                        window._fbPixelLoaded = true;
                        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
                        fbq('init', '{{ $fbPixel }}');
                        fbq('track', 'PageView');
                    }
                    // Load immediately if consent already granted in a previous session
                    if (localStorage.getItem('lvt_cookie_consent') === 'granted') {
                        loadFbPixel();
                    }
                    // Load when the user accepts via the banner
                    window.addEventListener('lvt-consent-granted', loadFbPixel);
                })();
            </script>
        @else
            {{-- Banner disabled: load unconditionally --}}
            <script>
                !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '{{ $fbPixel }}');
                fbq('track', 'PageView');
            </script>
            <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $fbPixel }}&ev=PageView&noscript=1"/></noscript>
        @endif
    @endif

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-white">
    @if ($gtmId)
        {{-- GTM noscript fallback — only render when consent has been granted
             (or banner disabled). A noscript fallback without JS-based consent
             gating could set cookies without user action; rendering it here is
             acceptable because users without JS cannot interact with the banner
             either. Consent Mode v2 handles the JS path above. --}}
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[60] focus:bg-orange-500 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg">
        {{ __('nav.skip_to_content') }}
    </a>

    <x-header :variant="trim($__env->yieldContent('header_variant')) ?: 'solid'" />

    <main id="main" role="main">
        @yield('content')
    </main>

    <x-footer />

    {{-- Botón flotante WhatsApp — posición 100% inline para funcionar sin rebuild de Tailwind.
         Offset elevado (evita taparse con CTAs "en reposo": hero de Home, cards de Tours, etc.)
         + se encoge/atenúa mientras el usuario hace scroll para no tapar controles al pasar por encima.
         Sin fallback a otro número de WhatsApp: ver App\Models\Setting::whatsappNumber().
         Sin dato cargado en Configuración → Contacto, el FAB simplemente no se pinta. --}}
    @php $waNumber = \App\Models\Setting::whatsappNumber(); @endphp
    @if ($waNumber)
    <a href="https://wa.me/{{ $waNumber }}"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="WhatsApp"
       id="waFab"
       style="position:fixed;bottom:104px;right:18px;z-index:9000;width:52px;height:52px;border-radius:9999px;background-color:#25D366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(0,0,0,0.3);transition:transform .2s ease,opacity .2s ease;color:#fff;text-decoration:none;"
       onmouseover="this.style.transform='scale(1.1)'"
       onmouseout="this.style.transform='scale(1)'">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.51 5.26l-.999 3.648 3.978-1.045zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.148-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.017-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.247-.694.247-1.289.173-1.413z"/>
        </svg>
    </a>
    @endif
    <script>
    (function () {
        var fab = document.getElementById('waFab');
        if (!fab) return;
        var BASE_BOTTOM = 104; // px — despeja el CTA del hero de Home y las cards de Tours "en reposo"
        var SAFE_GAP = 16;
        var HERO_BP = 1024; // debe igualar $bp-lg (resources/scss/abstracts/_variables.scss):
                             // ahí es donde el hero de Home pasa de apilado a dos columnas.
        var hero = document.querySelector('.lat-hero');

        function rectsOverlap(a, b) {
            return !(a.right < b.left || a.left > b.right || a.bottom < b.top || a.top > b.bottom);
        }

        // Detección dirigida: solo evita solaparse con controles "tipo botón/CTA"
        // conocidos (no con cualquier enlace de texto, que siempre habría alguno
        // cerca). Cubre los casos reportados: CTA del hero, favorito de tarjetas,
        // filtros, botones de reserva/checkout y la barra sticky del carrito.
        // OJO al añadir clases: `.lat-btn` NO cubre `.lat-btn-out` ni
        // `.lat-btn-reservar` — son clases sueltas, no modificadores BEM del tipo
        // `lat-btn lat-btn--red` (esos sí los cubre `.lat-btn`). Omitir
        // `.lat-btn-out` dejaba el FAB encima del "Ver Detalles" de la primera
        // tarjeta de Tours Destacados en móvil, justo al terminar el hero
        // (bug QA 2026-07-27). Misma clase en el CTA de las ofertas.
        var CONTROL_SELECTOR = [
            '.lat-btn', '.lat-tcard__fav', '.lat-filter', '.lat-btn-reservar',
            '.lat-btn-out',
            '.cart-cta-btn', '.cart-sticky', '.cart-coupon-submit',
            'button[type="submit"]', '.btn--primary', '.tour-card a[href]:last-child'
        ].join(', ');

        // En mobile/tablet (<HERO_BP) el hero de Home apila foto + texto + tarjeta
        // de confianzas + buscador a pantalla casi completa. Entre el párrafo y la
        // tarjeta de confianzas NO hay banda vertical libre para un botón de 52px
        // (~24px de hueco real en 360×800, y varía por idioma/longitud del texto
        // del CMS): empujar el FAB dentro de ese hueco solo cambia QUÉ tapa, no SI
        // tapa (bug QA 2026-07-27 — .lat-hero-trust se había sumado antes al
        // CONTROL_SELECTOR para librar la tarjeta y el FAB aterrizó sobre el
        // párrafo). La tarjeta no es un CTA y no pertenece a ese selector: el
        // hueco se resuelve ocultando el FAB (fuera de pantalla, sin
        // pointer-events) mientras cualquier parte del hero siga en el viewport,
        // y restaurándolo apenas el hero termina de pasar.
        function withinMobileHero() {
            if (!hero || window.innerWidth >= HERO_BP) return false;
            var r = hero.getBoundingClientRect();
            return r.bottom > 0 && r.top < window.innerHeight;
        }

        // Iterativo (punto fijo), no de una sola pasada: empujar el FAB para librar
        // el primer control que lo tapa puede aterrizarlo sobre OTRO control que a
        // BASE_BOTTOM no se tocaban (bug QA 2026-07-27: en 1024, librar el botón
        // rojo "Buscar Tours" lo hacía aterrizar sobre "Ver video"). Se repite la
        // detección tras cada empuje hasta que no quede ningún solape o se agoten
        // los intentos (tope defensivo: nunca sigue empujando si una vuelta no
        // gana altura, para no entrar en bucle infinito).
        function avoidOverlap() {
            var bottom = BASE_BOTTOM;
            var maxBottom = window.innerHeight - 60; // deja siempre algo de FAB visible
            for (var iter = 0; iter < 8; iter++) {
                fab.style.bottom = bottom + 'px';
                var fabRect = fab.getBoundingClientRect();
                var nodes = document.querySelectorAll(CONTROL_SELECTOR);
                var highestTop = null;
                for (var i = 0; i < nodes.length; i++) {
                    var el = nodes[i];
                    if (el === fab || fab.contains(el) || el.contains(fab)) continue;
                    var r = el.getBoundingClientRect();
                    if (r.width === 0 || r.height === 0) continue;
                    if (rectsOverlap(fabRect, r)) {
                        if (highestTop === null || r.top < highestTop) highestTop = r.top;
                    }
                }
                if (highestTop === null) return; // sin solapes: queda donde está
                var needed = Math.round(window.innerHeight - highestTop + SAFE_GAP);
                if (needed <= bottom) return; // sin progreso posible: evita bucle
                bottom = Math.min(Math.max(BASE_BOTTOM, needed), maxBottom);
            }
            fab.style.bottom = bottom + 'px';
        }

        function updateVisibility() {
            if (withinMobileHero()) {
                // Se desplaza en X lo suficiente para que su borde izquierdo quede
                // más allá del ancho del viewport: así el rect del FAB no puede
                // solaparse con NINGÚN elemento de la página (la página no scrollea
                // horizontal — punto 3 de no-regresión), sin depender de cálculos
                // verticales frágiles ante texto de CMS más largo/corto por idioma.
                fab.style.opacity = '0';
                fab.style.transform = 'translateX(' + window.innerWidth + 'px)';
                fab.style.pointerEvents = 'none';
                fab.setAttribute('aria-hidden', 'true');
                fab.setAttribute('tabindex', '-1');
            } else {
                fab.style.pointerEvents = '';
                fab.style.opacity = '1';
                fab.style.transform = 'scale(1)';
                fab.removeAttribute('aria-hidden');
                fab.removeAttribute('tabindex');
                avoidOverlap();
            }
        }

        updateVisibility();
        window.addEventListener('resize', updateVisibility, { passive: true });

        // El alto de la página también cambia SIN que el usuario scrollee:
        // imágenes lazy que terminan de cargar, fuentes que hacen swap, texto del
        // CMS que reflota. Cuando eso pasa después de avoidOverlap(), el FAB queda
        // colocado contra un layout viejo y termina encima del CTA que se movió
        // (bug QA 2026-07-27: "Ver Detalles" de las tarjetas y "Leer más" del
        // blog, justo donde las imágenes cargan tarde). Recalcular al observar el
        // cambio de tamaño cubre los tres casos sin depender del scroll.
        if (typeof ResizeObserver === 'function') {
            var roTimer = null;
            new ResizeObserver(function () {
                clearTimeout(roTimer);
                roTimer = setTimeout(updateVisibility, 150);
            }).observe(document.body);
        }

        // Mientras el usuario hace scroll, el botón se atenúa y encoge para no tapar
        // CTAs/controles que queden justo debajo; vuelve a su tamaño normal (y
        // recalcula colisiones/visibilidad) al detenerse.
        var scrollTimer = null;
        window.addEventListener('scroll', function () {
            if (!withinMobileHero()) {
                fab.style.opacity = '.45';
                fab.style.transform = 'scale(.82)';
            }
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(updateVisibility, 220);
        }, { passive: true });
    })();
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js" defer></script>

    @stack('scripts')

    {{-- Cookie consent banner (shown when no prior decision + admin has it enabled) --}}
    @if ($cookieBannerEnabled)
        <x-cookie-banner />
    @endif
</body>
</html>
