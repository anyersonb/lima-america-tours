# Plan de integración de pasarelas de pago — Culqi + PayPal
**Proyecto:** Lima América Tours (`G:\laragon\www\lima-america`) · Laravel (motor forkeado v1)
**Estado:** Documento de arquitectura — SOLO ANÁLISIS. No incluye código, credenciales ni texto legal definitivo.
**Fecha:** 2026-07-25 · **Autor:** Arquitectura

---

## 1. Resumen ejecutivo y objetivo

El fork ya trae **andamiaje de pagos parcialmente funcional**: PayPal está integrado de punta a punta (crear orden + capturar) y Culqi tiene servicio + webhook probado, pero la **captura de tarjeta con Culqi (pay-now) nunca se cableó en el controlador**. Además, en la iteración v1 se decidió "sin pago en línea": el wizard actual (`resources/views/checkout.blade.php`) cierra la reserva con botones **"Confirmar reserva por WhatsApp" / "Enviar por correo"** que crean una reserva `pending` vía `checkout.process` (`payment_timing=later`) y abren `wa.me`.

**Objetivo de esta fase:** convertir **Culqi (tarjeta) y PayPal** en los dos métodos que **CIERRAN la reserva**, reemplazando el flujo "formulario → WhatsApp" por "formulario → pago". WhatsApp queda **solo como contacto/soporte** (FAB flotante + página Contacto). Se define además la **máquina de estados de la orden** con manejo de cupo, la **reconciliación por webhooks** (Culqi ya; PayPal falta) y se deja el plan preparado para la **pregunta abierta pago total vs. anticipo/seña** (sin decidirla).

**Hallazgos críticos que condicionan el plan:**
1. **No existe acción de cobro Culqi.** `PaymentService::createCharge()` existe y está probado indirectamente, pero **ningún controlador lo invoca**. `CheckoutController::processPayment()` rechaza explícitamente `payment_timing=now` (líneas 89-94). El "pagar con tarjeta" hay que construirlo.
2. **PayPal no tiene webhook de reconciliación.** La reserva se marca `paid` de forma **síncrona en el navegador** (`paypalCaptureOrder`). Si el cliente pierde conexión tras aprobar pero antes del AJAX, el cobro existe en PayPal pero **no hay Booking**. Existe `PAYPAL_WEBHOOK_ID` en `.env` pero **no hay ruta ni handler**.
3. **No hay tabla "orden" ni concepto de cupo/hold.** Cada ítem del carrito genera un `Booking` independiente (`finalizeBookings()`); no existe `expires_at`, `orders` ni decremento de aforo. `tours.max_capacity` existe pero **no se valida** contra reservas. La única restricción de disponibilidad es `BlockedDate`.
4. `App\Mail\BookingConfirmed` **NO implementa `ShouldQueue`** → se envía síncrono (causa del fallo del test que usa `assertQueued`).

---

## 2. Estado actual del andamiaje — REUSO vs. REESCRITURA

### 2.1 Backend de pagos

| Componente | Archivo / método | Estado | Decisión |
|---|---|---|---|
| Servicio Culqi | `app/Services/PaymentService.php` → `createCharge()`, `retrieveCharge()`, `verifyWebhookSignature()` | Completo y correcto (HMAC-SHA256, logging estructurado, manejo de errores) | **REUSAR** tal cual |
| Servicio PayPal | `app/Services/PayPalService.php` → `accessToken()` (cache 8h), `createOrder()` (intent CAPTURE, USD), `captureOrder()`, `captureId()` | Completo, robusto (maneja el bug del `{}` en capture, credenciales desde `Setting` con fallback a `config`) | **REUSAR** tal cual |
| Webhook Culqi | `app/Http/Controllers/WebhookController.php` → `culqi()`, `handleChargeSucceeded()`, `handleChargeFailed()` | Verifica firma, idempotente (chequea `payment_status`), casa por `payment_reference` | **REUSAR**; extender estados (ver §4) |
| Config | `config/services.php` (`culqi`, `paypal`) | Correcto | **REUSAR** |
| Test webhook | `tests/Feature/CulqiWebhookTest.php` | **PASA** (4 casos: firma inválida, succeeded, failed, idempotencia) | **REUSAR** como referencia del contrato |

### 2.2 Controlador de checkout — `app/Http/Controllers/CheckoutController.php`

| Método | Estado actual | Decisión |
|---|---|---|
| `showPaymentForm()` | Redirige a `cart.index` con `open_step=pago`. La página de pago independiente **ya no se usa** | **REESCRIBIR** (o eliminar) — hoy el pago vive dentro del wizard |
| `processPayment()` | Solo procesa `payment_timing=later`; **rechaza `now`** (líneas 89-94). Crea Booking `pending` vía `finalizeBookings(..., 'pay_later', null, false)` | **REESCRIBIR**: el "later"/WhatsApp desaparece como cierre; este endpoint pasa a orquestar el inicio de pago (Culqi/PayPal) o se sustituye por acciones dedicadas |
| `paypalCreateOrder()` | Calcula total **server-side** (`cart->total()`), crea orden PayPal USD | **REUSAR**; añadir `custom_id`/reference propia para reconciliación por webhook |
| `paypalCaptureOrder()` | Captura, valida `BlockedDate`, crea Bookings `paid`/`confirmed` | **REUSAR** el núcleo; mover el "marcar pagado" a idempotente/compatible con webhook (§5) |
| `finalizeBookings()` | Crea 1 Booking por ítem, notifica, cierra carrito abandonado, limpia carrito, resuelve/crea `Customer` guest | **REUSAR**; parametrizar `status`/`payment_status` según nueva máquina de estados |
| `resolveCustomerId()` | Crea cliente invitado + envía `AccountCredentials` | **REUSAR** |
| `thanks()` | Renderiza `checkout.thanks` desde `session('last_bookings')` | **REUSAR** |

### 2.3 Request de validación — `app/Http/Requests/ProcessPaymentRequest.php`
- Valida `payment_timing in:now,later`, datos de cliente, `culqi_token required_if:payment_timing,now`.
- **REESCRIBIR**: `payment_timing`/`later` queda obsoleto (ya no hay "pagar después" como cierre). Separar validación por método (Culqi token vs. PayPal orderID). El bloque `paypalCaptureOrder()` ya valida inline (líneas 174-182) — unificar en Requests dedicados.

### 2.4 Rutas — `routes/web.php`

| Ruta | Estado | Decisión |
|---|---|---|
| `checkout.pay` (GET) | Redirige a carrito | Revisar/retirar |
| `checkout.process` (POST, `throttle:checkout`) | Cierre WhatsApp/later | **REESCRIBIR** hacia inicio de cobro Culqi |
| `checkout.paypal.create` / `checkout.paypal.capture` (POST) | Funcionales | **REUSAR** |
| `checkout.thanks` (GET) | OK | **REUSAR** |
| `webhooks.culqi` (POST, CSRF-exempt, fuera del grupo locale) | OK | **REUSAR** |
| **`webhooks.paypal`** | **NO EXISTE** | **CREAR** (§5) |
| **Cobro Culqi (crear charge)** | **NO EXISTE** ninguna ruta que llame `createCharge()` | **CREAR** |

