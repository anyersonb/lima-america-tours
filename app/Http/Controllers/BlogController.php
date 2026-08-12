<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class BlogController extends Controller
{
    /**
     * Display the paginated blog index.
     * Supports optional ?q= free-text search and ?categoria= filter,
     * ordered by published_at desc. Both server-side (not client/JS-only):
     * paginated results need to be filterable without loading every post,
     * and a server-rendered filtered URL is what search engines can index —
     * a client-side-only filter would leave Google seeing the unfiltered list.
     */
    public function index(Request $request, string $locale): View
    {
        App::setLocale($locale);

        $q = trim((string) $request->query('q'));

        $query = BlogPost::published()
            ->orderByDesc('published_at');

        if ($q !== '') {
            // Busca en el idioma actual Y en español (la mayoría del contenido
            // solo tiene la columna _es rellenada de verdad — ver BlogPost::boot,
            // que copia ES a EN/PT solo como respaldo de despliegue, no como
            // traducción real), para no devolver "0 resultados" por buscar en
            // una columna vacía cuando el post sí calza en español.
            $localeColumns = array_unique(["title_{$locale}", "excerpt_{$locale}", 'title_es', 'excerpt_es']);
            $query->where(function ($w) use ($localeColumns, $q) {
                foreach ($localeColumns as $column) {
                    $w->orWhere($column, 'like', "%{$q}%");
                }
            });
        }

        if ($request->filled('categoria')) {
            $query->where('category', $request->input('categoria'));
        }

        $posts = $query->paginate(12)->withQueryString();

        // All distinct categories for the filter sidebar / pill nav — pulled
        // from published posts only, never hardcoded. If none of the
        // published posts has a category yet, this comes back empty and the
        // filter row hides itself (blog.index guards on isNotEmpty()): an
        // empty taxonomy is a content gap for the client to fill from
        // Filament, not something this controller should invent.
        $categories = BlogPost::published()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('blog.index', compact('posts', 'categories', 'q'));
    }

    /**
     * Display a single published blog post.
     * Returns 404 if the post is not found or not yet published.
     */
    public function show(string $locale, string $slug): View
    {
        App::setLocale($locale);

        $post = BlogPost::published()
            ->where('slug', $slug)
            ->firstOrFail();

        // Related posts: same category first, then recent — max 3
        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->when($post->category, fn ($q) => $q->orderByRaw(
                'CASE WHEN category = ? THEN 0 ELSE 1 END',
                [$post->category]
            ))
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('blog.show', compact('post', 'related'));
    }
}
