<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El comando `data:audit-foreign` es la red que faltaba: los tres arrastres de
 * Lima View Tours que llegaron a publicarse (teléfono, foto, dirección) tenían el
 * código ya corregido y el dato vivo en la base — y en la base LOCAL estaba
 * limpio, así que ningún test los habría visto. El comando se corre contra el
 * entorno real; estos tests solo verifican que sabe detectar y que no grita
 * cuando no hay nada.
 */
class AuditForeignClientDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_pasa_cuando_la_base_no_tiene_datos_de_otro_cliente(): void
    {
        Setting::set('contact_address_es', 'Calle Propia 123, Lima');
        Setting::set('contact_phone', '+51 957 299 438');

        $this->artisan('data:audit-foreign')
            ->expectsOutputToContain('Limpio')
            ->assertExitCode(0);
    }

    public function test_detecta_la_direccion_de_otro_cliente(): void
    {
        Setting::set('contact_address_es', 'Av. Larcomar 233, Of. 410 — Miraflores, Lima');

        $this->artisan('data:audit-foreign')->assertExitCode(1);
    }

    public function test_detecta_el_telefono_de_otro_cliente(): void
    {
        Setting::set('whatsapp', '51925886725');

        $this->artisan('data:audit-foreign')->assertExitCode(1);
    }

    public function test_detecta_un_asset_de_otro_cliente_en_cualquier_columna_de_texto(): void
    {
        Setting::set('home_gallery_img_2', 'tours/machu-picchu-paquete-de-4-dias-lima-view-tours.jpg');

        $this->artisan('data:audit-foreign')->assertExitCode(1);
    }

    public function test_no_marca_la_palabra_lima_sola_que_es_legitima(): void
    {
        // "Lima" aparece en medio catálogo con toda razón: si el comando la marcara,
        // nadie lo correría dos veces.
        Setting::set('contact_address_es', 'Centro Histórico de Lima, Lima, Perú');
        Setting::set('site_name', 'Lima América Tours');

        $this->artisan('data:audit-foreign')->assertExitCode(0);
    }

    public function test_el_modo_json_devuelve_el_mismo_veredicto_para_encadenarlo_a_un_deploy(): void
    {
        // El contenido del JSON se verificó por CLI (`artisan data:audit-foreign
        // --json`), donde la salida sale por stdout de verdad: devuelve un array de
        // objetos con table/column/id/pattern/reason/value. Acá solo se fija el
        // contrato que consume un script de deploy: el código de salida.
        // Con `--json`, ni `Artisan::output()` ni `expectsOutputToContain()` ven la
        // salida del comando en este entorno, así que afirmar sobre el texto desde
        // un test sería afirmar sobre algo que no se está midiendo.
        Setting::set('contact_address_es', 'Av. Larcomar 233, Of. 410');
        $this->artisan('data:audit-foreign', ['--json' => true])->assertExitCode(1);

        Setting::set('contact_address_es', '');
        $this->artisan('data:audit-foreign', ['--json' => true])->assertExitCode(0);
    }
}