### 2.5 Modelo y tabla `bookings`
- `app/Models/Booking.php`: `guarded=['id']`, casts de fechas/decimales, `reference` autogenerada (`LVT-XXXXXXXX`).
- Migraciones: `create_bookings_table` (status `pending`, payment_status `pending`, payment_method, payment_reference, currency USD, notes, locale) + `add_pickup_and_notes` + `add_customer_id` + `add_payment_reminder_sent_at` + `add_discount_and_custom_tour` + `add_payment_link_url`.
- **Falta** para la máquina de estados propuesta: `expires_at` (hold), `refunded_at`/`refund_amount`, `deposit_amount`/`balance_due` (escenario seña), y opcionalmente una tabla `orders`/`payment_events`. **REESCRIBIR vía nuevas migraciones aditivas** (no romper columnas existentes).

### 2.6 Vistas

| Vista | Estado | Decisión |
|---|---|---|
| `resources/views/checkout/payment.blade.php` | **HUÉRFANA**. Página Culqi v4 standalone (`<script src="checkout.culqi.com/js/v4">`, `Culqi.open()`, callback de token → POST a `checkout.process`). Chips: `card` activo; `yape`/`plin`/`paypal` como "coming soon" deshabilitados. **No está ruteada** | **REESCRIBIR/ELIMINAR** — su JS Culqi es la mejor referencia para el nuevo paso de pago |
| `resources/views/checkout.blade.php` (~113 KB) | **VIVA** — la renderiza `CartController@index` (`view('checkout', ...)`). Wizard 3 pasos. Panel 2 = `form#payment-form` POST `checkout.process` con `payment_timing=later` fijo. Botones `btn-whatsapp-confirm` y `btn-email-confirm` (ambos submit → crean Booking `pending` → abren `wa.me`). PayPal SDK **comentado/desactivado** (líneas 1444-1446). Aviso "Sin pago en línea por ahora... confirmamos por WhatsApp o correo. Sin comisiones." (líneas 1330-1333) | **REESCRIBIR el paso de pago**: conservar recolección de datos y resumen; retirar cierre por WhatsApp/correo; insertar botones Culqi (tarjeta) + PayPal |
| `resources/views/checkout/thanks.blade.php` | OK | **REUSAR** |

**Qué se CONSERVA del formulario/wizard actual:**
- Recolección de datos del viajero: nombre/apellidos, email, país, teléfono+prefijo, fecha de viaje, punto de recojo (`pickup_point`/`pickup_detail`), idioma/guía/comentarios.
- Sincronización JS de campos ocultos → `customer_name`/`customer_phone`/`travel_date`/`pickup_detail` (mecánica `syncDetallesFields`).
- Resumen de orden con total server-side, checkbox de términos, validación de fecha bloqueada (`BlockedDate::isBlocked`), navegación por pasos, sticky footer.
- `finalizeBookings()` + `BookingNotifier` + cierre de carrito abandonado + creación de cliente invitado.

**Qué se RETIRA:**
- Botones `btn-whatsapp-confirm` / `btn-email-confirm` como mecanismo de cierre.
- `input hidden payment_timing=later` fijo y toda la rama "pagar después / sin pago en línea".
- Aviso "Sin pago en línea por ahora / Sin comisiones" (líneas 1330-1333).
- JS `buildWhatsAppSummary()` / `btn-whatsapp-confirm` listener (líneas ~1816-1835).

---

## 3. Arquitectura propuesta del flujo `formulario → pago`

### 3.1 Principio
El wizard sigue siendo la única superficie de checkout (`checkout.blade.php`, dentro de `cart.index`). El Paso 2 recolecta datos y ofrece **dos botones de cierre reales**: **Pagar con tarjeta (Culqi)** y **Pagar con PayPal**. Ambos crean/consolidan la reserva y la confirman contra el resultado del cobro. El total **siempre se calcula en el servidor** (`CartService::total()`), nunca desde el cliente (ya se respeta en `paypalCreateOrder`).

### 3.2 Flujo Culqi (tarjeta) — a construir
1. Usuario completa datos → clic "Pagar con tarjeta".
2. JS valida campos y abre **Culqi Checkout v4** (`Culqi.publicKey`, `Culqi.settings({amount, currency})`, `Culqi.open()`) — reutilizar el patrón de `checkout/payment.blade.php` (líneas 336-393). `amount` en **centavos enteros**; definir moneda de cobro (ver §3.4).
3. Callback `window.culqi` obtiene `Culqi.token.id` → POST a **nueva acción** (p. ej. `checkout.culqi.charge`) con token + datos del cliente.
4. Servidor: revalida datos (Request dedicado), revalida `BlockedDate`, **crea Bookings en estado `pending_payment` con `payment_reference` provisional**, llama `PaymentService::createCharge()` con `amount`/`currency`/`email`/`source_id`/`metadata` (incluir `booking references` para reconciliación).
5. Si el charge responde 2xx (`outcome venta_exitosa`) → marca `paid`/`confirmed`, guarda `payment_reference = chr_...`, `session('last_bookings')`, redirige a `checkout.thanks`.
6. Si falla → marca `payment_failed`, mensaje de reintento (§6). El **webhook Culqi** (`charge.succeeded`/`charge.failed`) reconcilia de forma asíncrona (ya existe, casa por `payment_reference`).

> Decisión clave: el `payment_reference` debe fijarse **antes o durante** el cobro para que el webhook pueda casar la reserva. Hoy `finalizeBookings()` guarda la referencia solo al final; para Culqi hay que reservar el booking primero (estado `pending_payment`) y actualizar el `payment_reference` con el `chr_id` devuelto.

### 3.3 Flujo PayPal — ya existente, a completar
1. Botón PayPal (SDK JS) → `checkout.paypal.create` (`paypalCreateOrder`) devuelve `order.id`.
2. Aprobación en el popup PayPal → `checkout.paypal.capture` (`paypalCaptureOrder`) captura, valida `BlockedDate`, crea Bookings `paid`/`confirmed`, redirige a `checkout.thanks`.
3. **A completar:** (a) pasar `custom_id` con la referencia interna en `createOrder`; (b) **webhook PayPal** `PAYMENT.CAPTURE.COMPLETED`/`DENIED` para reconciliar los casos en que el AJAX de captura no vuelve al navegador (§5); (c) hacer `paypalCaptureOrder` idempotente frente al webhook (no duplicar Bookings si el webhook llega primero).

