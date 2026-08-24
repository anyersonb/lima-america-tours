<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lote del 2026-08-24 — los comentarios que mandó el jefe por WhatsApp el
 * 2026-08-21: RUC, el sello que le pide la municipalidad, ESNNA y el bloque de
 * reseñas de Tripadvisor.
 *
 * Lo que estos tests protegen NO es que las piezas se vean: es que NO se vean
 * cuando el dato no está. En este repo ya se publicaron un "4.8 (0 reseñas)"
 * sembrado en los 24 tours, un "Desde $200" del seeder y dos RUC
 * contradictorios a la vez. La regla es: sin dato confirmado, no se pinta —
 * y un guard sin test se rompe en el próximo lote sin que nadie lo note.
 */
class FooterTrustAndEsnnaTest extends TestCase
{
    use RefreshDatabase;

    private function footer(string $html): string
    {
        $start = strpos($html, '<footer');
        $this->assertNotFalse($start, 'No se encontró el footer.');

        return substr($html, $start);
    }

    private function cargarTripadvisorCompleto(): void
    {
        Setting::updateOrCreate(['key' => 'social_tripadvisor'], ['value' => 'https://www.tripadvisor.com/Attraction_Review-PRUEBA', 'type' => 'string']);
        Setting::updateOrCreate(['key' => 'tripadvisor_rating'], ['value' => '4.9', 'type' => 'string']);
        Setting::updateOrCreate(['key' => 'tripadvisor_reviews_count'], ['value' => '3968', 'type' => 'string']);
        \Illuminate\Support\Facades\Cache::forget('settings.all');
    }

    // ── Bloque de Tripadvisor: todo o nada ──────────────────────────────

    public function test_el_bloque_de_tripadvisor_no_se_pinta_sin_datos(): void
    {
        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringNotContainsString('lat-ta__rating', $this->footer($html),
            'El bloque de Tripadvisor apareció sin ningún dato cargado.');
    }

    public function test_el_bloque_de_tripadvisor_se_pinta_con_los_tres_datos(): void
    {
        $this->cargarTripadvisorCompleto();

        $footer = $this->footer($this->get('/es')->assertOk()->getContent());

        $this->assertStringContainsString('lat-ta__rating', $footer);
        $this->assertStringContainsString('4,9', $footer, 'El rating debe ir con coma decimal en español.');
        $this->assertStringContainsString('3.968 reseñas', $footer, 'La cantidad debe ir con separador de miles.');
        $this->assertStringContainsString('tripadvisor.com/Attraction_Review-PRUEBA', $footer);
    }

    public function test_falta_uno_de_los_tres_datos_y_el_bloque_desaparece(): void
    {
        // Control del control: primero se comprueba que CON los tres aparece,
        // así el "no aparece" de abajo significa algo. Un guard que oculta
        // siempre pasaría este test sin verificar nada.
        $this->cargarTripadvisorCompleto();
        $this->assertStringContainsString('lat-ta__rating', $this->footer($this->get('/es')->getContent()));

        foreach (['social_tripadvisor', 'tripadvisor_rating', 'tripadvisor_reviews_count'] as $faltante) {
            $this->cargarTripadvisorCompleto();
            Setting::updateOrCreate(['key' => $faltante], ['value' => '', 'type' => 'string']);
            \Illuminate\Support\Facades\Cache::forget('settings.all');

            $this->assertStringNotContainsString('lat-ta__rating', $this->footer($this->get('/es')->getContent()),
                "El bloque se pintó con «{$faltante}» vacío: la cifra queda sin respaldo verificable.");
        }
    }

    public function test_un_rating_fuera_de_rango_no_se_publica(): void
    {
        $this->cargarTripadvisorCompleto();
        Setting::updateOrCreate(['key' => 'tripadvisor_rating'], ['value' => '7', 'type' => 'string']);
        \Illuminate\Support\Facades\Cache::forget('settings.all');

        $this->assertStringNotContainsString('lat-ta__rating', $this->footer($this->get('/es')->getContent()),
            'Un 7 sobre 5 es un dato mal cargado y rompería las estrellas: no debe publicarse.');
    }

