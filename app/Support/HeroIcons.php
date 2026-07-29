<?php

namespace App\Support;

/**
 * Juego de íconos de los 4 "features" del hero de la home.
 *
 * El texto de cada feature ya era editable desde el panel; el ícono era un SVG
 * escrito a mano en el Blade, así que cambiarlo pedía un despliegue. Ahora la
 * clienta elige el ícono desde Configuración → Home con un select.
 *
 * Es un set CERRADO a propósito: si el campo aceptara SVG libre, cualquiera con
 * acceso al panel podría inyectar markup arbitrario en la home (el SVG se pinta
 * sin escapar, porque es markup). Con un catálogo, lo peor que puede pasar es
 * elegir un ícono feo. Una clave desconocida cae al ícono por defecto del slot,
 * nunca deja el hueco vacío ni rompe la página.
 *
 * Los `path` son de Lucide (mismo trazo de 2px que el resto del sitio) y se
 * pintan dentro de un círculo con borde rojo (.lat-htc__ic).
 */
class HeroIcons
{
    /** Ícono por defecto de cada slot — es el orden exacto del mockup. */
    public const DEFAULTS = ['guide', 'shield', 'headset', 'tag'];

    private const PATHS = [
        'guide' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.7"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'headset' => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>',
        'tag' => '<path d="M20.59 13.41 11 3.83A2 2 0 0 0 9.59 3.24L4 3a1 1 0 0 0-1 1l.24 5.59a2 2 0 0 0 .58 1.41l9.6 9.6a2 2 0 0 0 2.83 0l4.34-4.34a2 2 0 0 0 0-2.85z"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor" stroke="none"/>',
        'star' => '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
        'map' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l8.8 8.8 8.8-8.8a5.5 5.5 0 0 0 0-7.8z"/>',
        'award' => '<circle cx="12" cy="8" r="6"/><path d="M8.2 13.9 7 22l5-3 5 3-1.2-8.1"/>',
        'camera' => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'group' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/>',
        'wallet' => '<path d="M20 12V8H6a2 2 0 0 1 0-4h12v4"/><path d="M4 6v12a2 2 0 0 0 2 2h14v-4"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
    ];

    /** Etiquetas para el select del panel (en español, como el resto del CMS). */
    public const LABELS = [
        'guide' => 'Guía / personas',
        'shield' => 'Escudo (seguridad)',
        'headset' => 'Auriculares (atención)',
        'tag' => 'Etiqueta (precio)',
        'star' => 'Estrella',
        'map' => 'Mapa / ubicación',
        'clock' => 'Reloj',
        'heart' => 'Corazón',
        'award' => 'Medalla',
        'camera' => 'Cámara',
        'group' => 'Grupo',
        'wallet' => 'Billetera',
    ];

    /** @return array<string,string> opciones para Filament Select */
    public static function options(): array
    {
        return self::LABELS;
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && isset(self::PATHS[$key]);
    }

    /**
     * Normaliza la clave guardada en Settings: si no existe en el catálogo,
     * devuelve el ícono por defecto del slot (1-4).
     */
    public static function resolveKey(?string $key, int $slot): string
    {
        $key = trim((string) $key);

        if (self::exists($key)) {
            return $key;
        }

        return self::DEFAULTS[$slot - 1] ?? self::DEFAULTS[0];
    }

    /**
     * SVG listo para pintar. `data-hero-icon` deja el ícono elegido visible en
     * el HTML servido, que es lo que permite verificarlo desde un test sin
     * comparar cadenas de <path>.
     */
    public static function svg(string $key): string
    {
        $paths = self::PATHS[$key] ?? self::PATHS[self::DEFAULTS[0]];

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
            .'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" '
            .'data-hero-icon="'.e($key).'">'.$paths.'</svg>';
    }
}