### 3.4 Moneda y montos
- `Booking.currency` = `USD`; PayPal cobra USD; el resumen muestra USD. La vista huérfana calculaba `totalPen = total*3.70` **solo para mostrar** pero cobraba USD (`total_centavos`).
- **Decidir para Culqi**: cobrar en **USD** (coherente con PayPal y el total del carrito) o en **PEN** (Culqi es peruano; muchas cuentas Culqi operan en PEN). Si se cobra PEN hay que fijar tipo de cambio y evitar descuadres con el total mostrado. **Recomendación:** cobrar en la misma moneda que se muestra (USD) si la cuenta Culqi lo permite; si no, mostrar el equivalente PEN de forma explícita. `amount` a Culqi = entero en céntimos.

### 3.5 Endpoints resultantes (propuesta)
- `POST /checkout/culqi/charge` (nuevo) — throttle:checkout.
- `POST /checkout/paypal/create` y `/capture` (existentes).
- `POST /webhooks/culqi` (existente) + `POST /webhooks/paypal` (nuevo), ambos CSRF-exempt y fuera del grupo `locale` (como el de Culqi).
- `checkout.process` (existente): **se reconvierte** o se retira; ya no representa "cerrar por WhatsApp".

---

## 4. Máquina de estados de la orden + manejo de cupo

### 4.1 Estados propuestos (sobre `bookings.status` + `bookings.payment_status`)
Hoy existen de facto: `payment_status ∈ {pending, paid, failed}` y `status ∈ {pending, confirmed}`. Se propone formalizar:

| Estado lógico | `status` | `payment_status` | Significado |
|---|---|---|---|
| **Pendiente de pago** | `pending` | `pending` | Booking creado, cupo apartado (hold), esperando cobro |
| **Pagado/Confirmado** | `confirmed` | `paid` | Cobro exitoso (Culqi/PayPal), cupo consumido |
| **Pago fallido** | `pending` | `failed` | Intento rechazado; permite reintento; hold vigente hasta `expires_at` |
| **Abandonado** | `cancelled` | `expired` | Hold venció sin pago; cupo liberado |
| **Reembolsado** | `refunded` | `refunded` | Reembolso total/parcial ejecutado; cupo liberado (según política) |
| *(seña — ver §12)* | `confirmed` | `partially_paid` | Anticipo cobrado; saldo pendiente |

> Se añaden los valores `expired`/`refunded`/`partially_paid` (payment_status) y `cancelled`/`refunded` (status). Como `bookings` usa columnas `string`, es aditivo; documentar el enum en un `App\Enums\...` o constantes.

### 4.2 Transiciones
```
[carrito] --crear booking--> Pendiente de pago (hold: expires_at = now + T)
Pendiente de pago --cobro OK (Culqi/PayPal o webhook)--> Pagado/Confirmado
Pendiente de pago --cobro rechazado--> Pago fallido
Pago fallido --reintento OK--> Pagado/Confirmado
Pago fallido / Pendiente --expira hold (job)--> Abandonado (libera cupo)
Pagado/Confirmado --reembolso (admin/webhook)--> Reembolsado (libera cupo)
```

### 4.3 Cupo / stock durante el pago (hoy INEXISTENTE)
- **Situación actual:** no hay hold ni decremento de aforo. `tours.max_capacity` existe pero **no se compara** con reservas por fecha; solo `BlockedDate` bloquea fechas completas. Riesgo de sobreventa si se activa pago real con aforo limitado.
- **Propuesta (nueva):**
  1. Añadir `bookings.expires_at` (nullable). Al crear un Booking `pending_payment` se fija `expires_at = now()+T`.
  2. **Timeout `T` recomendado:** 15-20 min para Culqi/PayPal (suficiente para completar el pago; alineado con el patrón de "reserva temporal"). Configurable vía `Setting`/env.
  3. **Cálculo de disponibilidad:** aforo restante para `(tour_id, travel_date)` = `max_capacity − (pax confirmados + pax en holds vigentes)`. Un hold cuenta contra el cupo **solo mientras `expires_at > now()` y `payment_status ∈ {pending, failed}`**.
  4. **Liberación:** un job programado (`schedule`) marca `Abandonado` (`status=cancelled`, `payment_status=expired`) los holds vencidos → el cupo se libera automáticamente. También liberar de inmediato en fallo definitivo si el usuario abandona.
  5. **Al confirmar pago:** el hold se convierte en consumo firme (`confirmed`/`paid`), se limpia `expires_at`.
- **Nota:** si el negocio no tiene aforo limitado por tour/fecha (tours privados/on-demand), el hold puede ser meramente informativo y el timeout servir solo para limpieza de reservas fantasma. Confirmar con el jefe si `max_capacity` debe realmente restringir ventas.

---

## 5. Webhooks (Culqi + PayPal)

### 5.1 Culqi — EXISTE, reutilizar
- Ruta `webhooks.culqi` (CSRF-exempt). `WebhookController::culqi()` verifica **HMAC-SHA256** del cuerpo crudo contra `services.culqi.webhook_secret` (`PaymentService::verifyWebhookSignature`, `hash_equals`), rechaza con 400 si no valida.
- **Idempotencia:** casa Bookings por `payment_reference` y **omite** si ya está en estado final (`paid`/`failed`). Probado en `CulqiWebhookTest::test_webhook_is_idempotent`.
- **Reconciliación:** maneja `charge.succeeded` → `paid`/`confirmed`; `charge.failed` → `failed`.
- **Ajustes:** al integrar la nueva máquina de estados, extender para no re-confirmar reservas ya `refunded`/`cancelled`, y considerar eventos de reembolso Culqi si se usan.

### 5.2 PayPal — FALTA, crear
- **Crear ruta** `POST /webhooks/paypal` (CSRF-exempt, fuera del grupo `locale`, análoga a Culqi) + handler en `WebhookController`.
- **Verificación de firma:** usar el endpoint de PayPal `/v1/notifications/verify-webhook-signature` con `PAYPAL_WEBHOOK_ID` (ya en `.env`/`config('services.paypal.webhook_id')`), o validar cabeceras de transmisión. **No** confiar en el payload sin verificar.
- **Eventos mínimos:** `PAYMENT.CAPTURE.COMPLETED` (confirmar), `PAYMENT.CAPTURE.DENIED`/`DECLINED` (fallido), `PAYMENT.CAPTURE.REFUNDED` (reembolsado).
- **Idempotencia:** casar por `payment_reference` (capture id) o por `custom_id` (referencia interna que hay que empezar a enviar en `createOrder`). Omitir si ya en estado final. Debe coexistir con `paypalCaptureOrder` sin duplicar Bookings (mismo criterio de match).
- **Reconciliación:** cubre el hueco actual (captura exitosa en PayPal pero AJAX perdido) creando/confirmando la reserva desde el webhook.

### 5.3 Reglas comunes
- Responder 2xx rápido; procesar idempotente por referencia.
- Registrar todo (`Log::info/warning`) como ya hace el handler Culqi.
- Nunca crear cobros desde el webhook; solo **reconciliar** el estado local.

---

## 6. Fallo / cancelación / reintento

