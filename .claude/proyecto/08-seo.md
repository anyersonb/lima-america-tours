# 08 — SEO: auditoría del hero rehecho (home) — Lima América Tours

Fecha: 2026-07-27
Alcance: brief autocontenido "Auditoría SEO del hero rehecho de la home", branch `data/tours-reales`, sin commit ni deploy. Verificado sobre HTML servido por `php artisan serve` local (`APP_ENV=local`), no sobre el Blade fuente, salvo donde se cita línea de código explícitamente.

**Dependencia declarada**: la tabla Lighthouse y las capturas de pantalla son del `cro-validator` (corre en paralelo); no se repiten aquí. Este informe no encontró `05-cro.md` todavía en `.claude/proyecto/` al momento de escribir — si el CRO ya emitió su tabla on-page (title/meta/H1/schema/OG) y gates de discoverability, este documento no la repite y trabaja sobre lo que esos gates no cubren (validez de campos, duplicados de schema, indexación de rutas parametrizadas, i18n de contenido).

**Nota de alcance**: el Bloque 2 (mapa completo de enlazado interno del sitio) y el Bloque 3 (competencia) de la ficha genérica de SEO no aplican a este brief — es una auditoría de una feature (hero + buscador + resultados), no del sitio completo. Se revisaron únicamente los enlaces internos que el propio hero genera (buscador → resultados, sugerencias → tours). Si se necesita el mapa completo, es un encargo aparte.

**Sin acceso a internet desde este entorno**: no pude contrastar contra `limaamericatours.com` (WordPress en vivo) ni contra Search Console/CrUX — cero cifras de posiciones o impresiones inventadas; lo que no se pudo medir queda en Bloqueos.

---

## Resumen ejecutivo

El hero está técnicamente bien resuelto en su mayoría (H1 único, i18n completo en las 3 lenguas, imagen con `fetchpriority`/`loading`/dimensiones, hreflang recíproco con `x-default`, noindex de entorno activo y correctamente condicionado a `production`). Los hallazgos serios están fuera del propio hero pero dentro del alcance del cambio: (1) el JSON-LD de `WebSite` está **duplicado** con un `SearchAction` roto en una de las dos copias; (2) el schema `LocalBusiness`/`TravelAgency` tiene campos obligatorios vacíos (`name`, `priceRange`, `address`) por un bug de fallback (`??` en vez de `?:`) que además descarta una dirección real ya cargada en Settings; (3) la página de resultados del buscador (`/buscar`) no lleva `noindex` propio y depende solo de un `robots.txt` que además está incompleto para portugués; (4) la imagen de hero por defecto pesa 2.52 MB en PNG sin variante moderna. El H1 de marca es una decisión legítima del cliente pero con costo medible en la página de mayor autoridad del dominio — se documentan 3 alternativas concretas más abajo.

---

## Hallazgos

