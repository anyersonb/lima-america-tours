@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string => $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);

    // Redes reales tomadas de Settings (mismas que el footer/topbar); solo se
    // pintan las que tienen URL. Antes eran círculos placeholder con href="#".
    $norm = fn ($u) => $u ? (\Illuminate\Support\Str::startsWith($u, ['http://', 'https://']) ? $u : 'https://' . ltrim($u, '/')) : null;
    $socials = array_filter([
        'facebook'  => $norm(\App\Models\Setting::get('social_facebook')),
        'instagram' => $norm(\App\Models\Setting::get('social_instagram')),
        'tiktok'    => $norm(\App\Models\Setting::get('social_tiktok')),
        'youtube'   => $norm(\App\Models\Setting::get('social_youtube')),
    ]);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — {{ __('seo.site_name') }}</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Albert+Sans:wght@300;400;500;600;700&family=Hedvig+Letters+Serif:opsz@12..24&family=Instrument+Serif:ital@0;1&display=swap">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="font-sans">
    <main class="min-h-screen flex flex-col">
        {{-- ── mitad superior: foto + logo + numeral 404 ── --}}
        <section class="relative flex-1 min-h-[55vh] text-white">
            <img src="{{ asset('assets/banners/banner-hero.jpg') }}" alt="" class="absolute inset-0 w-full h-full object-cover" loading="eager" fetchpriority="high">
            <div class="absolute inset-0 bg-black/55"></div>
            <div class="relative h-full container mx-auto px-5 lg:px-10 py-10 flex flex-col items-center justify-between gap-10 text-center">
                <a href="{{ route('home', ['locale' => $locale]) }}" class="inline-flex items-center gap-2">
                    <img src="{{ asset('assets/logos/logo-america-white.webp') }}" alt="Lima América Tours" class="h-12 w-auto">
                </a>
                <p class="font-display text-[110px] sm:text-[160px] lg:text-[200px] leading-none">404</p>
            </div>
        </section>

        {{-- ── mitad inferior: mensaje real + CTAs + follow us ── --}}
        <section class="bg-lat-ink text-white py-14 lg:py-20">
            <div class="container mx-auto px-5 lg:px-10 grid gap-8 lg:grid-cols-[1fr_auto] items-end">
                <div>
                    <h1 class="font-display text-4xl md:text-5xl lg:text-6xl leading-tight">
                        {{ $L('Página no encontrada', 'Page not found', 'Página não encontrada') }}
                    </h1>
                    <p class="mt-4 max-w-xl text-sm md:text-base text-white/85 leading-relaxed">
                        {{ $L(
                            'La página que buscas no existe o fue movida. Vuelve al inicio o descubre nuestros tours en Lima, Ica y Cusco.',
                            "The page you're looking for doesn't exist or was moved. Head back home or discover our tours in Lima, Ica and Cusco.",
                            'A página que você procura não existe ou foi movida. Volte ao início ou descubra nossos tours em Lima, Ica e Cusco.'
                        ) }}
                    </p>
                    <div class="mt-7 flex flex-wrap gap-4">
                        <a href="{{ route('home', ['locale' => $locale]) }}" class="lat-btn lat-btn--white">
                            {{ $L('Volver al inicio', 'Back to home', 'Voltar ao início') }}
                        </a>
                        <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-btn lat-btn--outline-white">
                            {{ $L('Ver tours', 'View tours', 'Ver tours') }}
                        </a>
                    </div>
                </div>
                @if (count($socials))
                    <div class="text-right">
                        <p class="font-display text-2xl">{{ $L('Síguenos', 'Follow us', 'Siga-nos') }}</p>
                        <ul class="mt-3 inline-flex items-center gap-3">
                            @if ($u = ($socials['facebook'] ?? null))
                                <li><a href="{{ $u }}" target="_blank" rel="noopener" aria-label="Facebook" class="grid w-10 h-10 rounded-xl bg-white/10 place-items-center text-white hover:bg-lat-red transition"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.7-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.2c-1.2 0-1.6.8-1.6 1.5V12h2.7l-.4 2.9h-2.3v7A10 10 0 0 0 22 12z"/></svg></a></li>
                            @endif
                            @if ($u = ($socials['instagram'] ?? null))
                                <li><a href="{{ $u }}" target="_blank" rel="noopener" aria-label="Instagram" class="grid w-10 h-10 rounded-xl bg-white/10 place-items-center text-white hover:bg-lat-red transition"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1" fill="currentColor" stroke="none"/></svg></a></li>
                            @endif
                            @if ($u = ($socials['tiktok'] ?? null))
                                <li><a href="{{ $u }}" target="_blank" rel="noopener" aria-label="TikTok" class="grid w-10 h-10 rounded-xl bg-white/10 place-items-center text-white hover:bg-lat-red transition"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 3c.3 2.2 1.6 3.7 3.8 3.9v2.4c-1.3.1-2.5-.3-3.8-1v5.6c0 3.4-2.6 5.6-5.6 5.1-2.6-.4-4-2.1-4-4.6 0-2.9 2.6-4.9 5.6-4.3v2.5c-.4-.1-.9-.2-1.3-.1-1 .1-1.7.8-1.7 1.9 0 1.1.8 1.9 1.9 1.9 1.2 0 2-.9 2-2.1V3H16z"/></svg></a></li>
                            @endif
                            @if ($u = ($socials['youtube'] ?? null))
                                <li><a href="{{ $u }}" target="_blank" rel="noopener" aria-label="YouTube" class="grid w-10 h-10 rounded-xl bg-white/10 place-items-center text-white hover:bg-lat-red transition"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23 12s0-3.2-.4-4.7c-.2-.8-.9-1.5-1.7-1.7C19.4 5.2 12 5.2 12 5.2s-7.4 0-8.9.4c-.8.2-1.5.9-1.7 1.7C1 8.8 1 12 1 12s0 3.2.4 4.7c.2.8.9 1.5 1.7 1.7 1.5.4 8.9.4 8.9.4s7.4 0 8.9-.4c.8-.2 1.5-.9 1.7-1.7C23 15.2 23 12 23 12zM9.8 15.3V8.7l5.7 3.3-5.7 3.3z"/></svg></a></li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