- **Fallo de cobro (Culqi 4xx/rechazo, PayPal DENIED):** marcar `payment_failed`, conservar el Booking en hold (`expires_at` vigente) y mostrar mensaje claro con opción de **reintentar** con otra tarjeta/PayPal sin recrear la reserva. El controlador ya devuelve mensajes de error amigables (`processPayment` y `paypalCaptureOrder`).
- **Cancelación del usuario (cierra el modal Culqi / cancela PayPal):** no crear Booking o dejar el hold; permitir reintento inmediato. En Culqi, el token no se genera si cancela; en PayPal, `onCancel` del SDK.
- **Reintento:** el mismo `payment-form` permite reintentar. Si el Booking ya existe en `pending`/`failed`, reutilizarlo (por sesión/`last_bookings`) en vez de duplicar.
- **Doble cobro / carrera navegador-webhook:** la idempotencia por `payment_reference` en ambos webhooks + chequeo de estado final lo previene. `paypalCaptureOrder` debe verificar si el Booking ya fue confirmado por webhook antes de re-confirmar.
- **Expiración:** job de limpieza (§4.3) marca `Abandonado` y libera cupo.
- **Reembolso:** acción de admin (Filament) que llama a la API de reembolso (Culqi/PayPal) y/o el webhook `REFUNDED` que marca `refunded`. *(La API de refund no está implementada aún en los servicios — pendiente.)*

---

## 7. Variables de entorno (SOLO nombres) y credenciales a solicitar

### 7.1 Variables ya definidas (en `.env.example` / `config/services.php`)
**Culqi:** `CULQI_PUBLIC_KEY`, `CULQI_SECRET_KEY`, `CULQI_WEBHOOK_SECRET`, `CULQI_API_URL`, `CULQI_ENV`
**PayPal:** `PAYPAL_CLIENT_ID`, `PAYPAL_SECRET`, `PAYPAL_MODE`, `PAYPAL_WEBHOOK_ID`
**Infra relacionada:** `APP_URL` (para URLs de webhook), `QUEUE_CONNECTION` (hoy `sync`)

> Nota: `PayPalService` lee primero de la tabla `Setting` (`paypal_client_id`, `paypal_secret`, `paypal_mode`) y usa `.env` como respaldo. Definir dónde vivirán las credenciales de producción (panel vs. `.env`) para evitar ambigüedad.

### 7.2 Posibles nuevas variables
- `PAYMENT_HOLD_MINUTES` (timeout de cupo, §4.3) — o gestionarlo vía `Setting`.
- `CULQI_CURRENCY` si se decide cobrar en PEN vs USD (§3.4).
- (Seña) `DEPOSIT_PERCENT` / gestión vía `Setting` (§12).

### 7.3 Credenciales a pedir al jefe (NO generar)
Para **cada** pasarela, en **sandbox** y **producción**:
- **Culqi:** llave pública (`pk_...`), llave secreta (`sk_...`), **secreto de firma del webhook** (`whsec_...`), moneda de la cuenta (PEN/USD), y confirmación de la URL de webhook a registrar en el panel Culqi (`{APP_URL}/webhooks/culqi`).
- **PayPal:** `Client ID`, `Secret`, modo (`sandbox`/`live`), **Webhook ID** del webhook registrado en el dashboard PayPal apuntando a `{APP_URL}/webhooks/paypal`, y cuenta business de recepción.
- **Definición de negocio:** moneda de cobro final, política de reembolsos concreta, y **la decisión pago total vs. seña** (§12).

---

## 8. Textos legales necesarios (LISTA — no redactar)

Actualizar/crear (multilenguaje es/en/pt, sobre `lang/*/legal.php` y páginas `legal.terms`/`legal.privacy`):
1. **Términos de pago:** métodos aceptados (tarjeta vía **Culqi** + **PayPal**), moneda, momento del cargo, que no se almacenan datos de tarjeta (los procesa la pasarela certificada).
2. **Política de reembolsos:** porcentajes y ventanas por anticipación (hoy hay borrador PayPal-only en `terms_s4_body`; debe cubrir ambos métodos y el escenario seña).
3. **Política de cancelación:** plazos, no-show, fuerza mayor, reprogramación.
4. **Política de anticipo/seña** (si se adopta): monto/porcentaje, saldo, plazo y forma de pago del saldo, qué pasa si no se paga el saldo.
5. **Privacidad — tratamiento de datos de pago:** mencionar Culqi y PayPal como encargados (hoy `privacy_s1_body`/`privacy_s3_body` solo nombran PayPal).
6. **Términos de confirmación:** que la reserva se confirma con el **pago**, no por WhatsApp (ajustar copy legal que hoy dice "se confirma por WhatsApp/correo").
7. **Aviso de seguridad/PCI** y enlace a políticas de las pasarelas.

---

## 9. Lista de reversión — cambios "anti-Culqi" de anoche (QUÉ revertir/ajustar)

> Solo listado. No modificar en esta fase.

1. **`lang/es/legal.php` (y equivalentes `en`/`pt` si se replicaron):**
   - `terms_s3_body` (línea 16): reescrito a **PayPal-only + "reservar ahora/pagar después" + confirmación por WhatsApp**. Revertir para incluir **tarjeta vía Culqi** y **eliminar** el cierre por WhatsApp como mecanismo.
   - `terms_s4_body` (línea 19): reembolsos "solo PayPal" y "reservar ahora, pagar después" → ajustar a ambos métodos y a la nueva máquina de estados (sin "pagar después").
   - `privacy_s1_body` (línea 26) y `privacy_s3_body` (línea 32): mencionan **solo PayPal** como procesador → añadir Culqi.
2. **`lang/es/footer.php`:** clave `methods_of_payment` (línea 17) quedó **huérfana** — `components/footer.blade.php` ya **no renderiza** la fila de métodos de pago (solo sellos, líneas 139-152). Revertir: **restaurar la fila de métodos de pago** mostrando Culqi (VISA/Mastercard/Amex) + PayPal. Revisar `seal_secure_payment` ("Pago 100% Seguro") por si conviene volver a nombrar la pasarela.
3. **`lang/es/checkout.php`:** conserva strings Culqi (`hero_subtitle` "…con Culqi", `secure_payment` "Pago 100% seguro con Culqi", `accepted_cards`, `pay_button`) que corresponden a la **vista huérfana**. Al reescribir el paso de pago del wizard, decidir si se reutilizan o migran (no borrarlos sin reemplazo).
4. **`resources/views/checkout.blade.php`:** aviso "Sin pago en línea por ahora… Sin comisiones" (líneas 1330-1333) y toda la copy "confirmamos por WhatsApp o correo" → revertir hacia copy de pago en línea.
5. **`config/services.php`:** verificar que no se haya vaciado/comentado el bloque `culqi` (actualmente **intacto**, OK) — confirmar en el `.env` real de QA/prod que las llaves Culqi siguen presentes.

---

## 10. Causa raíz de los 7 fallos (CheckoutTest/CartTest) y cobertura faltante

