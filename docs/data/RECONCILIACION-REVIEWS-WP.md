# Reconciliación con producción — Reseñas (WordPress → app)

**Fecha:** 2026-07-26 · **Origen:** export local `storage/app/wp-import/reviews.json` (13 reseñas reales de `limaamericatours.com`) · **Destino:** tabla `testimonials`.
**Acceso:** sin VPS — solo el JSON ya preparado localmente.

---

## A) Censo

**13 reseñas, todas `status = publish`.** Todas son del **free tour** (guía "Augusto"), sin distinción por tour → se importan como testimonios **globales** (`tour_id = null`).

### Meta keys usadas (post type "reseñas", JetEngine)

| meta_key | # reseñas | ¿Importado? |
|---|---|---|
| `nombre` | 13 | ✅ `name` (ver excepción #861 abajo) |
| `procedencia` | 13 (2 vacías/solo espacio: #849, #860) | ✅ `country` (nullable si vacía tras `trim()`) |
| `comentario` | 13 | ✅ `quote_es`, tal cual (2 en inglés, 1 en italiano — **no se tradujo**, ver nota) |
| `valoracion` | 13 (siempre `"5"`) | ✅ `rating` (int, default 5 si faltara) |
| `fecha` | 13 (formatos irregulares ES/EN, ` 26 Marzo 2024` con espacio líder, `mar`/`Mar`/`Marzo`) | ✅ `review_date` (string verbatim, columna nueva — ver migración) |
| `viajo-en` | 13 (`en Pareja` / `Solo` / `en Familia` / `en Grupo`) | ✅ `traveled_as` (columna nueva) |
| `foto` | 13 (todas vacías) | ✅ mapeado a `avatar = null` — no había fotos que perder |
| `title` (post) | 13 | ✅ usado como fallback de `name` (ver #861) |

**Nota idioma:** 2 reseñas están en inglés (#853 Isuru, #855 Katie, entre otras) y 1 en italiano (#849 Margherita, "Una guida molto esperta e gentilissima."). Por instrucción explícita **no se tradujeron** — `quote_es` guarda el texto original tal cual llegó del WP, `quote_en` queda `null`.

---

## B) Caso borde — WP #861 ("Aura")

El registro `id=861` tiene `meta.nombre = "Bogotá"`, **idéntico** a `meta.procedencia = "Bogotá"` — un error de captura en el WP original (la ciudad se tipeó también en el campo de nombre). El nombre real solo sobrevive en el **post title**: `"Aura"`.

**Regla aplicada** (`App\Support\WpReviewMapper::resolveName()`): si `nombre` coincide con `procedencia` (case/espacios-insensible), se usa el `title` del post en su lugar.

Resultado importado: `name = "Aura"`, `country = "Bogotá"`. Cubierto por test (`ImportWpReviewsTest::test_import_uses_post_title_when_wp_name_matches_the_origin_city`).

---

## C) Migración aditiva

`database/migrations/2026_07_26_070000_add_wp_review_fields_to_testimonials_table.php` agrega, sin tocar columnas existentes:

- `external_ref` (string, nullable, **único**) — idempotencia: `"wp_review_{wp_id}"`.
- `review_date` (string, nullable) — el `fecha` de WP **verbatim**. No se forzó a columna `date`: los formatos son irregulares (`"03 Mar 2024"`, `" 26 Marzo 2024"`, mezcla ES/EN, mayúsculas inconsistentes) y forzar el tipo habría rechazado o mal-parseado alguna fila silenciosamente.
- `traveled_as` (string, nullable) — el `viajo-en` de WP.

`down()` revierte las 3 columnas (reversible).

---

## D) Orden de exhibición

El comando ordena las 13 reseñas por **fecha real del tour** (parseo best-effort de `meta.fecha` vía `WpReviewMapper::sortableDate()`, que reconoce abreviaturas ES de mes: ene/feb/mar/…) y, si no puede parsear una fecha, cae a **id de WP ascendente**. El campo `order` de `testimonials` queda con ese índice incremental (0-based). Resultado real (verificado en BD):

| # | name | review_date | external_ref |
|---|---|---|---|
| 0 | Katie | 24 Ene 2024 | wp_review_855 |
| 1 | Anja | 13 Feb 2024 | wp_review_854 |
| … | … | … | … |
| 12 | Benajmin | 26 Marzo 2024 | wp_review_859 |

---

## E) Idempotencia

`Testimonial::updateOrCreate(['external_ref' => "wp_review_{$id}"], $attrs)`. Verificado con 2 corridas reales consecutivas contra la BD local: 1ª → `creadas=13, actualizadas=0`; 2ª → `creadas=0, actualizadas=13`. `Testimonial::whereNotNull('external_ref')->count()` se mantiene en 13.

---

## F) Front — ¿hay hueco para testimonios globales?

**No hay hueco: ya funcionan sin cambios de código.** Se verificó que:

- `Tour::testimonials()` es un `hasMany` por-tour (`app/Models/Tour.php`), pero **ni `HomeController::fetchTestimonials()` ni `ReviewController::fetchTestimonials()` filtran por `tour_id`** — ambos hacen `Testimonial::active()...` a secas. Las 13 reseñas globales entran en el mismo pool.
- `resources/views/reviews.blade.php` (página pública `/resenas`) ya usa `@if ($t->tour)` (línea 173) antes de mostrar el nombre del tour, así que un testimonio con `tour_id = null` simplemente **omite esa línea** sin error.
- Con `is_active = true` (como las importó el comando), las 13 reseñas **ya son visibles en `/resenas`** de inmediato.
- Con `is_featured = false` (por instrucción explícita del import), **no aparecen en el home** (`HomeController` solo trae `featured()`); para destacar alguna ahí basta con marcar `is_featured = true` desde el panel Filament → Testimonios.

**Conclusión:** no se requirió construir ningún bloque nuevo — el hueco ya existía cubierto por el diseño previo del `ReviewAggregator`.

---

## G) Comandos ejecutados

```
php artisan migrate
php artisan reviews:import-wp
# Reseñas importadas: creadas=13, actualizadas=0 (total 13)
php artisan reviews:import-wp   # 2ª corrida, verificación de idempotencia
# Reseñas importadas: creadas=0, actualizadas=13 (total 13)
```
