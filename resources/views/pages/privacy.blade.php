@extends('layouts.app')

@section('title', $title . ' – Lima América Tours')
@section('description', __('legal.last_updated'))
@section('header_variant', 'solid')

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

        {{-- Section 1: Data collected --}}
        <section class="mb-10" aria-labelledby="privacy-s1">
            <h2 id="privacy-s1" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.privacy_s1_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.privacy_s1_body') }}</p>
        </section>

        {{-- Section 2: Use of data --}}
        <section class="mb-10" aria-labelledby="privacy-s2">
            <h2 id="privacy-s2" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.privacy_s2_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.privacy_s2_body') }}</p>
        </section>

        {{-- Section 3: Sharing --}}
        <section class="mb-10" aria-labelledby="privacy-s3">
            <h2 id="privacy-s3" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.privacy_s3_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.privacy_s3_body') }}</p>
        </section>

        {{-- Section 4: Cookies --}}
        <section class="mb-10" aria-labelledby="privacy-s4">
            <h2 id="privacy-s4" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.privacy_s4_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.privacy_s4_body') }}</p>
        </section>

        {{-- Section 5: User rights --}}
        <section class="mb-10" aria-labelledby="privacy-s5">
            <h2 id="privacy-s5" class="font-display text-xl md:text-2xl text-lat-ink mb-3">
                {{ __('legal.privacy_s5_title') }}
            </h2>
            <p class="text-lat-ink-soft leading-relaxed">{{ __('legal.privacy_s5_body') }}</p>
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