Referencia: `docs/qa/TRIAJE-TESTS.md`. De los 9 fallos totales, **7** corresponden a checkout/cart.

### 10.1 Los 4 de cobro (EN ALCANCE — se abordan con la reescritura)
- **#3 `test_payment_form_renders_with_items`** (`CheckoutTest`): espera `view('checkout.payment')` con `public_key`/`total_centavos`. Hoy `checkout.pay` **redirige (302)** a carrito y la vista `checkout/payment.blade.php` está huérfana. → **Reescribir**: el test debe apuntar al **paso de pago dentro del wizard** (o a la nueva vista/props Culqi) tras la integración.
- **#4 `test_process_payment_with_valid_token_creates_booking_and_charge`**: envía `culqi_token` **sin `payment_timing`** → hoy `ProcessPaymentRequest` exige `payment_timing` y el controlador **rechaza `now`**; además **no hay** invocación a `createCharge()` en el flujo real. → **Reescribir** apuntando al **nuevo endpoint de cobro Culqi** (§3.2). El mock `Http::fake(api.culqi.com/v2/charges)` sigue siendo válido.
- **#5 `test_process_payment_with_failed_token_marks_booking_failed`**: no existe hoy un path de "cobro fallido" real (el `now` se rechaza antes de cobrar). → **Reescribir** para el nuevo endpoint + estado `payment_failed`.
- **#7 `test_booking_email_is_queued_after_success`**: dos causas: (a) payload sin `payment_timing`; (b) usa `Mail::assertQueued(BookingConfirmed::class)` pero **`BookingConfirmed` NO implementa `ShouldQueue`** → se envía síncrono. → **Reescribir**: o cambiar la aserción a `Mail::assertSent`, o hacer `BookingConfirmed implements ShouldQueue` (recomendado para no bloquear el checkout; requiere `QUEUE_CONNECTION` real en prod, hoy `sync`).

### 10.2 Los 3 restantes de checkout/cart (VIVO-ROTO — fix de layout, aparte de pagos)
- **#1, #2 `CartTest`** y **#6 `CheckoutTest::test_thanks_page_renders`**: fallan por el **bug de layout jsonld**, no por pagos. `resources/views/components/jsonld.blade.php` líneas 34-37 usan acceso por corchete `$settings['social_instagram'] ?: null` que lanza **`Undefined array key` → 500** cuando `settings` está vacío (tests con `RefreshDatabase` sin seed). **Fix (1 línea):** `?:` → `?? null`. **No forma parte de la reescritura de pagos** pero desbloquea estos tests. (Ver también #8/#9 Newsletter, mismo origen.)

### 10.3 Cobertura faltante del flujo real (tests a crear)
No existe **ningún** test del camino real de checkout (grep de `payment_timing`/`pay_later`/`whatsapp` en `tests/` = vacío). Proponer:
1. **Culqi pay-now happy path:** carrito → nuevo endpoint charge (con `Http::fake` Culqi 201) → Booking `paid`/`confirmed`, `payment_reference=chr_...`, redirige a thanks.
2. **Culqi rechazo:** `Http::fake` 4xx → Booking `payment_failed`, mensaje de reintento, sin doble cobro.
3. **PayPal create+capture:** `paypalCreateOrder` devuelve id; `paypalCaptureOrder` (con `Http::fake` PayPal) → Booking `paid`, idempotente frente a segunda captura.
4. **Webhook PayPal:** firma válida/ inválida, `CAPTURE.COMPLETED` reconciliando un Booking pendiente, idempotencia (espejo de `CulqiWebhookTest`).
5. **Hold/cupo:** creación fija `expires_at`; job de expiración marca `Abandonado` y libera cupo; sobreventa bloqueada cuando `max_capacity` se agota.
6. **Reconciliación cruzada:** webhook llega antes que el AJAX de captura → no duplica Bookings.
7. **Email de confirmación** enviado en pago exitoso (ajustar `assertSent`/`assertQueued` según decisión ShouldQueue).

---

## 11. Inventario de copy/CTA "WhatsApp como pago/reserva" a reescribir

> Solo inventario. WhatsApp permitido **únicamente** como contacto/soporte (FAB + Contacto).

> **⚠️ DECISIÓN DEL JEFE (2026-07-25) — CUÁNDO se toca este copy.**
> Nada de esta tabla se reescribe todavía. Motivo: **hoy WhatsApp es el ÚNICO camino de
> conversión del sitio** (no hay pasarela en vivo), no un placeholder por falta de credenciales.
> Retirar los CTA de "Reservar por WhatsApp" antes de tener Culqi/PayPal cobrando dejaría al
> sitio **sin ninguna vía de cierre**.
>
> Regla de disparo: **el copy de las filas 1-8, 10, 13, 15, 16 cambia el MISMO DÍA en que la
> pasarela entra en producción y queda verificada cobrando — ni un día antes.** Es el último
> paso del despliegue de pagos (§12), no una tarea previa ni independiente.
>
> Filas 9, 12 y 14 (WhatsApp como **contacto/soporte**) se **conservan siempre**, con o sin pasarela.

