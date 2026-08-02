<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'decimal:1',
        'reviewed_at' => 'date',
    ];

    protected static function booted(): void
    {
        // Toda reseña nueva necesita una fecha real que mostrar en la portada
        // ("Nombre · fecha · tour"). Si quien la crea (panel o import) no la
        // fija, se asume "hoy" en vez de dejarla en null — nunca se inventa
        // una fecha pasada.
        static::creating(function (self $testimonial) {
            if (empty($testimonial->reviewed_at)) {
                $testimonial->reviewed_at = now()->toDateString();
            }
        });
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getQuoteAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"quote_{$locale}"} ?: $this->quote_es;
    }

    /**
     * Fecha real de la reseña para mostrar en pantalla, con fallback en
     * cascada: `reviewed_at` (fecha propia, la que se autocompleta al
     * crear) → `review_date` (texto crudo del import de WP, se intenta
     * parsear) → `created_at` (última red de seguridad, nunca null si el
     * registro existe). Nunca lanza: un formato de `review_date` que no se
     * puede parsear simplemente se descarta y sigue con el siguiente.
     */
    public function displayDate(): ?\Illuminate\Support\Carbon
    {
        if ($this->reviewed_at) {
            return $this->reviewed_at instanceof \Illuminate\Support\Carbon
                ? $this->reviewed_at
                : \Illuminate\Support\Carbon::parse((string) $this->reviewed_at);
        }

        if (! empty($this->review_date)) {
            try {
                return \Illuminate\Support\Carbon::parse($this->review_date);
            } catch (\Throwable) {
                // Formato irregular del import de WP: se ignora y se cae al siguiente.
            }
        }

        return $this->created_at;
    }
}
