@extends('layouts.app')

@section('title')
    @php $locale = app()->getLocale(); $L = fn($es,$en,$pt) => $locale==='pt'?$pt:($locale==='en'?$en:$es); @endphp
    {{ __('customer.recover_password') }} — Lima América Tours
@endsection

@section('content')
@php
    $locale = app()->getLocale();
    $L = fn($es,$en,$pt) => $locale==='pt'?$pt:($locale==='en'?$en:$es);
@endphp

<section class="min-h-[70vh] bg-lat-paper flex items-center justify-center py-16 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-3xl shadow-lg overflow-hidden">

            <div class="bg-lat-red px-8 py-7 text-center">
                <h1 class="font-display text-2xl text-white tracking-wide">
                    {{ __('customer.recover_password') }}
                </h1>
                <p class="text-white/80 text-sm mt-1">
                    {{ __('customer.recover_instructions') }}
                </p>
            </div>

            <div class="px-8 py-8">

                @if (session('status'))
                    <div class="mb-5 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('customer.password.email', ['locale' => $locale]) }}">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-semibold text-lat-ink mb-1.5">
                            {{ __('customer.email') }}
                        </label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email') }}"
                               autocomplete="email"
                               class="w-full rounded-xl border border-lat-line-strong bg-lat-paper/60 px-4 py-3 text-sm text-lat-ink placeholder-lat-ink/40 focus:border-lat-red focus:ring-1 focus:ring-lat-red outline-none transition"
                               required>
                    </div>

                    <button type="submit"
                            class="mt-7 w-full rounded-full bg-lat-red hover:bg-lat-red-deep active:bg-lat-red-deep text-white font-semibold py-3.5 text-sm transition">
                        {{ __('customer.send_link') }}
                    </button>
                </form>

                <p class="mt-6 text-center text-sm">
                    <a href="{{ route('customer.login', ['locale' => $locale]) }}"
                       class="text-lat-red hover:underline font-semibold">
                        {{ __('customer.go_back_login') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
