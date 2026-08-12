<?php

namespace App\Models;

use App\Support\ImagePath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Region extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $region) {
            if (empty($region->slug) && ! empty($region->name_es)) {
                $region->slug = Str::slug($region->name_es);
            }
        });
    }

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Destinos que REALMENTE se pueden visitar hoy: activos en el panel Y con
     * al menos un tour publicado. Sin esto, "Explora los destinos del Perú"
     * podía listar un destino sin ningún tour detrás (regla del proyecto:
     * ningún enlace a un listado de 0 resultados). El guard es automático —
     * cuando el cliente carga el primer tour publicado de una región nueva
     * (ej. Arequipa), esa región empieza a aparecer sola, sin tocar código.
     */
    public function scopeWithPublishedTours(Builder $query): Builder
    {
        return $query
            ->whereHas('tours', fn ($q) => $q->published())
            ->withCount(['tours as published_tours_count' => fn ($q) => $q->published()]);
    }

    public function getImageUrlAttribute(): ?string
    {
        return ImagePath::url($this->hero_image);
    }

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"name_{$locale}"} ?: $this->name_es;
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->{"description_{$locale}"} ?: $this->description_es;
    }

    public function getEyebrowAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->{"eyebrow_{$locale}"} ?: $this->eyebrow_es;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
