@php
    $locale = app()->getLocale();
    // Sin fallback a otro número: ver App\Models\Setting::contactPhone().
    // Si el cliente aún no lo cargó, el <li> de teléfono no se imprime.
    $contactPhone = \App\Models\Setting::contactPhone();
    $contactEmail = \App\Models\Setting::get('contact_email') ?: 'info@limaamericatours.com';
    // docs/qa/F7-personas.md §g #5: la dirección del panel (Configuración → Contacto)
    // se guardaba correctamente pero el footer ignoraba el Setting y mostraba el string
    // fijo de idioma __('footer.address'). Ahora ese string solo es un fallback.
    $contactAddress = \App\Models\Setting::get('contact_address_' . $locale) ?: __('footer.address');

    $sIg = \App\Models\Setting::get('social_instagram');
    $sFb = \App\Models\Setting::get('social_facebook');
    $sTk = \App\Models\Setting::get('social_tiktok');
    $sYt = \App\Models\Setting::get('social_youtube');
    $norm = fn ($u) => $u ? (\Illuminate\Support\Str::startsWith($u, ['http://', 'https://']) ? $u : 'https://' . ltrim($u, '/')) : null;

    // Fondo fotográfico del footer (fallback a la foto por defecto del sitio si no
    // hay banner propio). Pasa por ResponsiveImage: antes era el PNG de 2.52 MB en
    // crudo, descargado en TODAS las páginas del sitio para pintarlo detrás de un
    // velo oscuro.
    //
    // 640 y no 1024: es un fondo difuminado por el overlay, nadie le mira el
    // detalle, y así en móvil reutiliza EXACTAMENTE el archivo que el hero ya
    // descargó (misma URL, sale de caché y cuesta 0 KB). Medido: 139 KB → 60 KB
    // en desktop, gratis en móvil.
    $footBg = \App\Support\ResponsiveImage::defaultPhotoUrl(640);

    // Tours Populares: usa los tours reales destacados (mismo criterio que la home)
    try {
        $popularTours = \App\Models\Tour::published()->featured()->ordered()->limit(5)->get();
        if ($popularTours->isEmpty()) {
            $popularTours = \App\Models\Tour::published()->ordered()->limit(5)->get();
        }
    } catch (\Throwable $e) {
        $popularTours = collect();
    }

    // ── Enlaces que dependen de que HAYA contenido ───────────────────────────
    // Un enlace del footer que lleva a una pantalla de "Sin resultados" es un
    // enlace muerto: el visitante hace clic y recibe un mensaje de vacío en vez
    // de una sección. Estos dos se pintan solo cuando hay algo que mostrar, así
    // que reaparecen solos el día que el CMS tenga ese contenido (no hay que
    // acordarse de descomentar nada).
    //
    // "Free Tours" no es una sección propia: es la búsqueda ?q=free. Hoy no hay
    // ningún tour que la satisfaga (verificado: 0 resultados), así que el enlace
    // no se pinta.
    try {
        $hasFreeTours = \App\Models\Tour::published()->where(function ($q) {
            $q->where('title_es', 'like', '%free%')
                ->orWhere('title_en', 'like', '%free%')
                ->orWhere('description_es', 'like', '%free%');
        })->exists();
    } catch (\Throwable $e) {
        $hasFreeTours = false;
    }

    try {
        $hasBlogPosts = \App\Models\BlogPost::where('is_published', true)->exists();
    } catch (\Throwable $e) {
        $hasBlogPosts = false;
    }
