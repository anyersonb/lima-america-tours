# Implementación sandbox — Culqi + PayPal (Fase 0)

**Rama:** `feat/pasarela-sandbox` (creada desde `design/rojo-acento`) · **Fecha:** 2026-07-26
**Alcance:** server-side + lógica, con tests mockeados (`Http::fake`). Sin llaves reales, sin cambios de UI/wizard, sin pruebas en vivo.
Referencia de arquitectura completa: [`PLAN-PASARELAS.md`](./PLAN-PASARELAS.md).

---

## 1. Qué quedó cableado

### 1.1 Config y `.env`
- `config/services.php`: se agregó `culqi.currency` (default `USD`, coherente con el total mostrado y con PayPal). Todo lo demás (`culqi.*`, `paypal.*`) ya existía y sigue en sandbox por defecto (`CULQI_ENV=sandbox`, `PAYPAL_MODE=sandbox`).
- `.env.example`: se agregó `CULQI_CURRENCY=USD`. El resto de placeholders (`pk_test_REPLACE_ME`, `sk_test_REPLACE_ME`, `whsec_REPLACE_ME`, `PAYPAL_CLIENT_ID=`, `PAYPAL_SECRET=`, `PAYPAL_WEBHOOK_ID=`) ya estaban.
- **Nada de esto lee llaves reales** — todo pasa por `env()`/`config()`. El jefe carga los valores en su `.env` local (gitignored).

### 1.2 Migración aditiva
`database/migrations/2026_07_26_000000_add_payment_gateway_fields_to_bookings.php` agrega a `bookings` (sin tocar columnas existentes):
- `expires_at` (timestamp, nullable) — hold de cupo mientras Culqi cobra.
- `refunded_at` (timestamp, nullable).
- `refund_amount` (decimal 10,2, nullable).

`status`/`payment_status` siguen siendo columnas string libres (sin ENUM de BD). Los valores lógicos nuevos (`abandoned`→`cancelled`/`expired`, `refunded`→`refunded`/`refunded`, `partially_paid`→`confirmed`/`partially_paid`) están documentados y centralizados en el state machine, no en la BD.

### 1.3 Máquina de estados única — `App\Services\BookingStateMachine`
Un solo punto (`transition(Booking $booking, string $target, array $extra = [])`) que:
- Mapea 6 estados lógicos (`PENDING_PAYMENT`, `PAID`, `FAILED`, `PARTIALLY_PAID`, `REFUNDED`, `ABANDONED`) a los pares `(status, payment_status)` reales.
- Valida transiciones permitidas (p. ej. `PAID` nunca regresa a `FAILED`; `REFUNDED` es terminal).
- Es idempotente: reintentar el mismo estado es un no-op (no reescribe columnas ni pisa un `payment_reference` ya fijado).
- La usan **ambos** webhooks (Culqi y PayPal) y **ambos** gateways en el controlador (Culqi charge, PayPal capture, y el "pagar después").

### 1.4 Culqi pay-now — `CheckoutController::processPayment()` + `chargeWithCulqi()`
`checkout.process` (mismo endpoint de siempre) ahora decide por la **presencia de `culqi_token`**, no por `payment_timing`:
- Sin `culqi_token` → flujo "reservar y pagar después" (WhatsApp/correo), **sin cambios de comportamiento** — el wizard vivo (`checkout.blade.php`) sigue enviando `payment_timing=later` y nunca manda `culqi_token`, así que ese camino no se tocó.
- Con `culqi_token` →
  1. Lock (`Cache::lock`) por sesión, para que un doble clic no dispare dos cobros.
  2. Crea los Bookings en `pending_payment` (hold, `expires_at` = +20 min) **antes** de cobrar — así el `payment_reference` puede fijarse en cuanto vuelve el `chr_...`, y el webhook de Culqi puede reconciliar la misma reserva si la respuesta síncrona se pierde.
  3. Llama `PaymentService::createCharge()` (ya existía, reutilizado tal cual).
  4. Éxito → transición a `PAID` + email + limpia carrito. Fallo → transición a `FAILED`, el hold queda vivo para reintentar (no se duplica la reserva).
- `showPaymentForm()` (`GET /checkout/pago`) ahora **renderiza** `checkout.payment` (antes solo redirigía) cuando el carrito tiene ítems, pasando `items`, `total`, `total_centavos`, `public_key`.

