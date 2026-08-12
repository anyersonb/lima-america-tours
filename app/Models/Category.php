<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $cat) {
            if (empty($cat->slug) && ! empty($cat->name_es)) {
                $cat->slug = Str::slug($cat->name_es);
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
     * Categorías con al menos un tour publicado detrás, con el conteo real ya
     * cargado en `published_tours_count`. Mismo criterio que
     * Region::scopeWithPublishedTours: guard automático, nunca una categoría
     * vacía en el home ("Explora por categoría").
     */
    public function scopeWithPublishedTours(Builder $query): Builder
    {
        return $query
            ->whereHas('tours', fn ($q) => $q->published())
            ->withCount(['tours as published_tours_count' => fn ($q) => $q->published()]);
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
}
