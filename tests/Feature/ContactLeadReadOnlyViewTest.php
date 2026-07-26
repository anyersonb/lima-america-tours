<?php

namespace Tests\Feature;

use App\Models\ContactLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Defecto §labels #1 de docs/qa/F7-personas.md (F7, cro-validator):
 *
 * "la pantalla de 'leer' un mensaje de contacto es en realidad un
 * formulario de edición con campos técnicos (IP, Navegador, Origen)
 * editables."
 *
 * ContactLeadResource::infolist() reemplaza esa pantalla por una vista de
 * solo lectura. No hay un test de "schema" directo para un Infolist en
 * Filament (a diferencia de Form::getComponents()), así que la evidencia es
 * de comportamiento HTTP: la página "ver" ya no contiene ningún <input>
 * editable para ip/user_agent/source, y sí muestra el contenido del
 * mensaje y la sección técnica claramente marcada como informativa.
 */
class ContactLeadReadOnlyViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_page_has_no_editable_inputs_for_technical_fields(): void
    {
        $admin = User::factory()->create(['email' => 'admin@limaamericatours.com']);
        $lead = ContactLead::factory()->create([
            'name' => 'Rosa',
            'lastname' => 'QA',
            'message' => 'Mensaje de prueba F7',
            'ip' => '190.1.2.3',
            'user_agent' => 'Mozilla/5.0 (Test Agent QA)',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('filament.admin.resources.contact-leads.view', ['record' => $lead->getRouteKey()]));

        $response->assertOk();

        // El mensaje y los datos técnicos se muestran...
        $response->assertSee('Mensaje de prueba F7');
        $response->assertSee('190.1.2.3');
        $response->assertSee('Información técnica (solo informativa)');

        // ...pero ip/user_agent/source ya no son <input> editables.
        $response->assertDontSee('name="data.ip"', false);
        $response->assertDontSee('name="data.user_agent"', false);
        $response->assertDontSee('name="data.source"', false);
    }
}
