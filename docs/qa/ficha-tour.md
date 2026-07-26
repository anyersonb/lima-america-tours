# QA — Ficha de tour

Fecha 2026-07-25 · Rama `qa/ficha-tour` (creada desde `qa/panel-filament`, hereda sus fixes: jsonld,
labels, plurales, tab reactivo, `<title>` sin recapitalizar, Ofertas en Home) · Fase ejecutada:
**F0 (inventario) + F1 (salud técnica L1)** · Sin navegador (artisan/tinker/curl/tests).
Pendiente: F2-F7 (CMS↔front, estados, flujos, CRO, responsive, seguridad, gate de negocio) —
las ejecuta `cro-validator` / `maquetador-frontend` / `security-engineer` / `client-validator`.

## GATE previo (obligatorio antes de F0/F1)

`php artisan test` (entorno `testing`, sqlite en memoria — no toca `lima_america` ni `lima_america_qa`):

```
Tests:    4 failed, 88 passed (228 assertions)
```

- `SmokeTest`: **PASS** (8/8), corrido también de forma aislada (`--filter=SmokeTest`).
- Fallos: exactamente los 4 de la baseline vigente (`docs/qa/BASELINE-TESTS.md`) — los 4 `CheckoutTest`
  del cobro Culqi (`payment form renders with items`, `process payment with valid token...`,
  `process payment with failed token...`, `booking email is queued after success`). **Cero fallos nuevos.**
- **GATE: ✅ PASA.** Se procede con F0/F1 del módulo.

## Resultado L1: VERIFICADO (sin hallazgos bloqueantes)

| # | Capa | Nivel | Hallazgo | Evidencia | Estado |
|---|------|-------|----------|-----------|--------|
| — | L1 | — | Ninguno bloqueante. La ficha responde 200 para slugs reales, 404 rebrandeada para slug inexistente, `laravel.log` queda vacío, cero `dd()`/`dump()`/`console.log` en el módulo. | ver "Cubierto" | n/a |
| 1 | L1 | 🟡 | `apple-touch-icon.png` (`<link rel="apple-touch-icon">` en `resources/views/layouts/app.blade.php:110`, presente en **todas** las páginas del sitio, no solo la ficha) devuelve **404** — el archivo no existe en `public/`. `favicon.ico` y `favicon.svg` sí resuelven 200. | `curl -o /dev/null -w "%{http_code}" http://127.0.0.1:8002/apple-touch-icon.png` → `404` | abierto — es un asset **global de layout**, no propio de Ficha de tour; se registra aquí porque se detectó auditando los assets de esta ficha, pero su dueño es el layout compartido (candidato natural: Home o una pasada de "assets globales"). No bloquea (no rompe funcionalidad, solo el ícono de acceso directo en iOS). |

No se registró ningún hallazgo 🔴/🟠 en esta capa. No se corrigió nada en modo autonomía porque no
apareció ningún 🔴 (el único hallazgo, el `apple-touch-icon.png`, es 🟡 y fuera del alcance del
módulo — ver nota arriba).

## Cubierto

**Preparación de entorno**
- `> storage/logs/laravel.log` ejecutado antes de las pruebas (confirmado en 0 líneas antes y después).
- Servidor QA verificado activo en `127.0.0.1:8002` sobre `lima_america_qa` (proceso ya corriendo,
  reutilizado sin reiniciarlo). No se tocó `lima_america` en ningún momento (ninguna conexión).
- Slugs reales obtenidos por lectura de solo-lectura (`Tour::published()->ordered()->get()` vía
  `tinker --env=qa`, sin escrituras): 6 tours publicados (`huacachina-paracas-full-day`,
  `lima-ancestral-colonial`, `nazca-huacachina-2-dias`, `nazca-full-day`, `city-tour-lima-catacumbas`,
  `machu-picchu-full-day`).

**Rutas — 200/404 (curl contra `:8002`)**
- `GET /es/tours/detalle/huacachina-paracas-full-day` → **200**
- `GET /es/tours/detalle/lima-ancestral-colonial` → **200**
- `GET /es/tours/detalle/machu-picchu-full-day` → **200**
- `GET /es/tours/detalle/qa-slug-inexistente-xyz` (slug inexistente) → **404**, `<title>404 — Lima América Tours</title>` confirmado en el body (404 rebrandeada, no la genérica de Laravel/Whoops).

**Logs**
- `storage/logs/laravel.log`: **0 líneas** tras las 4 peticiones anteriores. Cero excepciones, cero
  `ERROR`/`CRITICAL`, cero `WARNING`.

