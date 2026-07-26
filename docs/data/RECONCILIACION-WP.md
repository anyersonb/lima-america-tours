# Reconciliación con producción — Tours (WordPress → app)

**Fecha:** 2026-07-26 · **Origen:** WordPress de `limaamericatours.com` (VPS Contabo, solo lectura) · **Destino:** tabla `tours` de la app Laravel.
**Acceso:** SOLO LECTURA. No se escribió, borró ni migró nada en producción.

> Responde a la pregunta del jefe: **¿qué existe allá y qué de eso trajimos?** No cuenta lo importado, cuenta lo que hay en producción y lo confronta.

---

## A) CENSO (lo que EXISTE en producción)

### A1 — Posts del tipo `tours` por estado
| Estado | Cantidad |
|---|---|
| publish | **26** |
| draft / pending / private / future / trash | **0** |

**Los 26 importados = los 26 `publish`.** No hay tours ocultos en private/pending/draft. Nada que se haya dejado atrás por estado.

### A2 — meta_keys del post type `tours` (distintas, con nº de tours que la usan)
| meta_key | #tours | ¿Importado? |
|---|---|---|
| `_apartment_price` | 26 | ✅ `price` |
| `acerca-del-tour` | 26 | ✅ `description_es` |
| `itinerario` | 26 | ✅ `itinerary_es` |
| `que-incluye` | 26 | ✅ `includes_es` + `excludes_es` |
| `que-llevar` | 26 | ✅ `recommendations_es` |
| `duracion` | 26 | ✅ `duration` |
| `salidas` | 26 | ✅ `departure_time` |
| `dias-de-salida` | 26 | ✅ `notes_es` |
| `pasajeros-minimos` | 26 | ✅ `notes_es` |
| `orden` | 26 | ✅ `order` |
| `imagen-post` | 26 | ✅ `cover_image` |
| `imagen_header` | 26 | ✅ `seo_image` |
| `galeria` | 26 | ✅ `gallery` |
| `foto_1..foto_5` | 26 | ✅ `gallery` |
| `frase-inicial` | 24 | ✅ `subtitle_es` |
| `idiomas` | 24 | ✅ `language` |
| `lugar` (meta) | 24 | ✅ región (+ se usa la **taxonomía** `lugar`, ver A3/E) |
| `descripcion-corta-del-tour` | 11 | ✅ `seo_description` |
| `agendar` | 26 | ⚠️ NO — horas de anticipación de reserva (4/12/18/24). Sin columna. → acción |
| `jet_abaf_price` | 26 | ➖ redundante (`{_apartment_price, _weekend_prices}`; todos los weekend 0/inactivos) |
| `jet_abaf_custom_schedule` | 8 | ➖ plugin JetBooking (agenda) |
| `jet_abaf_configuration` | 3 | ➖ plugin JetBooking (config) |
| `_pricing_rates` | 1 | ⚠️ NO — precio por duración (tour 1199: 2h→S/80, 7h→S/70). → acción (1 tour) |
| `free_tour` / `tipo_de_tour` (meta) | 1 | ➖ legacy de 1 tour; la clasificación real está en la taxonomía `tipo-de-tour` |
| `_aioseo_*` (postmeta) | 18 | ➖ vacíos (title/desc NULL, keywords `a:0:{}`) — ver C |
| `_edit_lock`, `_edit_last`, `_elementor_page_assets`, `_monsterinsights_*`, `_wp_page_template` | 26 | ➖ internos de WP/plugins, irrelevantes |

### A3 — Taxonomías de `tours`
| Taxonomía | #tours | Términos (tours) | ¿Importado? |
|---|---|---|---|
| `lugar` | 26 | Lima(15), Cusco(7), Callao(2), Nazca(1), Paracas(1) | ✅ → `region_id` (E) |
| `tipo-de-tour` | 26 | Personalizado(22), Free Tour(2), Full Day(2) | ➖ sin equivalente en la app (ver C) |
| `viaje-destacado` | 8 | Si(4), No(4) | ✅ → `is_featured` (4 destacados) |