    public function test_la_posicion_es_opcional_y_no_tumba_el_bloque(): void
    {
        $this->cargarTripadvisorCompleto();
        $footer = $this->footer($this->get('/es')->getContent());

        $this->assertStringContainsString('lat-ta__rating', $footer);
        $this->assertStringNotContainsString('#1 en Lima', $footer);

        Setting::updateOrCreate(['key' => 'tripadvisor_rank_es'], ['value' => '#1 en Lima', 'type' => 'string']);
        \Illuminate\Support\Facades\Cache::forget('settings.all');

        $this->assertStringContainsString('#1 en Lima', $this->footer($this->get('/es')->getContent()));
    }

    // ── RUC y sellos ────────────────────────────────────────────────────

    public function test_el_ruc_no_se_publica_vacio_y_si_cargado(): void
    {
        $this->assertStringNotContainsString('lat-footer__ruc', $this->footer($this->get('/es')->getContent()),
            'Sin RUC confirmado no se imprime nada (llegaron a convivir dos RUC distintos publicados).');

        Setting::updateOrCreate(['key' => 'company_ruc'], ['value' => '20512345678', 'type' => 'string']);
        \Illuminate\Support\Facades\Cache::forget('settings.all');

        $footer = $this->footer($this->get('/es')->getContent());
        $this->assertStringContainsString('lat-footer__ruc', $footer);
        $this->assertStringContainsString('20512345678', $footer);
    }

    public function test_los_sellos_solo_aparecen_si_hay_imagen_cargada(): void
    {
        $this->assertStringNotContainsString('lat-footer__seal-img', $this->footer($this->get('/es')->getContent()),
            'Sin imagen del cliente no se dibuja ningún sello: uno redibujado por nosotros sería falso.');

        Setting::updateOrCreate(['key' => 'company_registry_seal'], ['value' => 'legal/sello.png', 'type' => 'string']);
        \Illuminate\Support\Facades\Cache::forget('settings.all');

        $footer = $this->footer($this->get('/es')->getContent());
        $this->assertStringContainsString('lat-footer__seal-img', $footer);
        $this->assertStringContainsString('legal/sello.png', $footer);
    }

    public function test_el_sello_de_esnna_enlaza_a_la_pagina_del_codigo(): void
    {
        Setting::updateOrCreate(['key' => 'esnna_seal'], ['value' => 'legal/esnna.png', 'type' => 'string']);
        \Illuminate\Support\Facades\Cache::forget('settings.all');

        $footer = $this->footer($this->get('/es')->getContent());

        $this->assertMatchesRegularExpression('#<a href="[^"]*/es/esnna"[^>]*class="lat-footer__seal-img"#', $footer,
            'Un sello que no lleva a ninguna parte es solo un dibujo.');
    }

    // ── Página ESNNA ────────────────────────────────────────────────────

    /** @dataProvider locales */
    public function test_la_pagina_esnna_responde_en_los_tres_idiomas(string $locale, string $titulo): void
    {
        $res = $this->get("/{$locale}/esnna")->assertOk();

        $res->assertSee($titulo, false);
        // Los canales de denuncia del Estado peruano van SIEMPRE: no dependen
        // de ningún dato del panel.
        $res->assertSee('100', false);
        $res->assertSee('105', false);
        $res->assertSee('28251', false);
    }

    public static function locales(): array
    {
        return [
            'es' => ['es', 'Código de conducta contra la ESNNA'],
            'en' => ['en', 'Code of conduct against ESNNA'],
            'pt' => ['pt', 'Código de conduta contra a ESNNA'],
        ];
    }

    public function test_el_footer_enlaza_a_esnna_en_los_tres_idiomas(): void
    {
        foreach (['es' => 'Código de conducta ESNNA', 'en' => 'ESNNA code of conduct', 'pt' => 'Código de conduta ESNNA'] as $locale => $texto) {
            $footer = $this->footer($this->get("/{$locale}")->assertOk()->getContent());

            $this->assertStringContainsString("/{$locale}/esnna", $footer, "Falta el enlace a ESNNA en /{$locale}.");
            $this->assertStringContainsString($texto, $footer, "El enlace a ESNNA no está traducido en /{$locale}.");
        }
    }

    public function test_la_pagina_esnna_no_publica_la_fecha_de_terminos(): void
    {
        // La página se creó el 2026-08-24; `legal.last_updated` (Términos y
        // Privacidad) dice mayo. Publicar una fecha de actualización falsa en
        // un documento legal es peor que no publicar ninguna.
        $html = $this->get('/es/esnna')->assertOk()->getContent();

        $this->assertStringContainsString(__('legal.esnna_last_updated'), $html);
        $this->assertStringNotContainsString(__('legal.last_updated'), $html);
    }
}
