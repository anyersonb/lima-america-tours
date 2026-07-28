# QA FINAL — Modulo Reserva (Culqi PEN + PayPal en pausa)

**Fecha:** 2026-07-27 · **Rama:** `data/tours-reales` (HEAD `9d6ac31`) · **Entorno probado:** `https://limaamericatours.com/staging` (`/es`,`/en`,`/pt`)
**Metodo:** curl contra staging + lectura de codigo + `php artisan test`. **Chrome MCP caido: sin navegador.** Ver seccion "No cubierto sin navegador".

## Estado del gate de regresion SEO
**FAIL** (1 de 7 checks en FAIL — ver detalle). El resto del modulo funcional pasa.

## Fix verificado
Commits en alcance: `0757cb9`, `dddae49`, `583c7eb`, `c03e3f4` (moneda PEN end-to-end, blindaje isPenOnly, PayPal a 404, PaymentGuard). Alcance: CheckoutController, ProcessPaymentRequest, PaymentGuard, PaymentService/PayPalService, routes/web.php, VerifyCsrfToken, resources/views/checkout.blade.php y checkout/payment.blade.php, checkout/thanks.blade.php, TourResource (moneda), correos transaccionales.

---

## 1. /es/carrito — precios y cupon
- **PASS.** HTTP 200. Con carrito vacio: S/ 0. Con 1 tour real (city-tour-centro-historico-de-lima, id 16, 2 adultos) anadido via POST /es/carrito/agregar: subtotal S/ 138, total S/ 138, sin residuo USD/$ en es, en ni pt.
- Formulario de cupon presente (name="code", POST a cart.coupon) — solo verificado que existe y postea; no se probo un codigo real valido/invalido end-to-end (ver "no cubierto").

## 2. /es/checkout/pago + vista de pago
- **PASS.** Carrito vacio -> 302 a /es/carrito (confirmado por curl). Carrito con items -> 200, renderiza resources/views/checkout/payment.blade.php.
- Precios en S/ (S/ 138.00 en "Pagar ahora", resumen y total). Boton "Pagar ahora" dispara Culqi.open(); chip PayPal presente pero **deshabilitado** (disabled, badge "Coming soon", opacity-50 cursor-not-allowed), igual que Yape/Plin.
- Culqi.publicKey = ''; confirmado vacio en el HTML servido — coincide con lo documentado (llaves sandbox en blanco en .env de staging). **No es un bug.**

## 3. Rutas PayPal a 404
- **PASS.** Confirmado por curl, GET y POST, en los 3 locales:
  - /es/checkout/paypal/create -> 404 (GET y POST)
  - /es/checkout/paypal/capture -> 404 (GET y POST)
  - /en/checkout/paypal/create -> 404, /pt/checkout/paypal/create -> 404
  - Body del 404 es la pagina propia del sitio (no un error generico del servidor).

## 4. Flujo "pagar despues" (payment_timing=later)
- **PASS — verificado end-to-end en vivo (no solo codigo).** POST /es/checkout/procesar con payment_timing=later y datos de prueba (QA_RESERVA_TEST / qa.reserva.test@example.com) -> 302 a /es/checkout/gracias -> 200, booking creado **sin cobrar**, referencia LVT-LBFEG8TO, estado "Pendiente de pago", monto S/ 138.00 correcto.
- Dato de prueba queda en la BD de staging (qa.reserva.test@example.com, ref. LVT-LBFEG8TO) — pendiente de purga, mismo criterio que otras sesiones QA de este proyecto.
- Codigo: CheckoutController::finalizeBookings() con paid=false crea el Booking en pending/pending y NO invoca ninguna pasarela — correcto.

## 5. Guarda de moneda (no-PEN se rechaza sin cobrar)
- **PASS (por test, no repetido en vivo).** CheckoutCurrencyGuardTest (3 casos) verde: pay_later y pay_now se rechazan sin llamar a Culqi si el carrito trae un tour no-PEN; pay_later sigue funcionando si todo el carrito es PEN. Codigo: CartService::isPenOnly() + abort explicito en processPayment() antes de calcular/cobrar.

