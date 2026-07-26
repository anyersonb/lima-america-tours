<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoría responsive f3-rebrand (docs/qa/f3-rebrand-responsive.md), hallazgo
 * "drawer móvil sin scroll-lock": el header rehecho (design/rojo-acento) abre
 * el drawer con Alpine (`x-data="{ open: false }"` en <header>) pero nunca
 * bloqueaba el scroll del <body> de fondo mientras el drawer estaba abierto
 * (getComputedStyle(body).overflow se mantenía "visible" con el drawer
 * visible, verificado con Playwright MCP en 375px).
 *
 * Fix: `x-effect="document.body.classList.toggle('lat-drawer-open', open)"`
 * en el mismo <header> (alcanza <body> porque x-effect ejecuta JS arbitrario,
 * no solo bindings dentro del árbol del componente) + regla SCSS
 * `body.lat-drawer-open { overflow: hidden; }` en _lat-header.scss.
 *
 * Este test es un proxy estructural (Feature test de Laravel no ejecuta
 * Alpine/JS en el navegador): confirma que el markup servido por Laravel
 * contiene el cableado del scroll-lock. Falla si alguien borra el x-effect
 * del <header> sin querer. La verificación funcional real (que el scroll
 * efectivamente se bloquea/libera) se hizo con Playwright MCP y quedó
 * documentada en docs/qa/f3-rebrand-responsive.md.
 */
class HeaderDrawerScrollLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_wires_body_scroll_lock_via_alpine_effect(): void
    {
        $response = $this->get('/es');

        $response->assertOk();
        $response->assertSee(
            'x-effect="document.body.classList.toggle(\'lat-drawer-open\', open)"',
            false
        );
    }

    public function test_drawer_toggle_button_still_controls_the_same_open_state(): void
    {
        $response = $this->get('/es');

        $response->assertOk();
        // El burger sigue atado al mismo x-data del <header> que dispara el x-effect.
        $response->assertSee('@click="open = !open"', false);
        $response->assertSee('aria-controls="lat-drawer"', false);
    }
}
