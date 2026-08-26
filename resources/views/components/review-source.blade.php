@props(['source' => null, 'variant' => 'card'])

@php
    /**
     * Sello de origen de una reseña — pedido del jefe el 2026-08-25:
     * "los comentarios de Google quedarán así, en color negro? colócale el
     * logo de Google" y "si utilizamos el logo de Google para las reseñas en
     * vez del colorcito".
     *
     * Antes cada pantalla resolvía esto por su cuenta: la home y Nosotros
     * pintaban el nombre en texto gris y /resenas un punto de color con el
     * nombre al lado. Tres marcados distintos para el mismo dato — este
     * componente es la única fuente ahora.
     *
     * Los logos son de TERCEROS y van dibujados como los publican sus guías
     * de marca, sin recolorear: el isotipo de Google en sus cuatro colores y
     * el búho de Tripadvisor en su verde (#00AA6C). Recolorearlos al rojo de
     * la marca sería usar mal una marca ajena — y además borraría justo la
     * señal que el visitante reconoce.
     *
     * "Nuestra web" NO lleva logo inventado: no es una plataforma externa y
     * un sello propio ahí haría pasar por verificada una reseña que cargamos
     * nosotros. Va con el punto de color, como estaba.
     *
     * Variantes:
     *   card → tarjeta clara (home, /resenas)
     *   dark → sobre fondo oscuro (Nosotros)
     *   chip → píldora con anillo (columna derecha de /resenas)
     */
    $locale = app()->getLocale();
    $key = app(\App\Services\ReviewAggregator::class)->normalizeSource($source);

    $labels = [
        'google' => 'Google',
        'tripadvisor' => 'Tripadvisor',
        'trivago' => 'Trivago',
        'web' => ['es' => 'Nuestra web', 'en' => 'Our website', 'pt' => 'Nosso site'][$locale] ?? 'Nuestra web',
    ];

    // Color del punto para los orígenes sin logo propio dibujado.
    $dots = ['trivago' => '#E5546C', 'web' => '#0E7C6B'];
@endphp

<span {{ $attributes->merge(['class' => 'lat-rsrc lat-rsrc--'.$key.' lat-rsrc--'.$variant]) }}>
    <span class="lat-rsrc__mark" aria-hidden="true">
        @if ($key === 'google')
            {{-- Isotipo de Google, cuatro colores, tal como lo publica su kit de marca. --}}
            <svg viewBox="0 0 48 48" class="lat-rsrc__logo">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
        @elseif ($key === 'tripadvisor')
            {{-- El mismo búho del footer, con los ojos DIBUJADOS (esclerótica
                 blanca + pupila): el path de dos huecos que usa el ícono
                 social se lee como un antifaz a este tamaño. --}}
            <svg viewBox="0 0 32 32" class="lat-rsrc__logo">
                <path d="M16 7.5c-4.9 0-9.2 2.1-11.6 5.4a7.5 7.5 0 0 0 11.6 9.7 7.5 7.5 0 0 0 11.6-9.7C25.2 9.6 20.9 7.5 16 7.5z" fill="#00AA6C"/>
                <circle cx="10.7" cy="17.2" r="4.3" fill="#fff"/>
                <circle cx="10.7" cy="17.2" r="1.9" fill="#00AA6C"/>
                <circle cx="21.3" cy="17.2" r="4.3" fill="#fff"/>
                <circle cx="21.3" cy="17.2" r="1.9" fill="#00AA6C"/>
            </svg>
        @else
            <span class="lat-rsrc__dot" style="background: {{ $dots[$key] ?? '#0E7C6B' }}"></span>
        @endif
    </span>
    <span class="lat-rsrc__name">{{ $labels[$key] }}</span>
</span>
