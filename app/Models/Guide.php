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

    public function getPhotoUrlAttribute(): ?string
    {
        return ImagePath::url($this->photo);
    }
}
