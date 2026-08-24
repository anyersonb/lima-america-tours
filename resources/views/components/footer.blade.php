@php
    $locale = app()->getLocale();
    // Sin fallback a otro número: ver App\Models\Setting::contactPhone().
    // Si el cliente aún no lo cargó, el <li> de teléfono no se imprime.
    $contactPhone = \App\Models\Setting::contactPhone();
    $contactEmail = \App\Models\Setting::get('contact_email') ?: 'info@limaamericatours.com';
    // docs/qa/F7-personas.md §g #5: la dirección del panel (Configuración → Contacto)
    // se guardaba correctamente pero el footer ignoraba el Setting y mostraba el string
    // fijo de idioma __('footer.address'). SIN fallback a texto de idioma desde
    // 2026-08-11: esa dirección fija resultó ser la de OTRO cliente (Lima View
    // Tours) o una heredada del fork, ninguna confirmada — ver
    // App\Models\Setting::contactAddress(). Null = el <li> de abajo se oculta.
    $contactAddress = \App\Models\Setting::contactAddress($locale);
    // Mismo criterio: sin horario fijo de idioma. Ver Setting::contactHours().
    $contactHours = \App\Models\Setting::contactHours($locale);

    $sIg = \App\Models\Setting::get('social_instagram');
    $sFb = \App\Models\Setting::get('social_facebook');
    $sTk = \App\Models\Setting::get('social_tiktok');
    $sYt = \App\Models\Setting::get('social_youtube');
    $norm = fn ($u) => $u ? (\Illuminate\Support\Str::startsWith($u, ['http://', 'https://']) ? $u : 'https://' . ltrim($u, '/')) : null;

    // Fondo fotográfico del footer (fallback a la foto por defecto del sitio si no
    // hay banner propio). Pasa por ResponsiveImage: antes era el PNG de 2.52 MB en
    // crudo, descargado en TODAS las páginas del sitio para pintarlo detrás de un
    // velo oscuro.
    //
    // 640 y no 1024: es un fondo difuminado por el overlay, nadie le mira el
    // detalle, y así en móvil reutiliza EXACTAMENTE el archivo que el hero ya
    // descargó (misma URL, sale de caché y cuesta 0 KB). Medido: 139 KB → 60 KB
    // en desktop, gratis en móvil.
    $footBg = \App\Support\ResponsiveImage::defaultPhotoUrl(640);

    // Tours Populares: usa los tours reales destacados (mismo criterio que la home)
    try {
        $popularTours = \App\Models\Tour::published()->featured()->ordered()->limit(5)->get();
        if ($popularTours->isEmpty()) {
            $popularTours = \App\Models\Tour::published()->ordered()->limit(5)->get();
        }
    } catch (\Throwable $e) {
        $popularTours = collect();
    }

    // ── Enlaces que dependen de que HAYA contenido ───────────────────────────
    // Un enlace del footer que lleva a una pantalla de "Sin resultados" es un
    // enlace muerto: el visitante hace clic y recibe un mensaje de vacío en vez
    // de una sección. Estos dos se pintan solo cuando hay algo que mostrar, así
    // que reaparecen solos el día que el CMS tenga ese contenido (no hay que
    // acordarse de descomentar nada).
    //
    // "Free Tours" no es una sección propia: es la búsqueda ?q=free. Hoy no hay
    // ningún tour que la satisfaga (verificado: 0 resultados), así que el enlace
    // no se pinta.
    try {
        $hasFreeTours = \App\Models\Tour::published()->where(function ($q) {
            $q->where('title_es', 'like', '%free%')
                ->orWhere('title_en', 'like', '%free%')
                ->orWhere('description_es', 'like', '%free%');
        })->exists();
    } catch (\Throwable $e) {
        $hasFreeTours = false;
    }

    try {
        $hasBlogPosts = \App\Models\BlogPost::where('is_published', true)->exists();
    } catch (\Throwable $e) {
        $hasBlogPosts = false;
    }

    // home.blade.php avisa aquí (mismo $__env, compartido entre vistas de la
    // misma request) cuando ya pintó su propio bloque de newsletter (A5,
    // "Viaja. Explora. Vive.", antes del footer): en esa página este bloque
    // se omite para no dejar dos formularios del mismo canal (mismo
    // endpoint NewsletterController@subscribe) en la misma pantalla. En
    // cualquier otra página, $__env->shared(...) devuelve false y el
    // footer se ve exactamente igual que siempre.
    $hideFooterNewsletter = (bool) $__env->shared('lat_hide_footer_newsletter', false);

    // ── Franja de confianza (2026-08-21, los tres pedidos del jefe por
    // WhatsApp: RUC, "un logotipo que nos pide la municipalidad de Lima" y
    // "nuestro icono de Tripadvisor para reseñas").
    //
    // Los cuatro son datos del cliente y los cuatro tienen guard propio: sin
    // dato, la pieza no se pinta. Ninguno trae default — un RUC equivocado o
    // un rating inventado en el footer viajan a TODAS las páginas del sitio
    // (ver App\Models\Setting::companyRuc() y App\Support\TripadvisorBadge).
    $taBadge = \App\Support\TripadvisorBadge::data($locale);
    // Los sellos se guardan con FileUpload en el disco "media", igual que las
    // imágenes del home: se resuelven con ImagePath::homeImage(), NO con
    // ::url(), que apunta al disco "public".
    $registrySeal = \App\Support\ImagePath::homeImage(\App\Models\Setting::registrySealPath());
    $registrySealUrl = \App\Models\Setting::registrySealUrl();
    $esnnaSeal = \App\Support\ImagePath::homeImage(\App\Models\Setting::esnnaSealPath());
    $companyRuc = \App\Models\Setting::companyRuc();
