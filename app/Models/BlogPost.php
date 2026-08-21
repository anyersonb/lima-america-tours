<?php

namespace App\Models;

use App\Support\ImagePath;
use App\Support\VideoEmbed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'features' => 'array',
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
    // Relations
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Optional real author (docs/rebrand/inventario/spec-03-blog.md §5.1,
     * §9). Reuses the `Guide` model already built for "Nosotros" instead of
     * a second author-with-photo system. See the `signature_*` accessors
     * below for the precedence rule against the loose author_name/
     * author_role/author_photo columns.
     */
    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
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

    /**
     * Author avatar for the byline row. Unlike `cover_url`, this has no
     * generic-banner fallback: there's no honest stand-in for "a photo of
     * this specific person", so an empty/missing file returns null and the
     * byline component is expected to hide the avatar entirely rather than
     * show a stock silhouette (same "no invented placeholder" rule already
     * applied to rating and price elsewhere in this project). Checks the
     * disk, not just the column, for the same reason `cover_url` does: a DB
     * copied without its uploads shouldn't render a broken image.
     */
    public function getAuthorPhotoUrlAttribute(): ?string
    {
        $path = $this->attributes['author_photo'] ?? null;

        if ($path && Storage::disk('public')->exists($path)) {
            return ImagePath::url($path);
        }

        return null;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Signature (byline) — guide_id vs. loose author_* precedence
    // ──────────────────────────────────────────────────────────────────────────
    //
    // Four sources can describe the byline today: `guide_id` (FK to a real
    // `Guide` with photo/rol/bio) and the three loose columns `author_name`,
    // `author_role`, `author_photo` (added 2026-08-19 as a stopgap before
    // any post had a real author). The rule, decided here and documented so
    // it never competes silently again (same class of bug already hit with
    // phone/photo/address duplicated between Lima View and Lima América):
    //
    //   guide_id set + the Guide still exists  → the Guide wins completely
    //   for name/role/photo/verified-badge. The loose columns are ignored,
    //   not merged field-by-field (no "guide's photo but the loose role"
    //   Frankenstein state).
    //
    //   guide_id empty (or the linked Guide was deleted, which nullOnDelete
    //   already turns back into an empty guide_id) → fall back to the loose
    //   author_name/author_role/author_photo_url accessors exactly as they
    //   worked before this migration.
    //
    // These are exposed under NEW accessor names (`signature_*`), not by
    // overriding `getAuthorNameAttribute()`/`getAuthorRoleAttribute()`
    // in place. Reason: Filament's edit form pre-fills from
    // `$record->attributesToArray()`, which runs through accessors too — if
    // `author_name` resolved the Guide's name, opening "Editar" on a post
    // with a guide assigned would show the guide's name sitting inside the
    // "Nombre del autor" text field, and saving would silently copy it into
    // the raw column, poisoning the fallback data it's supposed to preserve.
    // Keeping the raw accessors untouched (still admin-editable, still the
    // fallback) and adding a distinct read-only "resolved for display" API
    // sidesteps that. The public byline (view layer, built separately)
    // should read `signature_name`/`signature_role`/`signature_photo_url`/
    // `signature_is_verified` — never the raw `author_*` fields directly.

    /**
     * The Guide actually wins only while it still exists — `nullOnDelete()`
     * on `guide_id` means a deleted guide already clears the FK, but this
     * also guards against a stale relation being cached with no matching row.
     */
    private function resolvedGuide(): ?Guide
    {
        return $this->guide_id ? $this->guide : null;
    }

    public function getSignatureNameAttribute(): ?string
    {
        return $this->resolvedGuide()?->name ?? $this->author_name;
    }

    public function getSignatureRoleAttribute(): ?string
    {
        if ($guide = $this->resolvedGuide()) {
            return $guide->role;
        }

        $raw = trim((string) ($this->attributes['author_role'] ?? ''));

        return $raw !== '' ? $raw : null;
    }

    public function getSignaturePhotoUrlAttribute(): ?string
    {
        return $this->resolvedGuide()?->photo_url ?? $this->author_photo_url;
    }

    /**
     * Verified checkmark next to the byline (spec-03-blog.md §5.1, and
     * §2.B #10 of 02-tour-y-blog.md): derived from having a real linked
     * `Guide` — staff is "verified" by definition — never a boolean column,
     * and never true for the loose author_* fallback (anyone could type
     * "Augusto" in a text box; only a linked Guide is a real team member).
     */
    public function getSignatureIsVerifiedAttribute(): bool
    {
        return $this->resolvedGuide() !== null;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Hero video (play button) and feature cards
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * `video_url` stores the pasted "share" link; this resolves it to the
     * embeddable form for an <iframe>, same normalizer/pattern as
     * `Tour::getVideoEmbedUrlAttribute()`. Null when empty or unrecognized —
     * the consumer should hide the play button rather than render a broken
     * embed.
     */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        return VideoEmbed::normalize($this->video_url);
    }

    /**
     * Resolves the up-to-4-row `features` JSON into the current locale,
     * dropping any row without a title. Deliberately NOT "always return 4
     * items" — the floating card on top of the hero has to look right with
     * 0, 2, 3 or 4 blocks (spec-03-blog.md §2), so the view can just
     * @foreach this and never guard a count itself.
     */
    public function getFeatureCardsAttribute(): array
    {
        $rows = is_array($this->features) ? $this->features : [];
        $locale = app()->getLocale();
        $cards = [];

        foreach (array_slice($rows, 0, 4) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $title = trim((string) ($row["title_{$locale}"] ?? $row['title_es'] ?? ''));
            if ($title === '') {
                continue;
            }

            $text = trim((string) ($row["text_{$locale}"] ?? $row['text_es'] ?? ''));
            $icon = trim((string) ($row['icon'] ?? ''));

            $cards[] = [
                'icon' => $icon !== '' ? $icon : null,
                'title' => $title,
                'text' => $text !== '' ? $text : null,
            ];
        }

        return $cards;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Pull quote
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Locale-aware quote text, falling back to Spanish like title/excerpt/
     * body — but unlike those, returns null (not '') when there's really no
     * quote in any locale, so the pull-quote component can hide itself
     * instead of rendering an empty <blockquote>.
     */
    public function getQuoteTextAttribute(): ?string
    {
        $locale = app()->getLocale();
        $value = trim((string) ($this->{"quote_text_{$locale}"} ?? $this->quote_text_es ?? ''));

        return $value !== '' ? $value : null;
    }

    /**
     * "— Augusto, Guía Local". Not locale-specific (same criterion already
     * used for `author_role` on this table): a short name+role string, not
     * prose that realistically changes per language.
     */
    public function getQuoteAttributionAttribute(): ?string
    {
        $raw = trim((string) ($this->attributes['quote_attribution'] ?? ''));

        return $raw !== '' ? $raw : null;
    }
}
