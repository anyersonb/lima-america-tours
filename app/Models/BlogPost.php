<?php

namespace App\Models;

use App\Support\ImagePath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    /**
     * Mass-assignment guard: only id is protected.
     */
    protected $guarded = ['id'];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'tags' => 'array',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // Boot
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Auto-generate slug and reading_minutes before creating/updating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $post): void {
            // Auto-slug from Spanish title when slug is empty
            if (empty($post->slug) && ! empty($post->title_es)) {
                $post->slug = Str::slug($post->title_es);
            }

            // Auto-calculate reading minutes from Spanish body word count —
            // but only when the editor hasn't set one by hand. Before, this
            // OVERWROTE any manual value on every save, which made the
            // "Minutos de lectura" field in Filament (added so the client
            // can correct/override it) useless: whatever was typed there
            // got silently replaced the next time the post was saved.
            if (empty($post->reading_minutes) && ! empty($post->body_es)) {
                $wordCount = str_word_count(strip_tags($post->body_es));
                $post->reading_minutes = (int) ceil($wordCount / 200);
            }

            // EN/PT translatable columns are nullable at the DB level so an
            // editor can publish with only the Spanish tab filled in without
            // a 500. Backfill the raw columns with the Spanish content so
            // anything that reads them directly (not through the locale
            // accessors below) still gets sensible content instead of NULL.
            foreach (['title', 'excerpt', 'body'] as $field) {
                foreach (['en', 'pt'] as $locale) {
                    if (empty($post->{"{$field}_{$locale}"})) {
                        $post->{"{$field}_{$locale}"} = $post->{"{$field}_es"};
                    }
                }
            }
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Scope: only posts that are published and whose publish date has passed.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Locale-aware accessors
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Returns the title in the current app locale, falling back to Spanish.
     */
    public function getTitleAttribute(): string
    {
        return $this->{'title_'.app()->getLocale()} ?? $this->title_es ?? '';
    }

    /**
     * Returns the excerpt in the current app locale, falling back to Spanish.
     */
    public function getExcerptAttribute(): string
    {
        return $this->{'excerpt_'.app()->getLocale()} ?? $this->excerpt_es ?? '';
    }

    /**
     * Returns the body in the current app locale, falling back to Spanish.
     */
    public function getBodyAttribute(): string
    {
        return $this->{'body_'.app()->getLocale()} ?? $this->body_es ?? '';
    }

    /**
     * Returns the SEO meta title in the current locale.
     * Falls back to meta_title_es, then to the localized title.
     */
    public function getMetaTitleAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"meta_title_{$locale}"}
            ?? $this->meta_title_es
            ?? $this->title;
    }

    /**
     * Returns the SEO meta description in the current locale.
     * Falls back to meta_description_es, then to the localized excerpt.
     */
    public function getMetaDescriptionAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"meta_description_{$locale}"}
            ?? $this->meta_description_es
            ?? $this->excerpt;
    }

    /**
     * Author byline, or null when the editor left it blank. Every seeded
     * post today has "Lima América Tours" (a real company byline, not a
     * fabricated person), but a future post created without filling the
     * field should never print an empty "Escrito por" line — falls back to
     * the site name from Settings (already admin-editable in Configuración
     * → General) instead of hardcoding the company name here.
     */
    public function getAuthorNameAttribute(): ?string
    {
        $raw = trim((string) ($this->attributes['author_name'] ?? ''));
        if ($raw !== '') {
            return $raw;
        }

        $siteName = trim((string) \App\Models\Setting::get('site_name', ''));

        return $siteName !== '' ? $siteName : null;
    }

    /**
     * Cover image URL with a graceful fallback. Two failure modes covered:
     *  - `cover_image` is empty (editor never uploaded one).
     *  - `cover_image` has a value but the file isn't actually on the "public"
     *    disk (e.g. missing on an environment where the DB was copied but the
     *    upload wasn't — exactly how "Visitemos el museo Larco en Lima" and
     *    "Desayuno Bueno Bonito y Barato en Lima" were reported as a gray box
     *    on staging). Both cases fall back to the same generic banner Tour
     *    already uses for the same reason (Tour::getCoverUrlAttribute).
     */
    public function getCoverUrlAttribute(): string
    {
        $path = $this->attributes['cover_image'] ?? null;

        if ($path && Storage::disk('public')->exists($path)) {
            return ImagePath::url($path);
        }

        return asset('assets/banners/banner-hero.jpg');
    }
}
