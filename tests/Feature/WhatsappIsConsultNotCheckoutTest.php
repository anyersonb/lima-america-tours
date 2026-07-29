<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regla de negocio (docs/pagos/PLAN-PASARELAS.md, y repetida por el jefe el
 * 2026-07-29): **WhatsApp es un canal de CONSULTA, nunca de cierre.** La reserva
 * se completa en el sitio. Un botón que dice "Reservar por WhatsApp" manda al
 * cliente a un chat que no cobra, no bloquea cupo y no deja registro de reserva.
 *
 * El drawer móvil llevaba exactamente ese texto ("Reservar por WhatsApp") en los
 * tres idiomas. Este test lo bloquea de forma general: ningún enlace a wa.me
 * puede prometer reservar/book.
 *
 * Antes del fix: falla (nav.book_whatsapp = "Reservar por WhatsApp").
 * Después del fix: pasa.
 */
class WhatsappIsConsultNotCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** Verbos de cierre que no pueden aparecer en un CTA de WhatsApp. */
    private const CLOSING_VERBS = ['reservar', 'reserva', 'book', 'comprar', 'pagar', 'checkout'];

    public function test_no_whatsapp_link_promises_a_booking(): void
    {
        Setting::set('whatsapp', '51957299438');

        foreach (['es', 'en', 'pt'] as $locale) {
            $html = $this->get("/{$locale}")->assertOk()->getContent();

            // Texto visible de cada enlace/botón que apunte a wa.me.
            preg_match_all('/<a[^>]*wa\.me[^>]*>(.*?)<\/a>/si', $html, $matches);

            foreach ($matches[1] ?? [] as $inner) {
                $text = mb_strtolower(trim(preg_replace('/\s+/', ' ', strip_tags($inner))));

                if ($text === '') {
                    continue; // enlaces solo-ícono (el FAB): no prometen nada
                }

                foreach (self::CLOSING_VERBS as $verb) {
                    $this->assertStringNotContainsString(
                        $verb,
                        $text,
                        "[{$locale}] Un CTA de WhatsApp promete cerrar la venta: \"{$text}\". ".
                        'WhatsApp es consulta; la reserva se cierra en el sitio.'
                    );
                }
            }
        }
    }

    public function test_the_nav_translation_itself_is_a_consult_copy(): void
    {
        foreach (['es', 'en', 'pt'] as $locale) {
            $copy = mb_strtolower(__('nav.book_whatsapp', [], $locale));

            foreach (self::CLOSING_VERBS as $verb) {
                $this->assertStringNotContainsString($verb, $copy, "[{$locale}] nav.book_whatsapp = \"{$copy}\"");
            }
        }
    }

    /**
     * El CTA principal del header ("Reservar Ahora") sí cierra: por eso debe
     * llevar al catálogo del sitio y NO a WhatsApp (cambio del 2026-07-29).
     */
    public function test_header_book_now_cta_points_to_the_catalogue(): void
    {
        Setting::set('whatsapp', '51957299438');

        $html = $this->get('/es')->assertOk()->getContent();

        preg_match('/<a[^>]*class="[^"]*lat-btn-reservar[^"]*"[^>]*href="([^"]+)"/', $html, $m)
            || preg_match('/<a[^>]*href="([^"]+)"[^>]*class="[^"]*lat-btn-reservar/', $html, $m);

        $href = $m[1] ?? '';

        $this->assertNotSame('', $href, 'No se encontró el CTA "Reservar Ahora" del header.');
        $this->assertStringNotContainsString('wa.me', $href, '"Reservar Ahora" no debe ir a WhatsApp.');
        $this->assertStringContainsString('/es/tours', $href, "\"Reservar Ahora\" debe llevar al catálogo: {$href}");
    }
}
