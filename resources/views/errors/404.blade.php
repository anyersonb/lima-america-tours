@php
    $locale = app()->getLocale();
    $L = fn (string $es, string $en, string $pt): string => $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);
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
                <div class="text-right">
                    <p class="font-display text-2xl">{{ $L('Síguenos', 'Follow us', 'Siga-nos') }}</p>
                    <ul class="mt-3 inline-flex items-center gap-4">
                        @foreach (['Instagram','Facebook','TikTok','Vimeo'] as $sn)
                            <li><a href="#" aria-label="{{ $sn }}" class="hover:text-lat-red transition"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg></a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
