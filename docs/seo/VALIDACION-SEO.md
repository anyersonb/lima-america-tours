# Validación SEO — Lima América Tours (rama `data/tours-reales`, pre-merge)

**Fecha:** 2026-07-26 · **Entorno probado:** `http://127.0.0.1:8001` (locales `/es` `/en` `/pt`) · **Método:** curl + lectura de código + `php artisan tinker` contra la BD local (**sin MCP de navegador** — caído; nada de render JS, CWV de campo ni capturas).

No repito la tabla on-page/7 gates del CRO (title/meta/H1/schema/OG por URL) ni Lighthouse: no se generó por la caída del MCP, así que tampoco hay tabla de performance que contrastar. Esta pasada cubre exactamente los 5 puntos pedidos.

---

## 1) Indexación y rastreo

| Área | ¿OK? | Evidencia |
|---|---|---|
| `robots.txt` | OK, condicionado por entorno | `SitemapController@robots`: en `local` emite `Disallow: /` (correcto para no indexar el entorno de pruebas); en `production` emite `Allow: /` + disallow de `/admin`, `/checkout`, `/buscar` + `Sitemap:` + bloques específicos para bots de IA. **Confirmar en el deploy real que `APP_ENV=production`** — si queda en `local` por error, todo el sitio sale con `Disallow: /` (ver Bloqueos). |
| `sitemap.xml` | OK | Existe, sirve XML válido, incluye estáticas + regiones + los **24 tours publicados** (no 26 — ver S-03) + blog index + 10 posts + páginas CMS, con `hreflang` por URL y `image:` en tours/posts. Verificado con `simplexml_load_file`: 135 `<url>`, 0 duplicados. |
| Canonical | OK | `<link rel="canonical" href="{{ url()->current() }}">` autorreferencial en todas las URLs probadas (home, tours, categoría, ficha, blog, legal). |
| Meta robots | OK con 1 excepción | `noindex` solo vía `env('NOINDEX')` global; por página, `index,follow` salvo checkout (`noindex,nofollow`, correcto en intención) — **pero duplicado**, ver **S-06**. |
| Redirecciones | 2 hallazgos | **S-02** (cadena 301→404 interna) y **S-03** (2 de las 26 URLs del plan §F apuntarían a un tour en borrador = 301→404). |
| robots.txt vs. locale `pt` | 1 hallazgo | **S-05**: falta `/pt/checkout` y `/pt/buscar` en el disallow de producción. |

## 2) Datos estructurados (JSON-LD)

- **Product/TouristTrip** (ficha de tour): parsea sin error, `priceCurrency` = **PEN** correcto y coherente con el precio visible y con la BD (confirmado: los 24 tours publicados están en PEN; los 7 tours USD son data demo heredada del fork, `is_published=0`, no entran a sitemap/schema).
- **Organization/TravelAgency** (global): `logo` = `logo-america-original.webp` ✔ (no `logo.png`).
- **BlogPosting** (post): `publisher.logo` = `logo-america-original.webp` ✔, `datePublished`/`dateModified`/`author`/`BreadcrumbList` presentes, parsea sin error.
- Todo el JSON-LD probado (`machu-picchu`, `desayuno-bueno-bonito-y-barato`) es JSON válido (`json_decode` sin `JSON_ERROR`).
- **S-07**: `/llms.txt` (fuera de JSON-LD pero mismo bloque de "datos que los bots leen") anuncia todos los precios con prefijo `US$` cuando la moneda real es PEN — desalineado con el propio schema `Offer.priceCurrency=PEN`.

## 3) hreflang — riesgo real de duplicado (contenido solo ES)

**S-01 (crítico).** El chrome (menú, botones, footer) SÍ está traducido a EN/PT, pero el contenido de fondo (title, meta description, `<h1>`, cuerpo, itinerario) es **idéntico carácter por carácter** en `/es/`, `/en/` y `/pt/` para los 24 tours y los 10 posts de blog probados. hreflang se emite igual en las 3 variantes + `x-default`, reciprocidad correcta a nivel de código, pero le está diciendo a Google "esto es una traducción" cuando es la misma página en español repetida 3 veces bajo 3 URLs distintas. Riesgo: Google puede consolidar señales sobre una sola URL e ignorar las otras dos, o marcarlas como duplicado — y un usuario que elige "English" cae en una ficha con cuerpo en español.

Verificación: `curl` comparando `<title>`, `<meta name="description">` y fragmento del `<body>` de `/es/tours/detalle/machu-picchu` vs `/en/...` vs `/pt/...` → 100% iguales. Mismo resultado en `/es/blog/desayuno-bueno-bonito-y-barato` vs `/en/...` vs `/pt/...`.