### A4 — Attachments
- Con `post_parent` = un tour: **294**.
- Referenciadas por los campos de imagen (imagen-post, imagen_header, galeria, foto_1-5) y **copiadas: 269**.
- Total de attachments del sitio: 463.
- **Brecha 294 − 269 = 25**: attachments subidos al post pero **no referenciados** en ningún campo de imagen del tour (no se muestran en la ficha). Confirmado que **no hay `<img>` embebidos** en `acerca-del-tour`/`itinerario` (0), así que no se pierde nada visible. Sin acción (documentado).
- **Alt text:** 0 imágenes tienen `_wp_attachment_image_alt` → no había alt que importar.

---

## B) EL DIFF (una fila por campo — nada sin clasificar)

Clasificación: ✅ importado · ➖ irrelevante/plugin/duplicado · ⚠️ **SE NOS PASÓ** (va a la lista de acción).

| Campo de producción | #tours | Ejemplo | ¿Importado? → a dónde | Clasificación |
|---|---|---|---|---|
| _apartment_price | 26 | `360` | ✅ `price` (moneda PEN) | ✅ |
| acerca-del-tour | 26 | `<p>Vive una experiencia…` | ✅ `description_es` | ✅ |
| itinerario | 26 | `<p><strong>3:00 AM:</strong>…` | ✅ `itinerary_es` (parser corregido, ver D) | ✅ |
| que-incluye | 26 | `<ul><li>…</li></ul>…No incluye:` | ✅ `includes_es` + `excludes_es` | ✅ |
| que-llevar | 26 | `<ul><li>Lentes…` | ✅ `recommendations_es` | ✅ |
| duracion | 26 | `17 Horas` | ✅ `duration` | ✅ |
| salidas | 26 | `3:00 am` | ✅ `departure_time` | ✅ |
| dias-de-salida | 26 | `Salidas diarias` | ✅ `notes_es` | ✅ |
| pasajeros-minimos | 26 | `2` | ✅ `notes_es` | ✅ |
| orden | 26 | `3` | ✅ `order` | ✅ |
| frase-inicial | 24 | `Embárcate en una aventura…` | ✅ `subtitle_es` | ✅ |
| idiomas | 24 | `Ingles - Español` | ✅ `language` (normalizado) | ✅ |
| descripcion-corta-del-tour | 11 | `Vive esta mágica…` | ✅ `seo_description` | ✅ |
| imagen-post | 26 | id → url | ✅ `cover_image` (copiada) | ✅ |
| imagen_header | 26 | id → url | ✅ `seo_image` (copiada) | ✅ |
| galeria + foto_1-5 | 26 | ids | ✅ `gallery` (copiadas) | ✅ |
| tax `lugar` | 26 | Lima/Callao/Nazca/Paracas/Cusco | ✅ `region_id` | ✅ |
| tax `viaje-destacado` | 8 | Si/No | ✅ `is_featured` (4) | ✅ (corregido en 2ª pasada) |
| tax `tipo-de-tour` | 26 | Personalizado/Full Day/Free Tour | ❌ sin campo equivalente en la app | ➖ (ver C) |
| `agendar` | 26 | `24` / `24 Horas` | ❌ horas de anticipación de reserva | ⚠️ **acción** |
| `_pricing_rates` | 1 | `[{2h:80},{7h:70}]` | ❌ precio variable por duración | ⚠️ **acción** (1 tour) |
| post_date / post_modified | 26 | `2024-02-13` | ❌ `created_at` quedó = fecha de import | ⚠️ menor |
| jet_abaf_price | 26 | weekend prices 0 | ➖ redundante/vacío | ➖ |
| jet_abaf_custom_schedule / configuration | 8/3 | agenda JetBooking | ➖ plugin de reservas | ➖ |
| _aioseo_* (postmeta + tabla) | 18/20 | title/desc NULL, keyphrases vacías | ➖ sin contenido real (ver C) | ➖ |
| free_tour / tipo_de_tour (meta) | 1 | serializado | ➖ legacy 1 tour | ➖ |
| _edit_*, _elementor_*, _monsterinsights_*, _wp_page_template | 26 | — | ➖ internos WP/plugins | ➖ |
| alt/caption de imágenes | 0 | — | ➖ no existían | ➖ |
| reviews atadas a tour | 0 | — | ➖ las reviews son globales (ver C) | ➖ |

