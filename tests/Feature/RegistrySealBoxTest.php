<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\RegistrySeal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El sello "Agencia de viajes y turismo registrada" tiene tamaño NORMADO.
 *
 * El manual de marca que entregó el cliente el 2026-08-25 fija dos
 * presentaciones para medios digitales, y no son intercambiables:
 * vertical 150×250 y horizontal 300×120.
 *
 * Qué había antes: el footer lo pintaba con `height: clamp(52px,7vw,68px)` y
 * ancho automático. El archivo del cliente resultó VERTICAL (480×758), así que
 * el navegador lo dejaba en **33×52 px** — medido, no supuesto — o sea la
 * quinta parte de lo normado, con las cuatro líneas de "Agencia de viajes y
 * turismo registrada" reducidas a una mancha.
 *
 * Por qué esto vive en un test y no solo en el CSS: el tamaño depende de la
 * ORIENTACIÓN del archivo, y el archivo lo cambia el cliente desde el panel
 * cuando quiera. Una regla escrita a mano para "el sello que tenemos hoy" se
 * rompe en silencio el día que suba la versión horizontal.
 */
class RegistrySealBoxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
        Cache::flush();
    }

    /** Escribe un PNG real del tamaño pedido: getimagesize() lee la cabecera. */
    private function fakeSeal(string $path, int $w, int $h): void
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        Storage::disk('media')->put($path, $bytes);
    }

    public function test_un_sello_vertical_se_pinta_a_150x250(): void
    {
        $this->fakeSeal('legal/vertical.png', 480, 758);

        $box = RegistrySeal::box('legal/vertical.png');

        $this->assertSame(150, $box['w']);
        $this->assertSame(250, $box['h']);
        $this->assertSame('vertical', $box['orientation']);
    }

    public function test_un_sello_horizontal_se_pinta_a_300x120(): void
    {
        $this->fakeSeal('legal/horizontal.png', 900, 360);

        $box = RegistrySeal::box('legal/horizontal.png');

        $this->assertSame(300, $box['w']);
        $this->assertSame(120, $box['h']);
        $this->assertSame('horizontal', $box['orientation']);
    }

    /** Sin archivo no se inventa una caja: el footer cae a su tamaño por defecto. */
    public function test_sin_sello_no_hay_caja(): void
    {
        $this->assertNull(RegistrySeal::box(null));
        $this->assertNull(RegistrySeal::box(''));
        $this->assertNull(RegistrySeal::box('legal/no-existe.png'));
    }

    /**
     * La prueba que de verdad importa: lo que llega al HTML.
     *
     * Que el helper devuelva 150×250 no sirve de nada si el footer no lo usa —
     * que es exactamente lo que pasaba antes, con el helper inexistente y el
     * tamaño hardcodeado en el CSS.
     */
    public function test_el_footer_publica_los_width_height_del_manual(): void
    {
        $this->fakeSeal('legal/vertical.png', 480, 758);
        Setting::set('company_registry_seal', 'legal/vertical.png');

        $html = $this->get('/es')->assertOk()->getContent();

        $this->assertStringContainsString('lat-footer__seal-img--vertical', $html);
        $this->assertMatchesRegularExpression(
            '/<img[^>]+legal\/vertical\.png[^>]+width="150"[^>]+height="250"/',
            $html,
            'El sello del footer no salió con los 150x250 que fija el manual de marca.'
        );
    }

    /** El RUC confirmado el 2026-08-25 se publica; vacío no imprime nada. */
    public function test_el_ruc_solo_se_publica_cuando_esta_cargado(): void
    {
        $sinRuc = $this->get('/es')->assertOk()->getContent();
        $this->assertStringNotContainsString('lat-footer__ruc', $sinRuc);

        Setting::set('company_ruc', '20616108264');

        $conRuc = $this->get('/es')->assertOk()->getContent();
        $this->assertStringContainsString('20616108264', $conRuc);
    }
}
