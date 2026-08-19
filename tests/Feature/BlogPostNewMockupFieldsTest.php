<?php

namespace Tests\Feature;

use App\Filament\Resources\BlogPostResource\Pages\CreateBlogPost;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Dos columnas nuevas para la línea de firma del artículo de blog
 * (docs/rebrand/inventario/02-tour-y-blog.md §2.D, adaptado por el brief a
 * columnas simples sin FK a `guides`): `author_role` y `author_photo`.
 * Ningún post publicado hoy tiene autor individual (los 10 dicen "Lima
 * América Tours"), así que ambas son nullable y el componente de firma debe
 * poder ocultar el rol/avatar cuando faltan.
 *
 * `author_photo` se sube con el mismo patrón FileUpload + ImageOptimizer que
 * `cover_image`. Igual que en TourNewMockupFieldsTest, se prueba con BYTES
 * REALES de imagen (no UploadedFile::fake()->image()) y se compara el
 * CONTENIDO leído del disco, no el tamaño que reporta el archivo fake.
 */
class BlogPostNewMockupFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function realPngBytes(int $width = 20, int $height = 20): string
    {
        $img = imagecreatetruecolor($width, $height);
        imagefill($img, 0, 0, imagecolorallocate($img, 30, 120, 200));
        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    public function test_author_role_persists_and_is_read_back(): void
    {
        $post = BlogPost::create([
            'title_es' => 'QA_ Artículo de prueba de firma',
            'excerpt_es' => 'Extracto de prueba.',
            'body_es' => 'Cuerpo de prueba.',
            'author_name' => 'Augusto',
            'author_role' => 'Guía Local',
        ]);

        $fresh = BlogPost::find($post->id);

        $this->assertSame('Augusto', $fresh->author_name);
        $this->assertSame('Guía Local', $fresh->author_role);
    }

    public function test_author_role_is_null_when_not_set(): void
    {
        $post = BlogPost::create([
            'title_es' => 'QA_ Artículo sin rol de autor',
            'excerpt_es' => 'Extracto de prueba.',
            'body_es' => 'Cuerpo de prueba.',
        ]);

        $this->assertNull($post->fresh()->author_role);
    }

    /**
     * getAuthorPhotoUrlAttribute() no tiene fallback genérico (a diferencia
     * de cover_url): sin foto, debe devolver null para que la vista oculte
     * el avatar, no un silueta de stock.
     */
    public function test_author_photo_url_accessor_is_null_when_there_is_no_photo(): void
    {
        $post = BlogPost::create([
            'title_es' => 'QA_ Artículo sin foto de autor',
            'excerpt_es' => 'Extracto de prueba.',
            'body_es' => 'Cuerpo de prueba.',
        ]);

        $this->assertNull($post->author_photo_url);
    }

    /**
     * Mismo caso límite que ya cubre BlogPost::getCoverUrlAttribute(): la
     * columna tiene una ruta pero el archivo no existe en el disco (BD
     * copiada sin sus uploads) — debe devolver null, no una URL rota.
     */
    public function test_author_photo_url_accessor_is_null_when_the_column_has_a_path_but_the_file_is_missing(): void
    {
        Storage::fake('public');

        $post = BlogPost::create([
            'title_es' => 'QA_ Artículo con foto huérfana',
            'excerpt_es' => 'Extracto de prueba.',
            'body_es' => 'Cuerpo de prueba.',
            'author_photo' => 'blog/authors/no-existe-de-verdad.webp',
        ]);

        $this->assertNull($post->author_photo_url);
    }

    public function test_admin_can_upload_an_author_photo_and_it_persists_with_real_content_on_disk(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['email' => 'qa@limaamericatours.com']);
        $this->actingAs($admin);

        $file = UploadedFile::fake()->createWithContent('augusto.png', $this->realPngBytes());

        Livewire::test(CreateBlogPost::class)
            ->fillForm([
                'title_es' => 'QA_ Artículo con foto de autor',
                'excerpt_es' => 'Extracto de prueba.',
                'body_es' => 'Cuerpo de prueba.',
                'author_photo' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::where('title_es', 'QA_ Artículo con foto de autor')->firstOrFail();

        // 1) Persistido en la BD con el prefijo/extensión que produce ImageOptimizer.
        $this->assertNotEmpty($post->author_photo);
        $this->assertStringStartsWith('blog/authors/', $post->author_photo);
        $this->assertStringEndsWith('.webp', $post->author_photo, 'ImageOptimizer debe convertir la foto a WebP.');

        // 2) El archivo existe en el disco "public"...
        Storage::disk('public')->assertExists($post->author_photo);

        // 3) ...con CONTENIDO real (no 0 bytes) leído del propio disco.
        $stored = Storage::disk('public')->get($post->author_photo);
        $this->assertNotEmpty($stored);
        $this->assertGreaterThan(50, strlen($stored), 'El archivo guardado no debería tener un contenido casi vacío.');

        // 4) El accesor ahora sí resuelve una URL (el archivo existe de verdad).
        $this->assertNotNull($post->fresh()->author_photo_url);

        $post->delete();
    }
}
