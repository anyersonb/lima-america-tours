<?php

namespace Tests\Feature;

use App\Support\VideoEmbed;
use Tests\TestCase;

/**
 * VideoEmbed::normalize() se extrajo de resources/views/home.blade.php
 * (home_hero_video_url, líneas ~203-233 antes de esta extracción), donde el
 * mismo bloque de regex normalizaba la URL "de compartir" que un cliente
 * pega en Configuración → Home al formato /embed/ID que un <iframe> sí puede
 * renderizar (la URL de compartir de YouTube da "Refused to display...
 * X-Frame-Options 'sameorigin'"). Ahora la usa también `tours.video_url`
 * (Tour::getVideoEmbedUrlAttribute), así que este test cubre las cuatro
 * variantes de entrada que el panel acepta, más basura y null: si cualquiera
 * de estas rompe, tanto el botón "Ver video" del home como el de la ficha de
 * tour se ven afectados a la vez.
 */
class VideoEmbedNormalizeTest extends TestCase
{
    public function test_normalizes_a_youtube_watch_url(): void
    {
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            VideoEmbed::normalize('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30s')
        );
    }

    public function test_normalizes_a_youtu_be_short_url(): void
    {
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            VideoEmbed::normalize('https://youtu.be/dQw4w9WgXcQ')
        );
    }

    public function test_normalizes_a_youtube_shorts_url(): void
    {
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            VideoEmbed::normalize('https://www.youtube.com/shorts/dQw4w9WgXcQ')
        );
    }

    public function test_normalizes_a_vimeo_url(): void
    {
        $this->assertSame(
            'https://player.vimeo.com/video/76979871',
            VideoEmbed::normalize('https://vimeo.com/76979871')
        );
    }

    /**
     * Ya viene en formato /embed/ (p.ej. copiado directo desde otro sitio):
     * debe pasar tal cual, forzando siempre el dominio -nocookie.
     */
    public function test_normalizes_an_already_embeddable_youtube_url(): void
    {
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            VideoEmbed::normalize('https://www.youtube.com/embed/dQw4w9WgXcQ')
        );
    }

    public function test_returns_null_for_a_garbage_url(): void
    {
        $this->assertNull(VideoEmbed::normalize('https://example.com/no-es-un-video'));
    }

    public function test_returns_null_for_a_plain_non_url_string(): void
    {
        $this->assertNull(VideoEmbed::normalize('no es una URL'));
    }

    public function test_returns_null_for_null_input(): void
    {
        $this->assertNull(VideoEmbed::normalize(null));
    }

    public function test_returns_null_for_an_empty_string(): void
    {
        $this->assertNull(VideoEmbed::normalize(''));
    }
}
