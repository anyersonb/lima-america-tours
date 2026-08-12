<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fixes 2 y 3 de docs/rebrand/LOTE-MOCKUPS-AGO-2026.md (lote "mockups
 * agosto 2026", medición real en navegador + CRO):
 *
 * Fix 2 (Medio) — el footer prometía "Pago 100% Seguro" sin tener ninguna
 * pasarela activa en v1 (PayPal apagado server-side, Culqi espera llaves
 * del cliente, la reserva se cierra por WhatsApp/correo). Prometer eso es
 * afirmar algo falso — la tabla "Lo que NO se publica" del contrato del
 * lote lo excluye explícitamente. El sello se retiró de los 3 idiomas.
 *
 * Fix 3 (Bajo) — `apple-touch-icon.png` daba 404 en las 4 páginas porque
 * `layouts/app.blade.php` lo referenciaba sin que el archivo existiera en
 * `public/`. Se generó el ícono real (180×180, mismo diseño de círculo rojo
 * de marca + "LA" que ya usa `favicon.svg`) para mantener consistencia
 * visual con el resto de íconos del sitio.
 */
class FooterPaymentSealAndAppleTouchIconTest extends TestCase
{
    use RefreshDatabase;

    /** @dataProvider localeProvider */
    public function test_footer_never_promises_secure_payment_in_any_locale(string $locale, string $forbidden): void
    {
        $response = $this->get(route('home', ['locale' => $locale]));

        $response->assertOk();
        $response->assertDontSee($forbidden);
        // Guard contra el modo de falla real: si el fix solo hubiera
        // borrado la clave de lang/ sin tocar el Blade, Laravel imprime la
        // clave cruda en vez del texto — un defecto peor que el original.
        $response->assertDontSee('footer.seal_secure_payment');
    }

    public static function localeProvider(): array
    {
        return [
            'es' => ['es', 'Pago 100% Seguro'],
            'en' => ['en', '100% Secure Payment'],
            'pt' => ['pt', 'Pagamento 100% Seguro'],
        ];
    }

    /**
     * Los sellos que SÍ son ciertos deben seguir publicándose: el fix es
     * quitar la promesa falsa, no vaciar la franja completa de confianza.
     */
    public function test_footer_keeps_the_seals_that_are_still_true(): void
    {
        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        $response->assertSee(__('footer.seal_best_price'));
        $response->assertSee(__('footer.seal_responsible'));
    }

    public function test_apple_touch_icon_file_exists_and_is_a_180x180_png(): void
    {
        $path = public_path('apple-touch-icon.png');

        $this->assertFileExists($path, 'apple-touch-icon.png debe existir en public/ — el layout lo referencia en las 4 páginas.');

        $info = getimagesize($path);
        $this->assertNotFalse($info, 'apple-touch-icon.png debe ser una imagen válida.');
        $this->assertSame(IMAGETYPE_PNG, $info[2], 'apple-touch-icon.png debe ser PNG.');
        $this->assertSame(180, $info[0]);
        $this->assertSame(180, $info[1]);
    }

    public function test_layout_references_the_apple_touch_icon_that_now_exists(): void
    {
        $response = $this->get(route('home', ['locale' => 'es']));

        $response->assertOk();
        $response->assertSee('apple-touch-icon.png', false);
        $this->assertFileExists(public_path('apple-touch-icon.png'));
    }
}
