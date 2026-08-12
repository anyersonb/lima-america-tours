<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'site_name', 'value' => 'Lima América Tours', 'group' => 'general'],
            ['key' => 'site_tagline_es', 'value' => 'Descubre la magia de Perú, un viaje que transforma', 'group' => 'general'],
            ['key' => 'site_tagline_en', 'value' => 'Discover the magic of Peru, a journey that transforms', 'group' => 'general'],
            ['key' => 'site_description_es', 'value' => 'Tours por Lima, Ica, Cusco y Machu Picchu con guías oficiales y experiencias auténticas.', 'group' => 'general'],
            ['key' => 'site_description_en', 'value' => 'Tours in Lima, Ica, Cusco and Machu Picchu with official guides and authentic experiences.', 'group' => 'general'],

            // Contacto
            ['key' => 'contact_email', 'value' => 'hola@limaamericatours.com', 'group' => 'contact'],
            // Vacíos a propósito: "+51 925 886 725" / "51925886725" eran el
            // teléfono y WhatsApp REALES de Lima View Tours (otro cliente,
            // proyecto del que este se forkeó), sembrados aquí como si fueran
            // el dato real de Lima América. El front nunca cae en ese fallback
            // (ver App\Models\Setting::contactPhone()/whatsappNumber()): oculta
            // el bloque de teléfono/WhatsApp hasta que el cliente cargue su
            // propio número en Configuración → Contacto.
            ['key' => 'contact_phone', 'value' => '', 'group' => 'contact'],
            // Vacío a propósito: es un teléfono secundario opcional. El front
            // (contact.blade.php) ya lo oculta con @if(!empty(...)) cuando no
            // hay valor cargado desde Configuración → Contacto.
            ['key' => 'contact_phone_secondary', 'value' => '', 'group' => 'contact'],
            // Vacíos a propósito desde 2026-08-11: "Av. Larcomar 233, Of. 410 —
            // Miraflores, Lima" es IDÉNTICA a la dirección real de Lima View
            // Tours (otro cliente) — el mismo patrón de contaminación del fork
            // que ya afectó a contact_phone/whatsapp arriba. Al mismo tiempo,
            // lang/*/footer.php y Términos/Privacidad publicaban una SEGUNDA
            // dirección ("Jr. Lampa 209, Lima Center") heredada del fork,
            // tampoco confirmada. Ninguna de las dos se "elige como la buena":
            // el Setting queda vacío hasta que el cliente confirme la
            // dirección real (docs/rebrand/ESTADO.md). Setting::contactAddress()
            // devuelve null con esto vacío, y cada consumidor oculta el bloque.
            ['key' => 'contact_address_es', 'value' => '', 'group' => 'contact'],
            ['key' => 'contact_address_en', 'value' => '', 'group' => 'contact'],
            ['key' => 'contact_hours_es', 'value' => 'Lun – Vie: 9:00 a.m. – 7:00 p.m.', 'group' => 'contact'],
            ['key' => 'contact_hours_en', 'value' => 'Mon – Fri: 9:00 a.m. – 7:00 p.m.', 'group' => 'contact'],
            ['key' => 'whatsapp', 'value' => '', 'group' => 'contact'],

            // Redes sociales
            ['key' => 'social_instagram', 'value' => 'https://instagram.com/limaamericatours', 'group' => 'social'],
            ['key' => 'social_facebook', 'value' => 'https://facebook.com/limaamericatours', 'group' => 'social'],
            ['key' => 'social_tiktok', 'value' => 'https://tiktok.com/@limaamericatours', 'group' => 'social'],
            ['key' => 'social_youtube', 'value' => 'https://youtube.com/@limaamericatours', 'group' => 'social'],

            // SEO
            ['key' => 'seo_default_title', 'value' => 'Lima América Tours — Tours auténticos por Perú', 'group' => 'seo'],
            ['key' => 'seo_default_description', 'value' => 'Descubre Lima, Ica, Cusco y Machu Picchu con Lima América Tours. Guías oficiales, transporte cómodo y experiencias diseñadas para viajeros exigentes.', 'group' => 'seo'],
            ['key' => 'seo_default_keywords', 'value' => 'tours peru, lima américa tours, machu picchu, huacachina, paracas, cusco', 'group' => 'seo'],
            ['key' => 'seo_og_image', 'value' => 'assets/banners/banner-hero.jpg', 'group' => 'seo'],
            ['key' => 'seo_google_site_verification', 'value' => '', 'group' => 'seo'],
            ['key' => 'seo_bing_site_verification', 'value' => '', 'group' => 'seo'],
            ['key' => 'seo_google_analytics_id', 'value' => '', 'group' => 'seo'],
            ['key' => 'seo_gtm_id', 'value' => '', 'group' => 'seo'],
            ['key' => 'seo_facebook_pixel', 'value' => '', 'group' => 'seo'],

            // Hero
            ['key' => 'hero_title_es', 'value' => 'Descubre la magia de Perú, un viaje que transforma', 'group' => 'home'],
            ['key' => 'hero_title_en', 'value' => 'Discover the magic of Peru, a journey that transforms', 'group' => 'home'],
            ['key' => 'hero_subtitle_es', 'value' => 'Vive una aventura inolvidable por los destinos más impresionantes del Perú.', 'group' => 'home'],
            ['key' => 'hero_image', 'value' => 'assets/banners/banner-hero.jpg', 'group' => 'home'],

            // Stats
            ['key' => 'stats_travelers', 'value' => '+824', 'group' => 'home'],
            ['key' => 'stats_years', 'value' => '+11', 'group' => 'home'],
            ['key' => 'stats_rating', 'value' => '4.8', 'group' => 'home'],
            ['key' => 'stats_tours', 'value' => '+50', 'group' => 'home'],

            // Métodos de pago
            ['key' => 'payment_methods', 'type' => 'array', 'value' => json_encode(['VISA', 'Mastercard', 'AmEx', 'PayPal']), 'group' => 'payment'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