| # | Ubicación | Elemento | Naturaleza actual |
|---|---|---|---|
| 1 | `resources/views/checkout.blade.php:1345-1348` | Botón **`btn-whatsapp-confirm`** "Confirmar reserva por WhatsApp" | **Cierra reserva** → retirar |
| 2 | `checkout.blade.php:1349-1352` | Botón `btn-email-confirm` "Enviar por correo" | Cierre alternativo sin pago → retirar |
| 3 | `checkout.blade.php:1330-1333` | Aviso "Sin pago en línea por ahora… confirmamos por WhatsApp o correo. Sin comisiones." | Reescribir a copy de pago |
| 4 | `checkout.blade.php:1255` | "Confirmamos por WhatsApp o correo" | Reescribir |
| 5 | `checkout.blade.php:1332` / `:817` | "reservas y te confirmamos por WhatsApp o correo" / paso 2 del instructivo | Reescribir |
| 6 | `checkout.blade.php:~1816-1835` | JS `buildWhatsAppSummary()` + listener que abre `wa.me` al confirmar | Retirar del cierre |
| 7 | `components/header.blade.php:68-70` | CTA nav **`.lat-btn-reservar` "Reservar ahora"** → `wa.me` (`nav.reservar_ahora`) | **Presenta WhatsApp como reservar** → redirigir a carrito/tours |
| 8 | `header.blade.php:124-126` | Drawer móvil **"Reservar por WhatsApp"** (`nav.book_whatsapp`) | Reescribir/retirar como reserva |
| 9 | `header.blade.php:28` | Top-bar link WhatsApp (contacto) | **Conservar** (es contacto) |
| 10 | `lang/{es,en,pt}/nav.php:15` | `book_whatsapp` = "Reservar por WhatsApp / Book via WhatsApp / Reservar pelo WhatsApp" | Reescribir/retirar |
| 11 | `lang/*/nav.php` | `reservar_ahora` (usado por CTA #7) | Revisar destino (no WhatsApp) |
| 12 | `layouts/app.blade.php:266-273` | **FAB flotante WhatsApp** | **Conservar** (soporte) |
| 13 | `resources/views/tours/show.blade.php:308-334` | Caja de reserva de la ficha: `form` → `cart.store` "Reservar ahora / Book now" | **Ya va al carrito** (no WhatsApp) — OK, revisar copy "Reservar ahora" para consistencia con el nuevo flujo |
| 14 | `contact.blade.php`, `about.blade.php` | Menciones WhatsApp | Revisar que sean **contacto**, no reserva/pago |
| 15 | `emails/bookings/*.blade.php` (`confirmed`, `admin-notification`, `payment-reminder`) | Menciones WhatsApp/"pagar después" | Revisar copy para el nuevo flujo de pago |
| 16 | `checkout/payment.blade.php:239-256` | Chips de método con `paypal`/`yape`/`plin` "coming soon" y `card` (Culqi) | Reescribir métodos reales (Culqi + PayPal activos) |

*(Home: no se detectó CTA de "reservar por WhatsApp" directo en el listado revisado; revisar `home.blade.php` durante la reescritura por si hay banners.)*

---

## 12. Riesgos y plan de implementación por fases

### 12.1 Preparado para la PREGUNTA ABIERTA — pago total vs. anticipo/seña
Diseñar sin decidir:
- **Escenario A — Pago total por adelantado:** el cobro cubre el total; Booking → `paid`/`confirmed`. Es el camino más simple y el que ya soporta PayPal.
- **Escenario B — Anticipo/seña:** añadir `bookings.deposit_amount` y `bookings.balance_due` (migración aditiva); nuevo `payment_status = partially_paid`; el cobro inicial es un % (config `DEPOSIT_PERCENT`/`Setting`); el saldo se cobra después (link de pago — ya existe `payment_link_url`, o segundo charge). Textos legales y correos deben explicitar saldo y plazo. La máquina de estados (§4) ya contempla `partially_paid`.
- **Ambos** comparten: hold/cupo, webhooks, reconciliación y máquina de estados; solo cambia el **monto cobrado** y la existencia de un **saldo**. Mantener el importe a cobrar **calculado server-side** en un único punto para conmutar A/B por configuración.

### 12.2 Riesgos principales
- **Sobreventa:** hoy sin hold ni validación de aforo. Mitiga §4.3.
- **Carrera navegador↔webhook (PayPal):** duplicar/omitir Bookings. Mitiga idempotencia + webhook PayPal (§5.2).
- **Culqi pay-now inexistente:** funcionalidad a construir desde cero en el controlador (el servicio ya está).
- **Moneda PEN/USD** (§3.4): descuadres si se cobra en moneda distinta a la mostrada.
- **Cola de correos:** `QUEUE_CONNECTION=sync` → si se hace `BookingConfirmed` `ShouldQueue`, requiere worker en prod; si no, el checkout puede bloquearse enviando correo.
- **Credenciales de producción** bloquean pruebas reales end-to-end.
- **Reembolsos:** la API de refund no está implementada en los servicios (solo cobro/captura) — trabajo adicional.
- **Vista wizard de 113 KB:** alto acoplamiento; reescribir el paso de pago con cuidado de no romper la navegación por pasos ni la sincronización de campos.

### 12.3 Fases

**FASE 0 — Desbloqueos sin credenciales (se puede empezar YA)**
- Fix `jsonld.blade.php` (`?:`→`?? null`) para desbloquear #1/#2/#6/#8/#9.
- Migraciones aditivas: `expires_at`, `refunded_at`/`refund_amount`, (seña) `deposit_amount`/`balance_due`; enum de estados.
- Reescribir `ProcessPaymentRequest` y separar Requests Culqi/PayPal.
- Reescribir el paso de pago del wizard (UI Culqi + PayPal) usando `Http::fake` en tests; retirar CTAs WhatsApp de cierre (§11) y revertir copy anti-Culqi (§9).
- Construir el **endpoint de cobro Culqi** y el **webhook PayPal** con tests mockeados (no requieren credenciales reales).
- Job de expiración de holds + validación de aforo.

**FASE 1 — Integración en SANDBOX (requiere credenciales sandbox del jefe)**
- Cablear llaves sandbox Culqi + PayPal; registrar webhooks sandbox.
- Pruebas end-to-end con tarjetas/cuentas de prueba; validar reconciliación por webhook y liberación de cupo.
- Ajustar copy legal (borrador) y correos.

**FASE 2 — Producción (BLOQUEADA hasta credenciales live + textos legales aprobados)**
- Llaves live, webhooks de producción, verificación de moneda.
- Textos legales definitivos (§8) redactados/aprobados (fuera de alcance de este documento).
- Monitoreo de logs de cobro/webhook; plan de reembolsos.

**Qué queda BLOQUEADO sin credenciales:** pruebas reales de cobro/captura, registro de webhooks en los paneles, verificación de firma PayPal en vivo, y decisión de moneda. **Todo lo demás (Fase 0)** avanza con mocks.

### 12.4 Manejo de secretos — REGLA (jefe, 2026-07-25)

**Las llaves _live_ de Culqi y PayPal van SIEMPRE en variables de entorno del hosting, NUNCA al repositorio.**

- Ningún valor real (`pk_live_*`, `sk_live_*`, `PAYPAL_CLIENT_ID`/`PAYPAL_SECRET` de producción, `PAYPAL_MODE=live`, webhook IDs/secrets) se escribe en archivos versionados. Se cargan en el `.env` del servidor de producción (o en el panel de variables de entorno del hosting), fuera de git.
- El repo solo contiene **placeholders** en `.env.example` (`pk_test_REPLACE_ME`, `PAYPAL_SECRET=`, etc.), como confirmó la auditoría `docs/qa/seguridad-secretos.md`.
- `config/services.php` lee todo por `env()`; nunca hardcodear una llave, ni en config, ni en Settings del CMS con valor por defecto commiteado.
- `.env`, `.env.qa`, `.env.prod` permanecen en `.gitignore` (ya verificado: no trackeados).
- Al pasar a Fase 2, las llaves live se cargan directo en el entorno del hosting; si en algún momento una llave real toca un commit, se **rota** en el panel del proveedor y se purga del historial antes de exponer el repo.

---

### 13. 🚩 BLOQUEO DE MONEDA — PayPal NO admite PEN (2026-07-26)

Al traer la data real, se confirmó que **los precios de los tours están en soles (PEN)**. Verificado contra la **documentación oficial de PayPal** (`developer.paypal.com/api/rest/reference/currency-codes/`): la lista de monedas soportadas es AUD, BRL, CAD, CNY, CZK, DKK, EUR, HKD, HUF, ILS, JPY, MYR, MXN, TWD, NZD, NOK, PHP, PLN, GBP, RUB, SGD, SEK, CHF, THB, USD. **PEN no aparece.** PayPal **no puede cobrar en soles.**

- **Culqi en PEN: SÍ funciona** (procesa soles nativamente).
- **PayPal: NO puede** cobrar en PEN. Solo cobraría en una moneda soportada (p.ej. USD).

**Impacto en el checkout completo** (por eso es un bloqueo, no un detalle):
1. **Conversión:** si se quiere PayPal, hay que fijar precio en USD o convertir PEN→USD a una tasa (y decidir quién asume la diferencia de tipo de cambio).
2. **Monto mostrado:** hoy el front **hardcodea el símbolo `$`** (`resources/views/tours/show.blade.php` líneas ~356/399: `${{ number_format($tour->price) }}` y `{{ $tour->currency ?: 'USD' }} $...`). Con precios en PEN, muestra "**$360**" (parece dólares) y "**PEN $720**" (incoherente). **Bug de presentación a corregir sí o sí.**
3. **Conciliación:** si Culqi cobra en PEN y PayPal en USD, los montos y reportes quedan en dos monedas → conciliación doble.

**Opciones (decide Anyerson — hay conflicto de costo):**
- **A) Culqi-only en soles.** El sitio cobra en PEN por Culqi; se retira PayPal (o se deja solo para extranjeros, en USD aparte). Más simple, coherente con el mercado local. *Costo: se pierde PayPal como opción general.*
- **B) Doble moneda.** Culqi cobra en PEN; PayPal cobra el equivalente en USD con tasa configurable. *Costo: complejidad de conversión, conciliación en dos monedas, riesgo de tipo de cambio.*
- **C) Todo en USD.** Se re-tarifan los 26 tours en dólares; ambas pasarelas cobran USD. *Costo: cambia el precio percibido por el cliente peruano.*

