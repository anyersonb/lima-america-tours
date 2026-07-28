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

---

# F2 — L2·L3·L4·L5a (cro-validator, con navegador)

Fecha: 2026-07-25 · Rama `qa/panel-filament` (sin cambios de código, sin git) · Entorno: `http://127.0.0.1:8002/admin` (BD `lima_america_qa`) · Login: `admin@limaamericatours.com`.

## Resultado: EN CORRECCIÓN (actualización 2026-07-25, `backend-laravel`)

Hallazgos #1, #2, #5, #6 y #7 quedan **corregidos** en la rama `qa/panel-filament` (ver `docs/qa/FIXES.md` filas 2-6 para causa raíz/cambio/prueba de cada uno). Hallazgos #3 y #4 (sync de `contact.blade.php`/`about.blade.php` con `Page.blocks`) quedan **fuera de alcance** de este lote — corresponden a los módulos Contacto/Nosotros, no a Panel Filament. Hallazgo #8 sigue informativo (🔵, no bloquea).

No se declara VERIFICADO todavía: #3 y #4 siguen abiertos (pertenecen a otros módulos del protocolo, ver ESTADO.md).

| # | Capa | Nivel | Hallazgo | Evidencia | Estado |
|---|------|-------|----------|-----------|--------|
| 1 | L2 | 🟠 | El fix `81ba722` ("labels i18n del admin") tradujo solo las **columnas de tabla** de Region/Category/Testimonial/Offer/Newsletter/ContactLead, pero **no los campos del formulario Crear/Editar**. En esos 6 Resources, campos como `is_active`, `order`, `seo_title`, `seo_description`, `rating`, `source`, `price`, `locale`, `is_read`, `is_archived`, `subscribed_at` se muestran en inglés ("Is active", "Order", "Seo title"…) en el formulario que usa el editor a diario. | Captura `region-create-empty-submit.png` (Region); confirmado por código en `RegionResource.php:67-76`, `CategoryResource.php:57-62`, `TestimonialResource.php:50-65`, `OfferResource.php:55-81`, `NewsletterSubscriberResource.php:39-46`, `ContactLeadResource.php:58-73`; diff de `81ba722` muestra que solo se tocó `table()`, no `form()` | **CORREGIDO** — `->label()` en español agregado a cada campo de los 6 `form()`; `tests/Feature/PanelFormLabelsSpanishTest.php` (6/6 verde) |
| 2 | L2/Sync | 🟠 | El recurso **Ofertas** (`OfferResource`, menú Marketing) no tiene **ningún** consumidor en el front. `home.blade.php` no contiene la palabra "offers" en ningún lado (grep 0 resultados) y `tours/show.blade.php`/`tours/index.blade.php` tampoco. El editor puede crear/editar/publicar una Oferta completa (título, imagen, precio, CTA, tour vinculado, vigencia) y nunca se verá en el sitio. | Creado `QA_ Oferta Prueba` (id 4, activa, tour vinculado, order=0 — debía ser la primera en `Offer::active()->orderBy('order')->limit(3)`), no apareció en `/es` tras 2 revisiones; `grep -rn "offers" resources/views` solo devuelve la propiedad JSON-LD `'offers'=>[...]` de schema.org en `tours/show.blade.php:110` (no relacionada al modelo) | **CORREGIDO** — resultó trivial: `HomeController::fetchOffers()` ya calculaba `$offers` y lo pasaba a la vista, que nunca lo leía. Se agregó sección "Ofertas especiales" en `home.blade.php` con datos reales del modelo (sin contenido inventado); `tests/Feature/HomeOffersSectionTest.php` (4/4 verde). Ver decisión completa en `docs/qa/BACKLOG-CONTENIDO.md`. |
| 3 | L2/Sync | 🟠 | La página **Contacto** (`PageResource`, slug `contacto`): los 14 campos de la pestaña "Contenido de la página" (5 imágenes + eyebrow/título H1/lead ×3 idiomas) se guardan correctamente en BD pero **`contact.blade.php` nunca los lee** — extrae `$b = $page->blocks` y no vuelve a usar `$b` en todo el archivo (grep `'$b['` → 0 resultados). El eyebrow y el H1 del hero están hardcodeados (`$L('ESTAMOS PARA AYUDARTE', ...)` y `__('ui.contact_us')`). | Creé Page slug=contacto, edité Eyebrow(ES)="QA_ RESOLVEMOS TUS DUDAS" y Título H1(ES)="QA_ Contáctanos Prueba", guardé (confirmé persistencia recargando /admin), visité `/es/contacto`: ninguno de los dos textos aparece | abierto — asignar a `backend-laravel` (conectar `contact.blade.php` a `$b`) |
| 4 | L2/Sync | 🟠 | La página **Nosotros** (`PageResource`, slug `nosotros`): SOLO 4 campos sincronizan con el front (`hero_eyebrow`, `hero_title`, `hero_lead`, `img_hero`, vía `about.blade.php`). El resto del formulario —grupo colapsable "Nosotros — contenido" completo: botón hero, 2º párrafo, botón contacto, banner "Somos Lima América Tours" (heading+texto), sección "Vive la cultura local" (heading+intro), Repeater `stats` (Misión/Visión/Valores/Equipo), Repeater `pillars`, encabezados de testimonios, y las imágenes `img_grid1-4`/`img_banner_cta`/`img_testimonios`— no se usa en ningún lugar de `about.blade.php` (grep de `stats`, `pillars`, `banner_heading`, `cultura_*`, `testimonios_eyebrow`, `img_grid`, `img_banner_cta`, `img_testimonios` → 0 resultados). Es la mayor brecha CMS↔front de todo el panel: ~30+ campos de un formulario construido a propósito, sin efecto en el sitio. Además, en sentido inverso, `about.blade.php` usa `$b['destinations']` y `$b['mvv']`, que **no existen** como campos en el formulario actual (huérfanos del lado del front). | Doble verificación: (a) código — grep confirma 0 usos; (b) navegador — edité `hero_eyebrow_es`="QA_SYNC_TEST_NOSOTROS" (SÍ apareció en `/es/nosotros`, control positivo) y `banner_heading_es`="QA_SYNC_TEST_BANNER" (NO apareció, control negativo) en la misma sesión de guardado | abierto — asignar a `backend-laravel` (conectar `about.blade.php` al resto de `$b`, o recortar el formulario a lo que sí se usa) |
| 5 | L2 | 🟡 | El campo `slug` de `PageResource` no tiene `->live()`. La pestaña "Contenido de la página" (condicionada a `slug ∈ {contacto,nosotros}`) por eso **no aparece en modo Crear** aunque se escriba `contacto`/`nosotros` en el campo Slug — solo aparece tras Guardar y volver a abrir en Editar. Un editor creando estas páginas por primera vez no encuentra dónde cargar imágenes/textos del hero hasta guardar una vez. | Reproducido en `/admin/pages/create`: tipeé slug=contacto, cambié de tab, la pestaña extra no apareció; sí apareció al recargar `/admin/pages/contacto/edit` | **CORREGIDO** — `->live()` agregado al `TextInput` de `slug`; `tests/Feature/PageResourceSlugLiveTest.php` (verde) |
| 6 | L2/L3 | 🟠 | `BlockedDateResource`: la columna calculada "Bloqueo" del listado, para bloqueos de tipo "Día de la semana", concatena mal el plural: `"Todos los {$day}s"` con `$day` ya en plural invariante en español para Lunes/Martes/Miércoles/Jueves/Viernes → se ve **"Todos los Luness"**, "Todos los Martess", etc. (doble "s", mayúscula de más). Con Domingo/Sábado coincide por accidente ("Domingos"/"Sábados" sí sirven). Afecta 5 de 7 opciones del día. | Creado bloqueo semanal "Lunes", columna mostró literalmente "Todos los Luness"; código en `BlockedDateResource.php:119-128` | **CORREGIDO** — lógica extraída a `BlockedDateResource::weeklyBlockLabel()` con mapa de plurales correctos; `tests/Unit/BlockedDateWeeklyLabelTest.php` (9/9 verde) |
| 7 | L3 | 🟡 | `<title>` de la ficha de tour aplica una transformación tipo `ucwords()`/`Str::title()` al título del tour, rompiendo mayúsculas intencionales: "QA_ Tour Prueba ñÑ áéíóú" se convirtió en "Qa_ Tour Prueba Ññ Áéíóú" en la pestaña del navegador. Solo afecta el `<title>` (SEO/pestaña), no el H1 visible en la página. | `document.title` capturado tras navegar a `/es/tours/detalle/qa-tour-prueba` | **CORREGIDO** — transformación eliminada en `tours/show.blade.php` (`$titleDisplay = $tour->title`); `tests/Feature/TourShowTitlePreservesCasingTest.php` (verde) |
| 8 | L2 | 🔵 | reCAPTCHA está **desactivado** en QA (`Activar reCAPTCHA` = off, sin Site/Secret Key) — comportamiento correcto y esperado para un entorno de pruebas (`RecaptchaService->verify()` es no-op si está desactivado), no es un defecto. Se anota solo para que quede registrado que no se probó el flujo v2/v3 real (requiere claves reales de Google). | Tab reCAPTCHA de Configuración, toggle apagado, campos vacíos | no bloquea — informativo |

