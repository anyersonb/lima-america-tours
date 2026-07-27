# Validación CRO — Lote moneda + pasarela + blog

**Proyecto:** Lima América Tours — `G:\laragon\www\lima-america`
**Rama evaluada:** `data/tours-reales` (HEAD `f16897a`)
**Fecha:** 2026-07-26
**Servidor:** http://127.0.0.1:8001 (local)
**Método:** curl + lectura de código + `php artisan tinker` contra la DB real. **Sin MCP Chrome/Playwright** — todo lo visual/interactivo en navegador queda BLOQUEADO (ver sección final).

---

## Veredicto: **APTO CON OBSERVACIONES**

No hay nada que rompa el core (PayPal correctamente desactivado, blog con 10 posts reales, moneda visible correcta en home/listado/ficha/checkout). Pero encontré **una grieta de fondo en el manejo de moneda** que hoy no se ve porque los 26 tours públicos son todos PEN, y **7 tours en USD ya viven en la base de datos** (borradores) sin ninguna barrera que impida que, el día que se publiquen o se agregue un ítem con otra moneda al carrito, el total se calcule y se cobre mal. Recomiendo resolver el hallazgo #1 antes de mergear (o como mínimo antes de publicar cualquier tour no-PEN), y el resto puede ir en seguimiento.

---

## Tabla de hallazgos

