<?php

namespace Tests\Feature;

use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO S-04: /tours y /tours/categoria/{lima|ica|cusco} (ES/EN/PT) armaban la
 * meta description interpolando ":section" con la MISMA frase completa usada
 * en el <h1> ("Descubre las mejores experiencias en Lima" / región genérica),
 * dentro de la plantilla "Descubre nuestros tours por :section." — el
 * resultado era un duplicado tipo "Descubre nuestros tours por Descubre las
 * mejores experiencias en Lima." (o "...por Tours en Lima." en categorías).
 *
 * Fix: resources/views/tours/index.blade.php ahora usa una etiqueta corta
 * separada ($sectionMetaLabel: "Lima"/"Ica"/"Cusco"/"Perú") solo para la
 * interpolación de la meta description; el <h1> no cambió.
 */
class TourIndexMetaDescriptionTest extends TestCase
{
    use RefreshDatabase;

    private function seedRegion(string $slug): Region
    {
        return Region::create([
            'slug' => $slug,
            'name_es' => ucfirst($slug),
            'name_en' => ucfirst($slug),
            'is_active' => true,
        ]);
    }

    private function metaDescription(string $html): string
    {
        preg_match('/<meta name="description" content="([^"]*)">/', $html, $matches);

        return $matches[1] ?? '';
    }

    public function test_generic_tours_listing_meta_description_has_no_duplicated_sentence(): void
    {
        foreach (['es', 'en', 'pt'] as $locale) {
            $response = $this->get("/{$locale}/tours");
            $response->assertOk();

            $description = $this->metaDescription($response->getContent());

            $this->assertNotSame('', $description, "empty meta description for /{$locale}/tours");
            $this->assertStringNotContainsString('por Descubre', $description);
            $this->assertStringNotContainsString('in Discover', $description);
            $this->assertStringNotContainsString('por Descubra', $description);
            // No debe repetir el propio nombre del sitio/segmento a modo de eco.
            $this->assertLessThanOrEqual(
                1,
                substr_count(strtolower($description), 'descubr'),
                "meta description repeats 'descubr*' more than once for /{$locale}/tours: {$description}"
            );
        }
    }

    public function test_category_tours_listing_meta_description_has_no_duplicated_sentence(): void
    {
        $this->seedRegion('lima');
        $this->seedRegion('ica');
        $this->seedRegion('cusco');

        foreach (['es', 'en', 'pt'] as $locale) {
            foreach (['lima', 'ica', 'cusco'] as $categoria) {
                $response = $this->get("/{$locale}/tours/categoria/{$categoria}");
                $response->assertOk();

                $description = $this->metaDescription($response->getContent());

                $this->assertNotSame('', $description, "empty meta description for /{$locale}/tours/categoria/{$categoria}");
                $this->assertStringNotContainsString('por Tours en', $description);
                $this->assertStringNotContainsString('in Tours in', $description);
                $this->assertStringNotContainsString('por Tours em', $description);
            }
        }
    }
}
