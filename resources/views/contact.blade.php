@extends('layouts.app')

@section('title', __('nav.contact') . ' — ' . __('seo.site_name'))
@section('description', __('seo.contact_description'))
@section('header_variant', 'solid')

@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string => $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);

    // Datos reales del CMS (Configuración → Contacto). Sin fallback a un
    // teléfono/WhatsApp ajeno: ver App\Models\Setting::contactPhone() /
    // whatsappNumber(). Si el cliente no cargó el dato, el bloque
    // correspondiente se oculta con @if más abajo.
    $contactPhone          = \App\Models\Setting::contactPhone();
    $contactPhoneSecondary = \App\Models\Setting::get('contact_phone_secondary');
    $contactEmail          = \App\Models\Setting::get('contact_email') ?: 'hola@limaamericatours.com';
    // SIN horario inventado ("Lunes a domingo 9:30-7pm" no es un dato real:
    // era un CUARTO horario distinto, además de los 3 que ya convivían en
    // footer.php/ui.php/legal.php — ver App\Models\Setting::contactHours()).
    // Null = la tarjeta "Horario de atención" de más abajo se oculta.
    $contactHours = \App\Models\Setting::contactHours($locale);
    $waPhoneDigits = \App\Models\Setting::whatsappNumber();

    // Blocks from CMS (Page model, slug "contacto") — mismo patrón que about.blade.php.
    $page ??= null;
    $b = $page?->blocks ?? [];

    $bl = function (string $key, string $fallback) use ($b, $locale): string {
        $val = trim((string) ($b[$key . '_' . $locale] ?? ''));
        return $val !== '' ? $val : $fallback;
    };

    $mediaUrl = function ($path): ?string {
        if (is_array($path)) { $path = $path[0] ?? ''; }
        $path = trim((string) $path);
        return ($path !== '' && $path !== '[]' && $path !== '""')
            ? \Illuminate\Support\Facades\Storage::disk('media')->url($path)
            : null;
    };

    // Hero fotográfico — editable en Configuración → Página "contacto" →
    // "Hero (fondo superior)" (blocks.img_hero, ya existía en el admin desde
    // el diseño hero-plano anterior). El fallback NO es Rectangle 19216.jpg:
    // ese archivo (como el resto de "Rectangle 192XX.jpg" del kit de mockup)
    // mide 205×123px reales — estirado a todo el ancho del hero se ve borroso
    // y pixelado (hallazgo CRO 2026-08-11). En su lugar, una foto REAL y en
    // alta resolución (1920×848) ya importada de Lima América Tours: el Faro
    // de Miraflores al atardecer — no está asignada como cover de ningún tour
    // hoy, así que no se duplica en otra pantalla del sitio.
    $heroImgUrl = $mediaUrl($b['img_hero'] ?? null)
        ?: \App\Support\ImagePath::url('tours/2024-04-Lima-America-Tours-2.webp');
    $heroImgAlt = $bl('hero_image_alt', $L(
        'Faro de Miraflores y malecón de Lima al atardecer',
        "Miraflores lighthouse and Lima's coastal boardwalk at sunset",
        'Farol de Miraflores e orla de Lima ao entardecer'
    ));

    // Card de asesor — el mockup trae la Montaña de 7 Colores (Vinicunca,
    // Cusco): la empresa no vende tours ahí, así que esa foto no va (ver
    // LOTE-MOCKUPS-AGO-2026.md, tabla "Lo que NO se publica"). En su lugar,
    // una foto REAL de un tour propio: el tour destacado (o el primero
    // publicado si no hay ninguno marcado), tomada de la misma fuente que ya
    // sirve las fichas de tour — nunca una ruta fija que pueda quedar rota.
    $advisorTour = \App\Models\Tour::published()->featured()->orderBy('order')->first()
        ?? \App\Models\Tour::published()->orderBy('order')->first();

    // Franja inferior de social proof — SOLO el agregado real de reseñas
    // (misma fuente que /resenas y el home, ReviewAggregator::overallStats).
    // Sin "Más de 5,000 viajeros felices" ni avatares de gente que no es
    // cliente (tabla "Lo que NO se publica"): si no hay reseñas, ese bloque
    // se oculta con @if, no se inventa un número.
    $reviewStats = app(\App\Services\ReviewAggregator::class)->overallStats($locale);