| # | Severidad | Dónde | Qué | Evidencia | A quién devolver |
|---|---|---|---|---|---|
| 1 | 🟠 **Alto** | Backend — `CartService::subtotal()` / `CheckoutController::chargeWithCulqi()` | El carrito suma `subtotal` de los ítems **sin mirar la moneda de cada tour** (`collect($this->rawItems())->sum('subtotal')`), y el cargo real a Culqi manda `'currency' => 'PEN'` **hardcodeado** (línea 195), sin validar que el total realmente sea en soles. Hoy no se manifiesta porque los 26 tours publicados son PEN, pero ya existen 7 tours con `currency=USD` en la tabla `tours` (ver #2). El día que uno se publique, o que un tour cambie de moneda por error de captura, el carrito sumará números de distinta moneda como si fueran la misma y Culqi cobrará ese total etiquetado como PEN. | `app/Services/CartService.php:173-210` (sin filtro de moneda) · `app/Http/Controllers/CheckoutController.php:195` (`'currency' => 'PEN'` hardcoded) · confirmado con `php artisan tinker`: `Tour::query()->groupBy('currency')` → `USD: 7, PEN: 26` | backend-laravel |
| 2 | 🟡 Medio | CMS — `TourResource.php` (Filament) | El campo **Moneda** es un `TextInput` libre (`maxLength(3)`, sin `Select`/opciones fijas), y el prefijo `S/` del campo Precio está hardcodeado (línea 345) igual que la columna Precio del listado admin (línea 442: `Money::format((float) $state, 'PEN', 2)` — **ignora el `currency` real del registro**). Un editor puede cargar un tour en USD y el panel se lo va a mostrar con prefijo/columna "S/" igual, lo que oculta el error en el momento en que más se necesita detectarlo (al cargar el dato). | `app/Filament/Resources/TourResource.php:90-94` (TextInput libre) · líneas 340-345 (`->prefix('S/')` fijo) · línea 441-443 (columna fuerza `'PEN'`) | backend-laravel |
| 3 | 🟠 Alto | Front — ficha de tour, campo "No incluye" (7 tours publicados en vivo) | Aparecen montos sueltos en dólares dentro del copy de "No incluye" / costos adicionales, importados tal cual del WordPress: `$10.00 USD`, `$15.00 USD`, `$ 35 dólares`, `$50 dólares`, `$60 dólares`. Contradice el criterio de aceptación "ningún $ suelto ni USD sobre un precio". | Confirmado por DB (`excludes_es`/`includes_es`) en: `full-day-nazca-e-islas-ballestas-desde-lima` ("Impuesto Aéreo $10.00 USD…", "Boleto Turístico $15.00 USD…"), `tour-islas-palomino` ("…$4.00 USD por persona"), `machu-picchu` y `conoce-machu-picchu-si-no-tienes-entrada` ("Huayna Picchu… $35 dólares"), `montana-arcoiris-de-7-colores` ("cuatrimotos… $60 dólares"), `city-tour-cusco` y `maras-moray-y-minas-de-sal-cuatrimotos` ("HOTEL EN CUSCO… $50 DOLARES"). Verificado en HTML servido: `curl http://127.0.0.1:8001/es/tours/detalle/full-day-nazca-e-islas-ballestas-desde-lima` línea 534-535. | backend-laravel (limpieza de contenido importado) — decisión de negocio: convertir a soles o dejar aclarado que son cobros directos a terceros en USD |
| 4 | 🟡 Medio | Datos — tabla `tours` | 7 tours **ya cargados en USD** están en estado borrador (`is_published=0`): `huacachina-paracas-full-day`, `lima-ancestral-colonial`, `nazca-huacachina-2-dias`, `nazca-full-day`, `city-tour-lima-catacumbas`, `machu-picchu-full-day`, `in-ea-quasi-fuga-5144` (este último con slug basura, parece residuo de import). Ninguno es visible hoy, pero es el detonante directo del hallazgo #1 si algún día se publican sin normalizar la moneda. | `php artisan tinker`: `Tour::where('currency','USD')->get(['slug','is_published','price'])` | backend-laravel |
| 5 | 🔵 Bajo | Front — `checkout.blade.php` | Atributo `data-total="usd2"` en el nodo del total del carrito (residuo del nombre anterior cuando el sitio cobraba en USD). No se muestra al usuario, es solo un `data-*` interno, pero puede confundir a futuro mantenimiento. | `resources/views/checkout.blade.php:1330` | backend-laravel (cosmético, sin urgencia) |
| 6 | 🔵 Bajo | Backend — rutas PayPal | `GET /es/checkout/paypal/create` y `/capture` responden **404** correctamente (✓ pedido cumplido). Pero **POST** a esas mismas rutas responde **419** (CSRF, porque el middleware corta antes de llegar al closure `abort(404)`), no 404. Funcionalmente sigue bloqueado (no se puede crear/capturar una orden), pero no es exactamente el código de estado pedido en el brief. | `curl -X POST http://127.0.0.1:8001/es/checkout/paypal/create` → `HTTP:419` (GET sí da 404) | backend-laravel (si se quiere 404 exacto en POST también, hay que mover el `abort(404)` fuera del alcance del middleware CSRF o excluir la ruta de VerifyCsrfToken) |

---

## Verificado — Moneda coherente (front público)

- [✓] **Home `/es`** — 7 apariciones de `S/`, cero `$` o `USD` sueltos.
- [✓] **Listado `/es/tours`** — 24 tours publicados, 24 precios en `S/`, cero `$`/`USD`.
- [✓] **Búsqueda `/es/buscar?q=nazca`** — 1 resultado real, precio `S/ 360` correcto (el resto de slugs que aparecían en el grep inicial eran enlaces del footer, no tarjetas de resultado — descartado como falso positivo).
- [✓] **Ficha de tour** (`/es/tours/detalle/full-day-nazca-e-islas-ballestas-desde-lima`) — precio principal `S/ 360`, total `S/ 720.00`, extras `S/ 80 / S/ 55 / S/ 45` todos coherentes. Pero ver hallazgo #3 (montos USD sueltos en el copy de "no incluye").
- [✓] **Carrito** (`/es/carrito`, con ítem agregado por POST simulando el form) — subtotal, total, "ahorro", precios de extras: todos en `S/`.
- [✓] **Checkout / pago** (`/es/checkout/pago`) — todos los montos visibles en `S/`: resumen, "pagar ahora — S/ 720.00", nota de cobro diferido "se te cobrará S/ 720.00 el 14 de ago.".

## Verificado — Pasarela

- [✓] PayPal aparece **deshabilitado** (`disabled` en el radio, `opacity-50 cursor-not-allowed`, badge "Próx.") igual que Yape y Plin — cumple el requisito.
- [✓] El botón "Pagar ahora" solo dispara `Culqi.open()` (JS), no hay ruta viva de PayPal alcanzable desde la UI.
- [✓] `GET /es/checkout/paypal/create` y `/capture` → **404** confirmado por curl.
- [⚠] `POST` a esas mismas rutas → **419**, no 404 (hallazgo #6, severidad baja — funcionalmente sigue bloqueado).
- [✓] `POST /es/checkout/procesar` sin `culqi_token` → **422** con mensajes en español claros (`"No se recibió el token de pago. Intente nuevamente."`), confirma que el flujo exige Culqi sí o sí.
- [⚠] La clave pública de Culqi en `.env` es el placeholder `pk_test_REPLACE_ME` — normal en local/QA (no se commitean credenciales reales), pero **no pude verificar un cobro real de extremo a extremo** porque requiere tokenización JS en navegador real. Ver "No verificado".

## Verificado — Blog

- [✓] `/es/blog` lista **10 posts reales** (slugs con nombres de contenido real: ceviche, restaurantes, Barranco, Huacachina, museo Larco, etc. — nada de "post de ejemplo" ni Lorem ipsum).
- [✓] Abiertas 2 fichas (`como-se-prepara-el-ceviche-peruano`, `huacachina-el-oasis-imperdible-de-ica-aventura-y-encanto-con-lima-america-tours`): título, autor, fecha, cuerpo (`itemprop="articleBody"`) y portada reales.
- [✓] Las 4 imágenes usadas en ambas fichas devuelven **200** (`/storage/blog/...`).

## Verificado — Ficha de tour (contenido)

- [✓] Descripción, itinerario, incluye/no incluye con datos reales (no placeholder).
- [✓] Aviso "Reserva con X horas de anticipación" condicionado a `booking_advance_hours` (si es null, no se muestra) — `resources/views/tours/show.blade.php:401-404`, con i18n ES/EN/PT.
- [✓] Portada + 9 imágenes de galería del tour Nazca devuelven **200**.
- [⚠] Ver hallazgo #3: 7 fichas publicadas mezclan `S/` (precio) con `$...USD` (costos adicionales de terceros) en el mismo bloque de "no incluye".

## Verificado — CMS → front (por código, sin login de navegador)

- [✓] `HomeController` lee `is_featured` / `featured_order` directo del modelo `Tour` (Eloquent), sin capa de caché intermedia visible — un cambio en Filament debería reflejarse sin redeploy. **No pude confirmarlo en vivo** (requiere entrar al panel en navegador).
- [⚠] Hallazgo #2: el campo Moneda en Filament es texto libre y el listado/formulario de precio fuerzan visualmente "S/" sin mirar el `currency` real del registro — esto es un defecto del propio CMS, no solo del front.
- [✓] Conteo real en BD: 24 tours publicados / 33 totales (26 PEN + 7 USD en borrador), 10 posts de blog publicados — coincide con lo esperado en el brief salvo por 2 tours PEN adicionales en borrador que parecen duplicados intencionales de una limpieza previa (`ruta-gastronomica-de-barrio-por-lima...` vs. el publicado `ruta-gastronomica-por-lima`), no defecto.

## No verificado (limitaciones — MCP Chrome caído)

- Pixel-perfect en ningún breakpoint (mobile/sm/md/lg/xl/2xl) — **no evaluado, no aprobar visualmente a ciegas**.
- Interacción real del selector de personas/fecha, modal de Culqi, tokenización de tarjeta y confirmación de cobro exitoso end-to-end (requiere navegador + posible sandbox de Culqi).
- Login al panel Filament, edición real de un tour y refresco del front en vivo (validé la ausencia de caché por código, no por navegador).
- Estados hover/focus/active/disabled reales, navegación por teclado, contraste — nada de esto se probó.
- Responsive del formulario de checkout y del carrito.

## Recomendación

**No bloquea el merge por sí solo**, pero antes de dar por cerrado el lote pediría:
1. Corregir el hallazgo #1 (moneda hardcodeada en `CartService`/`CheckoutController`) — es la única pieza que puede convertirse en un cobro real incorrecto.
2. Decidir qué hacer con los montos `$...USD` sueltos en las 7 fichas publicadas (hallazgo #3) — es contenido importado, decisión de negocio + limpieza.
3. Cuando se retome, repetir esta validación con MCP Chrome disponible para el pixel-perfect y el flujo de pago real en navegador — hoy esa parte queda enteramente sin verificar.