## 4) On-page (más allá de lo que ya cubre el CRO)

- Title/H1/meta description: únicos y coherentes en home, nosotros, contacto, blog index, reseñas, términos, privacidad. H1 único (1 solo `<h1>`) confirmado en ficha de tour.
- **S-04 (alto):** el listado de tours (`/tours`, `/tours/categoria/{lima|ica|cusco}`, las 3 locales) arma la meta description con la plantilla `Descubre nuestros tours por :section`, y para el caso sin categoría `:section` se rellena con el propio título de la sección — produce `"Descubre nuestros tours por Descubre las mejores experiencias en Lima."` (ES/EN/PT, mismo patrón). Afecta al snippet de SERP del listado principal de tours.
- **S-08 (bajo):** miniaturas de la galería en ficha de tour con `alt=""` (la imagen principal sí tiene `alt` correcto). Dato heredado de WP (0 alt en origen, documentado en `RECONCILIACION-WP.md`), pero la app puede rellenarlo con el título del tour sin depender de la fuente.

## 5) Plan de 301 (WP → app) — `docs/data/RECONCILIACION-WP.md` §F

Confirmado: **no se puede cerrar el 301 sin el dominio destino** (¿reemplaza al WP en `limaamericatours.com` o vive en otro dominio/subdominio?). Sigue bloqueante para **lanzamiento**, no para este merge.

**Hallazgo nuevo que se suma al bloqueo (S-03):** de las 26 URLs vivas en WP listadas en §F, **2 mapean a slugs que en la app HOY están en borrador** (`ruta-gastronomica-de-barrio-por-lima-street-food-prepara-anticucho-y-degusta-el-pisco-sour` y `recorrido-por-el-casco-historico-de-lima-degustaciones-de-pisco-sour`, ambas con `price=0`, `is_published=0` por la razón ya documentada en el ítem 8 de la Lista de Acción — moneda PEN pendiente de decisión). Si se implementa el 301 masivo del §F tal cual está hoy, esas 2 URLs quedarían **301 → 404**, exactamente el patrón "falso OK" que advierte el playbook. No cerrar esas 2 sin resolver precio/publicación antes.

---

## Tabla de hallazgos

| # | Severidad | Área | Hallazgo | Evidencia | Acción / Dueño | Esfuerzo |
|---|---|---|---|---|---|---|
| S-01 | Crítico | hreflang / duplicado | Contenido 100% idéntico en ES/EN/PT para los 24 tours y 10 posts; hreflang promete traducción que no existe | curl comparando title/meta/body de `/es/` vs `/en/` vs `/pt/` en tour y post de muestra | Decidir: traducir de verdad EN/PT antes de indexar esas rutas, o quitar/`noindex` esas variantes hasta tenerlo — **decide cliente/Anyerson** (ver Conflictos con CRO) · programación ejecuta | Alto (contenido) / bajo (código) |
| S-02 | Crítico | Redirección rota (301→404) | `routes/web.php` L65-66: redirect legacy apunta a slug `tour-privado-huacachina-islas-ballestas-atardecer-buggy-can-am`, inexistente en BD | `curl -L` → 404; `tinker` confirma slug ausente | Cambiar destino a `tour-paracas-ica-huacachina` (equivalente vivo) · programación | Bajo |
| S-03 | Crítico | Plan 301 §F | 2 de las 26 URLs del mapa WP→app apuntan a tours en borrador (`is_published=0`, `price=0`) → 301→404 si se implementa ya | curl a ambas rutas → 404; tinker confirma `is_published=false` | No cerrar el 301 de esas 2 sin resolver precio/publicación antes (o apuntar al listado padre como fallback temporal) · cliente decide precio, programación ejecuta | Bajo (una vez resuelto el precio) |
| S-04 | Alto | On-page / meta description | `/tours` y `/tours/categoria/*` (ES/EN/PT) generan meta description redundante: "Descubre nuestros tours por Descubre las mejores experiencias en Lima." | curl a las 9 combinaciones (3 categorías × 3 locales + genérica) | Reescribir `lang/{es,en,pt}/ui.php:300` (`tours_meta_description`) y/o el valor de `:section` en `resources/views/tours/index.blade.php:12` para el caso sin categoría · programación | Bajo |
| S-05 | Medio | robots.txt | Falta `/pt/checkout` y `/pt/buscar` en el disallow de producción (solo listan `/es/` y `/en/`) | Lectura de `SitemapController@robots` | Añadir las 2 líneas en ambos bloques (general + bots IA) · programación | Bajo |
| S-06 | Medio | Meta robots duplicado | `checkout.blade.php`, `checkout/thanks.blade.php`, `checkout/payment.blade.php` hardcodean su propio `<meta name="robots" content="noindex,nofollow">` ADEMÁS del que ya imprime el layout → 2 tags `<meta name="robots">` en el mismo `<head>` | curl a `/es/carrito` y `/es/checkout/gracias`: 2 matches de `name="robots"` con valores distintos | Quitar el tag hardcodeado de las 3 vistas; usar el mecanismo del layout (o exponer `$noindex` que el layout respete) · programación | Bajo |
| S-07 | Medio | Contenido para bots IA | `/llms.txt` muestra "desde US$500 por persona" para tours cuyo precio real está en PEN (mismo dato que `priceCurrency=PEN` en el schema) | curl `/llms.txt` vs `currency` en BD y en JSON-LD | Usar `$tour->currency` real en `SitemapController@llms` en vez de `'US$'` fijo | Bajo |
| S-08 | Bajo | Imágenes / alt | Miniaturas de galería en ficha de tour con `alt=""` (thumbnail principal sí tiene alt) | curl a `/es/tours/detalle/machu-picchu`, 5 `<img alt="">` | `resources/views/tours/show.blade.php:168` → `alt="{{ $titleDisplay }} - foto {{ $i + 1 }}"` · maquetación | Bajo |

