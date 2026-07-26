<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactLeadResource\Pages\ViewContactLead;
use App\Models\ContactLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Defecto #9 de docs/qa/F7-personas.md §g (F7, cro-validator):
 *
 * "el toggle 'Leído' no se activa automáticamente al abrir/ver el
 * registro; requiere acción manual + guardar."
 *
 * ViewContactLead::mount() ahora marca is_read=true en cuanto se abre el
 * mensaje, sin necesidad de tocar nada ni de guardar manualmente.
 */
class ContactLeadAutoReadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_opening_a_message_marks_it_as_read_automatically(): void
    {
        $lead = ContactLead::factory()->create(['is_read' => false]);

        $this->assertFalse($lead->fresh()->is_read);

        Livewire::actingAs($this->admin())
            ->test(ViewContactLead::class, ['record' => $lead->getRouteKey()])
            ->assertOk();

        $this->assertTrue($lead->fresh()->is_read);
    }

    public function test_opening_an_already_read_message_does_not_error(): void
    {
        $lead = ContactLead::factory()->create(['is_read' => true]);

        Livewire::actingAs($this->admin())
            ->test(ViewContactLead::class, ['record' => $lead->getRouteKey()])
            ->assertOk();

        $this->assertTrue($lead->fresh()->is_read);
    }
}
