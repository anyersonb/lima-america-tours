# Línea base de tests — Lima América Tours

**ACTUALIZACIÓN 2026-07-25:** tras corregir el bug del layout `jsonld.blade.php` (`?:`→`?? null`, commit en `qa/setup`), la suite bajó de **9 → 4 fallos** (`4 failed, 38 passed, 133 assertions`). Se resolvieron los 5 VIVO-ROTO (CartTest ×2, CheckoutTest·thanks, NewsletterTest ×2). **La línea base vigente son 4** (los del cobro Culqi pay-now, pendientes de la reescritura de pasarela).

Historial: arrancó en 9 failed / 33 passed (herencia del fork).

## Gate vigente (reemplaza a "todo verde")
**El gate pasa si:** `SmokeTest` en verde **Y** cero fallos NUEVOS respecto a la baseline de **4** (los CheckoutTest de abajo marcados "EN ALCANCE · pasarela").
- `SmokeTest` (8 tests): ✅ VERDE — es lo que el gate debe proteger.
- Un fallo distinto a esos 4 = regresión = 🔴 → corregir sobre la marcha (modo autonomía).

### Baseline VIGENTE (4)
- CheckoutTest · payment form renders with items
- CheckoutTest · process payment with valid token creates booking and charge
- CheckoutTest · process payment with failed token marks booking failed
- CheckoutTest · booking email is queued after success

> Los 4 se reescriben con la integración Culqi+PayPal (ver `docs/pagos/PLAN-PASARELAS.md` §10.1). NewsletterTest y CartTest **ya pasan**.

---
### Baseline original (histórica, 9) — 5 ya RESUELTOS por el fix jsonld

## Los 9 fallos de la baseline

| # | Suite | Test | Clasificación | Notas |
|---|---|---|---|---|
| 1 | CartTest | cart persists across requests in session | EN ALCANCE · pendiente de pasarela | 500 en flujo carrito |
| 2 | CartTest | cart index renders checkout view with items | EN ALCANCE · pendiente de pasarela | 500 en `cart.index` |
| 3 | CheckoutTest | payment form renders with items | EN ALCANCE · pendiente de pasarela | form de pago |
| 4 | CheckoutTest | process payment with valid token creates booking and charge | EN ALCANCE · pendiente de pasarela | cobro con token (Culqi) |
| 5 | CheckoutTest | process payment with failed token marks booking failed | EN ALCANCE · pendiente de pasarela | estado pago fallido |
| 6 | CheckoutTest | thanks page renders after successful payment | EN ALCANCE · pendiente de pasarela | `checkout.thanks` 500 (revisar si es la "gracias" del flujo) |
| 7 | CheckoutTest | booking email is queued after success | EN ALCANCE · pendiente de pasarela | `BookingConfirmed` no se encola |
| 8 | NewsletterTest | newsletter confirm link marks confirmed at | VIVO-ROTO (a confirmar) | 500 en `newsletter.confirm` — feature del motor, no de pago |
| 9 | NewsletterTest | newsletter unsubscribe works | VIVO-ROTO (a confirmar) | 500 en `newsletter.unsubscribe` |

## Contexto para la reescritura de pagos
- **CartTest + CheckoutTest (7)**: quedan congelados hasta la integración de pasarela (Culqi + PayPal). La reescritura del flujo de pago los reactivará/reescribirá. Ver `docs/pagos/PLAN-PASARELAS.md`.
- `CulqiWebhookTest` **PASA** → ya existe andamiaje de webhook Culqi en el fork (reusable).
- **NewsletterTest (2)**: NO es de pago; es 🔴 real del motor. Se corrige aparte (no entra en el QA de esta noche, que es solo Panel + Ficha).

## Fuera del QA de esta noche
Reserva/checkout NO se verifica esta noche (la reescribe la pasarela). Newsletter tampoco (bug aparte, baselineado).
