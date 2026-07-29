@php
    $locale = app()->getLocale();
    // Sin fallback a otro número/otra cuenta de WhatsApp: ver
    // App\Models\Setting::contactPhone() / whatsappNumber(). Los bloques
    // de teléfono y los botones de WhatsApp de este header se ocultan con
    // @if cuando el cliente todavía no cargó el dato real.
    $contactPhone = \App\Models\Setting::contactPhone();
    $contactEmail = \App\Models\Setting::get('contact_email') ?: 'info@limaamericatours.com';
    $whatsapp     = \App\Models\Setting::whatsappNumber();

    $sFb  = \App\Models\Setting::get('social_facebook');
    $sIg  = \App\Models\Setting::get('social_instagram');
    $sTk  = \App\Models\Setting::get('social_tiktok');
    $sYt  = \App\Models\Setting::get('social_youtube');
    $sTa  = \App\Models\Setting::get('social_tripadvisor');
    $norm = fn ($u) => $u ? (\Illuminate\Support\Str::startsWith($u, ['http://', 'https://']) ? $u : 'https://' . ltrim($u, '/')) : null;

    // Menú reducido por decisión del jefe (2026-07-29): solo Inicio, Nosotros y
    // Tours, porque esas otras secciones no se van a mostrar por ahora.
    //
    // Los 4 ítems retirados quedan COMENTADOS, no borrados: las rutas, las vistas
    // y las traducciones siguen existiendo y funcionando (/blog, /contacto y las
    // páginas siguen respondiendo si se entra por URL directa, y el footer las
    // sigue enlazando). Volver a mostrar cualquiera es descomentar su línea.
    // Esta lista alimenta el nav de escritorio Y el drawer de móvil: no hay que
    // tocar dos sitios.
    $navItems = [
        ['label' => __('nav.home'),       'url' => route('home', ['locale' => $locale]),                          'active' => request()->routeIs('home')],
        ['label' => __('nav.about'),      'url' => route('about', ['locale' => $locale]),                         'active' => request()->routeIs('about')],
        ['label' => __('nav.tours'),      'url' => route('tours.index', ['locale' => $locale]),                   'active' => request()->routeIs('tours.index', 'tours.category', 'tours.show')],
        // ['label' => __('nav.free_tours'), 'url' => route('tours.results', ['locale' => $locale, 'q' => 'free']),  'active' => request()->routeIs('tours.results') && request('q') === 'free'],
        // ['label' => __('nav.services'),   'url' => route('home', ['locale' => $locale]) . '#servicios',            'active' => false],
        // ['label' => 'Blog',               'url' => route('blog.index', ['locale' => $locale]),                    'active' => request()->routeIs('blog.index', 'blog.show')],
        // ['label' => __('nav.contact'),    'url' => route('contact', ['locale' => $locale]),                       'active' => request()->routeIs('contact')],
    ];
@endphp

<div class="lat-topbar">
    <div class="lat-wrap">
        <div class="lat-topbar__left">
            @if ($whatsapp && $contactPhone)
            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
                {{ $contactPhone }}
            </a>
            @endif
            <a class="lat-topbar__email" href="mailto:{{ $contactEmail }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                {{ $contactEmail }}
            </a>
        </div>
        <div class="lat-topbar__right">
            <div class="lat-topbar__socials">
                @if ($u = $norm($sFb))<a href="{{ $u }}" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.7-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.2c-1.2 0-1.6.8-1.6 1.5V12h2.7l-.4 2.9h-2.3v7A10 10 0 0 0 22 12z"/></svg></a>@endif
                @if ($u = $norm($sIg))<a href="{{ $u }}" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1" fill="currentColor" stroke="none"/></svg></a>@endif
                @if ($u = $norm($sTk))<a href="{{ $u }}" target="_blank" rel="noopener" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 3c.3 2.2 1.6 3.7 3.8 3.9v2.4c-1.3.1-2.5-.3-3.8-1v5.6c0 3.4-2.6 5.6-5.6 5.1-2.6-.4-4-2.1-4-4.6 0-2.9 2.6-4.9 5.6-4.3v2.5c-.4-.1-.9-.2-1.3-.1-1 .1-1.7.8-1.7 1.9 0 1.1.8 1.9 1.9 1.9 1.2 0 2-.9 2-2.1V3H16z"/></svg></a>@endif
                @if ($u = $norm($sYt))<a href="{{ $u }}" target="_blank" rel="noopener" aria-label="YouTube"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23 12s0-3.2-.4-4.7c-.2-.8-.9-1.5-1.7-1.7C19.4 5.2 12 5.2 12 5.2s-7.4 0-8.9.4c-.8.2-1.5.9-1.7 1.7C1 8.8 1 12 1 12s0 3.2.4 4.7c.2.8.9 1.5 1.7 1.7 1.5.4 8.9.4 8.9.4s7.4 0 8.9-.4c.8-.2 1.5-.9 1.7-1.7C23 15.2 23 12 23 12zM9.8 15.3V8.7l5.7 3.3-5.7 3.3z"/></svg></a>@endif
            </div>
            @if ($u = $norm($sTa))
                <a href="{{ $u }}" target="_blank" rel="noopener" aria-label="TripAdvisor">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 8.5c-2.9 0-5.6.9-7.9 2.4H0l1.9 2.1a4.6 4.6 0 1 0 7.5 5 4.6 4.6 0 0 0 5.2 0 4.6 4.6 0 1 0 7.5-5L24 10.9h-4.1A14 14 0 0 0 12 8.5zm-4.5 9.9a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5zm9 0a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5zM7.5 11.4a4.6 4.6 0 0 0-4 2.3 4.6 4.6 0 0 0-4-2.3 12 12 0 0 1 8 0zm4.5.6a4.6 4.6 0 0 0-2 .5 12 12 0 0 0-4-1.4A12 12 0 0 1 12 10c1.9 0 3.8.4 5.5 1.1a12 12 0 0 0-4 1.4 4.6 4.6 0 0 0-1.5-.5z"/></svg>
                </a>
            @endif
            <x-lang-switcher />
        </div>
    </div>
