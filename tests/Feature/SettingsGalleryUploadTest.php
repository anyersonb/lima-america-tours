<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Valida que la imagen #1 de la galería del home se pueda SUBIR desde el
 * panel (Filament → Configuración → Home → Galería), no solo que se vea en
 * el front con el fallback. Cubre el ciclo completo:
 *   1) Un admin autenticado sube un archivo al campo `home_gallery_img_1`
 *      y guarda el formulario de Settings.
 *   2) El Setting queda persistido (no vacío) y el archivo existe físicamente
 *      en el disco `media` (mismo saver que usan home_destino_img_*, etc.).
 *   3) El home (`/es`) refleja esa imagen subida (su URL real del disco
 *      `media`), no el fallback local.
 *
 * Antes del fix: falla — el campo `home_gallery_img_1` no existe en el
 * formulario de Settings (fillForm() lo ignora/lanza error de campo
 * desconocido) y la sección de galería no existe en el home.
 * Después del fix: pasa.
 */
class SettingsGalleryUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_gallery_image_1_and_it_persists_and_renders_on_home(): void
    {
        Storage::fake('media');

        $admin = User::factory()->create(['email' => 'qa@limaamericatours.com']);
        $this->actingAs($admin);

        $file = UploadedFile::fake()->image('galeria.jpg', 1600, 900);

        Livewire::test(SettingsPage::class)
            ->fillForm(['home_gallery_img_1' => $file])
            ->call('save')
            ->assertHasNoFormErrors();

        // 1) Persistido en la tabla settings.
        $stored = Setting::get('home_gallery_img_1');
        $this->assertIsString($stored);
        $this->assertNotSame('', trim((string) $stored));

        // 2) El archivo existe de verdad en el disco `media` (no solo la ruta en BD).
        Storage::disk('media')->assertExists($stored);

        // 3) El home muestra ESA imagen (URL del disco media), no el fallback.
        $expectedUrl = Storage::disk('media')->url($stored);

        $response = $this->get('/es');
        $response->assertOk();
        $response->assertSee($expectedUrl, false);
    }
}
