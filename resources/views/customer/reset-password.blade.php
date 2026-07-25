@extends('layouts.app')

@section('title')
    {{ __('customer.new_password_title') }} — Lima América Tours
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
                    {{ __('customer.new_password_title') }}
                </h1>
                <p class="text-white/80 text-sm mt-1">
                    {{ __('customer.reset_instructions') }}
                </p>
            </div>

            <div class="px-8 py-8">

                @if ($errors->any())
                    <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('customer.password.update', ['locale' => $locale]) }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="space-y-5">
                        <div>
                            <label for="password" class="block text-sm font-semibold text-lat-ink mb-1.5">
                                {{ __('customer.password') }}
                            </label>
                            <input type="password" id="password" name="password"
                                   autocomplete="new-password"
                                   class="w-full rounded-xl border border-lat-line-strong bg-lat-paper/60 px-4 py-3 text-sm text-lat-ink focus:border-lat-red focus:ring-1 focus:ring-lat-red outline-none transition"
                                   required>
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-lat-ink mb-1.5">
                                {{ __('customer.password_confirmation') }}
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   autocomplete="new-password"
                                   class="w-full rounded-xl border border-lat-line-strong bg-lat-paper/60 px-4 py-3 text-sm text-lat-ink focus:border-lat-red focus:ring-1 focus:ring-lat-red outline-none transition"
                                   required>
                        </div>
                    </div>

                    <button type="submit"
                            class="mt-7 w-full rounded-full bg-lat-red hover:bg-lat-red-deep active:bg-lat-red-deep text-white font-semibold py-3.5 text-sm transition">
                        {{ __('customer.reset_password_btn') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
