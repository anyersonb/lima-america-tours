# Inventario de diseño — Ficha de Tour y Artículo de Blog

Rama `feat/mockups-ago-2026`. Trabajo documental, sin código ni navegador (Playwright
compartido con otro agente en paralelo). Todo lo afirmado como "existe" se verificó
leyendo el archivo y línea citados; lo que no se pudo verificar se marca como tal.

Mockups:
- Tour: `WhatsApp Image 2026-08-17 at 23.53.40.jpeg` — tour real `city-tour-centro-historico-de-lima`
  ($69, duración "4 horas", 10 fotos en `gallery`, `group_type = "Grupal"`, sin `comparison`
  activo, sin FAQs cargadas — verificado con `Tour::where('slug', ...)->first()`).
- Blog: `WhatsApp Image 2026-08-17 at 23.53.39 (1).jpeg` — el post "Sabores que cuentan
  historias" **no existe** en los 12 posts de la BD (10 publicados + 2 en borrador,
  contados con `SELECT COUNT(*), SUM(is_published=1) FROM blog_posts` — la tabla no
  tiene `deleted_at`, así que el total es el total). Ni el título, ni el autor "Augusto – Guía Local", ni
  la cita, ni los 4 features son contenido real: todo el copy de esta pantalla es de
  la maqueta. El post real más cercano por categoría es "Mejores Restaurantes en Lima"
  (`mejores-restaurantes-en-lima-guia-gastronomica-para-viajeros`, Gastronomía, 4 min).

---

# 1. FICHA DE TOUR

## A. Anatomía (arriba a abajo)

1. Breadcrumb (Inicio · Tours · título)
2. Hero de galería: foto grande + badge ribbon "Más vendido" + flechas + paginación +
   overlay de título/rating/ubicación + botón "Ver video" (superpuesto sobre la foto,
   en el mockup)
3. Barra oscura de 5 datos: duración / opciones (grupal-privado) / idiomas / dificultad /
   cancelación
4. Tira de miniaturas (8 visibles, la última "+18 Ver más fotos")
5. Fila de 3 cajas: "Lo más destacado" / mapa del recorrido / reseña destacada
6. Descripción + Incluye (dos columnas, siempre visibles en el mockup)
7. Caja "¿Tienes dudas?" con CTA de WhatsApp
8. Franja de 4 garantías
9. Columna derecha sticky: precio "Desde $69" · selector Grupo/Privado · fecha ·
   contador de viajeros · precio total · "Reservar ahora" · "Reserva segura" ·
   "También te puede interesar" (3 tours)
10. Barra fija inferior en móvil (no está en el mockup pero ya existe en producción)

## B. Tabla de componentes

