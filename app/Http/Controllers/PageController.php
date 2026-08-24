<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Display a published CMS Page by slug.
     *
     * "contacto" and "nosotros" already have dedicated routes/views with
     * richer CMS blocks (hero images, stats, pillars…), so we redirect those
     * slugs to their canonical URL instead of rendering a duplicate,
     * content-poor version here.
     */
    public function show(string $locale, string $slug): View|RedirectResponse
    {
        if ($slug === 'contacto') {
            return redirect()->route('contact', ['locale' => $locale]);
        }

        if ($slug === 'nosotros') {
            return redirect()->route('about', ['locale' => $locale]);
        }

        // firstOrFail() throws ModelNotFoundException for unknown/unpublished
        // slugs, which Laravel's exception handler renders as a normal 404 —
        // no need to catch it here.
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        $title = $page->seo_title ?: $page->title;
        $description = $page->seo_description ?: Str::limit(strip_tags((string) $page->content), 160);

        return view('pages.show', compact('page', 'locale', 'title', 'description'));
    }

    public function terms(): View
    {
        try {
            $locale = app()->getLocale();
            $title = __('legal.terms_title');

            return view('pages.terms', compact('locale', 'title'));
        } catch (\Throwable $e) {
            Log::error('PageController@terms: failed to render terms page', [
                'locale' => app()->getLocale(),
                'exception' => $e->getMessage(),
            ]);

            abort(500);
        }
    }

    public function privacy(): View
    {
        try {
            $locale = app()->getLocale();
            $title = __('legal.privacy_title');

            return view('pages.privacy', compact('locale', 'title'));
        } catch (\Throwable $e) {
            Log::error('PageController@privacy: failed to render privacy page', [
                'locale' => app()->getLocale(),
                'exception' => $e->getMessage(),
            ]);

            abort(500);
        }
    }

    /**
     * Código de conducta contra la ESNNA (explotación sexual de niñas, niños
     * y adolescentes en el ámbito del turismo). Pedido del jefe el
     * 2026-08-21; el texto vive en lang/{es,en,pt}/legal.php igual que los
     * otros dos legales, no en la base.
     */
    public function esnna(): View
    {
        try {
            $locale = app()->getLocale();
            $title = __('legal.esnna_title');

            return view('pages.esnna', compact('locale', 'title'));
        } catch (\Throwable $e) {
            Log::error('PageController@esnna: failed to render ESNNA page', [
                'locale' => app()->getLocale(),
                'exception' => $e->getMessage(),
            ]);

            abort(500);
        }
    }
}