| # | Severidad | Bloque | Archivo:línea | Qué está mal | Cómo lo verifiqué | Qué cambiar | Asignado | Esfuerzo |
|---|---|---|---|---|---|---|---|---|
| S-01 | **Crítico** | 4 (Datos estructurados) | `resources/views/home.blade.php:7-20` vs `resources/views/components/jsonld.blade.php:92-106` | Dos bloques `<script type="application/ld+json">` de `@type: WebSite` en la misma página. El de `home.blade.php` apunta el `SearchAction.target` a `/{locale}/tours?q={search_term_string}` (ruta `tours.index`); `TourController::index()` (`app/Http/Controllers/TourController.php:17-28`) **ignora por completo el parámetro `q`** — nunca filtra. El otro bloque (`jsonld.blade.php`, global) apunta correctamente a `/{locale}/buscar?q=...` (ruta real del buscador, `tours.results`). Confirmado en HTML servido: `/es` emite ambos scripts con targets distintos. | `curl` al home en los 3 locales + lectura de `TourController@index` | Eliminar el `@push('schema')` de `home.blade.php` (líneas 7-20): el `WebSite`+`SearchAction` global ya lo cubre `x-jsonld` con el target correcto. No dejar dos instancias del mismo `@type` con datos contradictorios (si Google llega a activar el sitelinks search box con el target roto, cualquier búsqueda real devuelve el catálogo completo sin filtrar). | backend-laravel | Bajo |
| S-02 | **Crítico** | 4 / 7 (SEO local) | `resources/views/components/jsonld.blade.php:13-21, 49-64` | El schema `TravelAgency`/`LocalBusiness` se emite con `"name": ""`, `"priceRange": ""` y `"address": {"@type":"PostalAddress"}` (sin `streetAddress`/`addressLocality`/etc.). Causa: el componente usa `??` para los fallbacks (`$settings['geo_business_name'] ?? $siteName`, etc.), pero en BD esas claves **existen con valor `''`** (confirmado con `Setting::get()` en tinker: `geo_business_name`, `geo_street`, `geo_city`, `geo_region`, `geo_postal_code`, `geo_country`, `geo_price_range` → `string(0) ""`). `??` no cae al fallback cuando la clave existe y está vacía, solo cuando es `null`/no existe. `name` es un campo obligatorio de `LocalBusiness` — el schema queda inválido. Además, `contact_address_es` **sí tiene** la dirección real ("Av. Larcomar 233, Of. 410 — Miraflores, Lima", visible y rastreable en el footer) pero el bug impide que llegue al schema. | `php artisan tinker` con `Setting::get()` sobre las claves `geo_*` y `contact_address_es`; inspección del HTML servido (`<script type="application/ld+json">` con `"name":""`) | Cambiar `??` por `?:` (o un helper explícito tipo `filled($x) ? $x : $fallback`) en las líneas de fallback de `$geoName`, `$geoStreet`, `$geoCity`, `$geoRegion`, `$geoPostal`, `$geoCountry`, `$geoPrice` en `jsonld.blade.php`. El mismo patrón ya está bien resuelto en `home.blade.php` (usa `?:` para `home_hero_title_*`, etc.) — replicarlo aquí. | backend-laravel | Bajo |
| S-03 | **Alto** | 5 (indexación) | `resources/views/tours/results.blade.php` (sin `@section('robots', ...)`) + `routes/web.php:74` + `app/Http/Controllers/SitemapController.php:118-132` | La ruta `/{locale}/buscar` (`tours.results`, la que recibe `q`/`destino`/`fecha`/`pax` desde el buscador del hero) no define ningún `<meta name="robots">` propio → en producción cae al `else` del layout y se sirve `index,follow,...` por defecto. La única protección es `robots.txt`, que además solo bloquea `/buscar`, `/es/buscar`, `/en/buscar` — **falta `/pt/buscar`** (confirmado leyendo el array `$lines` de `SitemapController::robots()`, no hay ninguna línea para `pt`). Combinar `Disallow` (bloquea rastreo) sin `noindex` es el antipatrón que la propia documentación de Google desaconseja: si Google no puede rastrear la URL, tampoco puede ver un futuro `noindex` en ella, y cualquier variante de `?q=`/`?destino=` que reciba un enlace externo puede quedar indexada "a ciegas". Para `pt`, hoy no hay ninguna barrera. | `curl -D-` a `/es/buscar?q=...&destino=...` (canonical limpio, pero robots meta por defecto sin override), lectura de `SitemapController::robots()` y `routes/web.php` | Añadir `@section('robots', 'noindex,follow')` en `tours/results.blade.php` (así Google puede seguir los enlaces a los tours reales — incluida la sección "Recomendados" — pero no indexa la página de resultados en sí) y **quitar** el `Disallow: /buscar` de `robots.txt` una vez esté el `noindex,follow` explícito (no combinar ambos mecanismos). De paso, igualar el tratamiento en los 3 locales. | backend-laravel | Bajo |
| S-04 | **Alto** | 3 (CWV / lente SEO) | `public/assets/banners/hero-machu-picchu.png` (fallback de `home.blade.php:34`) | Imagen de fallback del hero (se sirve cuando `Setting::get('home_hero_image')` está vacío, que es el caso en este entorno local) pesa **2,582,107 bytes (2.52 MB)** en PNG — confirmado en disco y en la respuesta HTTP (`Content-Length: 2582107`, `Content-Type: image/png`). No existe pipeline de optimización de imágenes en el proyecto (sin `intervention/image`, sin `spatie/laravel-image-optimizer`, sin Glide en `composer.json`) ni variante WebP/AVIF junto al archivo. Es el candidato natural a LCP del hero. | `stat`/`getimagesize` en disco + `curl -D-` a la URL servida + `grep` en `composer.json` | Convertir a WebP/AVIF (peso esperado ~150–300 KB para esa resolución) o, mejor, montar un paso de optimización en el pipeline de subida de `home_hero_image` para que cualquier imagen que suba el admin desde Settings pase por el mismo tratamiento (si no, el problema se repite con la próxima imagen que se suba). | maquetador-frontend (asset actual) / backend-laravel (pipeline) | Medio |
| S-05 | Medio | 5 (indexación) | `app/Http/Controllers/SitemapController.php:128-132` | Inconsistencia de locale en `robots.txt`: bloquea `/es/buscar` y `/en/buscar` pero no `/pt/buscar` (ni tampoco `/pt/checkout`, mismo patrón — sí bloquea `/checkout`, `/es/checkout`, `/en/checkout` pero falta `/pt/checkout`). Si se resuelve S-03 con `noindex,follow` en la vista, este hallazgo queda cubierto por igual en los 3 locales sin depender de robots.txt; si no, hay que añadir las líneas de `pt` sueltas. | Lectura directa del array `$lines` en `SitemapController::robots()` | Añadir `Disallow: /pt/buscar` y `Disallow: /pt/checkout` si se mantiene el mecanismo de robots.txt, o resolver junto con S-03. | backend-laravel | Bajo |
| S-06 | Medio | 3 (CWV / lente SEO) | `resources/views/home.blade.php:176` | El `<img>` del hero declara `width="1200" height="1400"` (retrato, ratio 0.857) pero el archivo real servido en este entorno mide **1717×916** (paisaje, ratio 1.87) — verificado con `getimagesize()`. La regla CSS `.lat-hero__media img { width:100%; height:100%; object-fit:cover }` evita que esto produzca un salto de layout visible hoy (el tamaño de caja lo fija el contenedor, no el ratio intrínseco), pero los atributos siguen siendo metadata incorrecta: si algún día el contenedor pierde ese `height:100%` explícito (p. ej. un rediseño parcial de breakpoint), el navegador usará el `aspect-ratio` derivado de estos atributos como fallback y sí generará CLS. Además, si el admin sube una imagen de otro ratio vía Settings, el atributo seguiría siendo el mismo valor fijo (1200×1400), ahora incorrecto para ese archivo también. | `php -r "getimagesize(...)"` + lectura de `_lat-home.scss:121-135` | Corregir los atributos a la relación real del archivo actual, o (mejor) calcularlos dinámicamente a partir del archivo servido cuando `home_hero_image` viene de Settings, en vez de un valor fijo en el Blade. | maquetador-frontend / backend-laravel | Bajo |
| S-07 | Bajo | 3 (CWV / lente SEO) | `resources/views/layouts/app.blade.php` (`<head>`, sin `<link rel="preload">`) | No hay `<link rel="preload" as="image">` para la imagen de hero (candidata a LCP) en el `<head>`. Mitigado en parte por `fetchpriority="high"` + `loading="eager"` y por estar muy arriba en el DOM (el preload scanner ya la detecta razonablemente rápido), pero con el peso actual (S-04, 2.5 MB) cada milisegundo de descubrimiento anticipado importa más de lo habitual. | Lectura de `<head>` servido (sin coincidencias de `preload` + `hero`) | Agregar `<link rel="preload" as="image" href="{{ $heroImgUrl }}" fetchpriority="high">` condicionado a la home, antes de los `@stack('head')`. | maquetador-frontend | Bajo |
| S-08 | Bajo | 8 (contenido) / colateral, fuera del diff auditado | `resources/views/home.blade.php` sección "Ofertas especiales" (~446-488) | Los 3 CTA de la sección "Ofertas especiales" enlazan siempre a `/es/tours` en EN y PT (verificado en el HTML servido de los 3 locales) porque el dato `Offer.cta_url` viene sembrado con una ruta absoluta en español, y el código usa `$offer->cta_url ?: route(...)` — como `cta_url` no está vacío, nunca cae al fallback locale-aware. No forma parte del cambio de hero auditado, se reporta porque apareció en la misma página durante la revisión de i18n. | `curl` a `/en` y `/pt`, `grep href="/es/tours"` | Revisar en el admin (Marketing → Ofertas) los `cta_url` sembrados: dejarlos vacíos (para que use el fallback locale-aware) o usar rutas relativas sin locale que el propio helper resuelva. | backend-laravel / cliente (contenido) | Bajo |