---

## C) SOSPECHOSOS HABITUALES (revisados uno por uno)

| Sospechoso | ¿Existe en WP? | Resultado |
|---|---|---|
| **post_name (slug)** | Sí | ✅ importado tal cual (idéntico al de WP) |
| **fechas publicación/modificación** | Sí (2024-02-13 / 2026-06) | ⚠️ menor: `created_at` quedó = fecha de import, no la original |
| **menu_order** | Sí, pero **todos = 0** | ➖ no se usa; el orden real es la meta `orden` (✅ importada) |
| **SEO Yoast/RankMath** | No (usan **AIOSEO**) | AIOSEO presente pero **title/description/canonical/og todos vacíos**; keyphrases `focus:""`, `additional:[]`. **Nada real que importar.** |
| **campos ACF** | **No** (0 `field_*`, 0 grupos ACF) | ➖ no hay ACF; todo es JetEngine |
| **precios por tipo de pasajero (adulto/niño)** | No como tal | ➖ no existe adulto/niño; solo `_pricing_rates` por **duración** en 1 tour ⚠️ |
| **precio de oferta / tachado** | **No** | ➖ no hay precio de oferta en WP (la app tiene `price_before`, quedaría vacío) |
| **duración estructurada** | Parcial | `duracion` es texto libre (`17 Horas`) → `duration` ✅ |
| **cupos / capacidad** | Solo `pasajeros-minimos` (mínimo) | ✅ a `notes_es`; no hay capacidad máxima en WP |
| **punto y hora de encuentro** | Hora sí (`salidas`), punto no estructurado | ✅ `departure_time`; el punto de recojo va dentro del itinerario |
| **mapa / coordenadas** | **No** | ➖ no existen |
| **tours relacionados** | **No** | ➖ no existen (la app los arma sola) |
| **tour destacado** | Sí (`viaje-destacado`) | ✅ `is_featured` (4) |
| **dificultad / edad mínima / política de cancelación** | **No** | ➖ no existen en WP |
| **traducciones WPML/Polylang** | **No** (solo gtranslate) | ✅ importante: gtranslate traduce **al vuelo**, NO guarda posts EN/PT. **No estamos tirando traducciones** — no existen como data. EN/PT hay que generarlos. |
| **alt / caption de imágenes** | **No** (0) | ➖ nada que importar |
| **reseñas por tour** | CPT `reviews` (13) **global** | ➖ las reviews NO están atadas a un tour (campos: nombre, valoracion, comentario, procedencia, viajo-en). Son testimonios del sitio. Se verán en la fase de reviews, no por tour. |

---

## D) FIDELIDAD (no solo presencia — ¿se cortó contenido?)

Comparación de longitud de caracteres origen (WP, sin HTML) vs importado:

| Tour | desc src→imp | itinerario src→imp | inc/exc |
|---|---|---|---|
| Full day Nazca | 582→583 | 1004→987* | 7/2 |
| Full Day Paracas | 1401→1407 | 3028→3002* | 7/1 |
| Lima Highlights | 224→224 | 2272→**2272** ✅ | 5/0 |
| Machu Picchu (multi-día) | 448→448 | 2987→**2980** ✅ | 8/4 |
| **City Tour Centro (el más largo)** | 1458→1463 | 3216→**3216** ✅ | — |

> \* Diferencias de 1-2% = saltos de línea y normalización de espacios, no pérdida.

