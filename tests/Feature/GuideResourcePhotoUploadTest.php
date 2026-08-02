<?php

namespace Tests\Feature;

use App\Filament\Resources\GuideResource\Pages\CreateGuide;
use App\Filament\Resources\GuideResource\Pages\EditGuide;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Prueba de extremo a extremo del CMS de guías (TAREA 2, lote 2026-08):
 * un admin sube una foto desde Filament, se guarda optimizada a WebP con
 * App\Support\ImageOptimizer (MISMO patrón que las fotos de itinerario de
 * TourResource), persiste en la BD, y al reabrir el registro de edición la
 * foto sigue ahí. Mismo enfoque que SettingsGalleryUploadTest para probar
 * un FileUpload+ImageOptimizer sin depender de un navegador real.
 */
class GuideResourcePhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_guide_with_a_photo_and_it_persists_on_disk(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['email' => 'qa@limaamericatours.com']);
        $this->actingAs($admin);

        $file = UploadedFile::fake()->image('guia.jpg', 800, 800);

        Livewire::test(CreateGuide::class)
            ->fillForm([
                'name' => 'QA_ Guía de prueba',
                'role_es' => 'Guía de montaña',
                'years_experience' => 5,
                'order' => 0,
                'is_active' => true,
                'photo' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $guide = Guide::where('name', 'QA_ Guía de prueba')->firstOrFail();

        // 1) Persistido en la BD.
        $this->assertNotEmpty($guide->photo);
        $this->assertStringStartsWith('guides/', $guide->photo);
        $this->assertStringEndsWith('.webp', $guide->photo, 'ImageOptimizer debe convertir la foto a WebP.');

        // 2) El archivo existe de verdad en el disco "public" (no solo la ruta en BD).
        Storage::disk('public')->assertExists($guide->photo);

        // 3) Recargar el registro de edición confirma que la foto persiste.
        // FileUpload hidrata el campo como un array keyed por UUID (incluso
        // en un campo de una sola imagen), así que se compara por contenido
        // en vez de por igualdad estricta de string.
        Livewire::test(EditGuide::class, ['record' => $guide->getKey()])
            ->assertFormSet([
                'photo' => fn ($state) => is_array($state)
                    ? in_array($guide->photo, $state, true)
                    : $state === $guide->photo,
            ]);

        // Limpieza: nada de datos falsos permanentes en la BD de prueba.
        $guide->delete();
        $this->assertSame(0, Guide::where('name', 'QA_ Guía de prueba')->count());
    }
}