---

## Dictamen técnico — H1 de la home

**Decisión: del cliente, no de este informe.** Lo que sigue es el análisis técnico pedido y 3 opciones concretas, no una recomendación ejecutable por mi cuenta.

**Qué se pierde con el H1 = "Lima América Tours" (marca)**: el H1 es, después del `<title>`, la señal de contenido más pesada que un motor de búsqueda lee de una página, y la home es casi siempre la página de mayor autoridad de enlace de todo el dominio. Usar ese elemento para repetir una keyword de marca por la que el sitio ya tiende a rankear bien (poca competencia, exact match con el propio dominio) en vez de reforzar el intent transaccional real ("tours en Lima", "tours en Perú", "city tour Lima", "excursiones Perú") es, en términos de arquitectura de la información, la página con más autoridad del sitio *no* reforzando el tema por el que compite. **Mitigante real que sí verifiqué**: el `<title>` de la home ya lleva el intent correcto ("Tours en Lima — Experiencias inolvidables | Lima América Tours", visto en el head servido), y `/tours` (listado) sí tiene su propio H1 con intent ("Descubre las mejores experiencias en Lima"). El daño, por tanto, no es catastrófico ni deja al sitio sin ninguna señal — se concentra específicamente en desperdiciar la home para ese refuerzo. No tengo datos de posiciones/impresiones reales de este dominio (sin acceso a Search Console/GSC desde este entorno) para cuantificar el impacto en cifras — cualquier número sería inventado, así que lo dejo como riesgo direccional, no como porcentaje.