**⚠️ HALLAZGO IMPORTANTE (corregido):** en la 1ª pasada, el itinerario salía **7-9% más corto** en los tours multi-día. Causa: esos tours usan **`<h4>` para "Día 1/2" y `<b>` en vez de `<strong>`**, y el parser solo capturaba `<p>/<li>` con `<strong>` → **descartaba en silencio los `<h4>` (intro y días) y no reconocía la hora en `<b>`**. Se corrigió el parser (captura h1-6, reconoce hora por patrón en el texto), se añadió test (rojo antes / verde después) y se reimportó: **fidelidad ahora 100%**.

**Imágenes:** las 269 referenciadas están **copiadas a disco** (`storage/app/public/tours/`) y las URLs de producción responden **HTTP 200** (spot-check, sin 404). Peso total ~50 MB.

---

## E) INFERENCIAS (para que las revise el jefe)

### Región — validada contra la taxonomía `lugar` de WP
El importador ahora usa la **taxonomía `lugar`** como fuente (no adivina): Lima/Callao→**Lima**, Nazca/Paracas→**Ica**, Cusco→**Cusco**. La inferencia por título (fallback) coincidía 100% con la taxonomía. **Sin dudosas.**

Distribución final (26 importados): **Lima 17, Ica 2, Cusco 7**.

### Categoría — INFERIDA (WP no tiene equivalente)
La app clasifica en Culturales / Aventura / Culinarias / Otros; **WP no tiene esa taxonomía** (su `tipo-de-tour` es Personalizado/Full Day/Free Tour, otro eje). Por eso la categoría es una adivinación por palabras clave del título. **Revísala:**

| Tour | Categoría inferida | ¿Dudosa? |
|---|---|---|
| Full day Nazca e Islas Ballestas | Aventura | — |
| Tour Miraflores y Barranco | Culturales | — |
| Tour Callao / Fortaleza Real Felipe | Culturales | — |
| Tour Huaca Pucllana | Culturales | — |
| Tour Museo Larco | Culturales | — |
| Full Day Paracas - Ica - Huacachina | Aventura | ⚠️ ¿o Culturales? |
| Parque de Aguas + Cena y Danzas | Otros | — |
| City Tour Centro Histórico | Culturales | — |
| Tour Pachacamac | Culturales | — |
| City tour + Pisco Sour | Culinarias | ⚠️ es city tour con degustación |
| Islas Palomino (lobos marinos) | Aventura | — |
| Lima Highlights | Culturales | — |
| Machu Picchu | Culturales | — |
| Conoce Machu Picchu (sin entrada) | Culturales | — |
| Montaña Arcoíris 7 Colores | Aventura | — |
| Valle Sagrado | Culturales | — |
| Laguna Humantay | Aventura | — |
| City Tour Cusco | Culturales | — |
| Maras Moray + Cuatrimotos | Aventura | — |
| Ruta Gastronómica por mercados | Culinarias | — |
| City Tour Lima + Gastronómica + Catacumbas | Culinarias | ⚠️ mixto (cultural+culinario) |
| Casa Aliaga y Catacumbas | Culturales | — |
| Traslado aeropuerto → hotel | Otros | — |
| City Tour Huaca Pucllana de noche | Culturales | — |
| Ruta Gastronómica de Barrio (Street Food) | Culinarias | — |
| Recorrido casco histórico + Degustaciones | Culinarias | ⚠️ histórico con degustación |

---

## F) SLUGS Y SEO — mapeo de URLs (posible hallazgo caro)

- **Slug:** ✅ se conservó el `post_name` de WP idéntico. Bien.
- **PERO la estructura de URL cambia:**
  - WP (producción): `http://limaamericatours.com/tours/{slug}/`
  - App nueva: `/{locale}/tours/detalle/{slug}` (p.ej. `/es/tours/detalle/{slug}`)

**Si el sitio nuevo reemplaza al WP en el mismo dominio y el WP está indexado en Google, las 26 URLs cambian → se pierden posiciones sin 301.** Como el slug se conserva, el redirect es un patrón limpio y único:

