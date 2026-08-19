<?php

namespace App\Support;

/**
 * Normalizes a "share" video URL (YouTube watch/short/shorts, Vimeo) into the
 * embeddable form an <iframe> can actually render.
 *
 * Extracted from resources/views/home.blade.php (the `home_hero_video_url`
 * setting), which inlined this exact regex chain for the "Ver video" button
 * on the home hero. The share URL a client naturally copy-pastes
 * (youtube.com/watch?v=..., youtu.be/..., youtube.com/shorts/...,
 * vimeo.com/123) renders a blank iframe with "Refused to display...
 * X-Frame-Options 'sameorigin'" — only the /embed/ID form (YouTube) or
 * player.vimeo.com/video/ID (Vimeo) works inside an <iframe>. Centralizing
 * the conversion here lets any CMS field that stores a pasted video URL
 * (tours.video_url today, plus whatever reuses it later) share one
 * normalizer instead of duplicating the regex block per call site.
 *
 * YouTube results use youtube-nocookie.com (same choice already made in
 * Home: better privacy, already allowed by the site's CSP).
 */
class VideoEmbed
{
    /**
     * Returns the embeddable URL, or null when the input is empty/null or
     * doesn't match any known share-URL pattern. Returning null (instead of
     * the raw input) is deliberate: a caller that gets null should hide the
     * "Ver video" button rather than render a broken iframe.
     */
    public static function normalize(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (preg_match('~youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        if (preg_match('~youtube\.com/watch\?[^\s#]*\bv=([A-Za-z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        if (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        if (preg_match('~youtube\.com/shorts/([A-Za-z0-9_-]{6,})~i', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        if (preg_match('~player\.vimeo\.com/video/(\d+)~i', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        if (preg_match('~vimeo\.com/(?:channels/[\w-]+/|groups/[\w-]+/videos/)?(\d+)~i', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }
}
