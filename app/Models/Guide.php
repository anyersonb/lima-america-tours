<?php

namespace App\Models;

use App\Support\ImagePath;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guide extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'languages' => 'array',
        'years_experience' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    public function getRoleAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->{"role_{$locale}"} ?: $this->role_es;
    }

    public function getBioAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->{"bio_{$locale}"} ?: $this->bio_es;
    }

    /**
     * Dato de valor opcional (ej. "Mencionada por nombre en 15 de 20 reseñas").
     * Casi ningún guía lo tendrá: se deja vacío salvo que exista un respaldo
     * verificable (ver GuideSeeder para el caso de Samira).
     */
    public function getHighlightAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->{"highlight_{$locale}"} ?: $this->highlight_es;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return ImagePath::url($this->photo);
    }

    /**
     * URL del perfil de Instagram, o null si no hay handle real cargado.
     * `instagram_handle` se guarda sin "@" ni URL completa (solo el usuario);
     * el mockup solo pinta el ícono cuando esto no es null.
     */
    public function getInstagramUrlAttribute(): ?string
    {
        $handle = trim((string) $this->instagram_handle);
        if ($handle === '') {
            return null;
        }

        if (str_starts_with($handle, 'http://') || str_starts_with($handle, 'https://')) {
            return $handle;
        }

        return 'https://instagram.com/'.ltrim($handle, '@');
    }

    /**
     * Link de wa.me listo para usar, o null si no hay número real cargado.
     * Mismo criterio que Setting::whatsappNumber(): solo dígitos, sin "+".
     */
    public function getWhatsappUrlAttribute(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->whatsapp_number) ?? '';

        return $digits !== '' ? "https://wa.me/{$digits}" : null;
    }
}