```
/tours/{slug}/   →   /es/tours/detalle/{slug}   (301)
```

**Acción SEO obligatoria al lanzar:** implementar ese 301 (y su equivalente para las URLs de listado/categoría si las hubiera). Los 26 están **`publish` en WP** (los 2 que en la app quedaron borrador es solo porque tienen precio 0; en WP están vivos e indexables), así que **los 26 necesitan 301**. Mapa completo (mismo slug en ambos, cambia el path):

| slug (igual en ambos) | URL vieja (WP) | URL nueva (app) |
|---|---|---|
| city-tour-centro-historico-de-lima | /tours/city-tour-centro-historico-de-lima/ | /es/tours/detalle/city-tour-centro-historico-de-lima |
| city-tour-cusco | /tours/city-tour-cusco/ | /es/tours/detalle/city-tour-cusco |
| city-tour-en-lima-con-clases-de-pisco | /tours/city-tour-en-lima-con-clases-de-pisco/ | /es/tours/detalle/city-tour-en-lima-con-clases-de-pisco |
| city-tour-en-lima-con-visita-a-la-huaca-pucllana-de-noche | /tours/city-tour-en-lima-con-visita-a-la-huaca-pucllana-de-noche/ | /es/tours/detalle/city-tour-en-lima-con-visita-a-la-huaca-pucllana-de-noche |
| conoce-machu-picchu-si-no-tienes-entrada | /tours/conoce-machu-picchu-si-no-tienes-entrada/ | /es/tours/detalle/conoce-machu-picchu-si-no-tienes-entrada |
| full-day-nazca-e-islas-ballestas-desde-lima | /tours/full-day-nazca-e-islas-ballestas-desde-lima/ | /es/tours/detalle/full-day-nazca-e-islas-ballestas-desde-lima |
| laguna-humantay | /tours/laguna-humantay/ | /es/tours/detalle/laguna-humantay |
| lima-con-sabor-de-city-tour-con-pisco-sour-y-ceviche | /tours/lima-con-sabor-de-city-tour-con-pisco-sour-y-ceviche/ | /es/tours/detalle/lima-con-sabor-de-city-tour-con-pisco-sour-y-ceviche |
| lima-highlights-tour-miraflores-barranco-surquillo-y-centro-de-lima | /tours/lima-highlights-tour-miraflores-barranco-surquillo-y-centro-de-lima/ | /es/tours/detalle/lima-highlights-tour-miraflores-barranco-surquillo-y-centro-de-lima |
| machu-picchu | /tours/machu-picchu/ | /es/tours/detalle/machu-picchu |
| maras-moray-y-minas-de-sal-cuatrimotos | /tours/maras-moray-y-minas-de-sal-cuatrimotos/ | /es/tours/detalle/maras-moray-y-minas-de-sal-cuatrimotos |
| montana-arcoiris-de-7-colores | /tours/montana-arcoiris-de-7-colores/ | /es/tours/detalle/montana-arcoiris-de-7-colores |
| recorrido-por-el-casco-historico-de-lima-degustaciones-de-pisco-sour | /tours/recorrido-por-el-casco-historico-de-lima-degustaciones-de-pisco-sour/ | /es/tours/detalle/recorrido-por-el-casco-historico-de-lima-degustaciones-de-pisco-sour |
| ruta-gastronomica-de-barrio-por-lima-street-food-prepara-anticucho-y-degusta-el-pisco-sour | /tours/ruta-gastronomica-de-barrio-por-lima-street-food-prepara-anticucho-y-degusta-el-pisco-sour/ | /es/tours/detalle/ruta-gastronomica-de-barrio-por-lima-street-food-prepara-anticucho-y-degusta-el-pisco-sour |
| ruta-gastronomica-por-lima | /tours/ruta-gastronomica-por-lima/ | /es/tours/detalle/ruta-gastronomica-por-lima |
| tour-a-miraflores-y-barranco-la-lima-moderna-y-bohemia | /tours/tour-a-miraflores-y-barranco-la-lima-moderna-y-bohemia/ | /es/tours/detalle/tour-a-miraflores-y-barranco-la-lima-moderna-y-bohemia |
| tour-callao | /tours/tour-callao/ | /es/tours/detalle/tour-callao |
| tour-casa-aliaga-y-catacumbas-en-lima | /tours/tour-casa-aliaga-y-catacumbas-en-lima/ | /es/tours/detalle/tour-casa-aliaga-y-catacumbas-en-lima |
| tour-huaca-pucllana | /tours/tour-huaca-pucllana/ | /es/tours/detalle/tour-huaca-pucllana |
| tour-islas-palomino | /tours/tour-islas-palomino/ | /es/tours/detalle/tour-islas-palomino |
| tour-museo-larco | /tours/tour-museo-larco/ | /es/tours/detalle/tour-museo-larco |
| tour-pachacamac | /tours/tour-pachacamac/ | /es/tours/detalle/tour-pachacamac |
| tour-paracas-ica-huacachina | /tours/tour-paracas-ica-huacachina/ | /es/tours/detalle/tour-paracas-ica-huacachina |
| tour-parque-de-aguas-cena-show | /tours/tour-parque-de-aguas-cena-show/ | /es/tours/detalle/tour-parque-de-aguas-cena-show |
| traslado-en-auto-desde-el-aeropuerto-al-hotel | /tours/traslado-en-auto-desde-el-aeropuerto-al-hotel/ | /es/tours/detalle/traslado-en-auto-desde-el-aeropuerto-al-hotel |
| valle-sagrado-de-los-incas | /tours/valle-sagrado-de-los-incas/ | /es/tours/detalle/valle-sagrado-de-los-incas |

