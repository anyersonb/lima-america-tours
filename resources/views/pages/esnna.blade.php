@extends('layouts.app')

@section('title', $title . ' – Lima América Tours')
@section('description', __('legal.esnna_intro'))
@section('header_variant', 'solid')

{{-- ══════════════════════════════════════════════════════════════════════
     ESNNA — Código de conducta contra la explotación sexual de niñas, niños
     y adolescentes en el ámbito del turismo. Pedido del jefe el 2026-08-21
     por WhatsApp ("Y ESNNA también"), con la referencia de un competidor que
     lo enlaza en su footer.

     Misma estructura que pages/terms.blade.php y pages/privacy.blade.php:
     el texto vive en lang/{es,en,pt}/legal.php (no en la base) porque es
     texto legal nuestro, no contenido que la editora deba tocar por su
     cuenta. Si el asesor legal de la agencia lo revisa, se corrige ahí y
     salen los tres idiomas a la vez.

     El SELLO es lo único que sale del panel (Configuración → Contacto →
     Datos legales) y es opcional: la página se publica igual sin él. No se
     dibuja un sello propio — un sello oficial redibujado por nosotros sería
     una falsificación, no un placeholder.
     ══════════════════════════════════════════════════════════════════════ --}}

@php
    $esnnaSeal = \App\Support\ImagePath::homeImage(\App\Models\Setting::esnnaSealPath());
    $esnnaPoster = \App\Support\ImagePath::homeImage(\App\Models\Setting::esnnaPosterPath());
    $esnnaEmail = \App\Models\Setting::contactEmail();
    $esnnaPhone = \App\Models\Setting::contactPhone();
@endphp

@section('content')
<div class="lat-page">

    {{-- ── Hero interno de marca ── --}}
    <section class="lat-page-hero" style="background-image:url('{{ asset('assets/banners/banner-hero.jpg') }}')">
        <div class="lat-wrap">
            <nav class="lat-page-hero__crumb" aria-label="Breadcrumb">
                <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('ui.home') }}</a> &middot; <b>{{ $title }}</b>
            </nav>
            {{-- Fecha PROPIA, no la de Términos/Privacidad: esta página se
                 publicó el 2026-08-24 y `legal.last_updated` dice mayo. --}}
            <span class="lat-eyebrow">{{ __('legal.esnna_last_updated') }}</span>
            <h1>{{ $title }}</h1>
        </div>
    </section>

    <div class="lat-wrap" style="padding:56px 24px 80px;max-width:920px">

        {{-- El AFICHE va primero y a lo ancho: el pedido del jefe (2026-08-25,
             con la referencia de limaexperience) fue literal — "cuando damos
             clic debe abrir esto". El enlace del footer lleva acá, así que lo
             primero que se ve al llegar tiene que ser el afiche, no la bajada.
             Enlaza al archivo para poder abrirlo a pantalla completa: en el
             pie del afiche están las leyes y los teléfonos de denuncia, y a
             920px de ancho de columna esa letra no se lee. --}}
        @if ($esnnaPoster)
            <figure class="mb-10">
                <a href="{{ $esnnaPoster }}" target="_blank" rel="noopener"
                   class="block rounded-xl overflow-hidden ring-1 ring-black/10 hover:ring-black/25 transition">
                    <img src="{{ $esnnaPoster }}" alt="{{ __('legal.esnna_poster_alt') }}"
                         class="w-full h-auto block" width="1200" height="1689">
                </a>
                <figcaption class="mt-3 text-sm text-lat-muted">
                    {{ __('legal.esnna_poster_caption') }}
                </figcaption>
            </figure>
        @endif

        <p class="text-lat-ink leading-relaxed text-lg mb-10">{{ __('legal.esnna_intro') }}</p>

        @if ($esnnaSeal)
            <img src="{{ $esnnaSeal }}" alt="{{ __('legal.esnna_title') }}"
                 class="mb-10" style="max-width:220px;height:auto;display:block;">
        @endif

        {{-- 1: Compromiso --}}
        <section class="mb-10" aria-labelledby="esnna-s1">
            <h2 id="esnna-s1" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.esnna_s1_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.esnna_s1_body') }}</p>
        </section>

        {{-- 2: Qué es --}}
        <section class="mb-10" aria-labelledby="esnna-s2">
            <h2 id="esnna-s2" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.esnna_s2_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.esnna_s2_body') }}</p>
        </section>

        {{-- 3: Qué hacemos --}}
        <section class="mb-10" aria-labelledby="esnna-s3">
            <h2 id="esnna-s3" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.esnna_s3_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.esnna_s3_body') }}</p>
        </section>

        {{-- 4: Cómo denunciar. Los canales del Estado peruano van SIEMPRE
             (son públicos y no dependen de ningún dato del panel); los
             nuestros solo si están cargados. --}}
        <section class="mb-10" aria-labelledby="esnna-s4">
            <h2 id="esnna-s4" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.esnna_s4_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed mb-4">{{ __('legal.esnna_s4_intro') }}</p>
            <ul class="text-lat-ink-soft leading-relaxed" style="list-style:disc;padding-left:22px;display:grid;gap:10px;">
                <li>{{ __('legal.esnna_s4_line100') }}</li>
                <li>{{ __('legal.esnna_s4_police') }}</li>
                <li>
                    {{ __('legal.esnna_s4_us') }}
                    @if ($esnnaEmail)<a href="mailto:{{ $esnnaEmail }}" class="text-lat-red font-semibold">{{ $esnnaEmail }}</a>@endif@if ($esnnaEmail && $esnnaPhone) · @endif@if ($esnnaPhone)<a href="tel:{{ str_replace([' ', '+'], '', $esnnaPhone) }}" class="text-lat-red font-semibold">{{ $esnnaPhone }}</a>@endif
                </li>
            </ul>
        </section>

        {{-- 5: Marco legal --}}
        <section class="mb-10" aria-labelledby="esnna-s5">
            <h2 id="esnna-s5" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.esnna_s5_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.esnna_s5_body') }}</p>
        </section>

        {{-- Back link --}}
        <div class="mt-12 pt-8 border-t border-lat-line">
            <a href="{{ route('home', ['locale' => $locale]) }}"
               class="inline-flex items-center gap-2 text-lat-ink hover:text-lat-red font-medium transition-colors">
                <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                {{ __('common.back_home') }}
            </a>
        </div>
    </div>
</div>
@endsection