@endphp
<footer class="lat-footer" role="contentinfo"
        style="background-image:linear-gradient(rgba(16,13,11,.9), rgba(16,13,11,.96)), url('{{ $footBg }}');">

    {{-- ══════════════════════════════════════════════════════════════════
         Footer de 5 columnas (2026-08-14, referencia que aprobó el cliente):
         marca · Enlaces · Experiencias · Síguenos · Suscríbete, y abajo la
         barra con copyright a la izquierda y legales a la derecha.

         Qué cambia respecto del anterior:
         - El newsletter deja de ser una BANDA de dos columnas grandes encima
           del footer y pasa a ser una columna más, como en la referencia. Sigue
           siendo el mismo endpoint, el mismo honeypot y el mismo reCAPTCHA;
           pide solo el correo porque `name` es nullable en
           NewsletterController::subscribe(). Conserva la clase
           `.lat-footer__newsletter`, que NO es decorativa: el script del FAB de
           WhatsApp (layouts/app.blade.php) la usa para apartarse de estos
           campos.
         - Las redes salen de la columna de marca y pasan a "Síguenos" con el
           nombre al lado del ícono: cuatro cuadraditos sueltos no dicen a
           dónde llevan, y la referencia los lista con nombre.
         - Los datos de contacto suben a la columna de marca (la referencia no
           tiene contacto en el footer, pero acá sí existe y es información
           útil que no se va a tirar por seguir una maqueta ajena).
         ══════════════════════════════════════════════════════════════════ --}}
    <div class="lat-wrap">
        <div class="lat-footer__grid">
            <section aria-labelledby="footer-brand" class="lat-footer__brand">
                <a href="{{ route('home', ['locale' => $locale]) }}" aria-label="Lima América Tours — {{ __('nav.home') }}">
                    <img src="{{ asset('assets/logos/logo-america-white.webp') }}" alt="Lima América Tours" class="lat-footer__logo">
                </a>
                <p id="footer-brand" class="lat-footer__desc">
                    {{ \App\Models\Setting::get('footer_about_' . $locale) ?: __('footer.brand_description') }}
                </p>
            </section>

            {{-- Los tres títulos de columna de este footer eran <h4> y producían un
                 salto H2→H4 en TODAS las páginas del sitio (el footer es compartido),
                 que el gate de regresión SEO marcó como bloqueante el 2026-08-12.
                 Son <h3> a propósito: no subirlos a <h2> (competirían con los títulos
                 de sección de cada página) ni devolverlos a <h4>. --}}
            <nav aria-labelledby="footer-links">
                <h3 id="footer-links">{{ __('footer.links') }}</h3>
                <div class="lat-footer__links">
                    {{--
                        Menú completo (2026-08-03), espejo del header. Inicio,
                        Nosotros, Tours, Contacto, Términos y Privacidad van
                        siempre. Free Tours y Blog dependen de su guard
                        ($hasFreeTours / $hasBlogPosts, arriba): sin free tours
                        publicados el primero enlaza a una búsqueda de 0
                        resultados, y sin entradas el segundo a un listado vacío.
                    --}}
                    <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('nav.home') }}</a>
                    <a href="{{ route('about', ['locale' => $locale]) }}">{{ __('footer.about_short') }}</a>
                    <a href="{{ route('tours.index', ['locale' => $locale]) }}">{{ __('nav.tours') }}</a>
                    @if ($hasFreeTours)
                        <a href="{{ route('tours.results', ['locale' => $locale, 'q' => 'free']) }}">{{ __('nav.free_tours') }}</a>
                    @endif
                    @if ($hasBlogPosts)
                        <a href="{{ route('blog.index', ['locale' => $locale]) }}">Blog</a>
                    @endif
                    <a href="{{ route('contact', ['locale' => $locale]) }}">{{ __('nav.contact') }}</a>
                    <a href="{{ route('legal.terms', ['locale' => $locale]) }}">{{ __('footer.terms') }}</a>
                    <a href="{{ route('legal.privacy', ['locale' => $locale]) }}">{{ __('footer.privacy') }}</a>
                </div>
            </nav>

            <section aria-labelledby="footer-tours">
                <h3 id="footer-tours">{{ __('footer.popular_tours') }}</h3>
                {{-- Con chevrón rojo delante de cada enlace, como la columna
                     "Experiencias" de la referencia. El ícono es decorativo
                     (aria-hidden): quien navegue con lector de pantalla oye el
                     título del tour, no una flecha por línea. --}}
                <div class="lat-footer__links lat-footer__links--arrow">
                    @forelse ($popularTours as $pt)
                        <a href="{{ route('tours.show', ['locale' => $locale, 'slug' => $pt->slug]) }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            <span>{{ $pt->title }}</span>
                        </a>
                    @empty
                        <a href="{{ route('tours.index', ['locale' => $locale]) }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            <span>{{ __('nav.tours') }}</span>
                        </a>
                    @endforelse
                    <a href="{{ route('tours.index', ['locale' => $locale]) }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        <span>{{ __('nav.all_tours') }}</span>
                    </a>
                </div>
            </section>

            {{-- "Síguenos": redes CON NOMBRE (referencia). La sección entera
                 desaparece si el cliente no cargó ninguna red — un título de
                 columna sobre el vacío se lee como una pantalla a medio
                 terminar. --}}
            @php
                $redes = array_filter([
                    ['url' => $norm($sFb), 'nombre' => 'Facebook',  'icono' => '<path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.7-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.2c-1.2 0-1.6.8-1.6 1.5V12h2.7l-.4 2.9h-2.3v7A10 10 0 0 0 22 12z"/>'],
                    ['url' => $norm($sIg), 'nombre' => 'Instagram', 'icono' => '<path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.3 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.3 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.3-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c.1-1.2.3-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4 1.3-.1 1.7-.1 4.8-.1zm0 5.3a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9zm0 7.4a2.9 2.9 0 1 1 0-5.8 2.9 2.9 0 0 1 0 5.8zm5.7-7.6a1 1 0 1 1-2.1 0 1 1 0 0 1 2.1 0z"/>'],
                    ['url' => $norm($sTk), 'nombre' => 'TikTok',    'icono' => '<path d="M16 3c.3 2.2 1.6 3.7 3.8 3.9v2.4c-1.3.1-2.5-.3-3.8-1v5.6c0 3.4-2.6 5.6-5.6 5.1-2.6-.4-4-2.1-4-4.6 0-2.9 2.6-4.9 5.6-4.3v2.5c-.4-.1-.9-.2-1.3-.1-1 .1-1.7.8-1.7 1.9 0 1.1.8 1.9 1.9 1.9 1.2 0 2-.9 2-2.1V3H16z"/>'],
                    ['url' => $norm($sYt), 'nombre' => 'YouTube',   'icono' => '<path d="M23 12s0-3.2-.4-4.7c-.2-.8-.9-1.5-1.7-1.7C19.4 5.2 12 5.2 12 5.2s-7.4 0-8.9.4c-.8.2-1.5.9-1.7 1.7C1 8.8 1 12 1 12s0 3.2.4 4.7c.2.8.9 1.5 1.7 1.7 1.5.4 8.9.4 8.9.4s7.4 0 8.9-.4c.8-.2 1.5-.9 1.7-1.7C23 15.2 23 12 23 12zM9.8 15.3V8.7l5.7 3.3-5.7 3.3z"/>'],
                ], fn ($r) => ! empty($r['url']));
            @endphp
            @if (! empty($redes))
                <section aria-labelledby="footer-social">
                    <h3 id="footer-social">{{ __('footer.follow_us') }}</h3>
                    <div class="lat-footer__social">
                        @foreach ($redes as $red)
                            <a href="{{ $red['url'] }}" target="_blank" rel="noopener">
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $red['icono'] !!}</svg>
                                <span>{{ $red['nombre'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Suscríbete: el mismo formulario de siempre (endpoint, honeypot y
                 reCAPTCHA incluidos), ahora como columna. En la home NO se pinta:
                 el bloque de cierre ya trae uno y no puede haber dos formularios
                 del mismo canal en la misma pantalla. --}}
            @unless ($hideFooterNewsletter)
                <section aria-labelledby="newsletter-title" class="lat-footer__newsletter">
                    <h3 id="newsletter-title">{{ __('footer.newsletter_cta') }}</h3>
                    <p class="lat-footer__news-sub">{{ __('footer.newsletter_title') }}</p>

                    @if (session('newsletter_success'))
                        <p class="lat-footer__news-flash lat-footer__news-flash--ok" role="status">{{ session('newsletter_success') }}</p>
                    @elseif ($errors->has('email') || $errors->has('name'))
                        <p class="lat-footer__news-flash lat-footer__news-flash--err" role="alert">{{ $errors->first('email') ?: $errors->first('name') }}</p>
                    @endif

                    <form action="{{ route('newsletter.subscribe') }}" method="post" id="form-newsletter">
                        @csrf
                        <input type="text" name="website" tabindex="-1" autocomplete="off"
                               style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                        <label class="lat-footer__news-field">
                            <span class="sr-only">{{ __('footer.newsletter_email') }}</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                            <input type="email" name="email" required placeholder="{{ __('footer.newsletter_email') }}">
                        </label>
                        @include('partials.recaptcha', ['recaptchaAction' => 'newsletter', 'recaptchaFormId' => 'form-newsletter'])
                        <button type="submit" class="lat-footer__news-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4z"/></svg>
                            {{ __('footer.newsletter_submit') }}
                        </button>
                    </form>
                </section>
            @endunless
        </div>

        {{-- Contacto: fila propia bajo las columnas. Antes era una de las cuatro
             columnas; con cinco no entra sin apretar todo, y como es información
             de una sola línea (teléfono · correo · horario) se lee mejor en
             horizontal que en pila. --}}
        <section aria-labelledby="footer-contact" class="lat-footer__contact-row">
            <h3 id="footer-contact" class="sr-only">{{ __('footer.locate_us') }}</h3>
            <address class="lat-footer__contact" style="font-style:normal;">
                    @if ($contactAddress)
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        <span>{{ $contactAddress }}</span>
                    </li>
                    @endif
                    @if ($contactPhone)
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                        <a href="tel:{{ str_replace([' ', '+'], '', $contactPhone) }}">{{ $contactPhone }}</a>
                    </li>
                    @endif
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                        <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                    </li>
                    @if ($contactHours)
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ $contactHours }}</span>
                    </li>
                    @endif
                </address>
        </section>

        {{-- ══════════════════════════════════════════════════════════════
             FRANJA DE CONFIANZA — reseñas de Tripadvisor + sellos oficiales.
             Pedido del jefe el 2026-08-21, con la referencia del footer de
             incatrilogytours (logo + calificación + "3,968 reseñas · #1 en
             Lima") y la del sello "Agencia de viajes y turismo registrada"
             de limaexperience.

             La sección entera desaparece si no hay ninguno de los tres: un
             titular de "Certificaciones" sobre el vacío se lee como una
             pantalla a medio terminar (mismo criterio que "Síguenos").
             ══════════════════════════════════════════════════════════════ --}}
        @if ($taBadge || $registrySeal || $esnnaSeal)
            <section class="lat-footer__trust" aria-labelledby="footer-trust">
                <h3 id="footer-trust" class="sr-only">{{ __('footer.trust_title') }}</h3>

                @if ($taBadge)
                    {{-- Las estrellas de Tripadvisor son sus círculos verdes,
                         no estrellas: medio círculo cuando el decimal cae
                         entre .25 y .74. Son decorativos (aria-hidden) — la
                         cifra va en texto y el enlace lleva su propio
                         aria-label con rating y cantidad, así que un lector
                         de pantalla no oye "imagen" cinco veces. --}}
                    <a class="lat-ta" href="{{ $taBadge['url'] }}" target="_blank" rel="noopener"
                       aria-label="{{ __('footer.tripadvisor_aria', ['rating' => $taBadge['rating_label'], 'count' => $taBadge['count_label']]) }}">
                        {{-- El búho va con los ojos DIBUJADOS (esclerótica
                             blanca + pupila), no como los dos huecos del path
                             que usa el ícono social de la topbar: a 30 px
                             dentro de la caja verde, ese path se lee como un
                             antifaz y no como un búho — se vio en la captura
                             a 1440, no en las mediciones. --}}
                        <span class="lat-ta__logo" aria-hidden="true">
                            <svg viewBox="0 0 32 32">
                                <path d="M16 7.5c-4.9 0-9.2 2.1-11.6 5.4a7.5 7.5 0 0 0 11.6 9.7 7.5 7.5 0 0 0 11.6-9.7C25.2 9.6 20.9 7.5 16 7.5z" fill="currentColor"/>
                                <circle cx="10.7" cy="17.2" r="4.3" fill="#fff"/>
                                <circle cx="10.7" cy="17.2" r="1.9" fill="currentColor"/>
                                <circle cx="21.3" cy="17.2" r="4.3" fill="#fff"/>
                                <circle cx="21.3" cy="17.2" r="1.9" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="lat-ta__body">
                            <b class="lat-ta__name">Tripadvisor</b>
                            <span class="lat-ta__row">
                                <span class="lat-ta__dots" aria-hidden="true">
                                    @for ($i = 1; $i <= 5; $i++)
                                        @php $fill = $taBadge['rating'] >= $i ? 'full' : ($taBadge['rating'] >= $i - 0.75 ? 'half' : 'empty'); @endphp
                                        <svg viewBox="0 0 20 20" class="lat-ta__dot lat-ta__dot--{{ $fill }}">
                                            <circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2.5"/>
                                            @if ($fill === 'full')
                                                <circle cx="10" cy="10" r="6" fill="currentColor"/>
                                            @elseif ($fill === 'half')
                                                <path d="M10 4a6 6 0 0 0 0 12z" fill="currentColor"/>
                                            @endif
                                        </svg>
                                    @endfor
                                </span>
                                <b class="lat-ta__rating">{{ $taBadge['rating_label'] }}</b>
                            </span>
                            <span class="lat-ta__meta">
                                {{ __('footer.tripadvisor_reviews', ['count' => $taBadge['count_label']]) }}@if ($taBadge['rank']) &middot; {{ $taBadge['rank'] }}@endif
                            </span>
                        </span>
                    </a>
                @endif

                @if ($registrySeal || $esnnaSeal)
                    {{-- Caja clara detrás de cada sello: los sellos oficiales
                         vienen con tinta oscura sobre transparente y sobre el
                         footer (fondo #100d0b) se perderían. La caja es la
                         misma solución que usa la referencia. --}}
                    <div class="lat-footer__seal-imgs">
                        @if ($registrySeal)
                            @if ($registrySealUrl)
                                <a href="{{ $registrySealUrl }}" target="_blank" rel="noopener" class="lat-footer__seal-img">
                                    <img src="{{ $registrySeal }}" alt="{{ __('footer.registry_seal_alt') }}" loading="lazy">
                                </a>
                            @else
                                <span class="lat-footer__seal-img">
                                    <img src="{{ $registrySeal }}" alt="{{ __('footer.registry_seal_alt') }}" loading="lazy">
                                </span>
                            @endif
                        @endif
                        @if ($esnnaSeal)
                            {{-- El sello de ESNNA enlaza a la página del
                                 código de conducta: un sello que no lleva a
                                 ninguna parte es solo un dibujo. --}}
                            <a href="{{ route('legal.esnna', ['locale' => $locale]) }}" class="lat-footer__seal-img">
                                <img src="{{ $esnnaSeal }}" alt="{{ __('footer.esnna_seal_alt') }}" loading="lazy">
                            </a>
                        @endif
                    </div>
                @endif
            </section>
        @endif

        {{-- Sellos de confianza. "Pago 100% Seguro" se quitó 2026-08-12: el
             alcance v1 no tiene pasarela de pago activa (ver
             docs/rebrand/LOTE-MOCKUPS-AGO-2026.md, Fix 2) — prometerlo era
             afirmar algo falso. Solo quedan los sellos que sí son ciertos. --}}
        <div class="lat-footer__seals">
            <span class="lat-footer__seal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4 12 22l-9-9V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5" fill="currentColor"/></svg>
                {{ __('footer.seal_best_price') }}
            </span>
            <span class="lat-footer__seal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                {{ __('footer.seal_responsible') }}
            </span>
        </div>

        {{-- Copyright --}}
        <div class="lat-footer__bottom">
            <span class="lat-footer__lang">
                🇵🇪 {{ ['es' => 'Español', 'en' => 'English', 'pt' => 'Português'][$locale] ?? 'Español' }}
            </span>
            <p>
                &copy; <time datetime="{{ now()->year }}">{{ now()->year }}</time> Lima América Tours &ndash; {{ __('common.rights_reserved') }}.
                {{-- RUC: pedido del jefe el 2026-08-21. Sale de
                     Configuración → Contacto → Datos legales y se oculta si
                     está vacío: en este repo llegaron a convivir DOS RUC
                     contradictorios publicados a la vez, así que sin
                     confirmación no se imprime ninguno. --}}
                @if ($companyRuc)
                    <span class="lat-footer__ruc">&middot; {{ __('footer.ruc_label') }} {{ $companyRuc }}</span>
                @endif
            </p>
            <div class="lat-footer__legal">
                <a href="{{ route('legal.terms', ['locale' => $locale]) }}">{{ __('footer.terms') }}</a>
                <a href="{{ route('legal.privacy', ['locale' => $locale]) }}">{{ __('footer.privacy') }}</a>
                {{-- ESNNA: el enlace va SIEMPRE (la página es texto nuestro y
                     existe con sello o sin él). Destacado como en la
                     referencia del jefe, que lo pinta distinto del resto. --}}
                <a href="{{ route('legal.esnna', ['locale' => $locale]) }}" class="lat-footer__legal-esnna">{{ __('footer.esnna') }}</a>
            </div>
        </div>
    </div>
</footer>
