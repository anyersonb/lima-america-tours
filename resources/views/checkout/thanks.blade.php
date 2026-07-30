@extends('layouts.app')

@php
    $locale    = app()->getLocale();
    $L         = fn (string $es, string $en, string $pt): string => $locale === 'pt' ? $pt : ($locale === 'en' ? $en : $es);
    $firstRef  = $bookings->first()['reference'] ?? '';
    $firstName = $bookings->first()['customer_name'] ?? 'viajero';
@endphp

@section('title', __('checkout.thank_you_title') . ' — ' . __('seo.site_name'))
@section('description', 'Tu reserva en Lima América Tours ha sido confirmada. Recibirás un email con los detalles.')
@section('robots', 'noindex,nofollow')

@section('content')

{{-- ───────── HERO ───────── --}}
<section class="relative isolate text-white">
    <div class="absolute inset-0 -z-10">
        <img src="{{ asset('assets/banners/Rectangle 19215.jpg') }}" alt="" class="w-full h-full object-cover" loading="eager">
        <div class="absolute inset-0 bg-lat-ink/70"></div>
    </div>
    <div class="container mx-auto px-5 lg:px-10 py-16 md:py-24 text-center">
        {{-- Success icon --}}
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white/10 border-2 border-lat-red mb-8">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="font-display text-4xl md:text-5xl lg:text-6xl leading-tight">{{ __('checkout.thank_you_title') }}</h1>

        @if ($firstRef)
            <p class="mt-4 text-white/85 text-sm md:text-base">
                {{ __('checkout.reference_label') }}: <strong class="text-white font-mono tracking-widest">{{ $firstRef }}</strong>
            </p>
        @endif

        <p class="mt-4 mx-auto max-w-2xl text-sm md:text-base text-white/80">
            {{ __('checkout.thank_you_subtitle') }}
        </p>
    </div>
</section>

{{-- ───────── DETALLE RESERVAS ───────── --}}
@if ($bookings->isNotEmpty())
<section class="bg-lat-paper py-14 lg:py-20">
    <div class="container mx-auto px-5 lg:px-10 max-w-3xl">
        <h2 class="font-display text-2xl text-lat-ink mb-6">{{ $L('Detalle de tu reserva', 'Your booking details', 'Detalhe da sua reserva') }}</h2>

        <div class="space-y-5">
            @foreach ($bookings as $booking)
                <article class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-lat-ink/5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 class="font-display text-lg text-lat-ink leading-snug">
                                {{ $booking['tour_title_snapshot'] }}
                            </h3>
                            <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-lat-ink/65">
                                <li>
                                    <svg class="inline w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    {{ \Carbon\Carbon::parse($booking['travel_date'])->format('d M Y') }}
                                </li>
                                <li>
                                    <svg class="inline w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87M12 12a4 4 0 100-8 4 4 0 000 8z"/>
                                    </svg>
                                    {{ ($booking['adults'] ?? 1) + ($booking['children'] ?? 0) }} {{ $L('personas', 'people', 'pessoas') }}
                                    ({{ $booking['adults'] ?? 1 }} {{ $L('adultos', 'adults', 'adultos') }}, {{ $booking['children'] ?? 0 }} {{ $L('niños', 'children', 'crianças') }})
                                </li>
                            </ul>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-price text-2xl text-lat-ink leading-none">
                                {{ \App\Support\Money::format($booking['total_price'], $booking['currency'] ?? \App\Support\Money::site(), 2) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-lat-ink/10 flex flex-wrap gap-4 text-xs text-lat-ink/70">
                        <span>
                            {{ $L('Referencia', 'Reference', 'Referência') }}: <strong class="font-mono text-lat-ink">{{ $booking['reference'] }}</strong>
                        </span>
                        @php
                            $isPaid = ($booking['payment_status'] ?? 'pending') === 'paid';
                        @endphp
                        <span class="inline-flex items-center gap-1">
                            {{ $L('Estado', 'Status', 'Status') }}:
                            @if ($isPaid)
                                <span class="inline-block rounded-full bg-state-success/10 text-state-success px-2 py-0.5 font-semibold uppercase tracking-wide text-[10px]">
                                    {{ $L('Confirmada', 'Confirmed', 'Confirmada') }}
                                </span>
                            @else
                                <span class="inline-block rounded-full bg-lat-red-tint text-lat-red-deep px-2 py-0.5 font-semibold uppercase tracking-wide text-[10px]">
                                    {{ $L('Pendiente de pago', 'Payment pending', 'Pagamento pendente') }}
                                </span>
                            @endif
                        </span>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- CTAs --}}
        <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('tours.index', ['locale' => $locale]) }}"
               class="lat-btn lat-btn--red text-center">
                {{ __('checkout.back_to_tours') }}
            </a>
            <a href="{{ route('home', ['locale' => $locale]) }}"
               class="inline-flex items-center justify-center gap-2 rounded-full border-2 border-lat-ink/30 text-lat-ink px-6 py-3 text-sm font-semibold hover:border-lat-ink transition">
                {{ $L('Volver al inicio', 'Back to home', 'Voltar ao início') }}
            </a>
        </div>
    </div>
</section>
@else
<section class="bg-lat-paper py-20">
    <div class="container mx-auto px-5 lg:px-10 text-center">
        <p class="text-lat-ink/70">{{ $L('No hay detalles de reserva disponibles.', 'No booking details available.', 'Não há detalhes de reserva disponíveis.') }}</p>
        <a href="{{ route('tours.index', ['locale' => $locale]) }}" class="lat-btn lat-btn--red mt-6 inline-flex">{{ $L('Ver tours', 'View tours', 'Ver tours') }}</a>
    </div>
</section>
@endif

@endsection
