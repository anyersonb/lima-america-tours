<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo #5 de docs/qa/panel-filament.md (F2, cro-validator):
 *
 * El campo `slug` de PageResource no tenía `->live()`. La pestaña
 * "Contenido de la página" está condicionada a
 * `visible(fn (Get $get) => in_array($get('slug'), ['contacto', 'nosotros']))`,
 * pero sin `->live()` en el campo que alimenta ese `$get`, Livewire no
 * revalida la visibilidad mientras se escribe — solo aparece tras Guardar
 * y volver a abrir en Editar. Un editor creando la página no encuentra
 * dónde cargar imágenes/textos del hero hasta guardar una vez.
 *
 * No hay forma de simular "escribir en el campo y ver la pestaña aparecer"
 * sin un navegador real (Livewire polling requiere el ciclo de request de
 * Livewire), así que la verificación aquí es de código: el campo `slug`
 * debe declarar `isLive()` en el schema del formulario de creación.
 */
class PageResourceSlugLiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_field_is_live_so_the_content_tab_reacts_while_typing(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@limaamericatours.com',
        ]);

        /** @var \App\Filament\Resources\PageResource\Pages\CreatePage $page */
        $page = \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\PageResource\Pages\CreatePage::class);

        $slugField = collect($page->instance()->form->getFlatFields())->get('slug');

        $this->assertNotNull($slugField, 'El campo slug debe existir en el form schema de PageResource.');
        $this->assertTrue(
            $slugField->isLive(),
            'El campo slug debe ser ->live() para que la pestaña "Contenido de la página" '
            .'(condicionada a su valor) reaccione mientras se escribe, no solo al guardar.'
        );
    }
}
