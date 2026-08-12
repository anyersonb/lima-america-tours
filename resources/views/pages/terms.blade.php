@extends('layouts.app')

@section('title', $title . ' – Lima América Tours')
@section('description', __('legal.last_updated'))
@section('header_variant', 'solid')

@php
    // Fuente única para teléfono/horario/dirección en el texto legal — ver
    // App\Models\Setting::contactPhone()/contactHours()/contactAddress().
    // Cada línea de la sección 5 solo se imprime si el dato existe.
    $legalPhone = \App\Models\Setting::contactPhone();
    $legalHours = \App\Models\Setting::contactHours($locale);
    $legalAddress = \App\Models\Setting::contactAddress($locale);
@endphp

@section('content')
<div class="lat-page">

    {{-- ── Hero interno de marca ── --}}
    <section class="lat-page-hero" style="background-image:url('{{ asset('assets/banners/banner-hero.jpg') }}')">
        <div class="lat-wrap">
            <nav class="lat-page-hero__crumb" aria-label="Breadcrumb">
                <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('ui.home') }}</a> &middot; <b>{{ $title }}</b>
            </nav>
            <span class="lat-eyebrow">{{ __('legal.last_updated') }}</span>
            <h1>{{ $title }}</h1>
        </div>
    </section>

    <div class="lat-wrap" style="padding:56px 24px 80px;max-width:920px">

        {{-- Section 1: Acceptance --}}
        <section class="mb-10" aria-labelledby="terms-s1">
            <h2 id="terms-s1" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.terms_s1_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.terms_s1_body') }}</p>
        </section>

        {{-- Section 2: Service --}}
        <section class="mb-10" aria-labelledby="terms-s2">
            <h2 id="terms-s2" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.terms_s2_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.terms_s2_body') }}</p>
        </section>

        {{-- Section 3: Bookings --}}
        <section class="mb-10" aria-labelledby="terms-s3">
            <h2 id="terms-s3" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.terms_s3_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.terms_s3_body') }}</p>
        </section>

        {{-- Section 4: Cancellations --}}
        <section class="mb-10" aria-labelledby="terms-s4">
            <h2 id="terms-s4" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.terms_s4_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.terms_s4_body') }}</p>
        </section>

        {{-- Section 5: Contact --}}
        <section class="mb-10" aria-labelledby="terms-s5">
            <h2 id="terms-s5" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.terms_s5_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.terms_s5_intro') }}</p>
            @if ($legalPhone)
                <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.terms_s5_phone_line', ['phone' => $legalPhone]) }}</p>
            @endif
            @if ($legalHours)
                <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.terms_s5_hours_line', ['hours' => $legalHours]) }}</p>
            @endif
            @if ($legalAddress)
                <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.terms_s5_address_line', ['address' => $legalAddress]) }}</p>
            @endif
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
