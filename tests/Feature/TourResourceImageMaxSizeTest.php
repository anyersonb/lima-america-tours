<?php

namespace Tests\Feature;

use App\Filament\Resources\TourResource\Pages\CreateTour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Defecto §g #8 de docs/qa/F7-personas.md (F7, cro-validator):
 *
 * "No hay validación ni aviso de tamaño al subir fotos pesadas (probado con
 * archivo de 8.9MB, aceptado sin queja)."
 *
 * TourResource ahora limita cover_image/gallery con ->maxSize(4096) (4 MB).
 * Este test sube un archivo de prueba por encima de ese límite y confirma
 * que el formulario lo rechaza en vez de aceptarlo en silencio.
 */
class TourResourceImageMaxSizeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_cover_image_over_4mb_is_rejected(): void
    {
        // El disco "public" escribe directo en public/storage (real, compartido
        // con el entorno de desarrollo) — se fakea para no dejar archivos de
        // prueba ahí.
        Storage::fake('public');

        $oversized = UploadedFile::fake()->image('cover-pesada.jpg')->size(5000); // ~4.9 MB > 4096 KB

        Livewire::actingAs($this->admin())
            ->test(CreateTour::class)
            ->fillForm([
                'title_es' => 'Tour con foto pesada',
                'price' => '100',
                'currency' => 'USD',
                'cover_image' => $oversized,
            ])
            ->call('create')
            ->assertHasFormErrors(['cover_image']);

        $this->assertDatabaseMissing('tours', ['title_es' => 'Tour con foto pesada']);
    }

    public function test_cover_image_within_the_limit_is_accepted(): void
    {
        Storage::fake('public');

        $ok = UploadedFile::fake()->image('cover-normal.jpg')->size(500); // 0.5 MB

        Livewire::actingAs($this->admin())
            ->test(CreateTour::class)
            ->fillForm([
                'title_es' => 'Tour con foto normal',
                'price' => '100',
                'currency' => 'USD',
                'cover_image' => $ok,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tours', ['title_es' => 'Tour con foto normal']);
    }
}
