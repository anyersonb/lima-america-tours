# Matriz de estado QA — Lima América Tours

Fuente de verdad del avance de verificación. Mantener actualizada (la actualiza `project-manager`).
Leyenda capas: ✅ pasa · 🔴/🟠 con hallazgos · — no corrido. Estados: PENDIENTE · EN CORRECCIÓN · VERIFICADO · VERIFICADO c/ deuda.

> Nota: la validación de 2026-07-24/25 fue **ad-hoc** (CRO + QA experto), NO bajo este protocolo formal.
> Por eso todos arrancan en PENDIENTE (protocolo). Los hallazgos ad-hoc ya corregidos están en el commit `81ba722`.

| Módulo | L1 | L2 | L3 | L4 | L5 | L6 | Estado | Reporte |
|---|---|---|---|---|---|---|---|---|
| Panel Filament | ✅ | ✅¹ | — | — | — | — | VERIFICADO c/ deuda (F0-F2 hechos; falta F3-F7) | `docs/qa/panel-filament.md` |
| Ficha de tour | ✅² | — | — | — | — | — | EN CORRECCIÓN (F0+F1 hechos, sin hallazgos bloqueantes; falta F2-F7) | `docs/qa/ficha-tour.md` |
| Tours (listado) | — | — | — | — | — | — | PENDIENTE | |
| Reserva | — | — | — | — | — | — | PENDIENTE | |
| Blog | — | — | — | — | — | — | PENDIENTE | |
| Home | — | — | — | — | — | — | PENDIENTE | |
| Contacto | — | — | — | — | — | — | PENDIENTE | |
| Nosotros | — | — | — | — | — | — | PENDIENTE | |

¹ L2 de **Panel Filament** en sí (labels de formulario, pluralización de BlockedDate, tab reactivo de PageResource, `<title>` de ficha, Offer sin consumidor) quedó corregido por `backend-laravel` en `qa/panel-filament` (2026-07-25) — ver `docs/qa/FIXES.md` filas 2-6. Los hallazgos #3 y #4 de `panel-filament.md` (sync de `Page.blocks` con `contact.blade.php`/`about.blade.php`, ~44 campos) quedan **fuera de este módulo**: se resuelven cuando se verifiquen los módulos Contacto y Nosotros (no bloquean el DoD de Panel Filament, que es sobre el panel admin, no sobre el front de esas páginas). `SmokeTest` verde, `php artisan test` en 4 failed (baseline Culqi) / 88 passed, cero regresiones nuevas.

² L1 de **Ficha de tour** (`qa/ficha-tour`, creada desde `qa/panel-filament`): sin hallazgos bloqueantes. Rutas 200/404 OK, log limpio, cero `dd()`/`dump()`. Un 🟡 informativo (`apple-touch-icon.png` 404, asset global de layout, no propio del módulo). El inventario F0 (`docs/qa/inventarios/ficha-tour.md`) documenta 3 gaps de completitud CMS↔front que **no bloquean L1** pero son la checklist prioritaria de `cro-validator` en F2: (a) `<x-tour-comparison>`/`Tour::comparisonData()` existen pero la ficha nunca los invoca; (b) `tourReviews` (reseñas propias aprobadas del tour) se consulta en el controlador pero no se pinta; (c) `blockedDates`/`blockedWeekdays` se calculan pero el datepicker de reserva no los usa. También: `subtitle`, `notes`, `max_capacity`, `badge_text`/`badge_type`, `show_best_seller`, `seo_title`/`seo_description`/`seo_image`/`seo_keywords` no se consumen en la ficha. `php artisan test`: 4 failed (baseline Culqi) / 88 passed, cero regresiones.

## Orden de verificación (§10 del protocolo)
Panel Filament → Ficha de tour → Tours → Reserva → Blog → Home → Contacto → Nosotros

## Prep de entorno pendiente antes de correr el protocolo
- [ ] Crear conexión/entorno `qa` (`.env.qa` + BD `lima_america_qa`) para `migrate:fresh --seed --env=qa` (§2).
- [ ] `tests/Feature/SmokeTest.php` en verde (lo crea `backend-laravel` en F1).
- [ ] Purga de datos de prueba heredados (LVT-*, cro.test@example.com, "Carlos Prueba", FAQ de prueba en huacachina).
