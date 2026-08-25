<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'valid_until' => 'datetime',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            });
    }

    public function getTitleAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"title_{$locale}"} ?: $this->title_es;
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_{$locale}"} ?: $this->description_es;
    }

    public function getCtaLabelAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"cta_label_{$locale}"} ?: $this->cta_label_es;
    }

    /**
     * Destino real del botón "Leer más" de la tarjeta de promoción.
     *
     * Existe por un defecto que reportó el jefe el 2026-08-24: las tres
     * tarjetas del home llevaban a "No se ha podido encontrar la página" del
     * WordPress viejo. La causa era `cta_url = '/es/tours'` guardado como ruta
     * ABSOLUTA DESDE LA RAÍZ DEL DOMINIO. Esta app no vive en el docroot
     * (staging cuelga de /staging), así que el navegador pedía
     * limaamericatours.com/es/tours — que es WordPress, no esta app. Misma
     * clase de fallo que ya había tumbado el site.webmanifest.
     *
     * Reglas, en orden:
     *  1. URL absoluta o con esquema propio (http, https, mailto, tel, ancla):
     *     se respeta tal cual — puede apuntar afuera a propósito.
     *  2. Cualquier otra cosa se trata como ruta INTERNA y pasa por `url()`,
     *     que le antepone la base real de la instalación.
     *  3. Sin `cta_url`: el tour vinculado, o el catálogo, SIEMPRE en el idioma
     *     que se está viendo. El valor sembrado era `/es/tours` fijo, así que
     *     en inglés y portugués el botón además sacaba al visitante de su
     *     idioma.
     */
    public function ctaHref(string $locale): string
    {
        $raw = trim((string) $this->cta_url);

        if ($raw !== '') {
            return \Illuminate\Support\Str::startsWith($raw, ['http://', 'https://', 'mailto:', 'tel:', '#'])
                ? $raw
                : url($raw);
        }

        return $this->tour
            ? route('tours.show', ['locale' => $locale, 'slug' => $this->tour->slug])
            : route('tours.index', ['locale' => $locale]);
    }
}
