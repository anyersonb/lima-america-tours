<?php

namespace Tests\Feature;

use App\Filament\Resources\BlogPostResource\Pages\CreateBlogPost;
use App\Filament\Resources\TourResource\Pages\CreateTour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Defecto §labels #7 / §g #11 de docs/qa/F7-personas.md (F7, cro-validator):
 *
 * "El toggle 'Publicado' viene apagado por defecto en Blog, pero prendido
 * por defecto en Tours." — un criterio inconsistente entre dos recursos del
 * mismo panel podía llevar a publicar algo a medio llenar sin darse cuenta.
 *
 * Se unifica al criterio "seguro" (apagado = borrador) en ambos recursos,
 * con el mismo texto de ayuda.
 */
class PublishedToggleDefaultTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_a_new_tour_defaults_to_unpublished(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CreateTour::class)
            ->fillForm([
                'title_es' => 'Tour sin tocar el toggle Publicado',
                'price' => '100',
                'currency' => 'USD',
                // 'is_published' deliberately not set: must keep the schema default
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tours', [
            'title_es' => 'Tour sin tocar el toggle Publicado',
            'is_published' => false,
        ]);
    }

    public function test_a_new_blog_post_defaults_to_unpublished(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CreateBlogPost::class)
            ->fillForm([
                'title_es' => 'Artículo sin tocar el toggle Publicado',
                'excerpt_es' => 'Resumen de prueba',
                'body_es' => 'Contenido de prueba',
                // 'is_published' deliberately not set: must keep the schema default
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('blog_posts', [
            'title_es' => 'Artículo sin tocar el toggle Publicado',
            'is_published' => false,
        ]);
    }
}
