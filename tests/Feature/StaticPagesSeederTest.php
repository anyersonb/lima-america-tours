<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\Page;
use App\Models\Testimonial;
use App\Models\Tour;
use Database\Seeders\StaticPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo CRO 2026-08-11 (lote mockups ago-2026), FIX 2: el copy de
 * Nosotros/Contacto se migró a PageResource, pero sin ningún registro
 * `Page` con slug "nosotros"/"contacto" el panel aparece vacío y el
 * cliente no puede editar nada. `StaticPagesSeeder` crea esos dos
 * registros con el mismo texto que ya sale por defecto en los blades
 * (about.blade.php / contact.blade.php), para que abrir el panel muestre
 * el contenido real publicado, no campos en blanco.
 */
class StaticPagesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_nosotros_and_contacto_pages_when_missing(): void
    {
        $this->assertSame(0, Page::count());

        (new StaticPagesSeeder())->run();

        $this->assertSame(2, Page::count());
        $this->assertTrue(Page::where('slug', 'nosotros')->exists());
        $this->assertTrue(Page::where('slug', 'contacto')->exists());
    }

    public function test_it_is_idempotent_and_never_overwrites_a_page_already_edited_by_the_client(): void
    {
        Page::create([
            'slug' => 'nosotros',
            'title_es' => 'QA_ Ya editado por el cliente',
            'is_published' => true,
            'blocks' => ['hero_title_es' => 'QA_ Título que el cliente ya cambió'],
        ]);

        (new StaticPagesSeeder())->run();

        $page = Page::where('slug', 'nosotros')->first();
        $this->assertSame(1, Page::where('slug', 'nosotros')->count());
        $this->assertSame('QA_ Ya editado por el cliente', $page->title_es);
        $this->assertSame('QA_ Título que el cliente ya cambió', $page->blocks['hero_title_es']);

        // El segundo registro (contacto) sí debe crearse: la idempotencia es
        // por registro, no un "si ya hay algo no toco nada" a nivel tabla.
        $this->assertTrue(Page::where('slug', 'contacto')->exists());
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        (new StaticPagesSeeder())->run();
        (new StaticPagesSeeder())->run();

        $this->assertSame(2, Page::count());
    }

    public function test_seeded_pages_carry_the_exact_default_copy_shown_by_the_blades(): void
    {
        (new StaticPagesSeeder())->run();

        $about = Page::where('slug', 'nosotros')->first();
        $this->assertSame('Más de 10 años mostrando lo mejor del Perú', $about->blocks['hero_title_es']);
        $this->assertSame('More than 10 years showcasing the best of Peru', $about->blocks['hero_title_en']);
        $this->assertSame('Mais de 10 anos mostrando o melhor do Peru', $about->blocks['hero_title_pt']);
        $this->assertCount(5, $about->blocks['why_travel_items']);

        $contact = Page::where('slug', 'contacto')->first();
        $this->assertSame('Envíanos un mensaje', $contact->blocks['form_title_es']);
        $this->assertSame('Send us a message', $contact->blocks['form_title_en']);
        $this->assertSame('Envie-nos uma mensagem', $contact->blocks['form_title_pt']);
    }

    /**
     * La prueba central del FIX 2: correr el seeder NO puede cambiar ni un
     * carácter de lo que el visitante ve hoy. Se captura el HTML de las 2
     * páginas en los 3 idiomas ANTES de que exista el registro Page (blade
     * cae a sus defaults hardcodeados), se corre el seeder, y se vuelve a
     * pedir la misma página: debe salir carácter por carácter igual (el
     * token CSRF, que cambia por request, se normaliza antes de comparar).
     */
    public function test_seeding_static_pages_does_not_change_a_single_character_of_the_rendered_pages(): void
    {
        Guide::factory()->create(['is_active' => true]);
        Testimonial::factory()->create(['is_active' => true, 'rating' => 5]);
        Tour::factory()->create();

        $normalize = function (string $html): string {
            return preg_replace('/(name="_token" value)="[^"]+"/', '$1="TOKEN"', $html);
        };

        $before = [];
        foreach (['es', 'en', 'pt'] as $locale) {
            $before["about.$locale"] = $normalize($this->get("/$locale/nosotros")->getContent());
            $before["contact.$locale"] = $normalize($this->get("/$locale/contacto")->getContent());
        }

        (new StaticPagesSeeder())->run();

        foreach (['es', 'en', 'pt'] as $locale) {
            $afterAbout = $normalize($this->get("/$locale/nosotros")->getContent());
            $afterContact = $normalize($this->get("/$locale/contacto")->getContent());

            $this->assertSame($before["about.$locale"], $afterAbout, "La página /nosotros ($locale) cambió tras sembrar StaticPagesSeeder.");
            $this->assertSame($before["contact.$locale"], $afterContact, "La página /contacto ($locale) cambió tras sembrar StaticPagesSeeder.");
        }
    }

    public function test_seeded_pages_do_not_duplicate_sitemap_entries(): void
    {
        // /nosotros y /contacto YA están en el sitemap como rutas estáticas
        // (SitemapController::index). Si el registro Page entrara con
        // show_in_sitemap=true, el loop de "CMS pages" del mismo controller
        // agregaría un <loc> duplicado por idioma.
        (new StaticPagesSeeder())->run();

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertSame(1, substr_count($xml, '/es/nosotros</loc>'));
        $this->assertSame(1, substr_count($xml, '/es/contacto</loc>'));
        $this->assertSame(1, substr_count($xml, '/en/nosotros</loc>'));
        $this->assertSame(1, substr_count($xml, '/en/contacto</loc>'));
    }
}