@endphp
<footer class="lat-footer" role="contentinfo"
        style="background-image:linear-gradient(rgba(16,13,11,.9), rgba(16,13,11,.96)), url('{{ $footBg }}');">

    {{-- Newsletter --}}
    <div class="lat-wrap">
        <section aria-labelledby="newsletter-title" class="lat-footer__newsletter">
            <div>
                <p style="color:rgba(255,255,255,.75); font-size:.85rem;">{{ __('footer.newsletter_eyebrow') }}</p>
                <h2 id="newsletter-title" style="color:#fff; font-family:'Raleway',sans-serif; font-weight:800; font-size:clamp(1.4rem,3vw,2rem); line-height:1.3; margin-top:10px;">
                    {{ __('footer.newsletter_title') }}
                </h2>
            </div>

            <div>
                @if (session('newsletter_success'))
                    <p class="mb-3 rounded-2xl bg-emerald-500/20 border border-emerald-300/40 text-white text-sm px-4 py-3" role="status">{{ session('newsletter_success') }}</p>
                @elseif ($errors->has('email') || $errors->has('name'))
                    <p class="mb-3 rounded-2xl bg-red-500/20 border border-red-300/40 text-white text-sm px-4 py-3" role="alert">{{ $errors->first('email') ?: $errors->first('name') }}</p>
                @endif
                <form action="{{ route('newsletter.subscribe') }}" method="post" id="form-newsletter" class="space-y-3">
                    @csrf
                    <input type="text" name="website" tabindex="-1" autocomplete="off"
                           style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="sr-only">{{ __('footer.newsletter_name') }}</span>
                            <input type="text" name="name" required placeholder="{{ __('footer.newsletter_name') }}"
                                   class="w-full rounded-pill bg-white/10 border border-white/25 text-white placeholder-white/50 px-5 py-3.5 text-sm focus:border-[--red] focus:ring-1 focus:outline-none"
                                   style="--red:#cb101e;">
                        </label>
                        <label class="block">
                            <span class="sr-only">{{ __('footer.newsletter_email') }}</span>
                            <input type="email" name="email" required placeholder="{{ __('footer.newsletter_email') }}"
                                   class="w-full rounded-pill bg-white/10 border border-white/25 text-white placeholder-white/50 px-5 py-3.5 text-sm focus:border-[--red] focus:ring-1 focus:outline-none"
                                   style="--red:#cb101e;">
                        </label>
                    </div>
                    @include('partials.recaptcha', ['recaptchaAction' => 'newsletter', 'recaptchaFormId' => 'form-newsletter'])
                    {{-- Sobre fondo oscuro del footer, el botón ink quedaría invisible;
                         se usa la variante blanca (ink por defecto, rojo solo en hover). --}}
                    <button type="submit" class="lat-btn lat-btn--white" style="width:100%;">
                        {{ __('footer.newsletter_submit') }}
                    </button>
                </form>
            </div>
        </section>
    </div>

    {{-- Columnas principales --}}
    <div class="lat-wrap">
        <div class="lat-footer__grid">
            <section aria-labelledby="footer-brand">
                <a href="{{ route('home', ['locale' => $locale]) }}" aria-label="Lima América Tours — {{ __('nav.home') }}">
                    <img src="{{ asset('assets/logos/logo-america-white.webp') }}" alt="Lima América Tours" class="lat-footer__logo">
                </a>
                <p id="footer-brand" class="lat-footer__desc">
                    {{ \App\Models\Setting::get('footer_about_' . $locale) ?: __('footer.brand_description') }}
                </p>
                <div class="lat-footer__social">
                    @if ($u = $norm($sFb))<a href="{{ $u }}" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.7-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.2c-1.2 0-1.6.8-1.6 1.5V12h2.7l-.4 2.9h-2.3v7A10 10 0 0 0 22 12z"/></svg></a>@endif
                    @if ($u = $norm($sIg))<a href="{{ $u }}" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1" fill="currentColor" stroke="none"/></svg></a>@endif
                    @if ($u = $norm($sTk))<a href="{{ $u }}" target="_blank" rel="noopener" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 3c.3 2.2 1.6 3.7 3.8 3.9v2.4c-1.3.1-2.5-.3-3.8-1v5.6c0 3.4-2.6 5.6-5.6 5.1-2.6-.4-4-2.1-4-4.6 0-2.9 2.6-4.9 5.6-4.3v2.5c-.4-.1-.9-.2-1.3-.1-1 .1-1.7.8-1.7 1.9 0 1.1.8 1.9 1.9 1.9 1.2 0 2-.9 2-2.1V3H16z"/></svg></a>@endif
                    @if ($u = $norm($sYt))<a href="{{ $u }}" target="_blank" rel="noopener" aria-label="YouTube"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23 12s0-3.2-.4-4.7c-.2-.8-.9-1.5-1.7-1.7C19.4 5.2 12 5.2 12 5.2s-7.4 0-8.9.4c-.8.2-1.5.9-1.7 1.7C1 8.8 1 12 1 12s0 3.2.4 4.7c.2.8.9 1.5 1.7 1.7 1.5.4 8.9.4 8.9.4s7.4 0 8.9-.4c.8-.2 1.5-.9 1.7-1.7C23 15.2 23 12 23 12zM9.8 15.3V8.7l5.7 3.3-5.7 3.3z"/></svg></a>@endif
                </div>
            </section>

            <nav aria-labelledby="footer-links">
                <h4 id="footer-links">{{ __('footer.links') }}</h4>
                <div class="lat-footer__links">
                    <a href="{{ route('home', ['locale' => $locale]) }}">{{ __('nav.home') }}</a>
                    <a href="{{ route('about', ['locale' => $locale]) }}">{{ __('footer.about_short') }}</a>
                    <a href="{{ route('tours.index', ['locale' => $locale]) }}">{{ __('nav.tours') }}</a>
                    {{-- Solo si hay contenido detrás: ver el @php de arriba. --}}
                    @if ($hasFreeTours)
                        <a href="{{ route('tours.results', ['locale' => $locale, 'q' => 'free']) }}">{{ __('nav.free_tours') }}</a>
                    @endif
                    @if ($hasBlogPosts)
                        <a href="{{ route('blog.index', ['locale' => $locale]) }}">Blog</a>
                    @endif
                    <a href="{{ route('contact', ['locale' => $locale]) }}">{{ __('nav.contact') }}</a>
                    <a href="{{ route('legal.terms', ['locale' => $locale]) }}">{{ __('footer.terms') }}</a>
                    <a href="{{ route('legal.privacy', ['locale' => $locale]) }}">{{ __('footer.privacy') }}</a>
                </div>
            </nav>

            <section aria-labelledby="footer-contact">
                <h4 id="footer-contact">{{ __('footer.locate_us') }}</h4>
                <address class="lat-footer__contact" style="font-style:normal;">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        <span>{{ $contactAddress }}</span>
                    </li>
                    @if ($contactPhone)
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                        <a href="tel:{{ str_replace([' ', '+'], '', $contactPhone) }}">{{ $contactPhone }}</a>
                    </li>
                    @endif
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                        <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ __('footer.hours') }}</span>
                    </li>
                </address>
            </section>

            <section aria-labelledby="footer-tours">
                <h4 id="footer-tours">{{ __('footer.popular_tours') }}</h4>
                <div class="lat-footer__links">
                    @forelse ($popularTours as $pt)
                        <a href="{{ route('tours.show', ['locale' => $locale, 'slug' => $pt->slug]) }}">{{ $pt->title }}</a>
                    @empty
                        <a href="{{ route('tours.index', ['locale' => $locale]) }}">{{ __('nav.tours') }}</a>
                    @endforelse
                    <a href="{{ route('tours.index', ['locale' => $locale]) }}">{{ __('nav.all_tours') }}</a>
                </div>
            </section>
        </div>

        {{-- Sellos de confianza --}}
        <div class="lat-footer__seals">
            <span class="lat-footer__seal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                {{ __('footer.seal_secure_payment') }}
            </span>
            <span class="lat-footer__seal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4 12 22l-9-9V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5" fill="currentColor"/></svg>
                {{ __('footer.seal_best_price') }}
            </span>
            <span class="lat-footer__seal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                {{ __('footer.seal_responsible') }}
            </span>
        </div>

        {{-- Copyright --}}
        <div class="lat-footer__bottom">
            <span class="lat-footer__lang">
                🇵🇪 {{ ['es' => 'Español', 'en' => 'English', 'pt' => 'Português'][$locale] ?? 'Español' }}
            </span>
            <p>&copy; <time datetime="{{ now()->year }}">{{ now()->year }}</time> Lima América Tours &ndash; {{ __('common.rights_reserved') }}.</p>
            <div class="lat-footer__legal">
                <a href="{{ route('legal.terms', ['locale' => $locale]) }}">{{ __('footer.terms') }}</a>
                <a href="{{ route('legal.privacy', ['locale' => $locale]) }}">{{ __('footer.privacy') }}</a>
            </div>
        </div>
    </div>
</footer>
