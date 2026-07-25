<?php

namespace App\Models;

use App\Support\ImagePath;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tour extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'itinerary_es' => 'array',
        'itinerary_en' => 'array',
        'itinerary_pt' => 'array',
        'faqs_es' => 'array',
        'faqs_en' => 'array',
        'faqs_pt' => 'array',
        'includes_es' => 'array',
        'includes_en' => 'array',
        'includes_pt' => 'array',
        'excludes_es' => 'array',
        'excludes_en' => 'array',
        'excludes_pt' => 'array',
        'gallery' => 'array',
        'comparison' => 'array',
        'seo_keywords' => 'array',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'show_best_seller' => 'boolean',
        'show_offer_badge' => 'boolean',
        'price' => 'decimal:2',
        'price_before' => 'decimal:2',
        'rating' => 'decimal:1',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $tour) {
            if (empty($tour->slug) && ! empty($tour->title_es)) {
                $tour->slug = static::makeUniqueSlug($tour->title_es);
            }
        });

        // Cascade cleanup on delete (docs/qa/ficha-tour.md hallazgo #7): Tour uses
        // SoftDeletes, so a regular delete() only sets deleted_at (no DB-level
        // ON DELETE constraint fires). Without this, Testimonial/BlockedDate rows
        // keep pointing at a tour_id that no longer resolves via Tour::find()
        // (excluded by the soft-delete scope), leaving them as silent orphans.
        // Both children lack soft deletes of their own, so we hard-delete them
        // here for both a soft delete and a forceDelete().
        static::deleting(function (self $tour) {
            $tour->testimonials()->delete();
            $tour->blockedDates()->delete();
        });
    }

    public static function makeUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $count = static::where('slug', 'like', $slug.'%')->count();

        return $count ? $slug.'-'.($count + 1) : $slug;
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(BlockedDate::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderByDesc('created_at');
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

    public function getCoverUrlAttribute(): string
    {
        return ImagePath::url($this->cover_image) ?? asset('assets/banners/banner-hero.jpg');
    }

    public function getGalleryUrlsAttribute(): array
    {
        $g = $this->gallery ?? [];
        if (! is_array($g) || count($g) === 0) {
            return [$this->cover_url];
        }

        return array_values(array_filter(array_map(fn ($p) => ImagePath::url($p), $g)));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Devuelve el contenido del bloque comparativo ya resuelto para el idioma
     * actual (con fallback a español), o null si el bloque no está activo o
     * no tiene ni una columna con ítems. Pensado para consumirse en el Blade.
     */
    /**
     * Single source of truth for the "special offer" discount used by the
     * admin table/preview and (eventually) the front-end badge. A price of
     * 0 or a price_before that doesn't exceed price never counts as a valid
     * offer — otherwise it produces a misleading "-100%"/false discount
     * (docs/qa/ficha-tour.md hallazgo #8, price=0 sin validación mínima).
     *
     * @return int|null Discount percentage (1-99) or null when there's no valid offer.
     */
    public static function offerDiscountPercent(float $price, float $priceBefore): ?int
    {
        if ($price <= 0 || $priceBefore <= 0 || $priceBefore <= $price) {
            return null;
        }

        return (int) round((1 - ($price / $priceBefore)) * 100);
    }

    public function hasActiveOffer(): bool
    {
        return static::offerDiscountPercent((float) $this->price, (float) ($this->price_before ?? 0)) !== null;
    }

    public function discountPercent(): ?int
    {
        return static::offerDiscountPercent((float) $this->price, (float) ($this->price_before ?? 0));
    }

    public function comparisonData(?string $locale = null): ?array
    {
        $c = $this->comparison;
        if (! is_array($c) || empty($c['enabled'])) {
            return null;
        }

        $locale = $locale ?: app()->getLocale();
        // Resuelve una clave localizada con fallback: <key>_<locale> → <key>_es
        $t = function (string $key, $default = '') use ($c, $locale) {
            $val = $c["{$key}_{$locale}"] ?? null;
            if ($val === null || $val === '' || $val === []) {
                $val = $c["{$key}_es"] ?? $default;
            }

            return $val;
        };

        $conv = $t('conv', []);
        $prem = $t('prem', []);
        $conv = is_array($conv) ? array_values(array_filter($conv, fn ($i) => trim((string) $i) !== '')) : [];
        $prem = is_array($prem) ? array_values(array_filter($prem, fn ($i) => trim((string) $i) !== '')) : [];

        // Sin ítems en ninguna columna no vale la pena renderizar
        if (empty($conv) && empty($prem)) {
            return null;
        }

        return [
            'color' => in_array(($c['color'] ?? 'teal'), ['teal', 'orange'], true) ? $c['color'] : 'teal',
            'badge' => $t('badge'),
            'title' => $t('title'),
            'title_hl' => $t('title_hl'),
            'intro' => $t('intro'),
            'conv_title' => $t('conv_title'),
            'prem_title' => $t('prem_title'),
            'conv' => $conv,
            'prem' => $prem,
            'footer' => $t('footer'),
            'image' => $this->galleryUrls[0] ?? $this->cover_url,
        ];
    }
}