</div>

<header class="lat-header-shell" role="banner"
        x-data="{ open: false }"
        x-effect="document.body.classList.toggle('lat-drawer-open', open)"
        @keydown.escape.window="open = false">
    <nav class="lat-navbar" aria-label="{{ __('nav.main_navigation') }}">
        <a href="{{ route('home', ['locale' => $locale]) }}" class="lat-brand" aria-label="Lima América Tours — {{ __('nav.home') }}">
            <img src="{{ asset('assets/logos/logo-america-original.webp') }}" alt="Lima América Tours" width="190" height="42" draggable="false">
        </a>

        <div class="lat-nav-links">
            @foreach ($navItems as $item)
                <a href="{{ $item['url'] }}" @class(['is-active' => $item['active']])>{{ $item['label'] }}</a>
            @endforeach
        </div>

        <div class="lat-nav-cta">
            {{-- "Reservar Ahora" lleva al catálogo de tours, no a WhatsApp
                 (decisión del jefe, 2026-07-29). Además es coherente con
                 docs/pagos/PLAN-PASARELAS.md: WhatsApp es soporte, la reserva se
                 cierra en el sitio. Ya no depende de que haya número cargado. --}}
            <a class="lat-btn-reservar" href="{{ route('tours.index', ['locale' => $locale]) }}">
                {{ __('nav.reservar_ahora') }}
            </a>
            <button type="button"
                    class="lat-burger"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    aria-controls="lat-drawer"
                    :aria-label="open ? '{{ __('nav.close_menu') }}' : '{{ __('nav.open_menu') }}'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path x-show="!open" d="M3 6h18M3 12h18M3 18h18"/>
                    <path x-show="open" x-cloak d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </nav>

    {{-- Backdrop --}}
    <div x-show="open" x-cloak x-transition.opacity
         class="lat-drawer-backdrop"
         @click="open = false" aria-hidden="true"></div>

    {{-- Drawer --}}
    <div id="lat-drawer"
         x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="lat-drawer"
         role="dialog" aria-modal="true"
         aria-label="{{ __('nav.main_menu') }}">

        <div class="lat-drawer__head">
            <a href="{{ route('home', ['locale' => $locale]) }}" class="lat-drawer__brand" @click="open = false" aria-label="Lima América Tours — {{ __('nav.home') }}">
                <img src="{{ asset('assets/logos/logo-america-original.webp') }}" alt="Lima América Tours" width="160" height="34" draggable="false">
            </a>
            <button type="button" class="lat-drawer__close" @click="open = false" aria-label="{{ __('nav.close_menu') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form class="lat-drawer__search" method="GET" action="{{ route('tours.results', ['locale' => $locale]) }}" role="search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input type="search" name="q" placeholder="{{ __('ui.search_tours_placeholder') }}" aria-label="{{ __('ui.search') }}">
        </form>

        <nav class="lat-drawer__nav" aria-label="{{ __('nav.main_navigation') }}">
            @foreach ($navItems as $item)
                <a href="{{ $item['url'] }}" @click="open = false">{{ $item['label'] }}</a>
            @endforeach
        </nav>

        @if ($whatsapp)
        <a class="lat-drawer__wa" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.6.2-.2.3-.7.9-.9 1.1-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.4-1.5-.9-.8-1.5-1.8-1.6-2.1-.2-.3 0-.4.1-.6l.5-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.2-.6-1.5-.9-2-.2-.5-.4-.5-.6-.5h-.5c-.2 0-.5.1-.7.3-.3.3-1 .9-1 2.3s1 2.7 1.2 2.9c.1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3zM12 2a10 10 0 0 0-8.6 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2z"/></svg>
            {{ __('nav.book_whatsapp') }}
        </a>
        @endif

        <div class="lat-drawer__foot">
            @if ($contactPhone)
            <a href="tel:{{ str_replace([' ', '+'], '', $contactPhone) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                {{ $contactPhone }}
            </a>
            @endif
            <x-lang-switcher />
        </div>
    </div>
</header>