@endphp

@section('content')
<div class="lat-contact-page">

    {{-- ============================================================
         HERO fotográfico oscuro — eyebrow / H1 / lead / 3 chips
         ============================================================ --}}
    <section class="lat-contact-hero" aria-labelledby="contact-hero-title">
        <div class="lat-contact-hero__bg">
            <img src="{{ $heroImgUrl }}" alt="{{ $heroImgAlt }}" loading="eager" fetchpriority="high" decoding="async">
            <span class="lat-contact-hero__scrim" aria-hidden="true"></span>
        </div>

        <div class="lat-contact-hero__content lat-wrap">
            <nav class="lat-contact-hero__crumb" aria-label="Breadcrumb">
                <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('ui.home') }}</a> &middot;
                <b>{{ __('ui.contact_us') }}</b>
            </nav>

            <span class="lat-eyebrow lat-eyebrow--on-dark">{{ $bl('hero_eyebrow', $L('ESTAMOS PARA AYUDARTE', 'WE ARE HERE TO HELP', 'ESTAMOS AQUI PARA AJUDAR')) }}</span>

            <h1 id="contact-hero-title" class="lat-contact-hero__title">{{ $bl('hero_title', __('ui.contact_us')) }}</h1>

            <p class="lat-contact-hero__sub">
                {{ $bl('hero_lead', $L(
                    '¿Tienes dudas o quieres armar un tour a tu medida? Escríbenos y te respondemos a la brevedad.',
                    'Have questions or want a custom-made tour? Write to us and we will get back to you shortly.',
                    'Tem dúvidas ou quer montar um tour sob medida? Escreva para nós e responderemos em breve.'
                )) }}
            </p>

            {{-- 3 chips de confianza — título + bajada editables en
                 Configuración → Página "contacto" → "Hero — foto y 3 chips".
                 Los íconos son fijos (sin slot en el panel; sin promesa
                 horaria tipo "menos de 24h": el negocio no la sostiene hoy). --}}
            <ul class="lat-contact-chips">
                <li class="lat-contact-chips__item">
                    <span class="lat-contact-chips__ic" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <span class="lat-contact-chips__text">
                        <strong>{{ $bl('chip1_title', $L('Respuesta rápida', 'Fast response', 'Resposta rápida')) }}</strong>
                        <span>{{ $bl('chip1_desc', $L('Te respondemos a la brevedad', 'We reply to you shortly', 'Respondemos rapidamente')) }}</span>
                    </span>
                </li>
                <li class="lat-contact-chips__item">
                    <span class="lat-contact-chips__ic" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l1.5 1.5 3.75-3.75M12 21c-4.5-1.5-8.25-5.25-8.25-11.25V5.25L12 2.25l8.25 3v4.5C20.25 15.75 16.5 19.5 12 21z"/></svg>
                    </span>
                    <span class="lat-contact-chips__text">
                        <strong>{{ $bl('chip2_title', $L('Atención personalizada', 'Personalized service', 'Atendimento personalizado')) }}</strong>
                        <span>{{ $bl('chip2_desc', $L('Te ayudamos a crear la mejor experiencia', 'We help you build the best experience', 'Ajudamos você a criar a melhor experiência')) }}</span>
                    </span>
                </li>
                <li class="lat-contact-chips__item">
                    <span class="lat-contact-chips__ic" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                    </span>
                    <span class="lat-contact-chips__text">
                        <strong>{{ $bl('chip3_title', $L('Viaja con confianza', 'Travel with confidence', 'Viaje com confiança')) }}</strong>
                        <span>{{ $bl('chip3_desc', $L('Seguridad y respaldo garantizado', 'Guaranteed safety and support', 'Segurança e suporte garantidos')) }}</span>
                    </span>
                </li>
            </ul>
        </div>
    </section>

    {{-- ============================================================
         Formulario + canales de contacto — franja oscura
         ============================================================ --}}
    <section class="lat-contact-main" aria-labelledby="contact-form-title">
        <div class="lat-wrap">
            <div class="lat-contact-grid">

                {{-- ── Columna izquierda: formulario ── --}}
                <div class="lat-contact-card lat-contact-card--form">
                    <div class="lat-contact-card__head">
                        <div class="lat-contact-icon-sq" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        </div>
                        <div>
                            <h2 id="contact-form-title" class="lat-contact-card__title">{{ $bl('form_title', $L('Envíanos un mensaje', 'Send us a message', 'Envie-nos uma mensagem')) }}</h2>
                            <p class="lat-contact-card__desc">{{ $bl('form_desc', $L('Completa el formulario y te contactamos a la brevedad.', 'Fill out the form and we will get back to you shortly.', 'Preencha o formulário e entraremos em contato em breve.')) }}</p>
                        </div>
                    </div>

                    @if (session('success'))
                        <div role="alert" class="lat-contact-alert lat-contact-alert--ok">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div role="alert" class="lat-contact-alert lat-contact-alert--err">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('contact.submit', ['locale' => $locale]) }}" method="post"
                          id="form-contact"
                          novalidate>
                        @csrf
                        {{-- Honeypot --}}
                        <input type="text" name="website" tabindex="-1" autocomplete="off"
                               style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;"
                               aria-hidden="true">

                        <div class="lat-contact-form-grid">
                            <div class="lat-contact-two">
                                <div class="lat-contact-field">
                                    <label for="contact_nombre">{{ __('ui.contact_name') }} <span class="req">*</span></label>
                                    <input type="text" id="contact_nombre" name="nombre" required
                                           autocomplete="given-name"
                                           placeholder="{{ $bl('ph_nombre', $L('Ej. María López', 'E.g. Maria Lopez', 'Ex. Maria Lopez')) }}"
                                           value="{{ old('nombre') }}"
                                           class="lat-contact-input">
                                </div>
                                <div class="lat-contact-field">
                                    <label for="contact_celular">{{ __('ui.contact_phone') }}</label>
                                    {{-- Selector de país + input de celular. El proyecto no tiene
                                         ninguna librería de teléfono internacional (se revisó antes
                                         de tocar esto): en vez de sumar una dependencia nueva solo
                                         para un negocio de un único país, es un <select> nativo real
                                         (accesible por teclado) con Perú como única opción hoy. No
                                         lleva "name" que pise la validación existente: el campo que
                                         SÍ se envía y valida sigue siendo "celular", intacto. --}}
                                    <div class="lat-contact-phone">
                                        <label class="lat-contact-phone__country">
                                            <span class="sr-only">{{ $L('País', 'Country', 'País') }}</span>
                                            <span class="lat-contact-phone__flag" aria-hidden="true">🇵🇪</span>
                                            <select aria-label="{{ $L('Código de país', 'Country code', 'Código do país') }}">
                                                <option value="PE" selected>+51</option>
                                            </select>
                                        </label>
                                        <input type="tel" id="contact_celular" name="celular"
                                               autocomplete="tel"
                                               placeholder="{{ __('ui.contact_phone_placeholder') }}"
                                               value="{{ old('celular') }}"
                                               class="lat-contact-input lat-contact-input--phone">
                                    </div>
                                </div>
                            </div>

                            <div class="lat-contact-field">
                                <label for="contact_email">{{ __('customer.email') }} <span class="req">*</span></label>
                                <input type="email" id="contact_email" name="email" required
                                       autocomplete="email"
                                       placeholder="{{ __('ui.contact_email_placeholder') }}"
                                       value="{{ old('email') }}"
                                       class="lat-contact-input">
                            </div>

                            <div class="lat-contact-field">
                                <label for="contact_asunto">{{ $L('Asunto', 'Subject', 'Assunto') }} <span class="lat-contact-field__opt">({{ __('ui.optional') }})</span></label>
                                <input type="text" id="contact_asunto" name="asunto"
                                       placeholder="{{ $bl('ph_asunto', $L('¿En qué podemos ayudarte?', 'How can we help?', 'Como podemos ajudar?')) }}"
                                       value="{{ old('asunto') }}"
                                       maxlength="200"
                                       class="lat-contact-input">
                            </div>

                            <div class="lat-contact-field">
                                <label for="contact_mensaje">{{ __('ui.contact_message') }} <span class="req">*</span></label>
                                <textarea id="contact_mensaje" name="mensaje" required rows="4"
                                          placeholder="{{ $bl('ph_mensaje', $L('Cuéntanos tu plan de viaje, fechas, número de personas, intereses, etc.', 'Tell us about your trip plan, dates, number of people, interests, etc.', 'Conte-nos seu plano de viagem, datas, número de pessoas, interesses, etc.')) }}"
                                          class="lat-contact-textarea">{{ old('mensaje') }}</textarea>
                            </div>

                            <label class="lat-contact-legal">
                                <input type="checkbox" required class="lat-contact-legal__check">
                                <span>
                                    {{ __('ui.contact_privacy_accept') }}
                                    <a href="{{ route('legal.privacy', ['locale' => $locale]) }}">{{ __('footer.privacy') }}</a>
                                    {{ __('ui.contact_privacy_and_terms') }}
                                    <a href="{{ route('legal.terms', ['locale' => $locale]) }}">{{ __('footer.terms') }}</a>
                                </span>
                            </label>

                            @include('partials.recaptcha', ['recaptchaAction' => 'contact', 'recaptchaFormId' => 'form-contact'])

                            <button type="submit" class="lat-contact-submit">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                                {{ __('ui.contact_send') }}
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ── Columna derecha: canales de contacto ── --}}
                <div class="lat-contact-channels">

                    @if ($contactPhone || !empty($contactPhoneSecondary))
                    <div class="lat-contact-card lat-contact-card--sm">
                        <div class="lat-contact-channel">
                            <div class="lat-contact-icon-circle" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                            </div>
                            <div>
                                <h3>{{ $bl('channel_phone_label', $L('Teléfono / WhatsApp', 'Phone / WhatsApp', 'Telefone / WhatsApp')) }}</h3>
                                @if ($contactPhone)
                                    <a href="tel:{{ preg_replace('/\D/', '', $contactPhone) }}">{{ $contactPhone }}</a>
                                @endif
                                @if (!empty($contactPhoneSecondary))
                                    <a href="tel:{{ preg_replace('/\D/', '', $contactPhoneSecondary) }}">{{ $contactPhoneSecondary }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="lat-contact-card lat-contact-card--sm">
                        <div class="lat-contact-channel">
                            <div class="lat-contact-icon-circle" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                            </div>
                            <div>
                                <h3>{{ $bl('channel_email_label', $L('Correo', 'Email', 'E-mail')) }}</h3>
                                <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                            </div>
                        </div>
                    </div>

                    @if ($contactHours)
                    <div class="lat-contact-card lat-contact-card--sm">
                        <div class="lat-contact-channel">
                            <div class="lat-contact-icon-circle" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3>{{ $bl('channel_hours_label', $L('Horario de atención', 'Business hours', 'Horário de atendimento')) }}</h3>
                                <p>{{ $contactHours }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="lat-contact-card lat-contact-card--sm">
                        <div class="lat-contact-channel">
                            <div class="lat-contact-icon-circle" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            </div>
                            <div>
                                <h3>{{ $bl('channel_pickup_label', $L('Punto de recojo', 'Pickup point', 'Ponto de encontro')) }}</h3>
                                <p class="lat-contact-pickup-note">
                                    {{ $bl('channel_pickup_note', $L(
                                        'Coordinamos el punto de recojo o encuentro al confirmar tu reserva, según el tour y tu ubicación en Lima.',
                                        'We coordinate the pickup or meeting point once your booking is confirmed, based on the tour and your location in Lima.',
                                        'Coordenamos o ponto de encontro ao confirmar sua reserva, de acordo com o tour e sua localização em Lima.'
                                    )) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Card de asesor — foto de un tour REAL (nunca Vinicunca/Cusco:
                         la empresa no opera ahí). Se oculta sola si algún día no hay
                         ningún tour publicado, en vez de romper con una imagen rota. --}}
                    @if ($advisorTour)
                    <div class="lat-contact-advisor">
                        <img src="{{ $advisorTour->cover_url }}" alt="" loading="lazy" decoding="async">
                        <div class="lat-contact-advisor__panel">
                            <div class="lat-contact-icon-sq lat-contact-icon-sq--sm" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
                            </div>
                            <p class="lat-contact-advisor__title">{{ $bl('advisor_title', $L('¿Necesitas ayuda para elegir tu tour?', 'Need help choosing your tour?', 'Precisa de ajuda para escolher seu tour?')) }}</p>
                            <p class="lat-contact-advisor__desc">{{ $bl('advisor_desc', $L('Nuestros asesores te ayudarán a crear una experiencia a tu medida.', 'Our advisors will help you create a tailor-made experience.', 'Nossos consultores vão ajudar você a criar uma experiência sob medida.')) }}</p>
                            @if ($waPhoneDigits)
                                <a href="https://wa.me/{{ $waPhoneDigits }}" target="_blank" rel="noopener" class="lat-contact-wa-btn">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                                    {{ $bl('advisor_cta', $L('Hablar con un asesor', 'Talk to an advisor', 'Falar com um consultor')) }}
                                </a>
                            @endif
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         Franja inferior — social proof REAL (sin cifras inventadas
         ni avatares de gente que no es cliente)
         ============================================================ --}}
    <section class="lat-contact-social" aria-label="{{ $L('Confianza de nuestros viajeros', 'Trust from our travelers', 'Confiança dos nossos viajantes') }}">
        <div class="lat-wrap lat-contact-social__inner">
            <div class="lat-contact-social__left">
                <h2>{{ $bl('bottom_title', $L('¿Listo para tu próxima aventura?', 'Ready for your next adventure?', 'Pronto para sua próxima aventura?')) }}</h2>
                <p>{{ $bl('bottom_desc', $L('Escríbenos y armamos juntos la experiencia perfecta para ti.', 'Write to us and let’s build the perfect experience together.', 'Escreva para nós e vamos montar juntos a experiência perfeita para você.')) }}</p>
            </div>
            <div class="lat-contact-social__right">
                @if ($reviewStats['count'] > 0)
                    <div class="lat-contact-social__rating">
                        <span class="lat-contact-social__stars" aria-hidden="true">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg viewBox="0 0 20 20" fill="{{ $i <= round($reviewStats['rating']) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1"><path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.9l-5.2 2.61.99-5.79-4.21-4.1 5.82-.85z"/></svg>
                            @endfor
                        </span>
                        <span class="lat-contact-social__rate">{{ number_format($reviewStats['rating'], 1) }}</span>
                        <span class="lat-contact-social__count">
                            {{ str_replace(':n', (string) $reviewStats['count'], $L(':n opiniones', ':n reviews', ':n avaliações')) }}
                        </span>
                    </div>
                @endif
                <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-contact-btn-outline">
                    {{ $bl('bottom_cta', $L('Ver tours populares', 'See popular tours', 'Ver tours populares')) }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
        </div>
    </section>

</div>
@endsection