**DECISIÓN DE ANYERSON (2026-07-26):** el **cobro inicial es Culqi en soles (PEN)**. **PayPal NO está descartado: queda EN PAUSA pendiente de definición de moneda.** La meta final sigue siendo **ambas pasarelas (Culqi + PayPal)**. La data se importó con `currency = 'PEN'`.

#### 13.1 🔴 Hallazgo de la auditoría de moneda (2026-07-26)

La ficha ya muestra `S/` (helper `Money`), pero **el cobro seguía en USD**. Puntos donde el número viajaba sin su moneda correcta:

| Punto | Mostraba | Cobraba/guardaba | ¿Coincide? |
|---|---|---|---|
| Payload Culqi (`checkout.blade.php` L344) | — | `currency: 'USD'`, `amount = total*100` | 🔴 **cobra USD sobre un total en PEN → ~3.7× de sobrecobro** |
| Booking guardado (`CheckoutController.php` L298) | — | `'currency' => 'USD'` hardcode | 🔴 booking en PEN se guarda como USD |
| `checkout.blade.php` (subtotal, total, "pagar ahora") | `$720 USD` | — | 🔴 símbolo y código equivocados |
| `checkout/payment.blade.php` (L10) | `$totalPen = total*3.70` | — | 🔴 doble conversión: el total YA es PEN |
| `checkout/thanks.blade.php` (L79-80) | `$720` + `USD` | — | 🔴 |
| JSON-LD ficha (`show.blade.php` L119) | `priceCurrency = tour->currency` | — | 🟡 ya da PEN, pero con fallback `'USD'` frágil |
| JSON-LD blog (`blog/show.blade.php` L34) | logo `logo.png` (equivocado) | — | 🟡 logo, no moneda |

**Corrección (Opción A):** todo el checkout pasa a PEN, símbolo `S/` vía `Money`, se elimina el hack `*3.70`. Con test que falla antes / pasa después.

#### 13.2 ⏸️ PayPal — EN PAUSA (no descartado). Pendiente de definición de moneda

PayPal es parte del objetivo final (Culqi + PayPal). Está **en pausa**, no cancelado. Por instrucción de Anyerson, **no se decide ni implementa** hasta responder por escrito (Anyerson decide, no el equipo):

1. **¿En qué moneda se crea la orden PayPal?** (PayPal no admite PEN → tendría que ser USD u otra soportada).
2. **¿De dónde sale el tipo de cambio PEN→USD?** (tasa fija manual, API de un banco/SUNAT, markup propio). ¿Se refresca cada cuánto?
3. **¿Quién absorbe la diferencia de tipo de cambio** entre el momento de mostrar el precio y el de la captura? (el cliente, la agencia, o se fija un colchón).
4. **¿Qué monto EXACTO ve el cliente antes de confirmar** en PayPal? ¿"S/ 720 (≈ USD 195)"? ¿solo USD? Debe quedar sin ambigüedad para no generar disputas.
5. **¿Cómo se concilia** un booking registrado en PEN pero cobrado en USD? (qué moneda manda en el reporte, qué se guarda en `bookings.currency`, cómo cuadra con Culqi que sí cobra PEN).

Hasta tener esas 5 respuestas, PayPal se deja **en pausa del lado del servidor**: la ruta `paypalCreateOrder` queda desactivada (un endpoint vivo que crea órdenes en USD hardcodeado es un 🔴 aunque no haya botón). El código se conserva para retomarlo; se reactiva la ruta cuando se resuelva la moneda.

**Estado:** moneda del checkout = **PEN (Culqi)**, CERRADA por decisión. PayPal = **EN PAUSA por moneda (no descartado)**; ruta de creación de orden desactivada con test.

---

### Anexo — Archivos críticos para la implementación
- `app/Http/Controllers/CheckoutController.php`
- `app/Http/Controllers/WebhookController.php`
- `app/Services/PaymentService.php` y `app/Services/PayPalService.php`
- `resources/views/checkout.blade.php` (wizard vivo) y `resources/views/checkout/payment.blade.php` (referencia Culqi huérfana)
- `routes/web.php`, `app/Http/Requests/ProcessPaymentRequest.php`, `app/Models/Booking.php` y las migraciones de `bookings`

---

### 14. ✅ MONEDA DEFINIDA: USD — PayPal reactivado (2026-07-29)

**Decisión del cliente, confirmada por Anyerson:** el cobro es en **dólares (USD)**. Sustituye a la decisión provisional de §13 (Culqi en PEN, PayPal en pausa).

**Los números NO se convierten:** el cliente confirmó que el precio guardado de cada tour ya está en dólares. El `720` de un tour pasa de "S/ 720" a "$720" — la migración `2026_07_29_190000_set_site_currency_to_usd` cambia **solo la etiqueta** `tours.currency` de PEN a USD y **no toca `price`**. Queda dicho por escrito, porque el efecto práctico es que el sitio cobra ~3.7× lo que cobraba el día anterior: es intencional, no un bug de conversión.

`bookings` **no se reescribe**: cada reserva conserva la moneda con la que se cobró. Reescribir el histórico haría que una reserva mienta sobre lo que pagó el cliente.

#### 14.1 Una sola fuente de verdad para la moneda

