# QA — Panel Filament

Fecha 2026-07-25 · Rama `qa/panel-filament` · Fase ejecutada: **F0 (inventario) + F1 (salud técnica L1)**
Pendiente: F2-F7 (CMS↔front, estados, flujos, CRO, responsive, seguridad, gate de negocio) — los ejecuta `cro-validator` / `maquetador-frontend` / `security-engineer` / `client-validator` en fases siguientes.

## GATE previo (§ obligatorio antes de F0/F1)

`php artisan test` (entorno `testing`, sqlite en memoria — no toca `lima_america` ni `lima_america_qa`):

```
Tests:    9 failed, 33 passed (115 assertions)
```

- `SmokeTest`: **PASS** (8/8).
- Fallos: exactamente los 9 de `docs/qa/BASELINE-TESTS.md` (CartTest ×2, CheckoutTest ×5, NewsletterTest ×2). **Cero fallos nuevos.**
- **GATE: ✅ PASA.** Se procede con F0/F1 del módulo.

## Resultado L1: VERIFICADO (sin hallazgos)

| # | Capa | Nivel | Hallazgo | Evidencia | Estado |
|---|------|-------|----------|-----------|--------|
| — | L1 | — | Ninguno. Las 13 Resources + 2 Pages cargan (index y create) sin excepción, autenticado como admin. | `tests/Feature/PanelFilamentHealthTest.php` — 29/29 tests verdes | n/a |

No se registró ningún hallazgo 🔴/🟠/🟡/🔵 en esta capa.

## Cubierto

**Rutas protegidas**
- `GET /admin` sin sesión → **302** a `/admin/login` (confirmado con `curl` contra el servidor QA en `:8002`, y con test `test_admin_panel_requires_authentication`).
- `php artisan route:list --path=admin` → **43 rutas** registradas, todas bajo el guard de Filament (`admin/login`, `admin/logout`, `admin/profile` + 13 resources × (index/create/edit, salvo `abandoned-carts` que solo tiene `index`) + `admin/settings` + `admin/maintenance` + `admin` dashboard).

**Carga autenticada (test temporal `PanelFilamentHealthTest`, entorno `testing`, usuario `admin@limaamericatours.com` vía factory)**
- Dashboard: `filament.admin.pages.dashboard` → 200.
- Índice (13/13): `abandoned-carts`, `blocked-dates`, `blog-posts`, `bookings`, `categories`, `contact-leads`, `media-assets`, `newsletter-subscribers`, `offers`, `pages`, `regions`, `testimonials`, `tours` → 200.
- Create (12/13 — `abandoned-carts` no tiene create por diseño, `canCreate()` → `false`): `blocked-dates`, `blog-posts`, `bookings`, `categories`, `contact-leads`, `media-assets`, `newsletter-subscribers`, `offers`, `pages`, `regions`, `testimonials`, `tours` → 200.
- `Settings` (`/admin/settings`, 12 tabs, ~140+ campos) → 200.
- `Maintenance` (`/admin/maintenance`) → 200.
- **Total: 29/29 assertions verdes.**

**Logs**
- `storage/logs/laravel.log` vaciado antes de la corrida (`> storage/logs/laravel.log`).
- Tras correr el test suite completo (gate + `PanelFilamentHealthTest`): **0 líneas en el log** (archivo vacío). Cero `ERROR`/`CRITICAL`.

**Código**
- `grep` de `dd(`/`dump(`/`ray(` en `app/Filament/**`: **0 coincidencias**.
- Los 13 Resources y las 2 Pages fueron leídos completos línea por línea para construir el inventario (`docs/qa/inventarios/panel-filament.md`); no se observó código muerto, `TODO` bloqueante, ni credenciales hardcodeadas en los archivos de Filament (las claves de Pagos/APIs se guardan en BD vía `Setting::set`, con pre-relleno opcional desde `.env`/`config()` — no hay secretos en el código fuente de los Resources).

**Regresión**
- Suite completa (`php artisan test`) corrida dos veces: antes y después de añadir `PanelFilamentHealthTest`. Mismo resultado en ambas: 9 fallos baseline, cero nuevos. `PanelFilamentHealthTest` en sí: 29/29 verde.

**Entorno**
- BD de trabajo `lima_america` — **no se tocó** en ningún momento (ninguna conexión, ni lectura ni escritura).
- BD `lima_america_qa` — solo lectura (verificación de existencia del usuario admin vía `tinker --env=qa`, y `curl` de solo lectura a `GET /admin` y `GET /admin/login` contra el servidor `:8002`). Ninguna escritura, ningún login completado contra esa BD.
- El test de salud (`PanelFilamentHealthTest`) corre contra el entorno `testing` (sqlite en memoria, aislado por `phpunit.xml`), no contra `qa` ni `lima_america`.

## No cubierto (y por qué)

- **L2-L5a (CMS↔front, estados de datos, flujos, CRO)**: fuera de alcance de esta fase (F0+F1); corresponde a `cro-validator` en F2, con navegador.
- **L5b (responsive 375/768/1440 del panel)**: corresponde a `maquetador-frontend` en F3.
- **L6b (OWASP, mass assignment, XSS en RichEditor, permisos de storage)**: corresponde a `security-engineer` en F6. Se señala como pendiente relevante: `BlogPostResource` y `PageResource` usan `RichEditor` (HTML libre) — su sanitización en el front es parte natural del L6b/L2.
- **Edición (`edit`) de registros con datos reales**: el test de salud solo cubrió `index`/`create`. No se verificó `GET .../{record}/edit` porque solo existe factory para `Tour` y `User` en el proyecto (no hay factories para `Booking`, `BlogPost`, `Offer`, etc.); crear registros de prueba con prefijo `QA_` para probar `edit` de cada Resource es tarea de F2 (`cro-validator`), que ya trabaja con datos de prueba y navegador.
- **Verificación autenticada contra el servidor QA real (`:8002` / `lima_america_qa`)**: no se completó un login real contra ese servidor (habría requerido simular el flujo Livewire del formulario de login vía HTTP, que es frágil por CSRF/checksums). Se optó por el test PHPUnit autenticado (`actingAs`), que es equivalente para el objetivo de L1 ("cero excepciones al cargar los recursos") sin arriesgar la BD `lima_america_qa`. Si se requiere validar específicamente contra ese servidor, es tarea de `cro-validator` en F2 (que sí usa navegador contra `:8002`).
- **`CustomerResource`**: no existe en el panel (el modelo `Customer` tiene `bookings()` pero no está expuesto como Resource) — no aplica a este inventario.

## Backlog de contenido (🔵) — no bloquea

- Ninguno detectado en esta fase (F0/F1 no evalúa contenido real, solo salud técnica y estructura de campos).

## Archivos de esta fase

- `docs/qa/inventarios/panel-filament.md` — inventario completo (F0).
- `tests/Feature/PanelFilamentHealthTest.php` — test nuevo, verifica L1 (queda en el repo como regresión permanente, no se elimina).
- Este archivo (`docs/qa/panel-filament.md`) — sección L1; `cro-validator` debe **añadir** sus secciones L2-L5a a continuación, no sobreescribir.
