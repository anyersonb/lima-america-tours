<?php

namespace Tests\Unit;

use App\Support\WpTourParser;
use PHPUnit\Framework\TestCase;

/**
 * Verifica el parseo de los campos HTML reales exportados del WordPress de
 * limaamericatours.com (ver storage/app/wp-import/tours.json) hacia el formato
 * de la tabla `tours`. Muestras tomadas del tour "Full day Nazca e Islas
 * Ballestas".
 */
class WpTourParserTest extends TestCase
{
    public function test_html_to_text_converts_paragraphs_to_newlines(): void
    {
        $html = '<p class="isSelectedEnd">Vive una experiencia única.</p><p>La aventura continúa.</p>';
        $text = WpTourParser::htmlToText($html);

        $this->assertStringNotContainsString('<p', $text);
        $this->assertSame("Vive una experiencia única.\nLa aventura continúa.", $text);
    }

    public function test_parse_itinerary_extracts_time_title_description(): void
    {
        $html = '<p><strong>3:00 AM:</strong> Salida desde Lima</p>'
            .'<p>Empieza temprano en la mañana.</p>'
            .'<p>Viaje en transporte privado hacia Paracas.</p>'
            .'<p><strong>8:00 AM:</strong> Llegada a Paracas</p>'
            .'<p>Desayuna en un restaurante local.</p>';

        $steps = WpTourParser::parseItinerary($html);

        $this->assertCount(2, $steps);
        $this->assertSame('3:00 AM', $steps[0]['time']);
        $this->assertSame('Salida desde Lima', $steps[0]['title']);
        $this->assertStringContainsString('transporte privado', $steps[0]['description']);
        $this->assertSame('8:00 AM', $steps[1]['time']);
        $this->assertSame('Llegada a Paracas', $steps[1]['title']);
    }

    public function test_parse_itinerary_strips_nbsp_from_titles(): void
    {
        // El WP separa la hora del título con &nbsp; (trim normal no lo quita).
        $html = '<p><strong>3:00 AM:</strong>&nbsp;Salida desde Lima</p>';
        $steps = WpTourParser::parseItinerary($html);

        $this->assertSame('3:00 AM', $steps[0]['time']);
        $this->assertSame('Salida desde Lima', $steps[0]['title']);
    }

    public function test_parse_itinerary_handles_b_tags_and_headings_without_losing_content(): void
    {
        // Machu Picchu (y otros multi-día) usan <h4> "Día N" e <b> en vez de
        // <strong>. El parser NO debe descartar los <h4> ni la hora en <b>.
        $html = '<h4>Nuestro servicio de recojo cubre la Plaza de Armas de Cusco.</h4>'
            .'<h4><b>Día 1</b></h4>'
            .'<p><b>8:00 am recojo a hotel: </b>Daremos inicio recogiéndote en el lobby de tu hotel.</p>'
            .'<p><b>8:30 am viaje a </b><b>Ollantaytambo</b><b>: </b>Traslado en movilidad cómoda.</p>'
            .'<h4><b>Día 2</b></h4>'
            .'<p><b>6:00 am Machu Picchu: </b>Ingreso a la ciudadela inca.</p>';

        $steps = WpTourParser::parseItinerary($html);
        $joined = json_encode($steps, JSON_UNESCAPED_UNICODE);

        // Nada de contenido se pierde
        $this->assertStringContainsString('Plaza de Armas', $joined);
        $this->assertStringContainsString('Día 1', $joined);
        $this->assertStringContainsString('Día 2', $joined);
        $this->assertStringContainsString('Ingreso a la ciudadela', $joined);
        // Reconoce la hora aunque venga en <b>
        $times = array_column($steps, 'time');
        $this->assertContains('8:00 AM', $times);
        $this->assertContains('6:00 AM', $times);
    }

    public function test_parse_itinerary_empty_for_blank(): void
    {
        $this->assertSame([], WpTourParser::parseItinerary(''));
        $this->assertSame([], WpTourParser::parseItinerary('<p>  </p>'));
    }

    public function test_split_includes_and_excludes_on_no_incluye_marker(): void
    {
        $html = '<ul><li>Transporte de ida y vuelta.</li><li>Guía oficial de Turismo.</li></ul>'
            .'<p><strong>No incluye:</strong></p>'
            .'<ul><li>Impuesto Aéreo $10.00 USD por persona</li><li>Boleto Turístico</li></ul>';

        $r = WpTourParser::splitIncludesExcludes($html);

        $this->assertSame(['Transporte de ida y vuelta.', 'Guía oficial de Turismo.'], $r['includes']);
        $this->assertSame(['Impuesto Aéreo $10.00 USD por persona', 'Boleto Turístico'], $r['excludes']);
    }

    public function test_split_all_included_when_no_marker(): void
    {
        $html = '<ul><li>Chalecos salvavidas.</li><li>Embarcación turística.</li></ul>';
        $r = WpTourParser::splitIncludesExcludes($html);

        $this->assertSame(['Chalecos salvavidas.', 'Embarcación turística.'], $r['includes']);
        $this->assertSame([], $r['excludes']);
    }
}
