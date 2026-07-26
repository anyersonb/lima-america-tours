@extends('layouts.app')

@section('title', __('nav.contact') . ' — ' . __('seo.site_name'))
@section('description', __('seo.contact_description'))

@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string => $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);

    // Datos reales del CMS (Configuración → Contacto), con fallback solo si el
    // admin no ha cargado nada todavía. Mismo patrón que footer/otras páginas.
    $contactPhone          = \App\Models\Setting::get('contact_phone') ?: '+51 925 886 725';
    $contactPhoneSecondary = \App\Models\Setting::get('contact_phone_secondary');
    $contactEmail          = \App\Models\Setting::get('contact_email') ?: 'hola@limaamericatours.com';
    $contactHours          = \App\Models\Setting::get('contact_hours_' . $locale)
        ?: \App\Models\Setting::get('contact_hours_es')
        ?: $L('Lunes a domingo · 9:30 a.m. – 7:00 p.m.', 'Monday to Sunday · 9:30 a.m. – 7:00 p.m.', 'Segunda a domingo · 9:30 – 19:00');
    $waPhoneDigits = preg_replace('/\D/', '', $contactPhone);

    // Blocks from CMS (Page model, slug "contacto") — igual patrón que about.blade.php.
    // Nota: el mockup aprobado (docs/propuesta/exports/lat-07-contacto.jpeg) confirma un
    // hero plano SIN imagen ni collage; los campos blocks.img_hero/img_collage_1..4 del
    // admin no tienen sección viva en este diseño (ver docs/qa/contacto.md). Solo se
    // cablean aquí los 9 campos de texto del hero (eyebrow/título H1/lead ×3 idiomas).
    $page ??= null;
    $b = $page?->blocks ?? [];

    $bl = function (string $key, string $fallback) use ($b, $locale): string {
        $val = trim((string) ($b[$key . '_' . $locale] ?? ''));
        return $val !== '' ? $val : $fallback;
    };
@endphp

@section('content')

{{-- ── HERO plano (sin imagen) — replica lat-07-contacto.jpeg ── --}}
<section class="lat-flat-hero" aria-labelledby="contact-hero-title">
    <div class="lat-wrap">
        <nav class="lat-flat-hero__crumb" aria-label="Breadcrumb">
            <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('ui.home') }}</a> &middot;
            <b style="color:inherit">{{ __('ui.contact_us') }}</b>
        </nav>
        <span class="lat-eyebrow">{{ $bl('hero_eyebrow', $L('ESTAMOS PARA AYUDARTE', 'WE ARE HERE TO HELP', 'ESTAMOS AQUI PARA AJUDAR')) }}</span>
        <h1 id="contact-hero-title">{{ $bl('hero_title', __('ui.contact_us')) }}</h1>
        <p class="lat-flat-hero__sub">
            {{ $bl('hero_lead', $L(
                '¿Tienes dudas o quieres armar un tour a tu medida? Escríbenos y te respondemos el mismo día.',
                'Have questions or want a custom-made tour? Write to us and we will reply the same day.',
                'Tem dúvidas ou quer montar um tour sob medida? Escreva para nós e respondemos no mesmo dia.'
            )) }}
        </p>
    </div>
</section>

