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