| # | Componente | Clase CSS | Archivo:línea | Estado | Fuente de dato | Responsive | Horas |
|---|---|---|---|---|---|---|---|
| 1 | Breadcrumb | `.lat-crumb-bar` `.lat-crumb` | `tours/show.blade.php:152-162`, `_lat-tour.scss:9-41` | EXISTE | ruta + `$titleDisplay` | Truncado con ellipsis <768 (ya resuelto, hallazgo #9 histórico) | 0 |
| 2 | Slider principal (flechas/paginación/swipe) | `.lat-gal__main` `.lat-gal__nav` `.lat-gal__dots` | `tours/show.blade.php:180-217`, `_lat-tour.scss:61-139` | EXISTE | `$tour->gallery_urls` (`Tour::getGalleryUrlsAttribute`, `Tour.php:137-145`) | aspect-ratio 4/3 <640, dots ocultos <900 (swipe/flechas) | 0 |
| 3 | Badge "Más vendido" como **ribbon sobre la foto** | — (hoy es `.lat-detail-badge` inline, no ribbon) | actual: `tours/show.blade.php:266-268`; mockup pide overlay top-left sobre `.lat-gal__main` | ADAPTAR — reposicionar como ribbon absoluto sobre la imagen, reutilizando el mismo patrón `.lat-tcard__badge` que ya usa el listado (comentario en `_lat-tour.scss:299-301`) | `tour->badge_text` / `badge_type` (ya existen, ya se usan en listado) | Ribbon visible en los 4 breakpoints, tamaño reducido <480 | 1.5 |
| 4 | Overlay de título+rating+ubicación **sobre la foto** | — (hoy fuera de la galería, fondo claro) | mockup: overlay oscuro degradado sobre `.lat-gal__main`; actual: `tours/show.blade.php:263-288` fuera de la galería | ADAPTAR — decisión de diseño, ver sección H. Mover eyebrow/H1/estrellas dentro de un scrim sobre la imagen implica reestructurar el orden actual (hoy: galería → info bar → título; mockup: imagen con título encimado → barra oscura de datos → miniaturas) | mismos campos ya usados (`$titleDisplay`, `rating`, `reviews_count`) | Scrim debe mantener AA con foto variable (ver sección F) | 4 |
| 5 | "Lima, Perú" (ubicación con pin) | nuevo, ninguna clase hoy | no existe en `tours/show.blade.php` | NUEVO (markup) | `$tour->region->name` (relación `Tour::region()`, `Tour.php:73-76`, ya existe) + ", Perú" estático | una línea, no rompe en ningún breakpoint | 0.5 |
| 6 | Botón "Ver video" sobre el hero | — | no existe en la ficha; el patrón completo **ya existe en Home** (`home.blade.php:203-233` normaliza YouTube/Vimeo a embed, `home.blade.php:872-887` modal `.lat-video-modal`, CSS en `_lat-home.scss:809+`) | ADAPTAR — reutilizar el modal y el normalizador de URL de Home, no reconstruirlo. Falta el dato: no hay columna de video por tour | `tours.video_url` **no existe** — propuesta en sección D | Modal ya probado en los 4 breakpoints en Home | 2.5 (reutilizando; sería 6+ si se construye de cero) |
| 7 | Barra oscura de 5 datos (duración/opciones/idiomas/dificultad/cancelación) | hoy `.lat-detail-info` (tarjeta blanca con sombra) | actual: `tours/show.blade.php:247-261`, `_lat-tour.scss:242-260`; mockup: barra oscura pegada al borde inferior de la foto | ADAPTAR — 2 de los 5 datos no existen hoy: "Opciones" (Grupal/Privado, hoy el campo es `group_type` con label erróneo "Tamaño del grupo") y "Dificultad" (no existe columna). El de "Salidas" (departure/return_time) del actual no está en el mockup — se reemplaza por "Cancelación" (ya se muestra en otro lugar, línea 278-281) | `duration`, `group_type` (relabel), `language`; dificultad **NUEVO campo** | flex-wrap ya resuelto | 3 (incluye migración de dificultad, ver sección D) |
| 8 | Tira de miniaturas (8 + "+18 ver más") | `.lat-gal__thumbs` `.lat-gal__thumb` | `tours/show.blade.php:209-216`, `_lat-tour.scss:141-168` | EXISTE — 4 columnas en todos los anchos ya corregido (comentario `_lat-tour.scss:143-147`). El tour real tiene 10 fotos en `gallery`, no 26/+18 como el mockup | ADAPTAR trivial: el contador "+N ver más fotos" ya existe como botón (`galMoreBtn`, línea 201-204) pero no imprime el número real de fotos restantes — hoy es texto fijo | `count($galleryUrls) - 7` | ya responsive | 0.5 |
| 9 | Lightbox de galería | `.lat-lightbox` | `tours/show.blade.php:223-245`, `_lat-tour.scss:172-239` | EXISTE, con foco atrapado y teclado (líneas 742-796 del JS) | — | overlay a pantalla completa en todos los anchos | 0 |
| 10 | "Lo más destacado" (checklist 4 ítems) | nuevo | mockup only; hoy la ficha no tiene esta caja (los 3 "highlights" hardcodeados de `tours/show.blade.php:318-322` son un texto fijo distinto, no administrable — defecto ya existente, ver sección G) | ADAPTAR — reutilizar los títulos de los primeros pasos de `$itinerary` (`tours/show.blade.php:9-24`, ya viene del CMS) como lista corta, en vez de crear un campo nuevo | `itinerary_{locale}` (ya existe), primeros 4 pasos | tarjeta se apila con las otras 2 <980 | 2 |
| 11 | Mapa del recorrido (imagen + botón "Ver mapa") | nuevo | no existe ningún campo de coordenadas ni de imagen de mapa en `tours` | NUEVO — no hay `lat`/`lng` por parada ni en el tour ni en `itinerary_es` (`Tour.php` migración, sin columnas de geo). Construir un mapa interactivo real es alcance de otro proyecto; la opción honesta v1 es una **imagen estática subida por el cliente** (screenshot de Google Maps con la ruta dibujada, como ya hacen otras agencias) | `tours.route_map_image` — propuesta en sección D | imagen con `object-fit`, sin mapa real no hay interacción que romper | 3 (solo el campo + render de imagen; 15+ si se pide mapa interactivo real con Google Maps JS API) |
| 12 | Reseña destacada (comillas, avatar, nombre, país, estrellas) | nuevo, estilo distinto de `.lat-review` | dato ya disponible: `$tourReviews` (`TourController.php:75-78`, filtra `testimonials()->where('is_active', true)`) — el mismo query que alimenta el listado de abajo (`tours/show.blade.php:445-469`) | ADAPTAR — es el mismo dato (`Testimonial` con `avatar`, `country`, `name`, `rating`, `quote_es/en`, migración `create_testimonials_table`), solo falta una variante visual de tarjeta tipo cita destacada (comillas rojas grandes) en vez de la lista actual | `$tourReviews->first()` (o filtrar por `is_featured`) | tarjeta se apila con las otras 2 <980 | 1.5 |
| 13 | Descripción — **siempre visible**, dos columnas con Incluye | hoy dentro del tab "Acerca del Tour" (oculto detrás de clic en mobile/tabs en desktop) | `tours/show.blade.php:305-324`, `_lat-tour.scss:394-428` | ADAPTAR — decisión de diseño, ver sección H: el mockup no muestra tabs en absoluto para Descripción/Incluye; el sistema actual de tabs/acordeón (`.lat-tabs-wrap`, 302-412) es funcional, accesible (`role="tab"`, `aria-selected`) y ya cubre Itinerario/Qué llevar/Notas además. Aplanar solo estos dos paneles rompe la simetría del componente de tabs | `tour->description`, `includes_{locale}` (ambos ya existen) | dos columnas ≥768, una columna <768 | 3 |
| 14 | Incluye (columna derecha del bloque anterior) | `.lat-tab-panel[data-tab=incl]` | `tours/show.blade.php:356-381` | ADAPTAR (mismo cambio que #13, es la otra mitad del mismo bloque) | `includes_{locale}` / `excludes_{locale}` | ver #13 | incluido en #13 |
| 15 | Caja "¿Tienes dudas?" + CTA WhatsApp | nuevo componente, patrón ya usado en Contacto | no existe en la ficha; el patrón `wa.me/{{ $waPhoneDigits }}` ya existe en `contact.blade.php:23,345` vía `Setting::whatsappNumber()` (`Setting.php:65-73`) | ADAPTAR — reutilizar el helper, solo falta el partial en la ficha | `Setting::whatsappNumber()` (ya existe, ya cargado: `51957299438`) | ancho completo <640, dos columnas ≥640 | 1 |
| 16 | Franja de garantías (4 sellos) | `.lat-guarantee` `.lat-gt` | `tours/show.blade.php:570-589`, reutiliza componente de Home | EXISTE la mecánica; **2 de los 4 textos violan las restricciones**: "Atención al cliente 24/7" (`tours/show.blade.php:585-586`) ya es un defecto publicado hoy — el horario real es Lun-Vie 9-19 | `Setting::contactHours($locale)` (`Setting.php:109-117`, ya existe) reemplaza el texto fijo | grid ya responsive | 0.5 |
| 17 | Caja de precio — header "Desde $69" | `.lat-book-head` `.lat-amt` | `tours/show.blade.php:476-486`, `_lat-tour.scss:511-539` | EXISTE | `Money::format($tour->price, Money::site())` | sticky ≥980, estático <980 | 0 |
| 18 | Selector "Grupo / Privado" | nuevo | no existe ningún control de modalidad en el formulario (`tours/show.blade.php:507-536`) | NUEVO — y **bloqueado por el modelo de datos**: `tours.price` es una sola columna (`create_tours_table.php`, `decimal('price',10,2)`), `group_type` es texto libre descriptivo ("Grupal"), no un enum con precio propio. Construir el selector sin dos precios reales sería fingir una opción que no cambia nada — decisión para el jefe, ver sección H | requiere `tours.price_private` (propuesta en sección D) | selector de 2 botones, apilable <480 | 4 (UI) + depende de la migración de precio |
| 19 | Selector de fecha | `.lat-book-field` `#bkDate` | `tours/show.blade.php:518-525`, ya valida fechas bloqueadas (JS 826-871) | EXISTE | `BlockedDate::blockedDatesFor/blockedWeekdaysFor` | — | 0 |
| 20 | Contador de viajeros | `#bkPax` (hoy `<select>` 1-8, no un contador +/-) | `tours/show.blade.php:507-517` | ADAPTAR — el mockup pide un contador +/- (dos botones alrededor del número); hoy es un `<select>`. Cambio de UI, mismo dato (`adults`) | `adults` (ya validado en `CartItemRequest`) | — | 1.5 |
| 21 | Precio total | `.lat-book-total` `#bkTotal` | `tours/show.blade.php:526-529`, cálculo real en JS (813-824) | EXISTE — el cálculo es real (precio único × pax), no es una cifra inventada. Pero ver restricción #2: mostrar un monto exacto junto a "Reservar ahora" puede leerse como "vas a pagar esto ahora". El flujo real (`CartController::store`, `CartController.php:108-161`) solo agrega al carrito; el pago online depende de `$onlinePayment` (`CartController.php:80-88`), hoy `false` porque `paypal_client_id`/`culqi_public_key` son de prueba | ADAPTAR — agregar la misma aclaración que ya usa la barra fija inferior ("Reserva ahora y paga después", `tours/show.blade.php:604`) junto al total, para que no lea como checkout | 0.5 |
| 22 | "Reservar ahora" | `.lat-btn.lat-btn--red` | `tours/show.blade.php:536` | EXISTE | — | ancho completo | 0 |
| 23 | "Pago 100% seguro" / "Reserva segura" | `.lat-book-secure` | `tours/show.blade.php:537-540` | **BLOQUEADO** — implica una pasarela de pago que no está activa (`paypal_client_id='test'`, `culqi_public_key='pk_test_...'`). Reemplazo honesto: cuando `!$onlinePayment` (que es el caso hoy), usar copy tipo "Confirmamos por WhatsApp o correo, sin cobro inmediato"; cuando `$onlinePayment` sea `true` en el futuro, sí vale "Pago 100% seguro". El propio `CartController` ya calcula esa bandera (línea 86-88); falta pasarla a esta vista y bifurcar el copy | `$onlinePayment` (ya calculado en otro controlador, falta exponerlo aquí) | — | 1 |
| 24 | "También te puede interesar" (3 tours) | `.lat-more-tours-card` `.lat-mini-tour` | `tours/show.blade.php:544-562`, `_lat-tour.scss:611-634` | EXISTE. El mockup muestra tours que no existen o con precio incorrecto: "Tour a Miraflores y Barranco $49" (real: **$45**, `tour-a-miraflores-y-barranco-la-lima-moderna-y-bohemia`), "Catacumbas de San Francisco $30" y "Tour Nocturno de Lima $45" (**ninguno de los dos existe** en el catálogo de 24 tours publicados) | `$related` (ya resuelto en el controlador con tours reales) | — | 0 (el componente ya sirve datos reales; el mockup solo mostraba ejemplos inventados) |
| 25 | Rating agregado del tour (4.9, 238 reseñas) | `.lat-stars` | `tours/show.blade.php:269-277` | **BLOQUEADO / defecto ya publicado** — `rating` viene sembrado en `4.8` para los 24 tours (valor uniforme, no medido) y `reviews_count = 0` en todos. Mostrar "4.9 (238)" es inventado; mostrar "4.8 (0)" (lo que hace el código hoy) es técnicamente real pero engañoso (0 reseñas con 4.8 estrellas no tiene sentido). Reemplazo honesto: si el tour no tiene testimonios propios (`$tourReviews`), ocultar el rating agregado del tour y mostrar solo el agregado del sitio ("5.0 · 13 reseñas", vía `ReviewAggregator`, ya usado en Home) con enlace a `/resenas` | `Testimonial` agregado real (13 activos, promedio 5.0) en vez de la columna sembrada | — | 2 (lógica de fallback + test de regresión, mismo patrón que `OfferPriceIsNotSeededTest`) |
| 26 | Barra fija inferior (móvil) | `.lat-sticky-book` | `tours/show.blade.php:601-618`, `_lat-tour.scss:739-821` | EXISTE, no está en el mockup pero ya funciona en producción — no tocar | — | <1024 | 0 |

## C. Tokens nuevos

Ninguno estrictamente nuevo. Todo lo que pide el mockup se resuelve con tokens ya
definidos en `_variables.scss`: el scrim oscuro sobre la foto puede usar
`rgba($lat-ink, .55..92)` (mismo patrón que `.lat-gal__nav`, línea 91, y `.lat-lightbox__backdrop`,
línea 184); el verde de "cancelación gratuita" ya existe como color de superficie
puntual (`#e7f6ec`/`#128a4b`, usado en `.lat-badge-free`, línea 280-286, y en los
íconos de check de `.lat-tab-panel ul svg`, línea 406) — no está en `_variables.scss`
como variable nombrada, pero tampoco lo exige el mockup nuevo (ya se repite igual en
producción). No se propone nombrarlo aparte solo por este lote.

## D. Migraciones y campos de CMS necesarios

| Tabla | Columna | Tipo | Consumido por |
|---|---|---|---|
| `tours` | `difficulty` | `string nullable` (o `enum` corto: fácil/moderado/difícil) | Barra de 5 datos, item "Dificultad" (#7) |
| `tours` | `video_url` | `string nullable` | Botón "Ver video" (#6) — mismo patrón de normalización que `home_hero_video_url` (`home.blade.php:214-233`); recomendado extraer esa lógica a `App\Support\VideoEmbed::normalize()` para no duplicar el bloque de regex en el modelo `Tour` |
| `tours` | `route_map_image` | `string nullable` (upload) | Caja "Mapa del recorrido" (#11) — v1 honesto sin mapa interactivo real |
| `tours` | `price_private` | `decimal(10,2) nullable` | Selector "Grupo / Privado" (#18) — **sin esta columna el selector no puede cambiar nada real**; ver decisión en sección H antes de construir la UI |
| `tours` | (relabel, sin migración) | — | El campo `group_type` ya existe; solo cambia el label de "Tamaño del grupo" a algo coherente con su contenido real ("Grupal"/"Privado"/"Grupal o Privado") |

Nada de esto toca `blog_posts` (sección aparte más abajo). El campo `booking_advance_hours`
y las fechas bloqueadas ya existen y no requieren nada nuevo.

## E. Assets requeridos

- **Fotos de galería**: el tour real ya tiene 10 en `gallery` (servidas por
  `ImagePath::url()`, verificado con `Tour::getGalleryUrlsAttribute`, `Tour.php:137-145`).
  Suficiente para la tira de 8 + "ver más"; no hace falta pedir nada nuevo para este
  tour puntual. Para el patrón general, mínimo recomendado: 8 fotos ≥1200px de ancho
  (la miniatura pide 200×150 renderizado, `tours/show.blade.php:212`).
- **Imagen del mapa del recorrido**: 1 por tour, formato horizontal, ≥1000×700 — no hay
  nada hoy en `public/storage` que sirva (no existe ningún archivo con nombre "mapa"
  o "route" en el disco de tours revisado).
- **Ícono de "Ver video"**: ya existe como SVG inline reutilizable del modal de Home
  (`home.blade.php`, sin archivo de imagen que pedir).
- **Ninguna foto nueva de terceros involucrada** en esta pantalla (a diferencia del
  blog).

## F. Riesgos de contraste

| Elemento | Fondo | Ratio estimado | Token que sí cumple |
|---|---|---|---|
| Título + rating overlay sobre la foto (si se implementa #4) | foto variable por tour, sin control de luminosidad | **No verificable sin foto fija** — mismo riesgo que documentó `_lat-blog.scss:28-36` para el hero de blog (4.36:1 real medido, insuficiente). Requiere el mismo `::before` de refuerzo de oscurecimiento que ya usa `.lat-blog-hero` (línea 37-44), no asumir que el degradado genérico basta | `rgba($lat-ink, .68→.4→0)` verificado en el hero de blog, o más oscuro si la foto es muy clara — medir por tour, no una vez |
| Ribbon "Más vendido" sobre foto | rojo `$lat-red` sobre foto oscurecida, con fondo propio (no transparente) | Alto — mismo componente que ya pasa AA en el listado (`.lat-tcard__badge`), fondo sólido rojo + texto blanco = **~5.9:1** (rojo `#cb101e` vs blanco), no depende de la foto | ya resuelto, reusar tal cual |
| Barra oscura de 5 datos (si se hace oscura, item #7) | si se implementa como banda `$lat-ink` sólida (no sobre foto) | blanco sobre `$lat-ink` (`#171412`) = **~15.6:1**, sobrado | `$lat-ink` + texto blanco, sin depender de contraste sobre foto |
| Badge de "cancelación gratuita" verde | `#e7f6ec` / `#128a4b` | ya en producción, no medido en este lote pero es el mismo patrón repetido sin incidentes reportados | sin cambios |

## G. Reutilización

La ficha de tour **ya está construida en un ~70%** frente al mockup. Lo que sirve sin
tocar:
- Slider de galería completo (flechas, swipe, dots, lightbox con foco atrapado).
- Sistema de tabs/acordeón responsive (`.lat-tabs-wrap`) — funcional y accesible; solo
  se discute si Descripción/Incluye deben salir de ahí (sección H), no si el mecanismo
  funciona.
- Caja de reserva sticky, validación de fechas bloqueadas, cálculo de total.
- Franja de garantías, barra fija inferior móvil.
- Datos de tours relacionados (ya reales, el mockup mostraba ejemplos inventados).
- El modal "Ver video" de Home es 100% reutilizable para el botón nuevo de la ficha —
  no construir un segundo modal.
- El bloque comparativo `<x-tour-comparison>` y las reseñas del tour ya están cableados
  (hallazgos #2 y #3 de una ronda de QA anterior, comentarios en
  `tours/show.blade.php:432-439` y `441-469`) — el mockup no los muestra pero no hay
  que tocarlos.

Lo que de verdad falta (no es solo estilo): selector Grupo/Privado (bloqueado por
modelo de precio), mapa del recorrido (bloqueado por falta de coordenadas/imagen),
campo de dificultad, campo de video por tour, reposicionar el rating agregado
(defecto de datos, no de maquetación).

## H. Decisiones que no me corresponden

1. **Rediseño del hero (overlay de título sobre la foto) vs. mantener el layout actual
   (título fuera de la imagen).** El mockup pide el primero; hoy funciona el segundo,
   ya probado en 4 breakpoints. Cambiar implica reordenar HTML/CSS de un bloque que
   funciona y volver a medir contraste por cada foto de portada nueva que cargue el
   cliente (mismo tipo de deuda que ya se documentó para el hero de Home, `ESTADO.md`
   punto 6). Decide el jefe si el costo visual vale la deuda de mantenimiento.
2. **Aplanar Descripción/Incluye fuera del sistema de tabs**, tal como lo muestra el
   mockup, vs. mantenerlos dentro de `.lat-tabs-wrap` junto con Itinerario/Qué
   llevar/Notas (funciona hoy, es accesible, cubre 5 secciones no solo 2). Aplanar solo
   dos de cinco secciones rompe la simetría del componente.
3. **Selector Grupo/Privado**: construir la UI sin que cambie el precio (decorativo) o
   pedir primero la columna `price_private` y la regla de negocio de cuándo un tour
   admite modalidad privada. Construir la UI antes de tener el dato real repite el
   patrón ya señalado como defecto en este proyecto (cifras/opciones sin respaldo).
4. **Mapa del recorrido**: imagen estática subida por el cliente (barato, v1) vs. mapa
   interactivo real con coordenadas por parada (Google Maps JS API, requiere ampliar
   `itinerary_es/en/pt` con lat/lng por paso, alcance de otra tarea).
5. **Rating agregado del tour en `4.8`/`0 reseñas` sembrado**: es un defecto que ya se
   publica hoy, no algo que introduzca este lote — pero corregirlo (ocultar cuando no
   hay testimonios propios) es una decisión de producto, no solo de maquetación.

## Total de horas — Ficha de Tour

**≈ 32 horas** (suma de la columna Horas; no incluye el mapa interactivo real ni
cualquier trabajo de backend fuera de las migraciones listadas).

---

# 2. ARTÍCULO DE BLOG

## A. Anatomía (arriba a abajo)

1. Breadcrumb
2. Hero split: foto a la derecha con degradado hacia el texto, botón de play circular,
   anotación manuscrita con flecha
3. Eyebrow + H1 a dos líneas + bajada
4. Línea de autor: avatar, nombre, rol, badge verificado, fecha, minutos de lectura,
   categoría
5. Tarjeta blanca flotante con 4 features (ícono + título + texto)
6. Cuerpo del artículo a dos columnas con cita destacada (comillas rojas)
7. Sidebar: caja rosa "¿Listo para vivirlo?" + CTA + tira de avatares + cifra
8. Grid de 3 artículos relacionados

## B. Tabla de componentes

| # | Componente | Clase CSS | Archivo:línea | Estado | Fuente de dato | Responsive | Horas |
|---|---|---|---|---|---|---|---|
| 1 | Breadcrumb | `.lat-crumb` con microdatos `BreadcrumbList` | `blog/show.blade.php:79-96` | EXISTE | — | — | 0 |
| 2 | Hero split (foto derecha + degradado + texto izquierda) | nuevo (`.lat-post-head` hoy es una banda plana sin foto, `blog/show.blade.php:101-134`) | actual: `_lat-blog.scss:364-393`; el patrón de foto+degradado **sí existe**, pero en el listado (`.lat-blog-hero`, `_lat-blog.scss:24-53`, con refuerzo de oscurecimiento medido en píxeles) | ADAPTAR/NUEVO — reutilizar la estructura de `.lat-blog-hero` (degradado + refuerzo) en vez de crear un tercer patrón de hero. Pendiente de la decisión de tema, ver sección H | `$post->cover_url`, `$post->title`, `$post->excerpt` (ya existen) | el mockup corta el texto a la izquierda cuando la foto ocupa toda la derecha; hay que probar en 390 que la foto no se coma el texto | 6 |
| 3 | Botón de play circular sobre la foto | nuevo | no existe en el artículo | **BLOQUEADO por asset** (ver #4) + el patrón de modal ya existe en Home (`home.blade.php:872-887`) — mismo caso que el video de tour: reutilizar el modal, falta el campo | `blog_posts.video_url` — propuesta en sección D | — | 2 (reutilizando el modal) |
| 4 | Foto del hero (Mitsuharu Tsumura + logo MAIDO) | — | mockup only | **BLOQUEADO — derechos de terceros.** Persona real identificable con marca de un tercero (MAIDO) visible. No se publica sin autorización escrita del restaurante y de la persona. Se necesita: (a) autorización firmada, o (b) una foto propia del catálogo de gastronomía sin rostro identificable de un tercero ni logo ajeno | `cover_image` del post (ya existe la columna; falta el asset lícito) | — | 0 (bloqueado, no es trabajo de maquetación) |
| 5 | Anotación manuscrita con flecha | nuevo decorativo | no existe | NUEVO — decorativo, SVG inline (mismo criterio ya usado para el motivo de Nazca del CTA de Home, sin foto ni asset de terceros, `ESTADO.md` lote 2026-08-14 §4). Baja prioridad, opcional | estático | oculto <768 recomendado (ilegible a ese tamaño) | 1.5 |
| 6 | Eyebrow con ícono circular + categoría | `.lat-post-badge` (hoy solo texto, sin ícono) | `blog/show.blade.php:103-107`, `_lat-blog.scss:370-376` | ADAPTAR — agregar el ícono circular decorativo que pide el mockup; el dato (`$post->category`) ya existe y ya enlaza al filtro del listado | `$post->category` | — | 0.5 |
| 7 | H1 a dos líneas | `.lat-post-title` | `blog/show.blade.php:109`, `_lat-blog.scss:378-386` | EXISTE — **nota de tipografía obligatoria**: el mockup usa una serif editorial para el titular; producción usa Raleway (sans, 800/900) para todos los `h1` del sitio, verificado en el navegador (`_variables.scss:16-32`, `ESTADO.md` §1). Este titular **se traduce a Raleway 800-900**, con el mismo tratamiento de color/tracking que ya usa el resto del sitio — no se introduce una serif nueva solo para este artículo | `$post->title` | — | 0 (ya resuelto por el token existente, solo hay que no desviarse) |
| 8 | Bajada (excerpt) | dentro de `.lat-post-head` | `blog/show.blade.php` (no impreso hoy — **el excerpt no se muestra en el artículo actual**, solo se usa para meta description, línea 10) | ADAPTAR — falta imprimir `$post->excerpt` visible en el header, hoy solo se usa como `<meta description>` | `$post->excerpt_{locale}` (ya existe, solo falta imprimirlo) | — | 0.5 |
| 9 | Línea de autor: avatar + nombre + rol + verificado | `.lat-post-meta` (hoy solo texto, sin avatar/rol/verificado) | `blog/show.blade.php:111-115`, `_lat-blog.scss:388-393` | **ADAPTAR con bloqueo de dato** — `blog_posts.author_name` es texto plano (`create_blog_posts_table.php`), sin foto ni rol; los 10 posts publicados tienen **todos** `author_name = "Lima América Tours"`. Ninguno dice "Augusto – Guía Local". El proyecto ya tiene un modelo `Guide` con foto/rol/bio (`create_guides_table.php`) construido para Nosotros — la solución correcta es enlazar el post a un `Guide` en vez de inventar un campo de foto nuevo en `blog_posts` | `blog_posts.guide_id` (nullable FK) — propuesta en sección D; hoy **ningún post tiene autor real distinto de "Lima América Tours"**, así que el componente se oculta hasta que el cliente asigne autor | avatar 40px, colapsa a solo nombre <480 si no hay foto | 3 (relación + fallback + vista) |
| 10 | Badge de verificado (check azul) | nuevo | no existe ningún campo de "autor verificado" | NUEVO — y cuestionable: no hay noción de "verificación" en el modelo `Guide` ni en `blog_posts`. Si el autor es un `Guide` real del equipo, ya es "verificado" por definición (es del staff) — no hace falta un booleano nuevo, se puede mostrar el check siempre que el autor sea un `Guide` enlazado (no cuando sea el fallback "Lima América Tours") | derivado de `guide_id !== null`, sin columna nueva | — | 0.5 |
| 11 | Fecha de publicación | `<time>` en `.lat-post-meta` | `blog/show.blade.php:117-124` | EXISTE | `$post->published_at` | — | 0 |
| 12 | Minutos de lectura | `.lat-post-meta` | `blog/show.blade.php:126-131` | EXISTE — ya editable en Filament (`ESTADO.md`, lote 2026-08-11) | `$post->reading_minutes` | — | 0 |
| 13 | Tarjeta blanca flotante con 4 features | nuevo | no existe ningún campo de "features" en `blog_posts` (columnas: `slug`, `is_published`, `author_name`, `category`, `tags`, `cover_image`, `reading_minutes`, `title/excerpt/body/meta_*`) | NUEVO — requiere columna JSON nueva. Los 4 textos del mockup ("Cultura en cada bocado", etc.) son específicos del post de gastronomía inventado; **no hay contenido real para esto en ningún post existente** | `blog_posts.features` — propuesta en sección D | grid 4 columnas ≥1024, 2×2 en 768, 1 columna <480 | 4 |
| 14 | Cuerpo del artículo (dos columnas en el mockup) | `.lat-post-body` | `blog/show.blade.php:143-145`, `_lat-blog.scss:405-427` | ADAPTAR — hoy el cuerpo es **una sola columna** de máx. 760px (línea 406); el mockup lo muestra en dos columnas de texto. Es contenido HTML libre (`{!! $post->body !!}`, WYSIWYG), así que forzar 2 columnas de CSS sobre HTML arbitrario del editor es delicado (imágenes, listas y blockquotes no siempre parten bien en columnas CSS) — decisión de diseño, ver sección H | `$post->body_{locale}` | recomendado: 1 columna <1024, 2 columnas solo ≥1024 con `column-gap`/`column-count`, nunca forzar en el editor | 3 |
| 15 | Cita destacada con comillas rojas grandes | `blockquote` dentro de `.lat-post-body` | estilo actual: `_lat-blog.scss:420-426` (borde izquierdo rojo, cursiva — **sin** el glifo grande de comillas del mockup) | ADAPTAR — es CSS puro sobre un elemento que el editor ya puede insertar desde el WYSIWYG; no requiere campo nuevo, solo un `::before` con el carácter de comillas en `$lat-red`, tamaño grande | contenido del editor (`$post->body`) | el glifo debe escalar o esconderse <480 para no romper el ancho de columna | 1 |
| 16 | Tags | `.lat-post-tags` `.lat-tag-pill` | `blog/show.blade.php:147-154`, `_lat-blog.scss:429-445` | EXISTE — no aparece en el mockup pero no hay que quitarlo | `$post->tags` (JSON) | — | 0 |
| 17 | Caja rosa "¿Listo para vivirlo?" | nuevo | no existe en el artículo (sí existe una caja similar de otro color al final de la página, ver #19) | ADAPTAR — es una variante de sidebar del mismo patrón de CTA que ya existe abajo de la página (`.lat-blog-cta`, `_lat-blog.scss:296-341`), en versión angosta para la columna lateral en vez de ancho completo | — | sidebar ≥1024, se apila debajo del cuerpo <1024 | 2 |
| 18 | Tira de avatares + "+2,500 viajeros ya lo vivieron" | nuevo | no existe | **BLOQUEADO — cifra sin respaldo.** No hay ninguna fuente real de "viajeros que vivieron la experiencia gastronómica"; los avatares del mockup son personas genéricas de stock, no clientes reales (mismo patrón ya prohibido en Home/Contacto por `LOTE-MOCKUPS-AGO-2026.md`, tabla "Lo que NO se publica"). Reemplazo honesto: el agregado real de reseñas del sitio, **5.0 sobre 13 testimonios activos** (medido con `Testimonial::where('is_active',1)->avg('rating')`/`count()`), sin avatares de terceros | `ReviewAggregator` (ya existe, usado en Home) | — | 1.5 |
| 19 | Botón "Ver experiencias gastronómicas" → filtro por categoría | `.lat-btn.lat-btn--red` (patrón ya existe) | mockup only; hoy el CTA final del artículo (`blog/show.blade.php:186-205`) enlaza genérico a "Ver todos los tours" | ADAPTAR con bloqueo de mapeo: la categoría del post (`category` = "Gastronomía", texto libre) **no coincide** con la taxonomía de tours (`Category` real: Culturales/Aventura/Culinarias/Otros — "Gastronomía" del blog ≈ "Culinarias" de tours, pero no son la misma tabla ni el mismo string). Enlazar bien requiere un mapeo explícito, no un `str_contains` a ciegas | `blog_posts.related_category_id` (FK a `categories`) — propuesta en sección D, o decisión de unificar taxonomías (sección H) | — | 2 |
| 20 | Grid de 3 artículos relacionados | `.lat-related-grid` `.lat-related-card` | `blog/show.blade.php:159-184`, `_lat-blog.scss:448-482` | EXISTE | `$related` (ya resuelto por el controlador) | 3 cols ≥900, 2 cols 560-900, 1 col <560 | 0 |
| 21 | CTA final "¿Te animaste a viajar?" | `.lat-blog-cta` | `blog/show.blade.php:186-205`, `_lat-blog.scss:296-359` | EXISTE — no aparece en este mockup (que solo cubre hasta los relacionados) pero está resuelto y con contraste ya corregido (el bug de "blanco sobre blanco" documentado en `_lat-blog.scss:284-295`). No tocar | — | ya responsive | 0 |

## C. Tokens nuevos

- **Glifo de comillas grande** (`::before` del blockquote, #15): no es una variable de
  color nueva, usa `$lat-red` ya existente. Solo se necesita definir el tamaño (p. ej.
  `font-size: 3.5rem` de la comilla) directamente en la regla, no amerita token.
- **Ninguna otra variable de color/tipografía nueva.** El rosa de la caja "¿Listo para
  vivirlo?" (#17) puede resolverse con `$lat-red-tint` (`#fbeaea`, ya definido en
  `_variables.scss:57`) — es el mismo tono que ya usa `.lat-blog-cta` en su variante
  clara (`_lat-blog.scss:300`). No se necesita un rosa nuevo.

## D. Migraciones y campos de CMS necesarios

| Tabla | Columna | Tipo | Consumido por |
|---|---|---|---|
| `blog_posts` | `guide_id` | `foreignId nullable, constrained('guides')->nullOnDelete()` | Línea de autor con foto/rol (#9) |
| `blog_posts` | `video_url` | `string nullable` | Botón de play sobre el hero (#3) |
| `blog_posts` | `features` | `json nullable` (repeater: `icon`, `title_es/en/pt`, `text_es/en/pt`) | Tarjeta de 4 features (#13) |
| `blog_posts` | `related_category_id` | `foreignId nullable, constrained('categories')->nullOnDelete()` | CTA "Ver experiencias de [categoría]" (#19) — alternativa a resolver la taxonomía compartida en sección H |

Ninguna migración de `tours` se ve afectada por esta pantalla.

## E. Assets requeridos

- **Foto del hero**: **bloqueada** (Mitsuharu Tsumura + logo MAIDO, sin autorización).
  Se necesita una foto de gastronomía del propio catálogo, sin rostro identificable de
  terceros ni marca ajena, mínimo 1600×1000 para el recorte de hero a sangre (mismo
  criterio de ancho que usa `.lat-blog-hero` en el listado). No hay nada en
  `public/storage` verificado como apto para esto en esta sesión — se necesita pedirlo
  o elegir de la biblioteca de producción con el mismo criterio que ya se aplicó para
  el banner de Nosotros (`ESTADO.md` §8: revisar mirando cada imagen, no solo por
  nombre de archivo).
- **Fotos de autor**: bloqueadas por el mismo motivo que en Nosotros — los 5 retratos
  candidatos existen en el volcado de WordPress
  (`storage/app/wp-import/prod-dump/.../Sin-titulo-2-0{1..5}-2.jpg`, 854×1024) pero
  **no está confirmado qué foto es cada guía** (`ESTADO.md` punto 4). No asignar a
  ciegas.
- **Íconos de los 4 features**: son genéricos (tenedor/cuchara, estrella, pin, cámara)
  — se resuelven con el mismo set de SVG inline que ya usa el resto del sitio, no
  requieren archivo de imagen.
- **Avatares de la tira "+2,500 viajeros"**: bloqueados por completo (sección B #18),
  no se sustituyen por otra foto de stock — se elimina el componente.

## F. Riesgos de contraste

| Elemento | Fondo | Ratio estimado | Token que sí cumple |
|---|---|---|---|
| Eyebrow rojo + H1 + bajada sobre el hero (si se implementa con foto propia) | foto de gastronomía + degradado, luminosidad no medida (no hay foto real aún) | **No verificable** — mismo patrón que ya falló una vez en el hero del listado de blog (4.36:1 medido, insuficiente) hasta agregar el refuerzo `::before` (`_lat-blog.scss:37-44`) | reutilizar ese mismo refuerzo verificado, no el degradado genérico de `.lat-page-hero` |
| Título/bajada de la caja rosa "¿Listo para vivirlo?" sobre `$lat-red-tint` | superficie sólida clara | Alto — mismo patrón ya medido y corregido en `.lat-blog-cta`: título `$lat-ink` sobre `$lat-red-tint` = **15.8:1**, bajada `$lat-ink-soft` = **10.4:1** (documentado en `_lat-blog.scss:320-322`) | `$lat-ink` / `$lat-ink-soft`, ya verificados |
| Glifo de comillas rojo sobre fondo del cuerpo (`$lat-paper`/blanco) | decorativo, no es texto de lectura — el mínimo WCAG 1.4.11 (3:1) aplica, no el 4.5:1 de texto | `$lat-red` sobre `$lat-paper` ≈ **5.3:1**, sobrado incluso como decorativo | sin cambios |

## G. Reutilización

Esta pantalla tiene **menos construido** que la de tour (el archivo tiene 207 líneas
contra 874) pero varios de sus componentes "nuevos" son en realidad variantes de algo
que ya existe en otra pantalla del mismo proyecto:
- El hero con foto+degradado del listado de blog (`.lat-blog-hero`) es la base correcta
  para el hero del artículo — no inventar un tercer patrón de hero oscuro.
- El modal de video de Home (`.lat-video-modal` + normalizador de URL) sirve para el
  botón de play del artículo tal cual, mismo mecanismo que para el tour.
- `.lat-blog-cta` ya resuelve la caja de CTA con ícono circular rojo — la caja rosa del
  sidebar es la misma tarjeta en formato angosto, no un componente nuevo desde cero.
- El grid de relacionados, el sistema de tags y el CTA final de la página **ya están
  terminados y no se tocan**.
- El modelo `Guide` (foto + rol + bio) ya existe por el trabajo de Nosotros — evita
  crear un segundo sistema de "autor con foto" específico del blog.

## H. Decisiones que no me corresponden

1. **Conflicto de tema (crítico, no lo resuelvo).** La decisión del jefe del
   2026-08-10 (`LOTE-MOCKUPS-AGO-2026.md`, decisión 1) fijó **Blog oscuro completo**,
   tomada sobre el mockup del **listado** (`04-blog.jpeg`). Este mockup nuevo es del
   **artículo** y llega en **tema claro**, con una caja rosa clara y fondo blanco.
   - **Costo de mantener el artículo claro** (como pide este mockup): el visitante
     pasa de un listado negro a un artículo blanco al hacer un solo clic — salto de
     tema dentro de la misma sección del sitio. Es además lo que ya está construido
     hoy (`blog/show.blade.php` sigue en claro, comentario explícito en
     `_lat-blog.scss:361-363`: "SIN TOCAR — fuera de alcance de este lote").
   - **Costo de forzar el artículo a oscuro** (para cumplir la decisión de agosto):
     hay que re-tematizar toda esta pantalla nueva — la caja rosa, la tarjeta blanca
     de features, el cuerpo de texto oscuro-sobre-claro — y volver a medir contraste
     en cada componente, además de contradecir visualmente lo que el cliente vio y
     aprobó en este mockup nuevo.
   No se puede promediar: alguien tiene que elegir.
2. **Autor "Augusto – Guía Local" vs. autor real "Lima América Tours".** Los 10 posts
   publicados no tienen autor individual asignado. ¿Se le pide al cliente que asigne
   un `Guide` real a cada post (y con qué rol), o se publica el artículo sin línea de
   autor individual hasta que exista ese dato?
3. **Taxonomía blog vs. tours.** "Gastronomía" (categoría de blog, string libre) no es
   lo mismo que "Culinarias" (categoría real de tours). ¿Se unifican las dos taxonomías
   en una sola tabla `categories` compartida, o se acepta el mapeo manual por post
   (`related_category_id`) propuesto en la sección D?
4. **Foto del hero con persona real + marca ajena (MAIDO).** Requiere autorización
   escrita antes de cualquier decisión de diseño — no es algo que yo pueda resolver
   eligiendo "otra foto parecida" sin que el cliente decida el reemplazo.
5. **Cuerpo del artículo en dos columnas CSS sobre HTML libre del editor.** Puede
   producir cortes feos en imágenes/listas/blockquotes según lo que cada autor escriba.
   ¿Se acepta el riesgo por fidelidad al mockup, o se mantiene una sola columna (más
   seguro, ya funciona)?

## Total de horas — Artículo de Blog

**≈ 28.5 horas**, sin contar la foto de hero de reemplazo (depende de qué elija el
cliente) ni el tiempo de backend para el modelo `Guide`↔`blog_posts` más allá de la
migración simple listada.

---

## Nota final sobre la pantalla de blog

El post que ilustra el mockup no es contenido real del sitio: ni el título, ni el
autor, ni la cita, ni los 4 features existen en la base de datos hoy. Todo lo que este
inventario marca como "EXISTE" para el artículo se refiere a la **plantilla**
(`blog/show.blade.php`), no a este post en particular — cuando el cliente publique
contenido real de gastronomía (o se use "Mejores Restaurantes en Lima", el más cercano
por categoría), varios de los componentes nuevos (features, autor con foto, cita
destacada) seguirán sin datos hasta que alguien los cargue en el CMS.