### 1.5 PayPal — orden, captura, webhook
- `paypalCreateOrder()` / `PayPalService::createOrder()`: sin cambios de comportamiento.
- `paypalCaptureOrder()`: ahora usa `PayPalCaptureRequest` (Form Request dedicado, antes era `$request->validate()` inline en el controlador) y es **idempotente**:
  - Lock por `orderID`.
  - Si PayPal responde `ORDER_ALREADY_CAPTURED` (doble clic / respuesta perdida), en vez de fallar, recupera la orden ya capturada vía el nuevo `PayPalService::getOrder()`.
  - Si el `payment_reference` (capture id) ya existe en un Booking `paypal`, no duplica — responde éxito e igual redirige a "gracias".
- **Webhook PayPal — NUEVO**: `POST /webhooks/paypal` (`webhooks.paypal`, fuera del grupo `{locale}`, CSRF-exempt como el de Culqi) → `WebhookController::paypal()`.
  - Verifica firma real contra `POST /v1/notifications/verify-webhook-signature` (`PayPalService::verifyWebhookSignature()`), usando `PAYPAL_WEBHOOK_ID`.
  - Eventos: `PAYMENT.CAPTURE.COMPLETED` → `PAID`; `PAYMENT.CAPTURE.DENIED`/`DECLINED` → `FAILED`; `PAYMENT.CAPTURE.REFUNDED` → `REFUNDED` (fija `refunded_at`/`refund_amount`, extrayendo el capture id del link `rel=up` del recurso de refund).
  - Reconciliación por `payment_reference` (capture id), igual que Culqi.

### 1.6 Requests separados por método
- `ProcessPaymentRequest`: `payment_timing` pasó de obligatorio a opcional (`nullable|in:now,later`); `culqi_token` es opcional (`nullable|string|max:191`), ya no usa `required_if`. El resto (nombre, email, teléfono, fecha) no cambió.
- `PayPalCaptureRequest` (**nuevo**): mismas reglas que antes vivían inline en `paypalCaptureOrder()`, ahora en un Form Request dedicado.

### 1.7 Panel Filament (`BookingResource`)
Se agregaron a los `Select`/badges las opciones que ya podían llegar por webhook pero no tenían label: `payment_status` → `failed`, `partially_paid`, `expired`; `status` → `refunded`; `payment_method` → `culqi` (se conserva `card` como legado, no se borró).

---

## 2. Baseline de tests — los 4 de `CheckoutTest` pasaron a verde

Los 4 fallos reportados (`payment form renders with items`, `process payment with valid token...`, `process payment with failed token...`, `booking email is queued...`) **ya estaban escritos en el repo** apuntando exactamente al comportamiento de esta fase. No se tocó `tests/Feature/CheckoutTest.php`: al cablear `showPaymentForm()`/`processPayment()`/`chargeWithCulqi()` y volver `BookingConfirmed` un `ShouldQueue` (faltaba el `implements`, causa real del 4º fallo), los 4 quedaron verdes sin modificar el archivo de test.

## 3. Tests nuevos

| Archivo | Cubre |
|---|---|
| `tests/Feature/BookingStateMachineTest.php` | Transiciones válidas, no-op idempotente, transición rechazada (`PAID`→`FAILED`, terminal `REFUNDED`), columnas extra (`refunded_at`/`refund_amount`). |
| `tests/Feature/CulqiChargeIdempotencyTest.php` | Doble submit no duplica la reserva pagada; cobro fallido deja el hold vivo (una sola reserva, `failed`, sin limpiar carrito). |
| `tests/Feature/PayPalCheckoutTest.php` | `create order`, `capture` exitoso, **doble captura no duplica** (simulando `ORDER_ALREADY_CAPTURED`), fecha bloqueada rechazada. |
| `tests/Feature/PayPalWebhookTest.php` | Firma inválida (400), `CAPTURE.COMPLETED`/`DENIED`/`REFUNDED`, idempotencia, evento sin booking coincidente (200 + log, no revienta). |

Todos usan `Http::fake` — ninguno requiere llaves reales.

## 4. Conteo final

```
php artisan test
Tests: 158 passed (459 assertions)
```
Baseline previo: 139 passed. Delta: **+19 tests nuevos**, 0 regresiones, los 4 de `CheckoutTest` en verde.

