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
     * Correo de contacto público. Única fuente para topbar, footer, ficha de
     * contacto, JSON-LD y ESNNA.
     *
     * SIN default en código, y el motivo no es teórico: el 2026-08-24 el jefe
     * reportó que la topbar publicaba `hola@limaamericatours.com`, que no es
     * una cuenta suya. Ese correo salía de un `?: 'hola@…'` escrito en la
     * vista. Peor: había TRES direcciones inventadas distintas repartidas por
     * el repo — `hola@`, `info@` y `reservas@` — así que el sitio publicaba
     * una u otra según qué archivo pintara la línea, y ninguna existía. Mismo
     * criterio que contactPhone(): null significa "el cliente no lo cargó" y
     * cada consumidor oculta la línea en vez de inventar una casilla.
     */
    public static function contactEmail(): ?string
    {
        $value = trim((string) static::get('contact_email', ''));

        return $value !== '' ? $value : null;
    }

    /**
     * Segundo correo de contacto. El cliente atiende por dos cuentas y las dos
     * están publicadas en el footer del sitio de producción, una debajo de la
     * otra. Opcional: vacío = se pinta solo el principal.
     */
    public static function contactEmailSecondary(): ?string
    {
        $value = trim((string) static::get('contact_email_secondary', ''));

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

    /**
     * Dirección física de contacto en el idioma pedido, con solo un
     * fallback: al español, no a texto libre de idioma.
     *
     * SIN default en código a propósito. Hallazgo 2026-08-11: había DOS
     * direcciones publicadas al mismo tiempo — el Setting traía
     * "Av. Larcomar 233, Of. 410 — Miraflores, Lima" (idéntica a la
     * dirección real de Lima View Tours, OTRO cliente) y `lang/*` +
     * Términos/Privacidad tenían como fallback "Jr. Lampa 209, Lima
     * Center" (heredado del fork, tampoco confirmado). Ninguna de las dos
     * está verificada, así que no se "elige la buena": si el Setting está
     * vacío, esto devuelve null y cada consumidor oculta el bloque de
     * dirección en vez de imprimir un dato ajeno o adivinado.
     */
    public static function contactAddress(string $locale): ?string
    {
        $value = trim((string) static::get("contact_address_{$locale}", ''));
        if ($value === '') {
            $value = trim((string) static::get('contact_address_es', ''));
        }

        return $value !== '' ? $value : null;
    }

    /**
     * Horario de atención en el idioma pedido, con fallback solo al
     * español. SIN default en código: antes convivían hasta 4 horarios
     * distintos hardcodeados en footer.php, ui.php, legal.php y
     * contact.blade.php, ninguno igual al que el cliente carga en el
     * panel (Configuración → Contacto). Esta es la única fuente ahora.
     */
    public static function contactHours(string $locale): ?string
    {
        $value = trim((string) static::get("contact_hours_{$locale}", ''));
        if ($value === '') {
            $value = trim((string) static::get('contact_hours_es', ''));
        }

        return $value !== '' ? $value : null;
    }

    /**
     * RUC de la empresa. SIN default: el footer y los Términos publicaban
     * dos RUC contradictorios (20616108264 "Viaja con LAT S.A.C." y
     * 10720481826 "Díaz Córdova Augusto Manuel"), ninguno confirmado por
     * el cliente. Null hasta que el cliente confirme cuál es el correcto.
     */
    public static function companyRuc(): ?string
    {
        $value = trim((string) static::get('company_ruc', ''));

        return $value !== '' ? $value : null;
    }

    /**
     * Sello oficial que la agencia debe exhibir ("Agencia de viajes y turismo
     * registrada"). Pedido del jefe el 2026-08-21: la Municipalidad de Lima
     * les exige publicarlo. Es una IMAGEN QUE ENTREGA EL CLIENTE — acá no hay
     * default y no se dibuja una versión propia a mano: un sello oficial
     * redibujado por nosotros sería una falsificación, no un placeholder.
     * Null = el footer no pinta nada.
     */
    public static function registrySealPath(): ?string
    {
        $value = trim((string) static::get('company_registry_seal', ''));

        return $value !== '' ? $value : null;
    }

    /** Enlace de verificación del sello (registro público). Opcional. */
    public static function registrySealUrl(): ?string
    {
        $value = trim((string) static::get('company_registry_seal_url', ''));

        return $value !== '' ? $value : null;
    }

    /**
     * Sello ESNNA (compromiso contra la explotación sexual de niñas, niños y
     * adolescentes). Mismo criterio que el sello de registro: la imagen la
     * entrega el cliente. La PÁGINA de /esnna no depende de esto — el texto
     * del compromiso es nuestro y se publica igual, con sello o sin él.
     */
    public static function esnnaSealPath(): ?string
    {
        $value = trim((string) static::get('esnna_seal', ''));

        return $value !== '' ? $value : null;
    }

    /**
     * AFICHE oficial "Protégeme — Turismo Responsable" de MINCETUR, el que la
     * agencia debe exhibir. Es distinto del SELLO: el sello es la marquita del
     * footer y el afiche es el documento completo, con las tres leyes y las
     * líneas de denuncia (1818 y Línea 100).
     *
     * Pedido del jefe el 2026-08-25 con la referencia de limaexperience: "al
     * dar clic debe abrir esto". Por eso la página /esnna lo publica arriba
     * del todo y el enlace del footer sigue llevando ahí.
     *
     * Como los sellos: es un archivo de la autoridad, sin default y sin
     * versión redibujada por nosotros. Sin archivo, la página se publica con
     * su texto y nada más.
     */
    public static function esnnaPosterPath(): ?string
    {
        $value = trim((string) static::get('esnna_poster', ''));

        return $value !== '' ? $value : null;
    }

    /** Razón social. Mismo criterio que companyRuc(): sin default inventado. */
    public static function companyLegalName(): ?string
    {
        $value = trim((string) static::get('company_legal_name', ''));

        return $value !== '' ? $value : null;
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