## Verificado — CRUD por Resource (Create/Read/Update/Delete + validaciones)

Prefijo `QA_` en todo registro de prueba. Purga confirmada al final (ver §Purga).

- **TourResource** (`/admin/tours`): Create con datos hostiles — título >255 car. → validación español "El campo título no debe tener más de 255 caracteres." (recortado y reintentado); título con `<script>alert(1)</script>` + ñÑáéíóú se guarda y se muestra **escapado** en el front (confirmado `innerHTML` sin `<script>` crudo); precio 150 + precio antes 200 → vista previa en vivo "OFERTA ESPECIAL -25%"; badge_text "QA_BADGE" + badge_type "Rojo"; región/categoría (selects Choices.js con nombres reales, no IDs); FAQ (pregunta+respuesta); Incluye/No incluye (TagsInput); sin imagen de portada (0 `<img>` rotas, fallback a placeholder). Comparativa: toggle y ~12 campos revisados estructuralmente (labels correctos), sin verificación profunda de sync visual (ver "No cubierto"). Edit/Delete sin huérfanos (confirmado en `/es/tours` tras borrar). Campos probados: `region_id`, `category_id`, `slug`, `price`, `price_before`, `currency`, `badge_text`, `badge_type`, `title_es`, `faqs` (repeater), `includes`, `excludes`.
- **RegionResource** (`/admin/regions`): Create (slug, name_es con `<script>`+ñÑ, is_active, order), validación HTML5 "Completa este campo" en español sobre campos requeridos vacíos, búsqueda en tabla (chip "Buscar: Cusco" removible), orden por columna clicable, Update (Name English) persistido tras reload, Delete con confirmación ("¿Está segura/o de hacer esto?") sin dejar huérfanos (3→4→3 registros).
- **CategoryResource** (`/admin/categories`): Create (slug, name_es, is_active), toast "Creado", sync con front `/es/tours` — categoría sin tours vinculados correctamente **no aparece** en el filtro (`@if ($category->tours_count > 0)` en `tours/index.blade.php:77` — comportamiento por diseño, no bug), Delete OK.
- **TestimonialResource** (`/admin/testimonials`): Create (name, quote_es con tildes, rating, source, is_active, tour_id), el combobox de tour muestra títulos reales. Hallazgo adicional (no listado arriba por ser menor que #3/#4 pero de la misma familia): `tourReviews` (testimonios propios del tour) se computa en `TourController@show:59-62` pero no se usa en `tours/show.blade.php` — anotado como parte del backlog técnico, no repetido en la tabla de hallazgos para no duplicar.
- **OfferResource** (`/admin/offers`): Create (title_es, tour_id — combobox con títulos reales, confirma que el fix "Offer muestra títulos" de `81ba722` sigue vigente —, is_active, order), Delete OK. Ver hallazgo #2 (sync).
- **PageResource** (`/admin/pages`): 0 registros preexistentes en QA. Create+Edit+Delete de `contacto` y `nosotros` (probados y purgados, DB vuelve a 0 Pages). Ver hallazgos #3, #4, #5.
- **ContactLeadResource** (`/admin/contact-leads`): 0 registros iniciales (empty state "No se encontraron registros" correcto). Create (name, email, message), badge de navegación "Mensajes" cuenta correctamente los no-leídos (mostró "1"), Delete OK.
- **BookingResource** (`/admin/bookings`): 0 registros iniciales. Create con tour de catálogo (`tour_id` autocompleta nombre y precio — "El nombre y el precio se completan solos al elegir el tour" ✓), adultos=2 → total recalculado en vivo 65×2=130.00 ✓, fecha vía datepicker en español, cambiar `payment_status` a "Pagado" **auto-confirma** `status` a "Confirmada" (regla de negocio verificada), campo "Referencia de pago" correctamente deshabilitado/no editable a mano. Correos de confirmación (cliente + admin) se dispararon vía `BookingNotifier` y quedaron en el log de mail (driver `log` en QA, no se envió correo real) con datos correctos (nombre, tour, fecha, precio, referencia `LVT-*`). Delete OK, badge nav "Reservas" reflejó el conteo pending correctamente durante la prueba.
- **AbandonedCartResource** (`/admin/abandoned-carts`): confirmado de solo lectura (sin botón "Crear", `canCreate()` false), 0 registros, filtro "Filtrar 0" presente, empty state correcto. No se pudo probar la acción "Reenviar recordatorio" ni el listado con datos reales por no existir ningún carrito abandonado en QA y no haber vía de UI para crear uno (ver "No cubierto").
- **NewsletterSubscriberResource** (`/admin/newsletter-subscribers`): Create con email inválido → validación HTML5 en español ("Incluye un signo "@" en la dirección de correo electrónico. La dirección "no-es-un-email" no incluye el signo "@"."), luego email válido → creado y borrado OK.
- **BlogPostResource** (`/admin/blog-posts`): **Regresión de `81ba722` verificada**: crear solo con ES (title_es/excerpt_es/body_es), dejando EN/PT vacíos, **NO da 500** (el bug original está resuelto). Slug auto-generado sanitiza correctamente el `<script>` y la ñ → `qa-articulo-prueba-nn-aeiou-scriptalertxscript` (sin riesgo de inyección en la URL). Publicado vía toggle "Publicado", verificado en `/es/blog`: el título con `<script>alert('x')</script>` se muestra como texto escapado (confirmado con `innerHTML`, sin ejecutar). Delete OK.
- **MediaAssetResource** (`/admin/media-assets`): subida real de un PNG de prueba (FilePond, "Subida completa"), campo Colección, Create/Delete OK.
- **BlockedDateResource** (`/admin/blocked-dates`): Create tipo "Fecha específica" (DatePicker en español: enero–diciembre, lun.–dom.) y tipo "Día de la semana (recurrente)", exclusión mutua entre `date`/`weekday` confirmada (cambiar el radio reemplaza el campo visible). Ver hallazgo #6. Delete OK (ambos registros).

## Verificado — Sincronía CMS ↔ Maqueta

- [✓] Tour → título, precio, precio antes/oferta (badge "-25%"), `badge_text`, rating, FAQ: editados en admin, confirmados en `/es/tours` (listado) y `/es/tours/detalle/{slug}` (ficha).
- [✓] Category → correctamente filtrada del front cuando no tiene tours (por diseño).
- [✓] BlogPost → título/publicación sincronizan con `/es/blog`.
- [✓] Settings → Contacto (`contact_phone`) sincroniza con `/es/contacto` (editado a "+51 900 000 000", confirmado, revertido a "+51 925 886 725", re-confirmado). Redes sociales coinciden entre admin y footer del front (verificado por comparación visual, sin editar).
  - ⚠️ **Corrección (2026-07-28):** ese "valor original" al que se revirtió era el teléfono de **Lima View Tours**, otro cliente, heredado del fork. Restaurar lo que uno encuentra no es restaurar lo correcto: un dato de contacto se verifica contra la fuente del cliente, no contra el estado previo de la BD. Corregido a `+51 957 299 438` (ver `BACKLOG-CONTENIDO.md` #5).
- [✓] Settings → `contact_phone_secondary` sigue vacío (fix `81ba722` "teléfono secundario vaciado" intacto).
- [✗] Página `contacto` (bloques hero) → NO sincroniza (hallazgo #3).
- [✗] Página `nosotros` (bloques "contenido" completos) → NO sincroniza salvo hero_eyebrow/title/lead/img_hero (hallazgo #4).
- [✗] Offer → NO sincroniza con ningún lugar del front (hallazgo #2).

## Verificado — Datos hostiles (L3)

- Título de tour >255 caracteres → rechazado con mensaje en español, sin 500.
- `<script>alert(1)</script>` en título de Tour, Región y BlogPost → se guarda tal cual en BD (texto plano) y se **renderiza escapado** en el front (confirmado con `innerHTML`/`textContent`, ningún `alert()` disparado, ninguna etiqueta `<script>` cruda en el HTML servido).
- Tour sin imagen de portada → 0 imágenes rotas en la ficha ni en el listado (fallback a placeholder).
- Email con formato inválido (Newsletter) → validación HTML5 en español.
- Slug con caracteres especiales/HTML (BlogPost) → se sanitiza correctamente al slugificar.
- 0 registros en ContactLead, Booking, Page, AbandonedCart al iniciar → estados vacíos ("No se encontraron registros") se ven correctamente, sin layout roto.

## Verificado — Flujos e interacción (L4)

- Búsqueda de tabla (Regiones): filtra, muestra chip "Buscar: X" removible, cuenta actualizada ("Se muestra un resultado").
- Orden de columna clicable (Regiones, Categorías, Ofertas — columna "Orden").
- Paginación: selector "5/10/25/50/Todos" presente en todas las tablas probadas.
- Toggles inline y en formulario: `is_active`, `is_published`, `Publicado`, `Destacado`, `Badge "BEST SELLER"`, `Badge "Oferta especial"` — todos reflejan su estado tras guardar y recargar.
- Radio con exclusión mutua (BlockedDate `type`): cambia el campo visible correctamente (DatePicker ↔ Select de día).
- Select dependiente con auto-cálculo (Booking): elegir tour autocompleta nombre+precio; cambiar adultos recalcula el total en vivo; cambiar `payment_status` a "Pagado" dispara `status`→"Confirmada".
- Acción "Limpiar caché" en Mantenimiento: modal de confirmación en español, ejecutada, el sitio siguió respondiendo normalmente después (`/es` cargó 200).

## No cubierto (y por qué)

- **`AbandonedCartResource` con datos reales**: no existe ningún carrito abandonado en `lima_america_qa` y el Resource es de solo lectura (sin `create()`) — no hay vía de UI para generar uno de prueba. No se probó la tabla con datos, los filtros de `status`, ni la acción "Reenviar recordatorio". Requeriría un carrito real generado desde el checkout público (fuera del alcance de F2, que es Panel Filament) o un seed manual vía tinker, que decidí no hacer para no mezclar capas.
- **Comparativa de Tour (sync visual profunda)**: se verificó que el toggle y los ~12 campos por idioma existen, están bien etiquetados y se pueden llenar, pero no se hizo el ciclo completo de llenar todos los campos → guardar → verificar renderizado exacto en `/es/tours/detalle/{slug}` (tiempo). Recomendado para una pasada de regresión dedicada o para `maquetador-frontend` en F3 (visual).
- **Settings → tab "Home"** (el más grande, ~50+ campos: hero, destinos, tipos de tour, experiencias, FAQs home, "Más Visitados", "Por qué elegirnos"): se confirmó que la pestaña carga y los sub-repeaters (`home_destinos`, `home_why_items`, etc.) están presentes, pero no se editó ni verificó sync campo por campo por el volumen (más de 50 campos) y el tiempo disponible. Recomendado como pasada dedicada.
- **Settings → tabs "Pagos", "SEO", "GEO", "AEO/FAQ", "APIs", "Recogida", "Cookies"**: se confirmó que cargan sin error y estructura de campos coincide con el inventario F0, pero no se editó/guardó ningún valor real en estas pestañas (por prudencia con claves de Pagos/APIs en un entorno compartido, y por tiempo).
- **`/sitemap.xml` vía navegador**: el `browser_navigate` a esa URL dio timeout repetido (posible incompatibilidad del visor de Chrome con XML crudo en este entorno automatizado), pero `curl` confirmó `200 OK` en 0.78s con contenido válido — se considera verificado por esta vía alterna, no por navegador.
- **Reenviar correo (acción custom de Booking)**: no se ejecutó para evitar generar tráfico de correo adicional; la funcionalidad de envío de correos de confirmación ya quedó verificada indirectamente al crear la reserva (`BookingNotifier` disparó ambos correos correctamente, ver log).
- **`Maintenance`: "Optimizar (producción)", "Recompilar assets", "Ejecutar migraciones"**: no ejecutados a propósito — son acciones que tocan archivos/config compilados del servidor compartido de desarrollo (`config:cache`, `filament:assets`, `migrate --force`) y podían interferir con otros agentes/trabajo en curso sobre el mismo repo. Solo se probó "Limpiar caché" (reversible, de bajo riesgo) y el link "Ver sitemap".
- **Responsive/pixel-perfect del panel (375/768/1440)**: fuera de alcance de F2 — corresponde a `maquetador-frontend` en F3 (L5b).
- **OWASP/seguridad (mass assignment, XSS en RichEditor de BlogPost, permisos de storage)**: fuera de alcance de F2 — corresponde a `security-engineer` en F6. Se señala como insumo: `BlogPostResource`/`PageResource` usan `RichEditor` (HTML libre), su sanitización real en el front no se auditó a fondo (solo se probó XSS en campos de texto plano, que sí escapan correctamente).

## Purga de datos QA_

Confirmado por `tinker --env=qa` al cierre: **0** registros con prefijo `QA_`/`qa-` en Tour, Region, Category, Testimonial, Offer, ContactLead, Booking, NewsletterSubscriber, BlogPost, MediaAsset; **0** Pages (estado original); **0** BlockedDate (estado original). `contact_phone` revertido a `+51 925 886 725`. `storage/logs/laravel.log`: sin líneas `ERROR`/`CRITICAL` nuevas durante toda la sesión F2 (solo `INFO`/`DEBUG` de envío de correo de reserva, esperado).

## Backlog de contenido (🔵) — no bloquea

- reCAPTCHA sin claves reales configuradas en QA (ver hallazgo #8, informativo).
