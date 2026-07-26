# Inventario — Ficha de tour (F0)

Fecha: 2026-07-25 · Rama: `qa/ficha-tour` (creada desde `qa/panel-filament`) · Generado por lectura
directa de código (sin navegador): `app/Http/Controllers/TourController.php`, `app/Models/Tour.php`,
`resources/views/tours/show.blade.php` y componentes relacionados.

Checklist para F2 (`cro-validator`): cada campo listado debe quedar marcado como probado
explícitamente (sincronía CMS → front), especialmente los marcados **NO CONSUMIDO**.

---

## 1. Rutas del módulo

| Ruta | Nombre | Controlador | Middleware | Notas |
|---|---|---|---|---|
| `GET /{locale}/tours/detalle/{slug}` | `tours.show` | `TourController@show` | `setlocale` (grupo) | Ficha propiamente dicha |
| `POST /{locale}/tours/detalle/{slug}/resena` | `tours.review.store` | `TourController@storeReview` | `setlocale`, `throttle:6,1` | Form de reseña del visitante (caja tipo WooCommerce) |
| `GET /{locale}/tours/detalle/tour-de-dia-completo-...` | (anónima) | closure → `redirect(...301)` | `setlocale` | Redirect 301 de slug legacy a `tour-privado-huacachina-islas-ballestas-atardecer-buggy-can-am`. Comparte prefijo de ruta pero no es lógica de ficha; se anota para no confundirla con un 404. |

`{locale}` restringido a `es|en|pt` a nivel de grupo.

## 2. Controlador — `TourController@show`

```
$tour     = Tour::published()->where('slug', $slug)->firstOrFail();
$related  = 4 tours misma región (published, id != actual); si <4, fallback a 4 tours published sin filtrar región
$testimonials = Testimonial::active()->featured()->orderBy('order')->limit(4)->get()   // NO es de este tour: es el bloque global de testimonios destacados del sitio
$tourReviews  = $tour->testimonials()->where('is_active', true)->latest()->get()        // reseñas propias del tour, aprobadas
$blockedDates    = BlockedDate::blockedDatesFor($tour->id)
$blockedWeekdays = BlockedDate::blockedWeekdaysFor($tour->id)
```

Variables pasadas a la vista: `tour`, `related`, `testimonials`, `tourReviews`, `blockedDates`, `blockedWeekdays`.

**Hallazgo de inventario (no es 🔴 de L1 — no rompe nada, es un gap de completitud para que CRO lo pese en L2/L4):**
De las 5 variables que el controlador prepara, **3 no se usan en ningún lugar de `tours/show.blade.php`**:
`tourReviews`, `blockedDates`, `blockedWeekdays` (confirmado por `grep` — 0 coincidencias en la vista, ni en JS de la vista). Es decir, el controlador hace el trabajo (consulta reseñas propias del tour, calcula fechas/días bloqueados) pero la ficha nunca lo pinta ni lo usa para restringir el datepicker de reserva.

## 3. Vista — `resources/views/tours/show.blade.php`

No usa componentes Blade dedicados (todo el markup está inline en el archivo). Sí referencia
(sin invocarlo) el componente `resources/views/components/tour-comparison.blade.php`, que existe
en el árbol de vistas pero **nunca se incluye** desde `show.blade.php` (ni desde ningún otro archivo
del proyecto — `grep -rn "tour-comparison"` solo devuelve su propia definición).

## 4. Campos del modelo `Tour` que la ficha CONSUME

