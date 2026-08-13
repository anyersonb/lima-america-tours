<?php

namespace Tests\Feature;

use App\Filament\Resources\HeroSlideResource\Pages\CreateHeroSlide;
use App\Filament\Resources\HeroSlideResource\Pages\EditHeroSlide;
use App\Models\HeroSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Prueba de extremo a extremo del CMS de diapositivas del hero: un admin
 * sube una imagen desde Filament, se guarda optimizada a WebP con
 * App\Support\ImageOptimizer (MISMO patrón que GuideResource — disco
 * "media", igual que home_hero_image), persiste en la BD, y al reabrir el
 * registro de edición la imagen sigue ahí.
 */
class HeroSlideResourcePhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_hero_slide_with_an_image_and_it_persists_on_disk(): void
    {
        Storage::fake('media');

        $admin = User::factory()->create(['email' => 'qa@limaamericatours.com']);
        $this->actingAs($admin);

        $file = UploadedFile::fake()->image('slide.jpg', 1920, 960);

        Livewire::test(CreateHeroSlide::class)
            ->fillForm([
                'image' => $file,
                'alt_es' => 'QA_ Diapositiva de prueba',
                'order' => 0,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $slide = HeroSlide::where('alt_es', 'QA_ Diapositiva de prueba')->firstOrFail();

        // 1) Persistido en la BD, optimizado a WebP (ImageOptimizer).
        $this->assertNotEmpty($slide->image);
        $this->assertStringStartsWith('home/', $slide->image);
        $this->assertStringEndsWith('.webp', $slide->image, 'ImageOptimizer debe convertir la imagen a WebP.');

        // 2) El archivo existe de verdad en el disco "media" (no solo la ruta en BD).
        Storage::disk('media')->assertExists($slide->image);

        // 3) Recargar el registro de edición confirma que la imagen persiste.
        Livewire::test(EditHeroSlide::class, ['record' => $slide->getKey()])
            ->assertFormSet([
                'image' => fn ($state) => is_array($state)
                    ? in_array($slide->image, $state, true)
                    : $state === $slide->image,
            ]);

        // Limpieza: nada de datos de prueba permanentes.
        $slide->delete();
        $this->assertSame(0, HeroSlide::where('alt_es', 'QA_ Diapositiva de prueba')->count());
    }

    public function test_alt_es_is_required_but_alt_en_and_alt_pt_are_optional(): void
    {
        $admin = User::factory()->create(['email' => 'qa2@limaamericatours.com']);
        $this->actingAs($admin);

        Storage::fake('media');
        $file = UploadedFile::fake()->image('slide2.jpg', 1920, 960);

        Livewire::test(CreateHeroSlide::class)
            ->fillForm([
                'image' => $file,
                'alt_es' => '',
                'order' => 0,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['alt_es' => 'required']);
    }
}