**Contexto operativo relevante para decidir**: el campo `home_hero_title_{es,en,pt}` **ya existe como `Textarea` en Filament** (`app/Filament/Pages/Settings.php:270-281`). Cambiar el copy del H1 (Opción 2 más abajo) es una edición de contenido que el cliente puede hacer hoy mismo desde el panel, sin pasar por maquetación ni programación.

### Opción 1 — Statu quo (lo que está aprobado hoy)
- H1: `Lima América Tours` (igual en los 3 idiomas).
- Costo: cero, ya implementado.
- Pierde: intent transaccional en el elemento de mayor peso de la home. Parcialmente compensado por el `<title>` y por el H1 de `/tours`.

### Opción 2 — Cambiar solo el copy del H1 (mismo diseño, mismo tamaño, editable ya en Settings)
- ES: `Tours en Lima y Perú — Lima América Tours`
- EN: `Tours in Lima & Peru — Lima América Tours`
- PT: `Tours em Lima e no Peru — Lima América Tours`
- Costo: cero desarrollo (campo ya existe en Filament); esfuerzo real es que alguien lo escriba y guarde. Único riesgo: el copy es ~2× más largo que "Lima América Tours" y el `clamp(2.1rem, 5vw, 3.4rem)` actual podría partir a 2-3 líneas en mobile — vale una revisión visual de 5 minutos por parte de maquetación tras el cambio, no es una tarea de desarrollo.
- Gana: intent + marca conviven en el mismo elemento, sin tocar código.

### Opción 3 — Reasignación semántica, cero cambio visual (requiere maquetador)
- Se conserva el diseño exacto: el nombre de marca sigue viéndose igual de grande y en el mismo lugar, pero deja de ser la etiqueta `<h1>` (pasa a `<p>`/`<span>` con la misma clase visual). El `<h1>` real pasa al eyebrow/tagline actual (o a un texto nuevo con la misma posición y tamaño que hoy tiene la tagline roja), con copy que sí lleva intent, p. ej.:
  - ES: `Tours en Lima y Perú: 10 años mostrando lo mejor`
  - EN: `Tours in Lima & Peru: 10 years showcasing the best`
- Cambio técnico necesario: en `_lat-home.scss:251` la regla es `.lat-hero h1 { ... }` (selector por etiqueta, no por clase) — hay que renombrarla a una clase (`.lat-hero__brand-heading`) para que el estilo no dependa de qué etiqueta HTML lleve el texto, y así el swap de tags en `home.blade.php` no mueva ni un píxel.
- Costo: bajo-medio (1 archivo Blade + 1 selector SCSS + QA visual en los breakpoints), maquetador-frontend.
- Gana: cumple literalmente el pedido del cliente (la marca se ve igual de protagonista) y a la vez le da a un motor de búsqueda un H1 con intent real. Es el balance técnico más razonable si el cliente no quiere ceder nada visualmente pero sí quiere resolver el problema de fondo.

---

## Conflictos con CRO

