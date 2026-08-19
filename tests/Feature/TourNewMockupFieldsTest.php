<?php

namespace Tests\Feature;

use App\Filament\Resources\TourResource\Pages\CreateTour;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tres columnas nuevas para la ficha de tour (docs/rebrand/inventario/02-tour-y-blog.md
 * §1.D): `video_url`, `difficulty` y `route_map_image`. Todas nullable, sin
 * default inventado — el frontend (maquetador, en paralelo en esta misma
 * rama) debe poder ocultar cada componente cuando el campo viene vacío.
 *
 * `route_map_image` sube por Filament con el mismo patrón que `cover_image`
 * (FileUpload + App\Support\ImageOptimizer). Se prueba subiendo un archivo
 * con BYTES REALES de imagen (no UploadedFile::fake()->image(), que puede
 * escribir 0 bytes de contenido real aunque reporte un tamaño en KB — así
 * se detectó el defecto en SettingsGalleryUploadTest) y comparando el
 * CONTENIDO leído del disco, no solo su tamaño declarado.
 */
class TourNewMockupFieldsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Genera bytes reales de un JPEG diminuto con GD, para que
     * App\Support\ImageOptimizer::store() pueda decodificarlo de verdad
     * (imagecreatefromstring) y produzca un .webp real, en vez de caer en
     * la rama "GD no pudo decodificar" que guardaría el archivo intacto.
     */
    private function realJpegBytes(int $width = 40, int $height = 30): string
    {
        $img = imagecreatetruecolor($width, $height);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 30, 30));
        ob_start();
        imagejpeg($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    public function test_video_url_and_difficulty_persist_and_are_read_back(): void
    {
        $tour = Tour::factory()->create([
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'difficulty' => 'Moderada',
        ]);

        $fresh = Tour::find($tour->id);

        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $fresh->video_url);
        $this->assertSame('Moderada', $fresh->difficulty);
    }

    /**
     * El accesor `video_embed_url` es lo que consumirá la vista: prueba que
     * de verdad delega en App\Support\VideoEmbed::normalize() en vez de
     * devolver la URL cruda (que rompería el <iframe>).
     */
    public function test_video_embed_url_accessor_normalizes_the_stored_url(): void
    {
        $tour = Tour::factory()->create(['video_url' => 'https://youtu.be/dQw4w9WgXcQ']);

        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $tour->video_embed_url);
    }

    public function test_video_embed_url_accessor_is_null_when_video_url_is_empty(): void
    {
        $tour = Tour::factory()->create(['video_url' => null]);

        $this->assertNull($tour->video_embed_url);
    }

    public function test_admin_can_upload_a_route_map_image_and_it_persists_with_real_content_on_disk(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['email' => 'qa@limaamericatours.com']);
        $this->actingAs($admin);

        $file = UploadedFile::fake()->createWithContent('mapa.jpg', $this->realJpegBytes());

        Livewire::test(CreateTour::class)
            ->fillForm([
                'title_es' => 'QA_ Tour con mapa de prueba',
                'price' => '99.00',
                'currency' => 'USD',
                'route_map_image' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tour = Tour::where('title_es', 'QA_ Tour con mapa de prueba')->firstOrFail();

        // 1) Persistido en la BD con el prefijo/extensión que produce ImageOptimizer.
        $this->assertNotEmpty($tour->route_map_image);
        $this->assertStringStartsWith('tours/route-map/', $tour->route_map_image);
        $this->assertStringEndsWith('.webp', $tour->route_map_image, 'ImageOptimizer debe convertir la imagen a WebP.');

        // 2) El archivo existe en el disco "public"...
        Storage::disk('public')->assertExists($tour->route_map_image);

        // 3) ...y con CONTENIDO real, no 0 bytes. Se lee el contenido del disco
        //    (no el tamaño reportado por UploadedFile) para no repetir el falso
        //    OK que dio SettingsGalleryUploadTest con el disco lleno.
        $stored = Storage::disk('public')->get($tour->route_map_image);
        $this->assertNotEmpty($stored);
        $this->assertGreaterThan(20, strlen($stored), 'El archivo guardado no debería tener un contenido casi vacío.');

        $tour->delete();
    }
}