---

## 5. Qué falta para pasar a vivo (Fase 1 del plan)

1. **Llaves sandbox reales** (jefe): `CULQI_PUBLIC_KEY`/`CULQI_SECRET_KEY`/`CULQI_WEBHOOK_SECRET`, `PAYPAL_CLIENT_ID`/`PAYPAL_SECRET`/`PAYPAL_WEBHOOK_ID` — cargarlas en `.env` local, **nunca commitear**.
2. **Registrar los webhooks** en los paneles sandbox:
   - Culqi → `{APP_URL}/webhooks/culqi`
   - PayPal → `{APP_URL}/webhooks/paypal`, eventos: `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.DENIED`, `PAYMENT.CAPTURE.DECLINED`, `PAYMENT.CAPTURE.REFUNDED`.
3. **UI del wizard**: conectar el botón real de "pagar con tarjeta" en `checkout.blade.php` para que tokenice con Culqi.js y mande `culqi_token` a `checkout.process` (hoy esa vista sigue como antes, sin CTA de tarjeta — ver `checkout/payment.blade.php` como referencia de JS ya escrito). Reactivar el SDK de PayPal en el wizard (hoy comentado, líneas ~1444-1446).
4. **Prueba real en sandbox** con tarjetas/cuentas de prueba: cobro exitoso, cobro rechazado, reconciliación por webhook con la respuesta síncrona bloqueada (simulable cortando la red tras iniciar el cobro), reembolso end-to-end.
5. **Limitación conocida documentada** (§5.2 del plan y comentario en `WebhookController::paypal()`): el webhook de PayPal solo **reconcilia** Bookings que ya existen (creados por `paypalCaptureOrder()`); no puede crear uno desde cero porque no tiene los datos del cliente. Si el navegador pierde la conexión **antes** de que `paypalCaptureOrder()` cree el Booking, ni el webhook puede recuperarlo — se necesitaría precrear un "hold" en `paypalCreateOrder()` (requiere mandar los datos del cliente en ese paso, cambio de UI fuera de esta fase). Recomendado para una iteración posterior si se detectan casos reales.
6. **Job de expiración de holds**: `expires_at` ya se puebla (Culqi, 20 min), pero **no hay cron** que marque `ABANDONED` los holds vencidos ni que valide `tours.max_capacity` contra reservas. Pendiente si el negocio decide restringir aforo real.
7. **Textos legales y copy del wizard** (§8, §11 del plan): se tocan el mismo día en que la pasarela quede cobrando en producción y verificada — no antes.
8. **Reembolsos**: solo se maneja la reconciliación del evento `REFUNDED` (webhook); no hay una acción admin que dispare el refund contra la API de Culqi/PayPal — pendiente si se necesita desde el panel.

## 6. Cómo probar en sandbox cuando lleguen las llaves

```bash
# 1. Cargar llaves sandbox reales en .env (NO commitear)
CULQI_PUBLIC_KEY=pk_test_xxx
CULQI_SECRET_KEY=sk_test_xxx
CULQI_WEBHOOK_SECRET=whsec_xxx
PAYPAL_CLIENT_ID=xxx
PAYPAL_SECRET=xxx
PAYPAL_WEBHOOK_ID=xxx

# 2. Migrar
php artisan migrate

# 3. Registrar webhooks en los paneles sandbox apuntando a:
#    {APP_URL}/webhooks/culqi
#    {APP_URL}/webhooks/paypal

# 4. Exponer local a internet para que los webhooks lleguen (ngrok/similar)
#    y usar esa URL como APP_URL temporalmente para las pruebas de webhook.

# 5. Cablear el botón de tarjeta en el wizard (pendiente, ver punto 3 arriba)
#    y probar con las tarjetas de prueba de Culqi + cuentas sandbox de PayPal.

# 6. Verificar en el panel (Reservas) que:
#    - Un cobro exitoso queda 'confirmada'/'Pagado', payment_method='culqi' o 'paypal'.
#    - Un cobro rechazado queda 'Pendiente'/'Rechazado', reintentable.
#    - Cortar la red tras iniciar el cobro y confirmar que el webhook reconcilia
#      la reserva (Culqi) sin duplicarla.
```