- **No pude leer `05-cro.md`** — no existe todavía en `.claude/proyecto/` al momento de este informe (corre en paralelo). Si el CRO valida el H1 de marca por motivos de reconocimiento/branding en el primer impacto visual, ese hallazgo entraría en conflicto directo con el punto de arriba. Costo de cada lado, para que decida Anyerson cuando ambos informes estén sobre la mesa:
  - **Costo de priorizar branding (H1 = marca, Opción 1)**: se resigna la señal de mayor peso de la home para reforzar el tema de negocio; mitigado parcialmente por `<title>` y por `/tours`.
  - **Costo de priorizar intent (Opciones 2 o 3)**: si el CRO considera que el nombre de marca grande en el primer vistazo es lo que genera confianza/reconocimiento inmediato (relevante si el sitio viene de reemplazar un WordPress con otra identidad visual), la Opción 2 lo diluye un poco (texto más largo, marca ya no está sola) y la Opción 3 lo resuelve sin ese costo (visualmente idéntico) pero pide desarrollo.
  - Recomiendo revisar ambos informes juntos antes de instruir a maquetación, tal como indica el orden del ciclo (CRO+SEO → fix → QA).
- No se detectó ningún otro conflicto directo CRO vs SEO en el resto de hallazgos (S-01 a S-08 son correcciones técnicas sin costo de conversión).

---

## Bloqueos

- No hay acceso a internet desde este entorno de ejecución (`curl` a `limaamericatours.com` devolvió error de conexión) — no pude contrastar el H1/estructura actual del WordPress en producción, ni datos de Search Console/CrUX/posiciones. Cualquier cifra de impacto del H1 queda fuera del informe por esa razón, no se estimó.
- No se verificó el comportamiento real en el hosting/CyberPanel de producción del middleware `StagingNoindex` (S-XX no aplica como hallazgo porque el código está bien escrito y depende solo de `app()->environment('production')`) — queda como ítem de checklist de despliegue, no como hallazgo de código: confirmar que el `.env` de producción real tenga `APP_ENV=production` antes de publicar. Si alguien despliega con `APP_ENV=local` o `staging` por error, todo el sitio queda con `noindex,nofollow` en producción (sería Crítico si ocurre, pero hoy el código en sí está correcto).
- No se auditó el mapa completo de enlazado interno del sitio (Bloque 2 genérico) ni brecha competitiva (Bloque 9) — fuera del alcance de este brief (auditoría de la feature "hero", no del sitio completo).

---

## Matriz Impacto × Esfuerzo

| Hallazgo | Impacto | Esfuerzo | Prioridad |
|---|---|---|---|
| S-01 (WebSite JSON-LD duplicado, SearchAction roto) | Alto | Bajo | 1 |
| S-02 (LocalBusiness con campos vacíos) | Alto | Bajo | 2 |
| S-03 (noindex faltante en /buscar) | Alto | Bajo | 3 |
| S-05 (robots.txt sin /pt/buscar) | Medio | Bajo | 4 |
| S-06 (width/height del hero incorrectos) | Medio | Bajo | 5 |
| S-07 (preload de LCP faltante) | Bajo-Medio | Bajo | 6 |
| S-04 (hero PNG 2.5MB sin WebP) | Alto | Medio | 7 |
| S-08 (CTA ofertas hardcoded a /es) | Bajo | Bajo | 8 |
| H1 (marca vs intent) | Alto (direccional) | Depende de la opción elegida | Decisión de cliente |

---

## Revisión diferida 30-90 días (post-lanzamiento)

Cuando se reinvoque esta ficha tras el despliegue, medir:
- **Cobertura en Search Console**: cuántas URLs de `/buscar?...` terminaron indexadas pese a S-03/S-05 (si no se corrigieron antes del lanzamiento) — informe *Indexación → Páginas*, específicamente "Indexada, no enviada en sitemap" y "Rastreada, actualmente sin indexar" para el patrón `/buscar`.
- **Validación del schema**: correr el home por Rich Results Test / Schema Markup Validator una vez corregidos S-01/S-02, confirmar 0 errores en `LocalBusiness`/`TravelAgency` y que solo quede una instancia de `WebSite`.
- **Core Web Vitals de campo** (CrUX/PageSpeed Insights con datos de 28 días reales) para el LCP del hero, contrastado contra el laboratorio del CRO — solo tiene sentido una vez haya tráfico real acumulado.
- **Impacto del H1** (cualquiera sea la opción elegida): consultas e impresiones de la home en *Rendimiento → Páginas* para queries genéricas tipo "tours lima"/"tours peru" vs. queries de marca, comparando antes/después si se cambió el copy.
- **Efecto de S-08** (si se corrige): que las sesiones EN/PT que tocan "Ofertas especiales" ya no aterricen en la versión ES.
