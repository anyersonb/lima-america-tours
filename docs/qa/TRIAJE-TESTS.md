# Triaje de los 9 tests que fallan — Lima América Tours

Fecha 2026-07-25 · Solo lectura (no se tocó código). Complementa `BASELINE-TESTS.md`.
**Reencuadre por decisión del jefe:** Culqi y PayPal SÍ van como métodos de pago. Por tanto los tests de
checkout/cart NO se descartan; los del cobro con pasarela quedan **en alcance, pendientes de la reescritura de pagos**.

## Causa raíz dominante (5 de 9): bug del layout, no de los módulos
`resources/views/components/jsonld.blade.php` (renderizado por `<x-jsonld/>` en `layouts/app.blade.php`), líneas 34-37:
```php
$stripScheme($settings['social_instagram'] ?: null),  // ← acceso por corchete; revienta si la clave no existe
```
Cuando la tabla `settings` está vacía (tests con `RefreshDatabase` **sin** seed), `$settings['social_instagram']`
lanza `Undefined array key` → **500** al pintar el layout. Las líneas 38-40 usan `?? null` (correcto).
- `SmokeTest` siembra en `setUp()` → por eso pasa. Cart/Checkout/Newsletter no siembran → 500.
- **En producción `settings` está sembrado → NO da 500.** Es fragilidad latente, no un 500 público real.
- **Fix recomendado (1 línea, NO es pasarela):** cambiar `?:` → `?? null` en jsonld.blade.php:34-37 (endurece también producción). Opcional: sembrar settings en esas 3 clases de test.

## Tabla
| # | Test | Ruta | Clasif | Causa raíz | Propuesta |
|---|------|------|--------|-----------|-----------|
| 1 | CartTest · cart persists across requests | `/es/carrito` | VIVO-ROTO | jsonld (layout) + test sin seed; la lógica del carrito es correcta | fix jsonld `?? null`; sembrar en test |
| 2 | CartTest · cart index renders checkout with items | `/es/carrito` | VIVO-ROTO | idéntica a #1 | idem |
| 3 | CheckoutTest · payment form renders with items | `/es/checkout/pago` (hoy 302→carrito) | EN ALCANCE (pasarela) | el form de pago se fusionó al wizard; vista `checkout/payment.blade.php` (Culqi v4) huérfana | reescribir con la nueva pasarela; no es 500 público (302) |
| 4 | CheckoutTest · process payment valid token creates booking+charge | POST `/es/checkout/procesar` | EN ALCANCE (pasarela) | payload sin `payment_timing`; el cobro Culqi pay-now fue retirado | reescribir al integrar Culqi/PayPal |
| 5 | CheckoutTest · process payment failed token marks booking failed | POST `/es/checkout/procesar` | EN ALCANCE (pasarela) | no existe path de "cobro fallido" hoy | reescribir (estado pago-fallido) con la pasarela |
| 6 | CheckoutTest · thanks page renders after successful payment | `/es/checkout/gracias` | VIVO-ROTO **PRIORITARIO** | jsonld (layout) + test sin seed; `thanks.blade.php` es correcta | fix jsonld; sembrar en test |
| 7 | CheckoutTest · booking email is queued after success | POST `/es/checkout/procesar` | EN ALCANCE (pasarela) + cobertura | payload sin `payment_timing`; además `BookingConfirmed` NO es `ShouldQueue` (usar `assertSent`, no `assertQueued`) | reescribir con pasarela; corregir aserción |
| 8 | NewsletterTest · confirm link marks confirmed_at | `/newsletter/confirm/{token}` | VIVO-ROTO | jsonld (layout) al pintar `newsletter.confirmed`; controller correcto | fix jsonld; sembrar en test |
| 9 | NewsletterTest · unsubscribe works | `/newsletter/unsubscribe/{token}` | VIVO-ROTO | idéntica a #8 | idem |

Resumen: **5 VIVO-ROTO** (#1,#2,#6,#8,#9 → todos por el jsonld del layout) y **4 EN ALCANCE-pasarela** (#3,#4,#5,#7 → cobro Culqi que se reescribe con la integración). Ningún MUERTO real: `checkout.pay` y `checkout.process` responden 302 (no 500 público).

## Respuestas explícitas
- **A. `checkout.thanks`**: SÍ es la página "gracias" del flujo actual (wizard → "Confirmar reserva" → `checkout.process` crea Booking `pending` → `redirect checkout.thanks`). Su 500 en test es por el jsonld, no por la vista. VIVO-ROTO prioritario. (Con la pasarela, este destino se mantiene tras el pago.)
- **B. Cobertura del flujo v1 real**: **NO existe ningún test** del camino real (wizard → confirmar → Booking pendiente → correo/WhatsApp). `grep` de `payment_timing`/`pay_later`/`whatsapp` en `tests/` = vacío. Es **hueco de cobertura**, no bug. (Se cubrirá con la reescritura de pagos.)
- **C. Newsletter**: enlazado en front (form en footer + popup). El `subscribe` no falla (redirige). `confirm`/`unsubscribe` sí se disparan por flujo real (link del email); su 500 es del jsonld del layout, no del módulo. En producción (sembrado) responden 200.

## Andamiaje de pagos existente en el fork (para el plan)
`app/Services/PaymentService.php` (Culqi) · `app/Services/PayPalService.php` · rutas `checkout.paypal.create/capture` + `webhooks.culqi` · `tests/Feature/CulqiWebhookTest.php` (**PASA**) · vista huérfana `checkout/payment.blade.php` (Culqi js v4) · wizard `checkout.blade.php` con `payment_timing=later`. Detalle → `docs/pagos/PLAN-PASARELAS.md`.
