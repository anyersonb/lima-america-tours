<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents non-production environments (local, staging, testing) from being
 * indexed by search engines at the HTTP-header level.
 *
 * The <meta name="robots"> tag in resources/views/layouts/app.blade.php only
 * covers HTML responses rendered through that layout. This middleware adds
 * the equivalent X-Robots-Tag header to EVERY response (HTML, JSON, assets
 * served through Laravel, sitemap.xml, etc.), registered globally so no
 * route group is left uncovered.
 *
 * In production this middleware is a no-op: it never touches the response.
 */
class StagingNoindex
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! app()->environment('production')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