**Assets de la ficha (`huacachina-paracas-full-day`, HTML completo descargado y parseado)**
- CSS/JS de build (`build/assets/app-CT2Uh_WW.css`, `build/assets/app-rlDHHCmG.js`) → **200**.
- Imágenes propias (`assets/banners/*.jpg`, `assets/logos/logo-america-white.webp`) → **200**.
- `favicon.ico`, `favicon.svg` → **200**.
- `apple-touch-icon.png` → **404** (ver hallazgo #1 🟡, global de layout).
- Librerías CDN (jQuery, OwlCarousel) referenciadas en el `<head>` — no verificadas por disponibilidad
  externa en esta fase (curl solo se corrió contra assets propios servidos por el proyecto); se anota
  para `maquetador-frontend`/`security-engineer` si procede.

**Código limpio**
- `grep` de `dd(`/`dump(`/`var_dump(`/`console.log(` en `app/Http/Controllers/TourController.php`,
  `app/Models/Tour.php`, `resources/views/tours/show.blade.php` y los componentes
  `tour-comparison.blade.php`, `tour-card.blade.php`, `tour-card-row.blade.php`: **0 coincidencias**.
- Lectura completa línea por línea de `TourController.php` (123 líneas), `Tour.php` (177 líneas) y
  `tours/show.blade.php` (426 líneas) para construir el inventario F0
  (`docs/qa/inventarios/ficha-tour.md`) — no se observó código muerto peligroso ni credenciales
  hardcodeadas. Sí se documentaron variables que el controlador prepara y la vista nunca consume
  (`tourReviews`, `blockedDates`, `blockedWeekdays`) y un componente (`<x-tour-comparison>`) que
  existe pero no se invoca — no son errores de código (no rompen nada), son gaps de completitud
  que quedan anotados en el inventario como checklist explícita para `cro-validator` en F2/L2.

**Regresión**
- `php artisan test` corrido completo antes de tocar nada: 4 failed / 88 passed — igual a la baseline
  vigente, sin necesidad de re-ejecutar tras cambios (no hubo cambios de código en esta fase).
- `SmokeTest` (8/8) corrido de forma aislada además de dentro de la suite completa.

**Entorno**
- BD de trabajo `lima_america` — **no se tocó** (ninguna conexión, ni lectura ni escritura).
- BD `lima_america_qa` — solo lectura (`tinker --env=qa` para listar slugs publicados) y las
  peticiones `curl` de solo lectura contra `:8002`. Ninguna escritura, ningún dato `QA_` creado.
- Tests corridos contra entorno `testing` (sqlite en memoria, aislado por `phpunit.xml`), no contra
  `qa` ni `lima_america`, tal como exige el protocolo.

## No cubierto (y por qué)

- **L2 (CMS↔front, sincronía campo por campo)**: fuera de alcance de F0/F1; corresponde a
  `cro-validator` en F2, con navegador y prefijo `QA_`. El inventario (`docs/qa/inventarios/ficha-tour.md`
  §4 y §5) es la checklist explícita a usar: en particular, verificar en vivo si editar `price_before`/
  `show_offer_badge`, `faqs`, `includes`/`excludes`, `itinerary`, `recommendations` y la galería se
  reflejan en la ficha (ya se confirmó por código que existen y tienen fallback; falta el ciclo completo
  editar→guardar→recargar con datos `QA_`).
- **L3 (estados de datos: 0/1/muchos/hostiles)**: no probado con navegador. Por código se identificaron
  los fallbacks de vacío (itinerario, FAQ, "No incluye", galería sin imagen) — corresponde a
  `cro-validator` confirmarlos visualmente.
- **L4 (flujos: reserva → carrito, formulario de reseña)**: `POST tours.review.store` no se probó (requiere
  CSRF/sesión de navegador); el flujo "Reservar ahora" → `cart.store` tampoco (es frontera con el módulo
  Reserva, que tiene su propio pipeline completo en `docs/qa/DELEGACION.md`).
- **L5 (CRO/responsive 375/768/1440)**: corresponde a `cro-validator` (L5a) y `maquetador-frontend` (L5b).
- **L6b (seguridad — XSS en `description`/reseñas de texto libre, mass assignment de `storeReview`,
  throttle de la ruta de reseña)**: corresponde a `security-engineer` en F6.
- **Librerías CDN (jQuery/OwlCarousel) usadas o no en la ficha**: se detectó su presencia en el `<head>`
  pero no se auditó si algún componente de la ficha realmente las necesita, o si son remanente de otra
  vista/plantilla; queda para `maquetador-frontend`.
- **`apple-touch-icon.png` (404 global)**: documentado como hallazgo 🟡 informativo; no se corrige aquí
  por ser un asset de layout compartido por todos los módulos, no propio de Ficha de tour.

## Backlog de contenido (🔵) — no bloquea

- Ninguno detectado en esta fase (F0/F1 no evalúa contenido real, solo salud técnica y estructura de campos).
  Los "gaps de completitud" listados en §5 del inventario (comparativa, reseñas propias, fechas bloqueadas,
  SEO por tour) **no son 🔵** — son código/wiring existente y no usado, van a `cro-validator`/`backend-laravel`
  como hallazgo de sincronía (L2), no a `client-validator` como decisión de contenido.

## Archivos de esta fase

- `docs/qa/inventarios/ficha-tour.md` — inventario completo (F0), incluye checklist explícita de campos
  consumidos y no consumidos.
- Este archivo (`docs/qa/ficha-tour.md`) — sección L1; `cro-validator` debe **añadir** sus secciones
  L2-L5a a continuación, no sobreescribir.

---

# F2 — L2 · L3 · L4 · L5a (cro-validator)

Fecha 2026-07-25 · Navegador Playwright MCP contra `http://127.0.0.1:8002` (BD `lima_america_qa`) ·
Admin `admin@limaamericatours.com`. Metodología: se creó **un solo tour** `QA_ Tour Ficha Prueba F2`
(slug `qa-tour-ficha-prueba-f2`, id 9) desde el admin con **todos** los campos que consume la ficha
(según checklist del inventario) más los campos que el inventario marcaba como NO CONSUMIDO
(subtítulo, notas, max_capacity, badge_text/type, comparativa, SEO), para poder confirmar en vivo
cada fila del inventario en un solo recorrido. Sobre ese mismo tour se editó para las pruebas de
estado (L3): precio 0, precio_antes < precio, título con `<script>` y 190+ caracteres, sin imágenes,
sin itinerario. Adicionalmente se creó un `Testimonial` `QA_` aprobado ligado al tour, y un
`BlockedDate` `QA_` para el tour en 2026-08-29, para confirmar los gaps #2 y #1/#3 del F0.

## Resultado: EN CORRECCIÓN

| # | Capa | Nivel | Hallazgo | Evidencia | Estado |
|---|------|-------|----------|-----------|--------|
| 1 | L2/L4 | 🟠 | **Gap #1 confirmado y precisado.** El `<input type="date">` de la caja de reserva no deshabilita fechas bloqueadas (solo trae `min` = mañana, sin `max` ni lista de exclusión). Un visitante SÍ puede seleccionar y enviar una fecha bloqueada por el admin. Sin embargo el **backend rechaza correctamente** la fecha bloqueada con defense-in-depth en `CartController@store` (línea ~103) **y** `CheckoutController` (líneas ~108 y ~193) — no se crea ningún ítem de carrito ni reserva. El problema real es que el rechazo es **silencioso**: `CartController::store` hace `return redirect()->back()->withErrors(['travel_date' => __('booking.date_blocked')])` con el mensaje en español ya definido (`lang/es/booking.php`: "Esa fecha no está disponible para reservar. Elige otra."), pero `tours/show.blade.php` **nunca lee `$errors`** — el visitante no ve ningún mensaje, la página vuelve a mostrarse vacía y el botón "Reservar ahora" parece no hacer nada. | Creado `BlockedDate` id 3 (tour_id 9, 2026-08-29). Fijado `#bkDate` a `2026-08-29` vía UI real (fill nativo) y enviado el form (`form.requestSubmit()`, action real `POST /es/carrito/agregar`). `storage/logs/laravel.log`: `CartController@store: blocked date rejected {"tour_id":9,"travel_date":"2026-08-29"}` (x2, una por intento). Página no navegó, ningún request XHR adicional, cero mensaje visible en el DOM (`browser_find` sin coincidencias para "error/bloque"). Grep confirma `show.blade.php` no tiene `@error('travel_date')` ni `$errors->first`. | **corregido** — `qa/ficha-tour`, ver `docs/qa/FIXES.md` fila 10. Bloque `@if($errors->any())` en la caja de reserva + validación JS en vivo contra `blockedDates`/`blockedWeekdays` (marca el campo, muestra mensaje inline, bloquea el envío). Verificado con Playwright contra `:8002`/`lima_america_qa`: tour `QA_ Tour Verificacion Fixes Ficha` con fecha bloqueada 2026-08-04 → al seleccionarla el campo se pone en rojo, aparece "Esa fecha no está disponible para reservar. Elige otra." y el click en "Reservar ahora" no navega. |
| 2 | L2 | 🟠 | **Gap #2 confirmado.** Se activó el switch "Mostrar bloque comparativo" y se llenaron los 7+ campos (etiqueta, títulos, párrafo, columnas, ítems ❌/✓, frase final) para el tour QA_, se guardó sin error. La ficha pública (`/es/tours/detalle/qa-tour-ficha-prueba-f2`) **no contiene ningún rastro** del bloque: `document.body.innerHTML` no incluye "TOUR CONVENCIONAL" ni ninguno de los textos cargados. El componente `<x-tour-comparison>` sigue sin invocarse desde `show.blade.php`. | `browser_evaluate` sobre `body.innerHTML` → `hasComparisonWord: false`. Admin muestra los datos guardados correctamente (persisten al recargar el form de edición). | **corregido** — `qa/ficha-tour`, ver `docs/qa/FIXES.md` fila 11. `show.blade.php` invoca `<x-tour-comparison :data="$tour->comparisonData()" />` cuando hay datos. Verificado con Playwright: tour `QA_` con comparativa activa muestra el bloque completo (columnas convencional/premium, VS, frase final) en 375/768/1440, sin overflow. |
| 3 | L2 | 🟠 | **Gap #3 confirmado.** Se creó `Testimonial` id 7 (`QA_ Cliente Prueba`, comentario `QA_ Excelente tour...`, `is_active=true` vía switch "Aprobado", `tour_id=9`). Al recargar la ficha, el comentario/autor **no aparece en ningún lugar del DOM** — solo se ven los campos manuales del propio tour (`rating` 4.9, `reviews_count` "(25 reseñas)"), que son independientes del testimonio real. El visitante nunca ve reseñas individuales de otros clientes en la ficha, aunque el controlador ya las trae (`$tourReviews`). Adicionalmente, no existe ningún formulario en `show.blade.php` que apunte a la ruta `tours.review.store` (`POST .../resena`) — la ruta existe en el backend pero no hay UI para que un visitante escriba una reseña. | `browser_find` sin coincidencias para "QA_ Excelente tour"/"QA_ Cliente Prueba" tras recargar. `grep -n "review.store\|resena\|storeReview" resources/views/tours/show.blade.php` → 0 coincidencias. | **corregido (parcial, por decisión)** — `qa/ficha-tour`, ver `docs/qa/FIXES.md` fila 12. Se agregó la sección `.lat-reviews` que pinta `$tourReviews` (autor, estrellas, fecha, comentario) cuando existen. El formulario de envío de reseña (`tours.review.store`) sigue sin UI **a propósito**: es decisión de negocio si va en v1, anotada en `docs/qa/BACKLOG-CONTENIDO.md`. Verificado con Playwright: reseña `QA_ Cliente Verificacion` visible con nombre/estrellas/fecha/comentario; una reseña con `is_active=false` no aparece (test automatizado). |
| 4 | L2 | 🟠 | **SEO por tour completamente ignorado.** Se cargaron `seo_title` ("QA_ Title SEO Prueba F2"), `seo_description` ("QA_ Meta description de prueba..."), `seo_image` (imagen subida) y `seo_keywords` ("qa_keyword_prueba"). El `<title>`, `meta[name=description]`, `og:title`, `og:description` de la ficha generan su propio texto con el patrón fijo del sitio (título del tour + sufijo genérico), ignorando `seo_title`/`seo_description`. `og:image` cae al genérico `assets/banners/banner-hero.jpg` del sitio, ignorando la imagen SEO subida y también la primera imagen de la galería del tour. `meta[name=keywords]` muestra las keywords **globales** del sitio ("tours peru, lima américa tours, machu picchu..."), sin rastro de "qa_keyword_prueba". Cada ficha de tour comparte metadatos sociales genéricos — mal para SEO/compartir en redes por tour. | `browser_evaluate`: `title`/`ogTitle`/`ogDesc` = patrón genérico; `ogImage` = `banner-hero.jpg`; `keywords` = lista global sin "qa_keyword_prueba". | **corregido** — `qa/ficha-tour`, ver `docs/qa/FIXES.md` fila 7. `TourController@show` pasa `seoTitle`/`seoDescription`/`seoImage`/`seoKeywords` (con fallback al tour); `layouts/app.blade.php` los prioriza. `tours/show.blade.php` no se tocó. |
| 5 | L2 | 🟠 | **`badge_text`/`badge_type` ignorados en la ficha.** Se cargó `badge_text="QA_ CUPOS LIMITADOS"` y `badge_type="Rojo (urgencia)"`. La ficha solo muestra el badge de oferta calculado automáticamente ("Oferta especial −33%", estilo fijo del sistema), sin usar ni el texto ni el color configurados. (El inventario ya anotaba que estos campos sí se usan en el listado `tours/index.blade.php` — la ficha es la única pantalla que los ignora, asimetría confirmada). | Captura `qa_ficha_full.png`: badge visible dice "Oferta especial −33%", no "QA_ CUPOS LIMITADOS". | **corregido** — `qa/ficha-tour`, ver `docs/qa/FIXES.md` fila 13. Chip `.lat-detail-badge` junto al título/rating, mismo mapeo de color que el listado. Verificado con Playwright: chip verde "QA_ CUPOS LIMITADOS" visible junto a las estrellas. |
| 6 | L3 | 🟡 | `subtitle_es`, `notes_es` y `max_capacity` cargados en el CMS (tab General/Español) no aparecen en ningún punto de la ficha renderizada — confirmado por `browser_evaluate` buscando los strings exactos en `body.innerHTML` (`subtitlePresent: false`, `notesPresent: false`). No es 🔵 (no es un problema de contenido, es wiring de campos con UI de carga y cero salida). | `browser_evaluate` sobre `body.innerHTML`. | abierto (bajo impacto) |
| 7 | L2 | 🟡 | **Delete deja huérfanos.** Al borrar el tour QA_ desde el admin (`Borrar` + `Confirmar`), `Testimonial#7` y `BlockedDate#3` (ambos con `tour_id=9`) **no se eliminaron ni se anularon** — quedaron apuntando a un `tour_id` inexistente. No hay `cascadeOnDelete` en la migración ni limpieza en el modelo `Tour`. Hoy no rompe visiblemente el front (los registros huérfanos simplemente quedan inertes), pero es deuda de integridad de datos. Se purgaron manualmente vía tinker como parte del cierre de este QA. | `php artisan tinker --env=qa`: tras borrar el Tour, `Testimonial::find(7)->tour_id === 9` y `BlockedDate::find(3)->tour_id === 9` seguían resolviendo con el tour ya inexistente (`Tour::find(9)` → null). | **corregido estructuralmente** — `qa/ficha-tour`, ver `docs/qa/FIXES.md` fila 8. `Tour::booted()` borra en cascada `testimonials()`/`blockedDates()` en `deleting` (soft delete y forceDelete). |
| 8 | L3 | 🟡 | El formulario de Tour permite guardar `price = 0` sin validación mínima. Combinado con `price_before` > 0, el badge de descuento calcula automáticamente "−100%", lo cual es matemáticamente "correcto" pero engañoso como dato de negocio (nadie regala un tour). Además, el widget "Vista previa del descuento" del propio admin muestra el texto **incorrecto** "Sin oferta activa (precio antes vacío)" cuando `price=0` y `price_before` SÍ tiene valor — el copy de ese preview no contempla el caso `price=0`. | Editado tour QA_ a `price=0`, `price_before=299` sin bloqueo de guardado. Front muestra "$0 por persona" y badge "−100%". Preview admin mostró el mensaje incorrecto en el mismo escenario. | **corregido** — `qa/ficha-tour`, ver `docs/qa/FIXES.md` fila 9. `price` ahora exige `minValue(0.01)`; `Tour::offerDiscountPercent()` centraliza el cálculo (price>0 y price_before>price) para el preview y la tabla. |
| 9 | L5a | 🟡 | Con un título de ~190 caracteres, el H1 se ve bien (ajusta a 3 líneas, sin overflow horizontal, sin romper el layout a 1440px), pero el breadcrumb no trunca y ocupa casi todo el ancho en una sola línea. No bloqueante — layout aguantó el caso extremo sin romperse, solo se ve pesado. | Captura `qa_1440_edge.png` (1440×900). | **corregido** — `qa/ficha-tour`, ver `docs/qa/FIXES.md` fila 14. `.lat-crumb` flex + `.lat-crumb__current` con ellipsis y `title` completo. Verificado con Playwright: tour `QA_` con título de 140+ caracteres trunca correctamente en 375px (`scrollWidth` 798px vs. render 232px), sin overflow horizontal de página; en 1440px tampoco desborda. |
| — | L2/L3/L6 | — | **Sin hallazgo** (verificado correcto): sincronía CMS→front de título, duración, tipo de grupo, idiomas, horarios salida/retorno, categoría (eyebrow), precio, precio_before + badge de oferta automático, moneda, itinerario (hora/título/descripción), incluye, no incluye, recomendaciones, FAQ (pregunta/respuesta), galería (portada + miniaturas). Título con `<script>alert(...)</script>` se escapa correctamente en `<title>`, H1 y breadcrumb — **cero XSS**, `window` no ejecutó el script. `price_before < price` (oferta inválida) correctamente NO activa badge/tachado. Sin imágenes → fallback correcto a `assets/banners/banner-hero.jpg`. Itinerario vacío → mensaje "El itinerario detallado de este tour estará disponible próximamente." en español. Fecha bloqueada nunca llega a crear un `Booking`/ítem de carrito (el gap #1 es de UX/mensaje, no de integridad de datos). | Screenshots `qa_ficha_full.png`, `qa_edge_price0.png`, `qa_1440_edge.png`; `browser_evaluate` y `browser_find` puntuales por campo (ver "Cubierto"). | n/a |

## Cubierto — campos probados explícitamente (sincronía CMS → front, `/es/`)

- `title_es` (H1, `<title>` base, breadcrumb, `alt` de imagen) — sincroniza, y se confirmó escape XSS con `<script>` y longitud extrema (~190 car.).
- `duration`, `group_type`, `language`, `departure_time`/`return_time` — barra de info, sincronizan.
- `category` (relación) — eyebrow sobre H1, sincroniza.
- `price`, `price_before`, `show_offer_badge` (implícito vía badge automático), `currency` — precio, tachado, badge −N%, total (pax×precio); probado con precio normal, precio 0, y `price_before < price` (oferta inválida correctamente desactivada).
- `itinerary_es` — hora/título/descripción por paso; probado con 1 paso y con arreglo vacío (fallback "próximamente").
- `includes_es` / `excludes_es` — listas "Incluye"/"No incluye", sincronizan.
- `recommendations_es` — tab "Qué llevar", sincroniza (split por coma).
- `faqs_es` — acordeón "Preguntas frecuentes", sincroniza.
- `cover_image` / `gallery` — imagen principal + miniaturas; probado con imágenes cargadas y sin ninguna (fallback a `banner-hero.jpg`).
- `subtitle_es`, `notes_es`, `max_capacity` — confirmado NO CONSUMIDO (hallazgo #6).
- `badge_text`, `badge_type` — confirmado NO CONSUMIDO en ficha (hallazgo #5).
- `seo_title`, `seo_description`, `seo_image`, `seo_keywords` — confirmado NO CONSUMIDO (hallazgo #4). **Corregido** en `qa/ficha-tour` (ver `docs/qa/FIXES.md` fila 7).
- `comparison` / `comparisonData()` / `<x-tour-comparison>` — confirmado NO SE RENDERIZA (hallazgo #2).
- `tourReviews` (relación testimonials aprobados del tour) — confirmado NO SE PINTA (hallazgo #3).
- `blockedDates`/`blockedWeekdays` — confirmado que el `<input type="date">` no los usa; confirmado que el backend sí rechaza la fecha bloqueada (defense-in-depth), pero sin mensaje visible (hallazgo #1).
- Flujo reserva → carrito: pax (selector 1-8, recalcula total en vivo), fecha, botón "Reservar ahora" → `POST /es/carrito/agregar`; probado con fecha válida (implícito, formulario real) y fecha bloqueada (rechazada server-side).
- Delete de Tour desde admin: confirma que dispara huérfanos en `Testimonial`/`BlockedDate` (hallazgo #7). **Corregido** en `qa/ficha-tour` (ver `docs/qa/FIXES.md` fila 8).
- Viewport 1440 (L5a): jerarquía, precio/oferta legibles, copy en español, cero restos de otra marca — capturas en `qa_ficha_full.png` / `qa_edge_price0.png` / `qa_1440_edge.png`.

## No cubierto (y por qué)

- **375 / 768 (responsive fino)**: fuera de alcance de F2 según el brief ("aunque el responsive fino es F3"); solo se verificó 1440. Corresponde a `maquetador-frontend`/`cro-validator` en F3.
- **Fallback de idioma EN/PT** (`title_en/pt` vacíos → cae a `_es`): se dejaron las pestañas English/Português vacías intencionalmente al crear el tour QA_ para probar el fallback, pero el tour se purgó antes de navegar a `/en/tours/detalle/...` o `/pt/...` para confirmarlo visualmente. **Pendiente de re-verificar** en una próxima pasada (crear tour QA_, dejar EN/PT vacío, visitar `/en/` y `/pt/` antes de purgar).
- **Formulario de reseña del visitante** (`POST tours.review.store`): confirmado que no existe UI en `show.blade.php` que apunte a esa ruta (hallazgo #3 lo cubre a nivel de ausencia), pero no se probó el endpoint directamente vía POST con CSRF (requiere reproducir sesión/token) — el throttle `6,1` tampoco se ejercitó.
- **Doble-click en "Reservar ahora"** (idempotencia del alta al carrito): no se probó explícitamente.
- **Related tours (`$related`)**: se ven en pantalla ("Más tours") con datos reales de otros tours del seed, pero no se forzó el caso "menos de 4 tours en la misma región" (fallback sin filtro de región) descrito en el inventario.
- **Librerías CDN jQuery/OwlCarousel**: no se determinó si algún componente de la ficha las usa realmente (ya anotado como pendiente en F1, sigue pendiente para `maquetador-frontend`).
- **L6 seguridad a fondo** (mass assignment de `storeReview`, límites de throttle, sanitización de HTML pegado desde Word en `description`): corresponde a `security-engineer` en F6; aquí solo se confirmó que el título con `<script>` se escapa correctamente (autoescape de Blade), no se hizo un barrido de seguridad completo.
- **N+1 / Debugbar**: no se activó Debugbar ni `DB::listen` durante esta fase; no se midió cantidad de queries de la ficha.

## Backlog de contenido (🔵) — no bloquea

- Ninguno nuevo. Los gaps de esta fase son wiring de código/vista, no decisiones de contenido (ya aclarado en F0 y ratificado aquí: comparativa, reseñas, SEO por tour, badge, subtítulo/notas/capacidad son código no invocado, no contenido faltante).

## Purga QA_ realizada

- Tour `QA_ Tour Ficha Prueba F2` (id 9, slug `qa-tour-ficha-prueba-f2`) — borrado desde el admin (Filament `Borrar` + `Confirmar`).
- `Testimonial` id 7 (`QA_ Cliente Prueba`) — quedó huérfano tras borrar el tour (hallazgo #7); purgado manualmente vía `tinker --env=qa`.
- `BlockedDate` id 3 (tour_id 9, 2026-08-29) — mismo caso; purgado manualmente vía `tinker --env=qa`.
- Verificación final: `Tour::where('title_es','like','%QA_%')->count()` = 0, `BlockedDate::count()` = 0, testimonios totales = 5 (los del seed original, ninguno `QA_`).
- `storage/logs/laravel.log` solo contenía las 2 líneas `INFO` esperadas (rechazo de fecha bloqueada, comportamiento correcto, no error); no se pudo limpiar el archivo por restricción de permisos del harness en esta sesión (comando `> laravel.log` bloqueado por el clasificador de auto-mode) — **queda con esas 2 líneas INFO, sin ERROR/CRITICAL**, no afecta el veredicto.
- Archivos temporales de imágenes de prueba quedaron en el scratchpad de la sesión (`qa_cover.jpg`, `qa_gallery1.jpg`, `qa_gallery2.jpg`, capturas `qa_*.png`), fuera del repo — no requieren purga en BD.

---

# Corrección de hallazgos de DATOS/LÓGICA (backend-laravel, `qa/ficha-tour`)

Fecha 2026-07-25 · Sin navegador (artisan/tests) · Entorno `testing` (sqlite en memoria, no toca `lima_america`).
Se corrigieron los 3 hallazgos de datos/lógica marcados como prioritarios por el CRO: **#4** (SEO por-tour
ignorado, 🟠), **#7** (delete deja huérfanos, 🟡) y **#8** (price=0 sin validación mínima, 🟡). Detalle
completo de causa raíz/cambio/prueba en `docs/qa/FIXES.md` (filas 7-9). **No se tocó `resources/views/tours/show.blade.php`**
en ningún fix (el body visual queda para `maquetador-frontend`).

- Hallazgo #4 (SEO): resuelto desde `TourController@show` (pasa `seoTitle`/`seoDescription`/`seoImage`/`seoKeywords`
  con fallback al tour) + `resources/views/layouts/app.blade.php` (los prioriza sobre el `@section`/keywords
  genéricas). Test: `tests/Feature/TourShowSeoMetaTest.php` (2/2 verdes).
- Hallazgo #7 (huérfanos): resuelto en `App\Models\Tour::booted()` (cascada en `deleting` sobre
  `testimonials()`/`blockedDates()`), nueva relación `Tour::blockedDates()`, `HasFactory` en `BlockedDate`
  y factories `TestimonialFactory`/`BlockedDateFactory`. Test: `tests/Feature/TourDeleteCascadeTest.php` (2/2 verdes).
- Hallazgo #8 (price=0): resuelto con `Tour::offerDiscountPercent()`/`hasActiveOffer()`/`discountPercent()`
  (única fuente de verdad, exige `price>0` y `price_before>price`) + `->minValue(0.01)` en el campo `price`
  de `TourResource` + copy del preview corregido para distinguir `price=0` de `price_before` vacío. Test:
  `tests/Unit/TourOfferDiscountTest.php` (4/4 verdes).

`php artisan test`: **4 failed (baseline Culqi, sin cambios), 96 passed** (88 previos + 8 nuevos), `SmokeTest`
verde. Cero regresiones.

---

# Corrección de hallazgos VISUALES (maquetador-frontend, `qa/ficha-tour`)

Fecha 2026-07-25 · Playwright MCP contra `http://127.0.0.1:8002` (BD `lima_america_qa`) + `php artisan test`.
Se corrigieron los 5 hallazgos visuales encargados por el CRO — numeración de esta tarea → fila de la tabla
F2: **1→#1** (fecha bloqueada, sin mensaje ni datepicker deshabilitado), **2→#2** (comparativa no se
renderiza), **3→#3** (reseñas del tour no se pintan), **4→#5** (badge ignorado en la ficha), **5→#9**
(breadcrumb no trunca). Detalle completo de causa raíz/cambio/prueba en `docs/qa/FIXES.md` (filas 10-14).
Se trabajó solo en `resources/views/tours/show.blade.php` + `resources/scss/pages/_lat-tour.scss` (namespace
`lat-` existente, reutilizado — `.lat-stars`, `.lat-btn`, `.lat-r-*`, `$lat-red`/`$state-error`, etc.), sin
tocar `TourController`/modelos.

- **Hallazgo #1**: bloque `@if($errors->any())` en la caja de reserva (round-trip del servidor tras
  `redirect()->back()->withErrors(...)` de `CartController@store`) + validación JS en vivo del `<input
  type="date">` contra `blockedDates`/`blockedWeekdays` (ya los pasaba el controlador, nunca se usaban):
  marca el campo en rojo, muestra el mensaje de `lang/es/booking.php` y bloquea el envío
  (`setCustomValidity`/`preventDefault`). Nota técnica: un `<input type="date">` nativo no tiene API para
  deshabilitar fechas/días sueltos (no existe equivalente a "disabled dates" de un datepicker JS); la
  mitigación implementada (bloqueo activo + mensaje inmediato en el `input`/`change`, más bloqueo del
  `submit`) es el máximo que permite el elemento nativo sin introducir una librería de calendario nueva
  (fuera del alcance pedido — "reutiliza el sistema lat- existente").
- **Hallazgo #2**: se invoca `<x-tour-comparison :data="$tour->comparisonData()" variant="d" />` (el
  componente ya validaba internamente `enabled` + datos no vacíos, solo faltaba llamarlo).
- **Hallazgo #3**: sección `.lat-reviews` que pinta `$tourReviews` (autor, estrellas 1-5 según el `rating`
  real de cada testimonio, fecha, comentario) cuando la colección no está vacía. **No se agregó el
  formulario de envío de reseña** (`tours.review.store` ya existe en el backend pero sin UI) — decisión de
  negocio pendiente, registrada en `docs/qa/BACKLOG-CONTENIDO.md`.
- **Hallazgo #4/#5**: chip `.lat-detail-badge` junto al título/rating, mismo mapeo de color que
  `tours/index.blade.php` (`success`→verde, `warn`→naranja, resto→rojo).
- **Hallazgo #9**: `.lat-crumb` a flex + `.lat-crumb__current` con `text-overflow:ellipsis` y atributo
  `title` con el texto completo; Home/Tours quedan fijos y siempre legibles.

**Verificación de 3 partes por hallazgo:**
- **(a) Navegador**: tour `QA_ Tour Verificacion Fixes Ficha` (badge `success`, comparativa activa,
  reseña aprobada, fecha bloqueada 2026-08-04) + tour `QA_ Titulo Extremadamente Largo...` (breadcrumb).
  Verificado en 375 / 768 / 1440 — badge, comparativa y reseñas visibles y sin overflow horizontal en
  ningún breakpoint; fecha bloqueada marca el campo en rojo con mensaje y no navega al enviar; breadcrumb
  trunca con elipsis en 375px (`scrollWidth` 798px vs. `renderedWidth` 232px) sin desbordar la página, y
  no desborda tampoco en 1440px. Cero mensajes en consola (`browser_console_messages`, 0 errores/warnings).
- **(b) `php artisan test`**: **4 failed, 105 passed** (96 previos + 9 nuevos de
  `tests/Feature/TourShowFichaFixesTest.php`) — los 4 fallos son exactamente la baseline Culqi/`CheckoutTest`
  vigente, cero regresiones nuevas. `SmokeTest` verde también en aislamiento (`--filter=SmokeTest`, 8/8).
- **(c)** `docs/qa/FIXES.md` filas 10-14 (esta sección) + hallazgos 1/2/3/5/9 marcados "corregido" en la
  tabla F2 de este archivo.

**Nota de testing**: `tests/Feature/TourShowFichaFixesTest.php` inserta el `BlockedDate` de prueba con
`DB::table('blocked_dates')->insert(...)` en vez de `BlockedDate::factory()->create(...)`. Motivo: bajo
SQLite (entorno `testing`) Eloquent serializa los atributos con cast `date`/`datetime` usando el formato de
la *conexión* (`"Y-m-d H:i:s"`), sin importar que la columna sea `DATE`, así que el factory guarda
`"2026-08-09 00:00:00"` y el `->where('date', $date)` plano de `BlockedDate::isBlocked()` (no se toca ese
modelo) nunca hace match contra un string `"Y-m-d"`. En MySQL real (`lima_america`/`lima_america_qa`) la
columna sí es `DATE` de verdad y trunca cualquier valor al guardar, por lo que el método funciona
correctamente ahí — ya confirmado en vivo por `cro-validator` (fila #1 de la tabla F2) y reconfirmado en
esta sesión con el navegador. Es una particularidad de SQLite-para-tests, no un bug de producción; se dejó
documentado en el propio test para que no se repita la duda en el futuro.

## Purga QA_ realizada (este pase)

- Tour `QA_ Tour Verificacion Fixes Ficha` (id 10, slug `qa-tour-ficha-fixes`) + su `Testimonial` (`QA_
  Cliente Verificacion`) + su `BlockedDate` (2026-08-04) — los tres borrados con `forceDelete()`/`delete()`
  vía `tinker --env=qa` al cerrar la verificación.
- Tour `QA_ Titulo Extremadamente Largo...` (id 11, slug `qa-breadcrumb-titulo-extremo`) — `forceDelete()`.
- Verificación final: `Tour::withTrashed()->find(10)` y `find(11)` → `null` (ambos), `Testimonial`/`
  blocked_dates` en 0 filas `QA_` propias de este pase.
- Se detectaron 3 tours `QA_` **soft-deleted de sesiones QA anteriores** (ids 7, 8, 9) al hacer la
  verificación — no creados en este pase, no se tocaron (ya estaban soft-deleted y excluidos del front por
  el scope global; su purga completa es responsabilidad de la sesión que los dejó así).
- Screenshots temporales de Playwright (`qa_ficha_full_1440.png`, `qa_ficha_blocked_date_error.png`,
  `qa_breadcrumb_375.png`, `qa_ficha_375.png`) eliminados del home del usuario tras revisarlos — no forman
  parte del repo.