> **Sin resolver todavía (necesito de ti):** ¿el sitio nuevo vive en el **mismo dominio** `limaamericatours.com` reemplazando al WP, o en otro dominio/subdominio? De eso depende si el 301 se hace en el WP saliente, en el `.htaccess`/OLS del server, o en `Route::fallback` de la app. También hace falta el **listado de URLs de categoría/`lugar`** (`/tours/lugar/{term}/`, etc.) que WP también indexa. Eso lo cierro cuando me confirmes el dominio de destino.

---

## LISTA DE ACCIÓN — solo lo clasificado como "SE NOS PASÓ"

| # | Ítem | Alcance | Estado |
|---|---|---|---|
| 1 | **Itinerario truncado** (multi-día, `<h4>`/`<b>`) | Parser + reimport + test | ✅ **HECHO** |
| 2 | **`is_featured`** desde `viaje-destacado` (4 tours) | Columna existente + reimport + test | ✅ **HECHO** |
| 3 | **Región** desde taxonomía `lugar` (no adivinar) | Reimport + test | ✅ **HECHO** |
| 4 | **`agendar`** = horas de anticipación de reserva (26 tours) | Requiere **columna nueva** + Filament + ficha | ⏳ propuesto — espera OK del jefe |
| 5 | **`_pricing_rates`** = precio por duración (1 tour) | Requiere columna/feature; solo 1 tour | ⏳ propuesto — bajo valor |
| 6 | **`created_at`** = fecha original de publicación | Menor; afecta orden "más nuevos" | ⏳ opcional |
| 7 | **301** URLs viejas `/tours/{slug}/` → nuevas | SEO al lanzar (no es de data) | ⏳ va al plan SEO |
| 8 | **Moneda PEN** (precios en soles; PayPal no admite PEN) | Ver `docs/pagos/PLAN-PASARELAS.md` §13 | ⏳ decide Anyerson |

**Nota:** ítems 1-3 ya aplicados con el importador idempotente (`php artisan tours:import-wp`), sin borrar data. Los ítems 4-6 necesitan tu visto bueno porque agregan esquema/UI. El 7 y 8 son de otras fases (SEO / pasarelas).