## 6. Guarda anti-cobro-real (PaymentGuard)
- **PASS.** PaymentGuard::assertChargeAllowed() esta efectivamente cableado en PaymentService::createCharge() (linea 46) y PayPalService::captureOrder() (linea 172) — no es solo un test aislado, se invoca en el punto real de cobro. config('payments.live') default false (.env.example: PAYMENTS_LIVE=false). 7 tests unitarios + 2 de integracion (PaymentGuardCheckoutTest) verdes.

## 7. ProcessPaymentRequest
- **PASS.** Mensajes en espanol (attributes() mapea todos los campos tecnicos a espanol: "nombre", "correo electronico", "telefono", etc. — cubre el hallazgo previo de mensajes en ingles). culqi_token required_if:payment_timing,now. payment_timing se infiere a now/later si no se envia explicito. Confirmado con test dedicado.

## 8. Pagina de gracias / correos
- **PASS (parcial).** Pagina de gracias verificada en vivo (punto 4): referencia, estado, monto, item, todo correcto y en espanol.
- BookingConfirmed implements ShouldQueue confirmado en codigo. MAIL_MAILER=log es lo esperado en .env.example; **no pude confirmar el valor real en el .env del servidor de staging ni leer el log generado** (sin acceso SSH/FTP en este QA) — ver "no cubierto".

## 9. Suite de tests
```
php artisan test --filter="Checkout|PaymentGuard|PayPalPaused|CurrencyGuard"
Tests: 32 passed (99 assertions)
```
Incluye: PaymentGuardTest (7), VerifyCsrfTokenPaypalExceptTest (1), CartTest (1), CheckoutCurrencyGuardTest (3), CheckoutRobotsMetaTest (3), CheckoutTest (11), PayPalPausedTest (4), PaymentGuardCheckoutTest (2).

**Suite completa** (regresion sobre todo el proyecto, no solo el modulo):
```
php artisan test
Tests: 223 passed (698 assertions)
```
**0 failed.** La baseline previa de "4 failed" (los tests de Culqi documentados en ESTADO.md) quedo resuelta.

---

## Gate de regresion SEO (bloqueante)

| # | Check | Resultado | Evidencia |
|---|---|---|---|
| 1 | Ninguna URL del inventario con noindex/X-Robots-Tag indebido | PASS (con nota) | Todo el sitio en /staging sirve x-robots-tag: noindex, nofollow, comportamiento esperado de staging. Ademas carrito/pago/gracias traen su propio meta robots noindex,nofollow explicito, correcto incluso en produccion, confirmado tambien por CheckoutRobotsMetaTest (3/3 verde). |
| 2 | robots.txt de produccion, no bloquea CSS/JS | NO VERIFICABLE / N/A | robots.txt de staging bloquea todo (Disallow: /), correcto para staging, pero no hay URL de produccion separada disponible para confirmar el robots.txt real de prod. Pendiente antes de cutover. |
| 3 | Canonicals intactos, no apuntan a staging desde prod | PASS (dentro de este entorno) | Canonicals de carrito, pago, gracias, ficha de tour y blog apuntan consistentemente al propio dominio+path staging donde corren. |
| 4 | Slugs cambiados con 301, sin 404 | PASS / N/A | Este fix no cambio slugs. Las rutas paypal create/capture responden 404 intencional y documentado. |
| 5 | Un solo H1 por pagina | FAIL | carrito renderiza 2 elementos h1 en el mismo DOM (linea 701 Carrito de compra, linea 716 Detalles de la Reserva). Resto de paginas tocadas: 1 solo h1. |
| 6 | Datos estructurados siguen parseando | PASS | 3 bloques JSON-LD validos en ficha de tour y blog, priceCurrency PEN, logo correcto. |
| 7 | Fix global, gate en inventario completo | N/A | Este fix no es global. |

Un solo FAIL en el punto 5 hace que el gate de regresion SEO cierre en FAIL. Severidad Alta como minimo.

## Regresiones encontradas

| Severidad | URL / archivo | Que se rompio | A quien devolver |
|---|---|---|---|
| Alto | carrito (checkout.blade.php lineas 701 y 716) | Dos h1 en el mismo DOM. No es regresion nueva de este lote de pagos, pero falla el gate SEO sobre una URL tocada. Fix: el segundo h1 deberia ser h2. | maquetador-frontend |
| Menor | app/Models/Booking.php linea 34 | Referencia sigue con prefijo LVT- en vez de algo propio de Lima America Tours. Leftover del fork. | backend-laravel |
| Informativo | BD de staging | Booking de prueba LVT-LBFEG8TO creado durante esta verificacion, no cobra nada pero queda como dato real. | Purgar antes de cerrar el ciclo. |