El problema de fondo de §13 no era la moneda elegida, sino que la respuesta estaba **copiada como literal `'PEN'` en ~30 sitios** (vistas del carrito y checkout, controller, recursos de Filament, correos, factory). Cambiarla obligaba a cazarlos uno a uno, y dejar la mitad en una moneda y la mitad en otra es exactamente el bug del sobrecobro.

Ahora hay una sola función: **`App\Support\Money::site()`**, que resuelve
`Setting('site_currency')` → `config('services.site_currency')` → `'USD'`, validando contra `Money::SUPPORTED`. Todo lo demás la consulta:

| Punto | Antes | Ahora |
|---|---|---|
| Precios del carrito / checkout / correos / home / ficha | `'PEN'` literal ×~25 | `Money::site()` |
| Payload de cobro a Culqi | `'PEN'` | `Money::site()` |
| Orden de PayPal | `'USD'` hardcodeado | `Money::site()` + validación |
| `bookings.currency` | `'PEN'` | `Money::site()` |
| Guarda de carrito | `CartService::isPenOnly()` | `CartService::isSiteCurrencyOnly()` |
| Prefijos del panel (`S/`) | literal | `Money::prefix(Money::site())` |
| Default de `TourFactory` | `'PEN'` | `Money::site()` |

**Editable por el cliente:** Configuración → Pagos → "Moneda del sitio". Cambiarla NO re-tarifa: sigue siendo la misma cifra con otro símbolo, y el helper del campo lo advierte.

#### 14.2 PayPal reactivado, con la regla de §13 convertida en código

Las rutas `checkout.paypal.create` / `.capture` vuelven a apuntar al controller (eran closures `abort(404)`), ahora **solo POST** y con `throttle:checkout`. Lo que antes garantizaba la ruta muerta lo garantiza ahora el código:

- La orden se crea con `Money::site()`, no con `'USD'` escrito a mano.
- Si la moneda del sitio no está en `PayPalService::SUPPORTED_CURRENCIES` (PEN no está: PayPal no cobra soles), el endpoint responde 422 y **no manda nada** a PayPal.
- Si el carrito trae monedas mezcladas, 422 antes de calcular el total.
- **CSRF vuelve a exigirse** en ambas rutas: estaban en `$except` solo para que un POST diera 404 en vez de 419 mientras eran closures. Exentas y vivas, se podrían disparar desde otro sitio.

`PaymentGuard` sigue siendo el último cerrojo: credenciales LIVE fuera de producción abortan la captura antes de tocar la red (`PaymentGuardCheckoutTest`).

#### 14.3 Credenciales de prueba

Culqi ahora se lee **igual que PayPal**: primero el Setting del panel, luego el `.env`. Antes solo del `.env`, lo que obligaba a un deploy para cargar unas claves de prueba. Campos nuevos en Configuración → Pagos: `culqi_env`, `culqi_public_key`, `culqi_secret_key`.

**Pendiente del cliente (bloquea la prueba de cobro real):** claves `pk_test_` / `sk_test_` de la cuenta Culqi y el Client ID + Secret de la app **sandbox** de PayPal. Sin ellas el cableado está completo pero **no hay cobro de prueba verificado**.

#### 14.4 UI de pago en línea (lote del 2026-07-29, tarde)

El checkout público estaba en "sin pago en línea": `payment_timing` fijo en `later`, sin formulario de tarjeta ni botón de PayPal. El servidor sí sabía cobrar. Lo que faltaba era la vista — y resultó que **ya existía**: la ruta `/checkout/pago` (`checkout.pay` → `checkout/payment.blade.php`) montaba el formulario Culqi v4 completo con datos del viajero, recogida, timing y términos. Estaba viva y **nada la enlazaba**.

Lo que se hizo:

- **El carrito la enlaza**: botón rojo "Pagar ahora — $X" (`#btn-pay-online`) sobre el de WhatsApp, y el aviso de al lado cambia con él (prometer "sin pago en línea" debajo de un botón de pago es contradecirse). Rojo y no verde a propósito: el de WhatsApp reserva SIN pagar y con los dos del mismo color nadie distingue cuál cobra.
- **Botón de PayPal real** con su SDK, en `#paypal-buttons`, cargado con el `client-id` del panel y `currency=Money::site()`. El importe **no viaja desde el navegador**: `createOrder` no manda monto, el servidor lo calcula del carrito. Mandarlo desde el cliente sería dejar que cualquiera pague 1 dólar por un tour de 300.
- **Nada de "próximamente" escrito a mano**: cada método se ofrece si tiene credenciales cargadas (`PaymentService::isConfigured()` / `PayPalService::isConfigured()`, que exige Client ID **y** Secret). Sin ninguna pasarela, "pagar ahora" no se ofrece y el checkout queda en el flujo que funciona. Un método pintado sin claves detrás lleva al cliente a llenar todo para fallar en el último clic.
- **PayPal no se ofrece si la moneda del sitio no es una que PayPal admita**, aunque las claves estén cargadas.
- **Validación antes de abrir la pasarela** (datos del viajero, fecha y términos): si el cliente aprueba en PayPal y recién ahí el servidor rechaza la reserva por un campo vacío, el dinero queda autorizado sin reserva — el peor estado posible. Y si la captura falla después de aprobar, el error se muestra en pantalla pidiéndole que escriba antes de reintentar; nunca se traga en silencio.
- **La llave pública de Culqi sale del panel**, no del `.env`: la vista la pedía con `config()` y con las claves cargadas en Configuración → Pagos se quedaba con la del `.env` (o vacía), fallando la tokenización sin explicación.
- El CSP ya permitía Culqi y PayPal en `script-src`/`frame-src`/`connect-src`/`form-action`: no hizo falta tocarlo.

**Trampa de tests anotada** (`OnlinePaymentUiTest`): no sirve pedir la página, cambiar Settings y volver a pedirla en el MISMO test. Laravel cachea la instancia del controller dentro del objeto `Route`, que vive todo el proceso de pruebas, así que el segundo request reusa los servicios con las credenciales viejas. Da un rojo que parece un bug de caché de la app y no lo es. Un escenario por test.

#### 14.5 Lo que sigue pendiente

**Cobro de prueba real, sin verificar.** Faltan las credenciales sandbox: `pk_test_`/`sk_test_` de Culqi (salen de CulqiPanel → Desarrollo → API Keys; **puede que Lima América no tenga cuenta Culqi todavía** — Lima View la tenía retirada, hay que confirmarlo con el cliente porque sin ella no hay tarjeta al lanzar) y Client ID + Secret de una app **sandbox** de PayPal (developer.paypal.com → Apps & Credentials → Sandbox). No existen claves sandbox públicas: lo único público de PayPal es `client-id=test`, que solo sirve para que el botón se pinte en desarrollo (sin secret no hay `create order` ni captura). Las tarjetas de prueba de Culqi sí son públicas y están en su doc.

Yape y Plin siguen sin integrar (no es cuestión de claves).