{{-- ── Formulario + canales de contacto ── --}}
<section class="bg-lat-paper" style="background:#faf7f2; padding:56px 0;" aria-labelledby="contact-form-title">
    <div class="lat-wrap">
        <div class="lat-contact-grid">

            {{-- ── Columna izquierda: formulario ── --}}
            <div class="lat-contact-card">
                <div class="lat-contact-card__head">
                    <div class="lat-contact-icon-circle" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                    </div>
                    <div>
                        <h2 id="contact-form-title" class="lat-contact-card__title">{{ $L('Envíanos un mensaje', 'Send us a message', 'Envie-nos uma mensagem') }}</h2>
                        <p class="lat-contact-card__desc">{{ $L('Completa el formulario y te contactamos a la brevedad.', 'Fill out the form and we will get back to you shortly.', 'Preencha o formulário e entraremos em contato em breve.') }}</p>
                    </div>
                </div>

                @if (session('success'))
                    <div role="alert" class="mb-5 bg-state-success/10 border border-state-success/30 text-state-success rounded-xl px-5 py-3 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div role="alert" class="mb-5 bg-state-error/10 border border-state-error/30 text-state-error rounded-xl px-5 py-3 text-sm">
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
                                       placeholder="{{ $L('Tu nombre', 'Your name', 'Seu nome') }}"
                                       value="{{ old('nombre') }}"
                                       class="lat-contact-input">
                            </div>
                            <div class="lat-contact-field">
                                <label for="contact_celular">{{ __('ui.contact_phone') }}</label>
                                <input type="tel" id="contact_celular" name="celular"
                                       autocomplete="tel"
                                       placeholder="{{ __('ui.contact_phone_placeholder') }}"
                                       value="{{ old('celular') }}"
                                       class="lat-contact-input">
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
                            <label for="contact_asunto">{{ $L('Asunto', 'Subject', 'Assunto') }} <span style="color:#6f6a63;font-weight:400;text-transform:none;">({{ __('ui.optional') }})</span></label>
                            <input type="text" id="contact_asunto" name="asunto"
                                   placeholder="{{ $L('¿En qué te ayudamos?', 'How can we help?', 'Como podemos ajudar?') }}"
                                   value="{{ old('asunto') }}"
                                   maxlength="200"
                                   class="lat-contact-input">
                        </div>

                        <div class="lat-contact-field">
                            <label for="contact_mensaje">{{ __('ui.contact_message') }} <span class="req">*</span></label>
                            <textarea id="contact_mensaje" name="mensaje" required rows="4"
                                      placeholder="{{ $L('Cuéntanos qué tour te interesa o tu consulta', 'Tell us which tour you are interested in or your query', 'Conte-nos qual tour te interessa ou sua dúvida') }}"
                                      class="lat-contact-textarea">{{ old('mensaje') }}</textarea>
                        </div>

                        <label class="flex items-start gap-2.5 text-xs" style="color:#6f6a63;cursor:pointer;">
                            <input type="checkbox" required
                                   class="mt-0.5 w-4 h-4 rounded shrink-0"
                                   style="accent-color:#cb101e;">
                            <span>
                                {{ __('ui.contact_privacy_accept') }}
                                <a href="{{ route('legal.privacy', ['locale' => $locale]) }}" style="color:#cb101e;text-decoration:underline;text-underline-offset:2px;">{{ __('footer.privacy') }}</a>
                                {{ __('ui.contact_privacy_and_terms') }}
                                <a href="{{ route('legal.terms', ['locale' => $locale]) }}" style="color:#cb101e;text-decoration:underline;text-underline-offset:2px;">{{ __('footer.terms') }}</a>
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

                <div class="lat-contact-card">
                    <div class="lat-contact-channel">
                        <div class="lat-contact-icon-circle" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                        </div>
                        <div>
                            <h3>{{ $L('Teléfono / WhatsApp', 'Phone / WhatsApp', 'Telefone / WhatsApp') }}</h3>
                            <a href="tel:{{ $waPhoneDigits }}">{{ $contactPhone }}</a>
                            @if (!empty($contactPhoneSecondary))
                                <a href="tel:{{ preg_replace('/\D/', '', $contactPhoneSecondary) }}">{{ $contactPhoneSecondary }}</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="lat-contact-card">
                    <div class="lat-contact-channel">
                        <div class="lat-contact-icon-circle" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        </div>
                        <div>
                            <h3>{{ $L('Correo', 'Email', 'E-mail') }}</h3>
                            <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                        </div>
                    </div>
                </div>

                <div class="lat-contact-card">
                    <div class="lat-contact-channel">
                        <div class="lat-contact-icon-circle" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3>{{ $L('Horario de atención', 'Business hours', 'Horário de atendimento') }}</h3>
                            <p>{{ $contactHours }}</p>
                        </div>
                    </div>
                </div>

                <a href="https://wa.me/{{ $waPhoneDigits }}" target="_blank" rel="noopener" class="lat-contact-wa-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                    {{ $L('Escríbenos por WhatsApp', 'Write to us on WhatsApp', 'Escreva para nós no WhatsApp') }}
                </a>

                <div class="lat-contact-card">
                    <div class="lat-contact-channel">
                        <div class="lat-contact-icon-circle" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        </div>
                        <div>
                            <h3>{{ $L('Punto de recojo', 'Pickup point', 'Ponto de encontro') }}</h3>
                            <p class="lat-contact-pickup-note">
                                {{ $L(
                                    'Coordinamos el punto de recojo o encuentro al confirmar tu reserva, según el tour y tu ubicación en Lima.',
                                    'We coordinate the pickup or meeting point once your booking is confirmed, based on the tour and your location in Lima.',
                                    'Coordenamos o ponto de encontro ao confirmar sua reserva, de acordo com o tour e sua localização em Lima.'
                                ) }}
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