### Nota importante: no es un defecto, pero condiciona el veredicto
El flujo Pagar ahora con Culqi (checkout/payment.blade.php, ruta /es/checkout/pago) funciona correctamente de forma aislada (precios en S/, PayPal deshabilitado, JS de Culqi bien cableado, backend cobra en PEN con guarda anti-cobro-real) pero no esta enlazado desde la navegacion real del sitio. El unico camino de un visitante real (ficha de tour, boton Reservar ahora, cart.store, cart.index) desemboca siempre en el wizard checkout.blade.php, que sigue siendo v1 sin pago en linea: ambos botones de cierre (WhatsApp / correo) mandan payment_timing=later fijo (linea 1124), tal como el propio comentario del codigo lo documenta explicitamente (v1 SIN pago en linea). Confirme por busqueda en todo el repo que ningun blade/controlador enlaza la ruta checkout.pay: la unica forma de llegar a la pagina de Culqi es escribiendo /checkout/pago a mano con el carrito no vacio.

Esto coincide con la regla de disparo documentada en docs/pagos/PLAN-PASARELAS.md parrafo 11 (el copy de WhatsApp como cierre cambia el mismo dia en que la pasarela entra en produccion y queda verificada cobrando, ni un dia antes), asi que parece intencional, no un olvido. Lo senalo igual porque cambia el alcance real de que tan listo esta el modulo: hoy, un cliente real no puede pagar con tarjeta a traves del sitio aunque el codigo para hacerlo ya este construido y probado. Decision de negocio pendiente de Anyerson, no defecto de codigo.

## No cubierto sin navegador
- Cargo real Culqi sandbox end-to-end (tokenizacion real de tarjeta, respuesta real de la API sandbox): imposible sin llaves (estan en blanco) y sin navegador para ejecutar el JS de Culqi.
- Consola/red del navegador en carrito, pago y gracias: no se verificaron errores JS ni peticiones de red fallidas en tiempo real (solo se confirmo por curl que los assets HTML referenciados responden 200).
- Viewports responsivos (menor a 640, 768, 1024, 1440 o mas): no verificados visualmente; solo se leyo el CSS/HTML servido, sin renderizado real.
- Cupon: solo confirmado que el formulario existe y postea a cart.coupon; no se probo un codigo valido ni uno invalido en vivo.
- Correos en MAIL_MAILER=log: no se pudo leer el archivo de log del servidor de staging (sin acceso SSH/FTP en esta sesion) para confirmar que el correo de pagar despues efectivamente se escribio ahi.
- .env real del servidor de staging: inferido indirectamente que las llaves Culqi estan en blanco (via el HTML servido), pero no se leyo el archivo directamente.
- robots.txt de produccion: solo se verifico el de staging (bloquea todo, esperado). No hay una URL de produccion separada disponible en este QA para comparar.

## Veredicto final F-phase

APTO CON OBSERVACIONES para lo especificamente pedido en este QA (Culqi en PEN, PayPal en pausa con 404, guarda anti-cobro-real, validaciones, flujo pagar despues, suite de tests): todo eso pasa limpio, con evidencia en vivo y 223 de 223 tests verdes.

Con dos observaciones que bloquean un cierre sin reservas:
1. El gate de regresion SEO tiene 1 FAIL bloqueante (H1 duplicado en carrito). Debe corregirse antes de dar el ciclo por cerrado formalmente, aunque no es una regresion nueva de este lote.
2. El flujo Pagar ahora con Culqi, aunque construido y probado correctamente, no es alcanzable hoy desde la navegacion real del sitio. Es una decision de negocio pendiente (cuando cortar el wizard hacia Culqi), no un defecto de codigo, pero condiciona que tan en produccion esta realmente el cobro en linea.

No se encontro nada que amerite NO APTO: no hay riesgo de cobro real accidental (guarda mas llaves en blanco mas tests), no hay error 500, no hay noindex accidental fuera de lo esperado en staging, y la ruta PayPal esta correctamente desactivada en los 3 locales.
