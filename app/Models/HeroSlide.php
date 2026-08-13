<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Diapositiva administrable del slider del hero de la home. Ver
 * App\Services\HeroSlidesResolver para cómo se arman en `$heroSlides`
 * (contrato consumido por resources/views/home.blade.php).
 */
class HeroSlide extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Orden EXPLÍCITO y editable desde el panel (columna `order`), no por
     * `id` de creación — así el admin puede reordenar diapositivas sin
     * tener que recrearlas.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    /**
     * Alt en el idioma actual, con el mismo criterio de fallback a español
     * que el resto del proyecto (Guide::role, Guide::bio, Offer::title...).
     */
    public function getAltAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"alt_{$locale}"} ?: $this->alt_es;
    }
}
