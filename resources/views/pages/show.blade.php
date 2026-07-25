@php
    $locale = app()->getLocale();
@endphp

@extends('layouts.app')

@section('title', $title . ' — ' . __('seo.site_name'))
@section('description', $description)
@section('header_variant', 'solid')

@section('content')
<div class="lat-page">

    {{-- ============================================================
         HERO INTERNO — imagen de fondo + degradado (mismo patrón que
         about/blog/tours) con breadcrumb Inicio · Título de la página
         ============================================================ --}}
    <section class="lat-page-hero" style="background-image:url('{{ $page->hero_image ? \Illuminate\Support\Facades\Storage::disk('media')->url($page->hero_image) : asset('assets/banners/Rectangle 19212.jpg') }}')">
        <div class="lat-wrap">
            <nav class="lat-page-hero__crumb" aria-label="Breadcrumb">
                <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('ui.home') }}</a> &middot; <b>{{ $page->title }}</b>
            </nav>
            <h1>{{ $page->title }}</h1>
        </div>
    </section>

    {{-- ============================================================
         CONTENIDO — texto plano por idioma (con salto de línea)
         ============================================================ --}}
    <section class="lat-wrap" style="padding:64px 24px" aria-labelledby="page-content-title">
        <h2 id="page-content-title" class="sr-only">{{ $page->title }}</h2>

        <div class="prose max-w-3xl mx-auto text-teal-800/85 leading-relaxed">
            @if ($page->content)
                <p>{!! nl2br(e($page->content)) !!}</p>
            @endif
        </div>

        <div class="mt-12 pt-8 border-t border-gray-200 max-w-3xl mx-auto">
            <a href="{{ route('home', ['locale' => $locale]) }}"
               class="inline-flex items-center gap-2 text-teal-700 hover:text-orange-500 font-medium transition-colors">
                <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                {{ __('common.back_home') }}
            </a>
        </div>
    </section>
</div>
@endsection