## Matriz Impacto × Esfuerzo

| | Esfuerzo bajo | Esfuerzo alto |
|---|---|---|
| **Impacto alto** | S-02, S-03, S-04, S-06 | S-01 (si se opta por traducir) |
| **Impacto medio** | S-05, S-07 | — |
| **Impacto bajo** | S-08 | — |

Orden sugerido de ejecución: S-02 y S-06 (triviales, código ya existe el patrón correcto) → S-04 → S-05 → S-07 → S-08 → decisión S-01 → cerrar S-03 cuando haya precio/publicación.

## Conflictos con CRO

- **S-01 (hreflang/contenido duplicado) también es un problema de percepción**, no solo técnico: un visitante que cambia el selector de idioma a "English" o "Português" llega a una URL `/en/` o `/pt/` con el menú traducido pero la ficha del tour (título, itinerario, incluye/no incluye) en español. Esto puede leerse como sitio a medio traducir, dañando confianza justo antes de reservar. El CRO debería evaluar si el selector de idioma debe **ocultarse en fichas de tour/blog hasta tener contenido real**, mientras que SEO recomienda no dejar esas URLs indexables tal como están hoy. **No lo resuelvo yo — costo de cada opción:**
  - *Traducir de verdad (24 tours + 10 posts a EN/PT):* mejor SEO y CRO, costo de contenido más alto y no es inmediato.
  - *Ocultar selector EN/PT en tour/blog (dejar el chrome multi-idioma solo en home/nosotros/contacto donde no hay problema de fondo):* barato, resuelve ambos lados, pero reduce la promesa "trilingüe" del sitio.
  - *Dejarlo como está y lanzar:* barato hoy, pero acumula duplicado indexado y fricción de UX — no recomendado.

  Decide Anyerson con el cliente.

## Bloqueos (no pude verificar, no invento)

- **MCP de navegador caído:** no hay verificación de render JS, Core Web Vitals de campo, ni comportamiento visual del selector de idioma. Lo de arriba se apoyó en curl + código, que es determinístico para HTML servido, pero no cubre JS del lado cliente.
- **Dominio destino de producción** para el 301 masivo del §F: sigue sin confirmar (mismo dominio `limaamericatours.com` reemplazando al WP, u otro). Bloqueante de **lanzamiento**, no de este merge.
- **`APP_ENV` real en el servidor de destino:** no verificable desde aquí. Si por error queda en `local`/`staging` en producción, todo el `robots.txt` sale `Disallow: /` y el sitio no se indexa nada — confirmar explícitamente en el checklist de deploy.
- **Duplicado de host (www/no-www, http/https):** no aplica todavía porque el sitio no está en un dominio de producción real; revisar en la pasada de lanzamiento cuando se defina el dominio.

## Veredicto para merge

**APTO para MERGE de código**, con una corrección trivial recomendada antes de mergear (**S-02**, redirect roto — 2 líneas). El resto de hallazgos (S-01, S-04 a S-08) son deuda SEO real pero no rompen build ni funcionalidad; deben resolverse antes del **lanzamiento a producción**, no bloquean este merge. Los bloqueantes verdaderos de lanzamiento siguen siendo: (1) dominio destino para el 301 del §F, (2) resolver S-03 antes de ejecutar ese 301 masivo, y (3) la decisión de negocio sobre S-01 (hreflang/contenido multi-idioma) antes de que Google indexe `/en/` y `/pt/`.