| Campo/relación (columna real) | Uso en la ficha | Fuente | Fallback |
|---|---|---|---|
| `slug` | Route binding, canonical URL, breadcrumb | columna | — |
| `title_es/en/pt` (accessor `title`) | `<title>`, H1, breadcrumb, JSON-LD `name`, `alt` de imagen principal | accessor `getTitleAttribute()` | `title_{locale} ?: title_es` |
| `description_es/en/pt` (accessor `description`) | Tab "Acerca del Tour" (párrafos por `\n`), meta description JSON-LD (siempre usa `description_es` crudo, no el accessor) | accessor + columna directa | `description_{locale} ?: description_es`; JSON-LD cae a `$titleDisplay` si `description_es` vacío |
| `cover_image` (accessor `cover_url`) | Imagen principal si no hay galería | `ImagePath::url()` | `asset('assets/banners/banner-hero.jpg')` |
| `gallery` (accessor `gallery_urls`) | Galería (imagen principal + miniaturas), JSON-LD `image` | `ImagePath::url()` por ítem | Si vacío/no-array → `[cover_url]` |
| `duration` | Barra de info (icono reloj) | columna | Bloque completo se oculta si vacío (`@if`) |
| `group_type` | Barra de info (icono grupo) | columna | Bloque se oculta si vacío |
| `language` | Barra de info (icono idioma) | columna | Bloque se oculta si vacío |
| `departure_time` / `return_time` | Barra de info (icono salidas), combina ambos con " / " | columna | Bloque se oculta si ambos vacíos |
| `category` (relación `belongsTo Category`) | Eyebrow sobre el H1 (`$tour->category?->name`) | relación | `$L('Tour','Tour','Tour')` si null |
| `rating` | Estrellas + JSON-LD `aggregateRating.ratingValue` | columna (`decimal:1`) | JSON-LD: bloque `aggregateRating` completo se omite si `rating` es falsy |
| `reviews_count` | Texto "(N reseñas)" junto a estrellas + JSON-LD `reviewCount` | columna | JSON-LD: `reviews_count ?: 1` |
| `price_before` | Precio tachado + badge de descuento (caja de reserva + eyebrow de la ficha) | columna (`decimal:2`) | `hasOffer` requiere `price_before > 0 AND price_before > price` |
| `price` | Precio actual, cálculo de total (pax × precio, JS), JSON-LD `offers.price` | columna (`decimal:2`) | — |
| `show_offer_badge` | Flag que activa/desactiva el badge de oferta aun con `price_before` seteado | columna (boolean) | Si es `false`, no se muestra badge aunque haya `price_before` válido |
| `currency` | Moneda mostrada junto al precio/total, JSON-LD `offers.priceCurrency` | columna | `'USD'` si vacío |
| `itinerary_es/en/pt` | Tab "Itinerario" — pasos con hora/título/descripción | columna (`array`) | `itinerary_{locale} ?: itinerary_es ?: []`; pasos sin `title` se filtran; vacío → mensaje "disponible próximamente" |
| `includes_es/en/pt` | Tab "Qué incluye" (lista "Incluye") | columna (`array`) | `includes_{locale} ?: includes_es ?: []`; si vacío tras filtrar, cae a **4 ítems genéricos hardcodeados** (guía, transporte, recojo, impuestos) — no viene del CMS |
| `excludes_es/en/pt` | Tab "Qué incluye" (lista "No incluye", solo aparece si hay datos) | columna (`array`) | `excludes_{locale} ?: excludes_es ?: []`; si vacío, la sección completa "No incluye" (y el subtítulo "Incluye") no se muestra |
| `recommendations_es/en/pt` | Tab "Qué llevar" | columna (texto, se parte por líneas/comas) | `recommendations_{locale} ?: recommendations_es ?: ''`; si vacío, cae a **6 ítems de `lang/*/ui.php`** (no del CMS) |
| `faqs_es/en/pt` | Sección "Preguntas frecuentes" (acordeón), solo aparece si hay datos | columna (`array` de `{question, answer}`) | `faqs_{locale} ?: faqs_es ?: []`; entradas sin `question`/`answer` se filtran; vacío → sección entera oculta |
| `slug` (relacionados) | Links de "Más tours" | columna | — |
| `region_id` (indirecto, vía `TourController::show`) | Solo se usa para **elegir los relacionados** (`$related`), no se pinta en la ficha misma | columna (usada en el controlador, no en la vista) | Si la región no da 4 relacionados, el controlador reintenta sin filtro de región |

### Campos calculados/derivados fuera del modelo pero parte del flujo de la ficha
- `related` (hasta 3 tours, tomados de los 4 que trae el controlador — `->take(3)` en la vista): título, `cover_url`, `price`, `rating`, `reviews_count`, `slug`.
- Total del formulario de reserva (JS): `price × adults` — recalculado en cliente al cambiar el selector de pasajeros; el `<form>` envía `tour_id`, `adults`, `children=0` (hardcodeado a 0, no hay selector de niños en la ficha), `travel_date` a `cart.store`.

## 5. Campos del modelo `Tour` que la ficha NO CONSUME (existen en BD/CMS, ignorados por `show.blade.php`)

