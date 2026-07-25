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
    $mvvItems = $b['mvv'] ?? $defaultMvv;

    // ── Banda de estadísticas — datos reales cuando existen ───────────────
    $foundedYear = (int) (\App\Models\Setting::get('company_founded_year') ?: 2015);
    $yearsInBusiness = max(1, (int) date('Y') - $foundedYear);

    $toursCount = 0;
    try { $toursCount = \App\Models\Tour::published()->count(); } catch (\Throwable $e) { $toursCount = 0; }

    $happyTravelers = (int) (\App\Models\Setting::get('happy_travelers_count') ?: 5000);
    $valuesCount = count($mvvItems[2]['tags'] ?? []) ?: 8;
@endphp

@section('content')
<div class="lat-page">

    {{-- ============================================================
         HERO INTERNO — imagen de fondo + degradado rojo/oscuro
         ============================================================ --}}
    <section class="lat-page-hero" style="background-image:url('{{ $mediaUrl($b['img_hero'] ?? null) ?? asset('assets/banners/Rectangle 19215.jpg') }}')">
        <div class="lat-wrap">
            <nav class="lat-page-hero__crumb" aria-label="Breadcrumb">
                <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('ui.home') }}</a> &middot; <b>{{ __('nav.about') }}</b>
            </nav>
            <span class="lat-eyebrow">{{ $bl('hero_eyebrow', 'Nuestro equipo', 'Our team', 'Nossa equipe') }}</span>
            <h1>{{ $bl('hero_title', 'Somos Lima América Tours', 'We are Lima América Tours', 'Somos Lima América Tours') }}</h1>
            <p class="lat-page-hero__sub">
                {{ $bl('hero_lead',
                    'Una agencia limeña fundada por emprendedores apasionados por el turismo, con la misión de mostrarle al mundo la riqueza del Perú.',
                    'A Lima-based agency founded by entrepreneurs passionate about tourism, on a mission to show the world the richness of Peru.',
                    'Uma agência limenha fundada por empreendedores apaixonados por turismo, com a missão de mostrar ao mundo a riqueza do Peru.'
                ) }}
            </p>
        </div>
    </section>

    {{-- ============================================================
         SPLIT — imagen + "10 años mostrando lo mejor del Perú"
         ============================================================ --}}
    <section class="lat-wrap" style="padding:64px 24px" aria-labelledby="about-split-title">
        <div class="lat-split">
            <div class="lat-split__media">
                <img src="{{ $mediaUrl($b['img_split'] ?? null) ?? asset('assets/banners/Rectangle 19216.jpg') }}"
                     alt="{{ $L('Malecón de Miraflores al atardecer', 'Miraflores boardwalk at sunset', 'Calçadão de Miraflores ao entardecer') }}"
                     loading="lazy" width="640" height="544">
                <div class="lat-split__badge">
                    <b>10+</b>
                    <span>{{ $L('años en el mercado turístico', 'years in the tourism market', 'anos no mercado turístico') }}</span>
                </div>
            </div>

            <div class="lat-split__body">
                <span class="lat-eyebrow">{{ $bl('split_eyebrow', 'Agencia de turismo', 'Tourism agency', 'Agência de turismo') }}</span>
                <h2 id="about-split-title" class="lat-split-title">
                    {{ $bl('split_heading', '10 años mostrando lo mejor del Perú', '10 years showing the best of Peru', '10 anos mostrando o melhor do Peru') }}
                </h2>

                <p>
                    {{ $bl('split_body_1',
                        'Bienvenidos a Lima América Tours, una agencia de viajes fundada por emprendedores limeños apasionados por el turismo. Con más de 10 años de experiencia en el sector, nuestro propósito es compartir contigo las maravillas y experiencias únicas que ofrece el Perú.',
                        'Welcome to Lima América Tours, a travel agency founded by entrepreneurs from Lima passionate about tourism. With more than 10 years of experience in the sector, our purpose is to share with you the wonders and unique experiences that Peru offers.',
                        'Bem-vindo à Lima América Tours, uma agência de viagens fundada por empreendedores limenhos apaixonados por turismo. Com mais de 10 anos de experiência no setor, nosso propósito é compartilhar com você as maravilhas e experiências únicas que o Peru oferece.'
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
         MISIÓN, VISIÓN Y VALORES
         ============================================================ --}}
    <section class="lat-wrap" style="padding:20px 24px 70px" aria-labelledby="about-mvv-title">
        <div class="lat-sec-head">
            <span class="lat-eyebrow is-center">{{ $L('Lo que nos mueve', 'What drives us', 'O que nos move') }}</span>
            <h2 id="about-mvv-title">{{ $L('Misión, visión y valores', 'Mission, vision and values', 'Missão, visão e valores') }}</h2>
        </div>

        <div class="lat-mvv">
            @foreach ($mvvItems as $item)
                <div class="lat-mvv-card">
                    <div class="lat-mvv-card__ic">
                        @switch($item['icon'] ?? 'target')
                            @case('eye')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                @break
                            @case('heart')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                                @break
                            @default
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1" fill="currentColor"/></svg>
                        @endswitch
                    </div>

                    <h3>{{ $item['title_' . $locale] ?? $item['title_es'] ?? '' }}</h3>

                    @if (! empty($item['tags']))
                        @php $tagList = $item['tags_' . $locale] ?? $item['tags'] ?? []; @endphp
                        <div class="lat-mvv-card__tags">
                            @foreach ($tagList as $tag)
                                <span class="lat-mvv-tag">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @else
                        <p>{{ $item['desc_' . $locale] ?? $item['desc_es'] ?? '' }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================================================
         BANDA DE ESTADÍSTICAS — "Miles de viajeros ya confiaron en nosotros"
         ============================================================ --}}
    <section class="lat-stats-band" aria-labelledby="about-stats-title">
        <div class="lat-wrap lat-stats-band__grid">
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
                @php $waNumber = \App\Models\Setting::get('whatsapp') ?: '51925886725'; @endphp
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener noreferrer" class="lat-btn lat-btn--wa">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" width="18" height="18"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24z"/></svg>
                    {{ $L('Habla con nosotros', 'Talk to us', 'Fale conosco') }}
                </a>
            </div>

            <div class="lat-stat-tiles" data-stats-tiles>
                <div class="lat-stat-tile">
                    <strong data-count-to="{{ $yearsInBusiness }}">0</strong>
                    <span>{{ $L('Años de experiencia', 'Years of experience', 'Anos de experiência') }}</span>
                </div>
                <div class="lat-stat-tile">
                    <strong data-count-to="{{ $toursCount ?: 25 }}">0</strong>
                    <span>{{ $L('Tours & experiencias', 'Tours & experiences', 'Tours & experiências') }}</span>
                </div>
                <div class="lat-stat-tile">
                    <strong data-count-to="{{ $happyTravelers }}" data-suffix="+">0</strong>
                    <span>{{ $L('Viajeros felices', 'Happy travelers', 'Viajantes felizes') }}</span>
                </div>
                <div class="lat-stat-tile">
                    <strong data-count-to="{{ $valuesCount }}">0</strong>
                    <span>{{ $L('Valores que nos guían', 'Values that guide us', 'Valores que nos guiam') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         CTA FINAL — "Empecemos a planear"
         ============================================================ --}}
    <section class="lat-cta-final" aria-labelledby="about-cta-title">
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
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener noreferrer" class="lat-btn lat-btn--wa">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" width="18" height="18"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24z"/></svg>
                    {{ $L('Escríbenos por WhatsApp', 'Message us on WhatsApp', 'Escreva-nos pelo WhatsApp') }}
                </a>
                <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-btn lat-btn--white">
                    {{ $L('Ver tours', 'View tours', 'Ver tours') }}
                </a>
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
