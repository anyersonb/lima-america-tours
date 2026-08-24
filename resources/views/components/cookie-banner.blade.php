@php
    /**
     * Cookie consent banner component.
     *
     * Reads $cookieBannerEnabled and $cookieTexts from the parent layout.
     * Uses inline styles throughout — Tailwind purge removes arbitrary classes
     * in this project, so we follow the same pattern as the WhatsApp button.
     */
    $locale = app()->getLocale();

    // Text resolution: admin-configured value → lang fallback
    $bannerText = match ($locale) {
        'en' => \App\Models\Setting::get('cookie_text_en') ?: __('common.cookie_text'),
        'pt' => \App\Models\Setting::get('cookie_text_pt') ?: __('common.cookie_text'),
        default => \App\Models\Setting::get('cookie_text_es') ?: __('common.cookie_text'),
    };

    $privacyUrl = route('legal.privacy', ['locale' => $locale]);
@endphp

{{--
    x-cloak hides the element until Alpine boots (rule already in <head>: [x-cloak]{display:none!important})
    The component is only rendered when the banner is enabled (checked in layouts/app.blade.php).
--}}
<div
    x-data="{
        show: false,
        init() {
            const stored = localStorage.getItem('lvt_cookie_consent');
            // Show only when no decision has been made yet
            if (!stored) {
                this.show = true;
            }
        },
        accept() {
            localStorage.setItem('lvt_cookie_consent', 'granted');
            window.dispatchEvent(new Event('lvt-consent-granted'));
            this.show = false;
        },
        reject() {
            localStorage.setItem('lvt_cookie_consent', 'denied');
            this.show = false;
        }
    }"
    {{-- La barra es `fixed` y tapaba los últimos ~68 px del documento, que es
         donde está la barra de legales del footer: con la barra abierta,
         Términos, Privacidad y Código de conducta ESNNA eran INALCANZABLES al
         clic (medido con elementFromPoint en staging, 2026-08-24) justo en la
         primera visita. Mientras se muestra, empuja el <body> por su alto
         REAL — no una constante: el texto lo carga el CMS y en móvil ocupa
         dos o tres líneas. Al aceptar o rechazar se limpia solo.
         `$nextTick` es necesario porque en el momento del efecto la barra
         todavía no tiene alto (x-show acaba de mostrarla). Y el `show;` suelto
         del principio NO es residuo: Alpine registra las dependencias del
         efecto solo durante su ejecución SÍNCRONA, así que leyendo `show`
         únicamente dentro del $nextTick el efecto no se re-ejecutaba nunca y
         el padding quedaba puesto para siempre — un hueco de 68 px al final
         del documento después de aceptar. Medido en local antes y después. --}}
    x-effect="show; $nextTick(() => { document.body.style.paddingBottom = show ? $el.offsetHeight + 'px' : '' })"
    x-show="show"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    role="dialog"
    aria-live="polite"
    aria-label="{{ __('common.cookie_accept') }}"
    style="
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 9500;
        background-color: #171412;
        color: #fff;
        padding: 16px 20px;
        box-shadow: 0 -4px 24px rgba(0,0,0,0.25);
    ">

    <div style="
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
    ">
        {{-- Banner text --}}
        <p style="
            flex: 1 1 300px;
            margin: 0;
            font-size: 0.875rem;
            line-height: 1.5;
            color: #e2e8f0;
        ">
            {{ $bannerText }}
            <a href="{{ $privacyUrl }}"
               style="color: #ff6b6b; text-decoration: underline; white-space: nowrap; margin-left: 4px;"
               aria-label="{{ __('common.cookie_privacy') }}">
                {{ __('common.cookie_privacy') }}
            </a>
        </p>

        {{-- Action buttons --}}
        <div style="
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            flex-shrink: 0;
        ">
            {{-- Reject button (outline) --}}
            <button
                type="button"
                @click="reject()"
                style="
                    padding: 8px 20px;
                    border-radius: 6px;
                    border: 1.5px solid rgba(255,255,255,0.55);
                    background: transparent;
                    color: #fff;
                    font-size: 0.875rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: background 0.15s ease, border-color 0.15s ease;
                    white-space: nowrap;
                "
                onmouseover="this.style.background='rgba(255,255,255,0.12)';this.style.borderColor='#fff'"
                onmouseout="this.style.background='transparent';this.style.borderColor='rgba(255,255,255,0.55)'">
                {{ __('common.cookie_reject') }}
            </button>

            {{-- Accept button (brand orange) --}}
            <button
                type="button"
                @click="accept()"
                style="
                    padding: 8px 22px;
                    border-radius: 6px;
                    border: none;
                    background-color: #cb101e;
                    color: #fff;
                    font-size: 0.875rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: background 0.15s ease, transform 0.1s ease;
                    white-space: nowrap;
                "
                onmouseover="this.style.background='#9d0c16'"
                onmouseout="this.style.background='#cb101e'">
                {{ __('common.cookie_accept') }}
            </button>
        </div>
    </div>
</div>