| Campo | Dónde se edita (CMS) | Impacto de no usarse |
|---|---|---|
| `subtitle_es/en/pt` | `TourResource` (tab General) | El editor puede escribir un subtítulo por idioma y nunca aparece en la ficha. |
| `notes_es/en/pt` | `TourResource` | Notas internas/del tour, sin salida visible en ningún lado del front revisado. |
| `max_capacity` | `TourResource` | Solo se usa `group_type` (texto libre); el número real de capacidad máxima no se muestra. |
| `badge_text` / `badge_type` | `TourResource` | Se usan en el **listado** de tours (`tours/index.blade.php`, no verificado en esta fase) pero **no en la ficha**: la ficha solo calcula su propio badge de oferta (`hasOffer`) con texto fijo `__('ui.special_offer')`, ignorando `badge_text`/`badge_type` del CMS. |
| `show_best_seller` | `TourResource` | No se lee en `show.blade.php`. |
| `comparison` (JSON, vía `Tour::comparisonData()`) | `TourResource` (bloque "Comparativa", ~12 campos × idioma) | El modelo ya tiene el método `comparisonData()` que normaliza el bloque y el componente `<x-tour-comparison>` existe y espera exactamente ese `data`, pero **nada en `show.blade.php` invoca ni a uno ni al otro**. El bloque comparativo convencional-vs-premium (mencionado como entregado en memoria de proyecto) no se renderiza en la ficha de tour. |
| `seo_title` | `TourResource` (tab SEO) | La ficha genera su propio `<title>` (`$titleDisplay . ' — ' . site_name`) ignorando `seo_title`. |
| `seo_description` | `TourResource` | La ficha genera su propia meta description (`seo.tour_description_prefix` + título) ignorando `seo_description`. |
| `seo_image` | `TourResource` | El layout usa `@section('og_image')` con fallback a la imagen genérica del sitio (`defaultOgImage`); `show.blade.php` nunca hace `@section('og_image', ...)`, así que ni `seo_image` ni la primera imagen de la galería del tour se usan como imagen de Open Graph/Twitter Card — todas las fichas comparten la misma imagen social genérica. |
| `seo_keywords` | `TourResource` | No hay `<meta name="keywords">` ni uso equivalente en la ficha. |
| `tourReviews` (relación `testimonials()` filtrada `is_active=true`) | `TestimonialResource` (campo `tour_id`) | El controlador ya trae las reseñas propias y aprobadas del tour, pero la vista nunca las itera/pinta — el visitante solo ve el promedio (`rating`/`reviews_count`), nunca los comentarios individuales. |
| `blockedDates` / `blockedWeekdays` (vía `BlockedDateResource`) | `BlockedDateResource` | El controlador calcula fechas y días de la semana bloqueados por tour, pero el `<input type="date">` de la caja de reserva no recibe ningún `min`/`disabled`/lista de exclusión derivada de esto — un visitante puede seleccionar una fecha que el admin marcó como bloqueada. |

## 6. Assets y JS propios de la ficha

- CSS/JS de build: `build/assets/app-*.css`, `build/assets/app-*.js` (Vite, hash actual verificado en F1).
- Terceros vía CDN: jQuery 3.7.1 y OwlCarousel 2.3.4 (cdnjs) — no se usan visiblemente en la galería de la ficha (la galería usa JS vanilla propio, ver `@push('scripts')` al final del archivo); posible remanente de otro módulo/plantilla, a confirmar por `maquetador-frontend`.
- JS inline propio (`@push('scripts')`): tabs (Acerca/Itinerario/Incluye/Qué llevar), cambio de imagen principal por miniatura, recálculo de total (pax × precio) en la caja de reserva.

## 7. No cubierto en F0 (por diseño de esta fase)

- No se leyó `tours/index.blade.php` ni `tours-card*.blade.php` en profundidad (fuera del módulo Ficha; se nombra `badge_text`/`badge_type` como referencia porque si se usan ahí, refuerza que en la ficha su ausencia es una asimetría, no que el campo esté muerto en todo el sitio).
- No se verificó visualmente (sin navegador, por alcance de esta fase) si el bloque `<x-tour-comparison>` "casi cableado" produciría un render correcto si se invocara — solo se confirma por código que hoy no se invoca.
