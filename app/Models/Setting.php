<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        $clear = fn () => Cache::forget('settings.all');
        static::saved($clear);
        static::deleted($clear);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', function () {
            return static::all()->mapWithKeys(fn ($s) => [$s->key => static::castValue($s->value, $s->type)])->all();
        });

        return $all[$key] ?? $default;
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): self
    {
        $stored = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;

        return static::updateOrCreate(['key' => $key], ['value' => $stored, 'type' => $type, 'group' => $group]);
    }

    /**
     * Teléfono de contacto público, en el formato que carga el cliente en el
     * panel (p.ej. "+51 999 999 999").
     *
     * SIN fallback a propósito: este proyecto es un fork del motor de otro
     * cliente (Lima View Tours) y trece copias de "?: '+51 925 886 725'"
     * quedaron regadas por vistas, controllers y correos. En cuanto el
     * Setting quedaba vacío, el número del OTRO cliente reaparecía en el
     * sitio público, en el JSON-LD que lee Google y en los correos
     * transaccionales. Null significa "el cliente todavía no cargó el
     * dato": cada consumidor decide cómo fallar en seguro (ocultar el
     * bloque de teléfono, omitir la propiedad del schema, no imprimir la
     * línea en el correo), nunca inventar o heredar un número ajeno.
     */
    public static function contactPhone(): ?string
    {
        $value = trim((string) static::get('contact_phone', ''));

        return $value !== '' ? $value : null;
    }

    /**
     * Número de WhatsApp listo para un enlace wa.me: solo dígitos, con
     * código de país, sin "+" ni espacios. Prioriza el Setting 'whatsapp'
     * explícito; si no existe, lo deriva de contactPhone(). Sin fallback a
     * un número ajeno (mismo motivo que contactPhone()).
     */
    public static function whatsappNumber(): ?string
    {
        $raw = trim((string) static::get('whatsapp', ''));

        if ($raw === '') {
            $raw = static::contactPhone() ?? '';
        }

        $digits = preg_replace('/\D/', '', $raw);

        return $digits !== '' ? $digits : null;
    }

    protected static function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'array', 'json' => $value ? json_decode($value, true) : [],
            default => $value,
        };
    }
}
