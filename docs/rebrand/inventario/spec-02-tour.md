# Spec estructural — Ficha de Tour (mockup `WhatsApp Image 2026-08-17 at 23.53.40.jpeg`)

**Corrección de alcance del jefe (2026-08-20):** esta ficha pide **estructura**, no
pixel-perfect. Se midió lo mínimo necesario para fijar la estructura (grid mayor,
límites del hero, contraste del título encimado) y se bajó todo lo demás a nivel de
**token** (tipografía/color/radio), sin barrido exhaustivo de bordes para sacar
paddings al píxel.

Mockup: 1503×1046 px. Factor a diseño 1440: **×0,9581**. Medido con el puente CDP propio
(Chrome headless :9333) sobre un `<canvas>` con el JPEG embebido en base64 (`file://` no
se contamina así) — script en
`storage/app/spec-tour/probe.html` (temporal, se borra al cerrar esta tarea). El
contraste y las medidas de "hoy" se tomaron contra
`https://limaamericatours.com/staging/es/tours/detalle/city-tour-centro-historico-de-lima`
y contra `resources/views/tours/show.blade.php` + `resources/scss/pages/_lat-tour.scss`
(líneas citadas).

Contexto ya investigado y **no repetido aquí**: `docs/rebrand/inventario/02-tour-y-blog.md`
(tabla de 26 componentes con archivo:línea, migraciones necesarias, assets, decisiones
H1-H5) y `00-VALIDACION-STAGING.md` (defectos ya corregidos: 24/7, "pago 100% seguro",
rating sembrado). Esta ficha solo agrega lo que esos documentos no resolvían: el **orden
y el grid**, que es justo lo que pidió el jefe.

---

## 0. El hallazgo central

La ficha de tour no es un reskin: es un **reordenamiento**. Tres cambios estructurales,
no visuales:

| # | Hoy (`tours/show.blade.php`) | Mockup |
|---|---|---|
| 1 | Galería (slider + miniaturas) → barra de info blanca → eyebrow+H1+rating **fuera de la foto** → tabs | Foto con **título+rating+ubicación encimados** → barra oscura de 5 datos **pegada al borde inferior de la foto** → miniaturas |
| 2 | Descripción e Incluye son 2 de 5 paneles de un sistema de tabs (`about`, `itin`, `incl`, `bring`, `notes`) | Descripción e Incluye **siempre visibles**, a dos columnas, sin tabs |
| 3 | No existe ninguna caja de "Lo más destacado", mapa ni reseña destacada | Fila nueva de 3 cajas entre las miniaturas y Descripción/Incluye |

Todo lo demás (grid mayor, sidebar sticky, franja de garantías, barra fija móvil) **ya
coincide** con el mockup en proporción y no requiere reordenar nada.

---

## 1. Anatomía — orden final propuesto (arriba a abajo)

| Bloque | Acción | Nota |
|---|---|---|
| 1. Breadcrumb | sin cambios | `.lat-crumb-bar`, `show.blade.php:152-162` |
| 2. **Hero compuesto** (foto + ribbon + overlay título/rating/ubicación + "Ver video" + barra oscura de 5 datos pegada al borde inferior) | REESTRUCTURAR — ver §3 | sustituye a `.lat-gal__main` + `.lat-detail-info` + `.lat-detail-eyebrow/title/rate` de hoy |
| 3. Tira de miniaturas | MOVER — sale de dentro de `.lat-gal` (hoy va pegada al slider, antes de la barra de info) y pasa a ir **después** del hero completo | `.lat-gal__thumbs`, hoy `show.blade.php:208-216` |
| 4. **Fila de 3 cajas** (Lo más destacado / Mapa del recorrido / Reseña destacada) | NUEVO — ver §5 | no existe hoy en ningún punto del archivo |
| 5. Descripción + Incluye, 2 columnas, siempre visibles | REESTRUCTURAR — sale del sistema de tabs — ver §4 | hoy paneles `about` e `incl`, `show.blade.php:319-333` y `364-381` |
| 6. Bloque de Itinerario / Qué llevar / Información importante (forma final **sin decidir**: tab reducido, plano, o acordeón — ver §4.3, opciones A/B/C) | ADAPTAR — los 24/24 tours publicados tienen itinerario, así que este bloque no es opcional en ninguna ficha real — ver §4 | hoy `.lat-tabs-wrap`, `show.blade.php:310-419` |
| 7. FAQ (condicional) | sin cambios | `show.blade.php:423-438` |
| 8. Comparativo convencional-vs-premium (condicional) | sin cambios | `show.blade.php:443-447` |
| 9. Lista completa de reseñas (condicional) | sin cambios — **no es lo mismo** que la reseña destacada del punto 4 (esa es 1 sola cita teaser; esta es el listado completo) | `show.blade.php:453-477` |
| 10. Caja "¿Tienes dudas?" + CTA WhatsApp | NUEVO | no existe hoy — ver §6 |
| — fin de `.lat-detail-grid` — | | |
| 11. Franja de 4 garantías | sin cambios | `show.blade.php:590+` |
| 12. Barra fija inferior (móvil) | sin cambios, no tocar | `.lat-sticky-book`, `show.blade.php:621+` |

**Columna derecha (sidebar, `<aside class="lat-book">`)** — su agrupación interna ya
coincide con el mockup, ver §7.

**Dato que el mockup no muestra y que hoy sí existe:** el `.lat-detail-eyebrow` (categoría
en rojo, arriba del H1) y el `.lat-badge-offer` (pastilla de descuento junto al rating).
El mockup no tiene ningún eyebrow sobre la foto ni pastilla de oferta en el hero — solo
el ribbon "Más vendido". **Decisión abierta (marcada, no resuelta por mí):** ¿el eyebrow
de categoría desaparece del hero sin reemplazo, o se reubica (p. ej. dentro del
breadcrumb, que ya imprime la ruta)? La pastilla de oferta ya se muestra en el header de
precio del sidebar (`lat-book-head__offer`, línea 487-489): recomiendo **no duplicarla**
en el hero — el mockup tampoco lo hace.

---

## 2. El grid mayor

| Medida | Hoy (medido en staging, 1440px) | Mockup (medido, raw→diseño) | Delta |
|---|---|---|---|
| Contenedor | `.lat-wrap` `max-width:1480px` + `padding:24px` → 1392px de contenido a 1440px de viewport (medido: 1377px reales) | zona de contenido raw 1281px → diseño ≈1227px, con márgenes **asimétricos** (72px izq. / 150px der. raw ≈ 69/144 diseño) | el margen asimétrico del mockup **no es un dato de diseño** — es un artefacto de exportación (el canvas del mockup es más ancho que el marco real). No replicar esos márgenes; usar el contenedor existente centrado |
| Columna principal | 965px (`grid-template-columns: 1fr 372px`, `_lat-tour.scss:46`) | raw 939px → diseño ≈899px | proporción ≈70% hoy vs ≈73% mockup — diferencia menor, no amerita cambiar el token |
| Gap entre columnas | 40px (`_lat-tour.scss:47`) | raw 24px → diseño ≈23px | el mockup se ve más angosto pero es ruido de captura (ver nota de márgenes); **mantener 40px** |
| Columna sidebar | 372px fijo (`_lat-tour.scss:46`) | raw 318px → diseño ≈305px | proporción ≈27% hoy vs ≈25% mockup — misma conclusión, **mantener 372px** |
| Breakpoint a 1 columna | `max-width: 980px` → `grid-template-columns: 1fr`, aside pasa de `sticky` a `static` (`_lat-tour.scss:53` y `:500`) | no verificable (el mockup es solo desktop) | sin cambios — es el breakpoint ya usado por el resto de cajas nuevas de esta ficha (§5, §6) |
| Orden al apilar (<980px) | DOM: main (todo su contenido) → aside (book-card + more-tours-card), medido en staging a 390px: aside arranca en y=2630px, todo el contenido principal queda arriba | el mockup no lo muestra | sin cambios — ya funciona, y es coherente con que la barra fija inferior sea la CTA real en móvil |

**Conclusión de este punto: el grid mayor no se toca.** El trabajo real está en lo que
vive *dentro* de la columna principal.

---

## 3. El hero compuesto (el cambio de mayor impacto)

### 3.1 Capas, de atrás hacia adelante

| Capa | Contenido | Estado | Origen |
|---|---|---|---|
| 1 | Foto del slider (`.lat-gal__main`, mismo mecanismo de flechas/swipe/dots) | EXISTE, se reutiliza tal cual | `show.blade.php:181-206` |
| 2 | Scrim degradado oscuro, más denso abajo, transparente hacia arriba, cubriendo ~45% inferior de la foto | NUEVO — mismo patrón que `.lat-blog-hero::before` (`_lat-blog.scss:37-44`), **no** el degradado genérico sin refuerzo | reutilizar la solución ya verificada, no el degradado por defecto |
| 3 | Ribbon "Más vendido", esquina superior izquierda, absoluto sobre la foto | ADAPTAR — reposicionar `.lat-detail-badge` (hoy inline junto al rating) como ribbon absoluto, mismo patrón que `.lat-tcard__badge` del listado (`_lat-tour.scss:299-301`, ya pasa AA: rojo `$lat-red` + blanco ≈5.9:1) | `show.blade.php:266-268` |
| 4 | Flechas circulares prev/next, centradas verticalmente a los costados | EXISTE, sin cambios | `.lat-gal__nav`, `_lat-tour.scss:91+` |
| 5 | Botón "Ver video", esquina inferior derecha, sobre la foto | NUEVO, bloqueado por dato (`tours.video_url` no existe) — reutilizar el modal de Home | `home.blade.php:872-887` |
| 6 | Título (H1, 2 líneas) + línea de rating/ubicación, esquina inferior izquierda, sobre el scrim | REESTRUCTURAR — mismo H1 de hoy (`.lat-detail-title`, Raleway 800, ya correcto), cambia solo color a blanco + se mueve de "debajo de la galería" a "encimado en la foto" | `show.blade.php:264-296` |
| 7 | Barra oscura de 5 datos, ancho completo, pegada al borde inferior de la foto (no es una tarjeta blanca aparte) | ADAPTAR — `.lat-detail-info` cambia de tarjeta blanca con `border-radius` propio a banda `$lat-ink` sólida, sin gap respecto de la foto | `show.blade.php:248-261`, `_lat-tour.scss:242-260` |

### 3.2 Los 5 datos de la barra — mapeo exacto contra hoy

| Mockup | Hoy | Cambio |
|---|---|---|
| Duración — "4 Horas" | Duración — igual | sin cambio |
| Opciones — "Grupal / Privado" | "Tamaño del grupo" con el valor de `group_type` | **relabel only** (el campo ya existe, el texto está mal) |
| Idiomas — "Español / Inglés" | Idiomas — igual | sin cambio |
| Dificultad — "Camina fácil" | **no existe** | NUEVO campo `tours.difficulty` (ver `02-tour-y-blog.md`, sección D) |
| Cancelación — "Hasta 24h antes" | vive hoy como `.lat-badge-free` junto al rating, **fuera** de la barra | se mueve a la barra; el badge verde independiente del rating desaparece (queda dentro de la barra, no duplicado) |
| — | "Salidas" (departure/return_time) | **se retira** de la barra — el mockup no lo pide |

### 3.3 Riesgo de contraste — título encimado sobre la foto (el riesgo real de este rediseño)

Muestreado en píxeles del JPEG del mockup (no del CSS declarado):

| Zona muestreada | Fondo muestreado (hex) | Contraste vs. blanco `#fff` | ¿Pasa AA? |
|---|---|---|---|
| Detrás del H1, mitad izquierda/centro (cielo oscuro + scrim) | `#0a0905`–`#231910` (muy oscuro) | ≈15–19:1 | Sí, sobrado |
| Detrás del H1, tramo derecho de la primera línea ("...Histórico"), donde el scrim se solapa con la fachada iluminada de la catedral | `#8c6d51` (tono tierra medio-claro) | **≈4,75:1** | Pasa 3:1 (texto grande, H1) — **al límite** de 4,5:1 si algún sub-elemento ahí no es "texto grande" |
| Detrás de la línea de rating/ubicación (texto más chico que el H1, exige 4,5:1) | `#0a0904`–`#171310` (scrim denso, esta línea cae más abajo, con más scrim encima) | ≈17–19:1 | Sí, sobrado |

**Conclusión:** el H1 pasa hoy porque cuenta como "texto grande" (≥24px, umbral 3:1), pero
el margen sobre la zona iluminada de la fachada es angosto (4,75:1) y **depende de la
foto**. Cuando el cliente cargue otra foto de portada más clara en esa franja, puede
caer bajo 3:1. **Recomendación: no confiar en el degradado genérico** — usar el mismo
refuerzo de oscurecimiento ya verificado en `.lat-blog-hero::before`
(`_lat-blog.scss:37-44`), y volver a muestrear por cada foto de portada nueva, no una
sola vez. La línea de rating/ubicación no corre riesgo porque queda más abajo, con más
capas de scrim encima.

---

## 4. Descripción + Incluye — la decisión de los tabs

**Corrección (2026-08-20):** la primera versión de esta sección decía que el mockup no
muestra tabs "porque este tour no tiene datos en esos 3 paneles". **Es falso** y quedó
verificado contra la BD y contra el DOM de staging — el propio jefe lo detectó. El tour
`city-tour-centro-historico-de-lima` **sí tiene contenido en los cinco campos**:

| Campo en BD | Caracteres (BD) | Panel renderizado en staging (caracteres) |
|---|---|---|
| `description_es` | 1.463 | `about` → 1.522 |
| `itinerary_es` | **3.569** | `itin` → **3.232** |
| `includes_es` | 166 | `incl` → 185 |
| `recommendations_es` | 89 | `bring` → 85 |
| `notes_es` | 36 | `notes` → 36 |

Ninguno está vacío y los cinco paneles pintan su texto. El mockup **no omite las tabs
por falta de datos: quien lo armó maquetó Descripción e Incluye y no resolvió el resto**
(Itinerario/Qué llevar/Información importante quedaron fuera del mockup, no del sitio).
Eso deja **abierta** la decisión estructural más importante de este documento: si
Descripción e Incluye salen del componente de tabs, ¿qué pasa con los 3.569 caracteres
de itinerario?

### 4.1 Cuántos tours están en la misma situación (no es un caso aislado)

Contado con SQL sobre `lima_america`, filtrando `deleted_at IS NULL AND is_published = 1`
(el proyecto se equivocó tres veces por no filtrar borrados — este conteo sí filtra):

| Campo | Tours publicados con contenido (de 24) |
|---|---|
| `itinerary_es` | **24 / 24 (100%)** |
| `notes_es` | **24 / 24 (100%)** |
| `recommendations_es` ("Qué llevar") | **21 / 24 (87,5%)** |
| Al menos uno de los tres | **24 / 24 (100%)** |

**No es un caso aislado de este tour.** Los 24 tours publicados tienen itinerario y
notas, y 21 de 24 tienen "qué llevar". Cualquier solución que se elija para este mockup
se aplica igual en las 24 fichas — el tab reducido (opción A abajo) no es una preferencia
de diseño, es casi una obligación por volumen de contenido real.

### 4.2 Cuánto pesa el itinerario si se aplana (medido en staging, no estimado)

Con el puente CDP: se activó cada panel por su botón (`#tabbtn-itin`, etc.) y se midió
`getBoundingClientRect()` real del panel activo, en el mismo tour.

| Panel | Alto real a 1440px | Alto real a 390px |
|---|---|---|
| Descripción (`about`, referencia) | 436 px | 1.232,7 px |
| **Itinerario (`itin`)** | **986,6 px** | **2.386,2 px** |
| Qué llevar (`bring`) | 188 px | 204 px |
| Información importante (`notes`) | 99,2 px | 106,8 px |
| **Suma de los 3 si van siempre visibles** | **≈1.274 px** (sin contar encabezados de sección) | **≈2.697 px** (sin contar encabezados) |

A 390px de ancho, el panel de Itinerario solo mide **2.386px** — equivalente a **≈2,8
pantallas de scroll** (viewport móvil de 844px) agregadas de un solo tirón, y eso antes
de sumar Qué llevar y Notas. A 1440px, sumar los 3 paneles siempre visibles agrega
**≈1.274px** de alto a la columna principal, más que **el doble** de lo que ocupa hoy la
Descripción completa (436px). Aplanar todo no es gratis: alarga la página de forma
importante en las 24 fichas, no solo en esta.

### 4.3 Tres opciones, con su costo — no elijo por el jefe

| Opción | Qué hace | Costo | Accesibilidad |
|---|---|---|---|
| **A — Tab reducido (3 paneles)** | Descripción e Incluye salen del componente y quedan siempre visibles (2 columnas, §1). Itinerario/Qué llevar/Información importante se quedan en un `.lat-tabs-wrap` reducido de 5 a 3 paneles, mismo mecanismo | ~1h adicional sobre las 3h ya estimadas en `02-tour-y-blog.md` #13/#14, para achicar el componente y el guard de "ocultar si ninguno de los 3 tiene datos" (con el conteo de §4.1, ese guard casi nunca se activa: 24/24 tienen al menos uno) | **Se conserva casi intacto**: mismo `role="tab"`, `aria-selected`, navegación por teclado y el JS de `show.blade.php:663-692` — solo cambia la cantidad de paneles, no el mecanismo. Es la opción de menor riesgo de accesibilidad porque reusa código ya probado |
| **B — Todo plano, siempre visible** | Los 5 bloques (Descripción, Incluye, Itinerario, Qué llevar, Notas) se imprimen uno debajo del otro, sin tabs ni acordeón | Bajo en horas (~1,5h: es solo quitar el wrapper de tabs y dejar los `<div>` en flujo), pero **alto en costo de UX**: agrega ≈1.274px a 1440 y ≈2.697px a 390 a una página que ya es larga (grid principal solo, sin este bloque, mide ~1.809px a 1440 según `__rect('.lat-detail-grid > div')` medido en §2) | **Se pierde el componente entero**: no hay nada que navegar por teclado como "tab", `role="tab"`/`aria-selected` deja de aplicar (no hay a qué aplicarlo). No es una regresión de accesibilidad per se (contenido plano es leíble por lector de pantalla en orden), pero se tira el trabajo ya hecho en `show.blade.php:663-692` |
| **C — Acordeón sin barra de tabs** | Los 3 paneles restantes (Itinerario/Qué llevar/Notas) se muestran como acordeón vertical (`<details>`/`<summary>` o el mismo patrón ya usado en `.lat-faq-item`, `show.blade.php:427-435`) en **todos** los anchos, no solo en mobile | ~2h: es más barato que reescribir el sistema de tabs para desktop, porque el mockup ya no exige una barra horizontal de tabs en ningún ancho — se reutiliza el patrón de acordeón que YA existe en el FAQ del mismo archivo | **Distinto pero conocido**: se cambia `role="tab"` por el patrón nativo `<details>` (foco y teclado gratis del navegador) o por el acordeón ya usado en FAQ (que ya es accesible, `show.blade.php:427-435`). No se pierde accesibilidad, se **cambia de patrón** — hay que decidir si mezclar dos patrones de acordeón distintos en la misma página (FAQ + este) es aceptable o si conviene unificarlos |

**Lo que no cambia entre las 3 opciones:** el bloque de tabs (cualquiera que sea su
forma final) sigue viviendo **entre** Descripción/Incluye y el FAQ, en el mismo lugar de
la anatomía (§1, punto 6). Y en los tres casos, el guard por dato se mantiene: con
21-24 de 24 tours teniendo contenido, el guard casi nunca oculta el bloque, pero hay que
conservarlo para los tours sin "qué llevar" (3 de 24).

**Recomendación de quien maqueta (no es la decisión, es la lectura de costo):** con
24/24 tours teniendo itinerario y notas, y el itinerario pesando el doble que la
descripción, la opción B (todo plano) es la que más alarga la página en el 100% de las
fichas — no es un costo aislado de este tour. Entre A y C, A conserva más del trabajo de
accesibilidad ya hecho y probado; C es más barata en horas pero cambia de patrón. Decide
el jefe.

---

## 5. Fila nueva de 3 cajas — "Lo más destacado" / mapa / reseña destacada

No existe hoy en ningún punto de `show.blade.php`. Entra **entre la tira de miniaturas y
el bloque de Descripción/Incluye** (confirmado por el orden visual del mockup: hero →
miniaturas → esta fila → Descripción/Incluye).

| Caja | Grid | Fuente de dato | Breakpoint de apilado |
|---|---|---|---|
| "Lo más destacado" (checklist) | 1 de 3 columnas iguales | primeros 4 pasos de `itinerary_{locale}` (ya existe, ya viene del CMS) — no crear campo nuevo | apila con las otras 2 a <980px (mismo breakpoint del grid mayor) |
| Mapa del recorrido + botón "Ver mapa del recorrido" | 1 de 3 columnas iguales | `tours.route_map_image` — **campo nuevo**, imagen estática subida por el cliente (v1 honesto, no hay lat/lng por parada) | idem |
| Reseña destacada (comillas, avatar, nombre, país, estrellas) | 1 de 3 columnas iguales | `$tourReviews->first()` (mismo query que ya alimenta el listado completo de abajo, filtrar por 1 solo registro o por `is_featured` si se agrega ese flag) | idem |

Contenedor: `display:grid; grid-template-columns: repeat(3, 1fr); gap` — mismo patrón de
gap que ya usa `.lat-detail-info` u otro grid de 3 columnas del proyecto (no inventar un
valor de gap nuevo).

---

## 6. Caja "¿Tienes dudas?" — posición exacta

Entra **después** de la lista completa de reseñas (punto 9 de la anatomía) y **antes**
del cierre de `.lat-detail-grid` — es decir, es el último bloque dentro de la columna
principal, no dentro de la franja de garantías. Reutiliza el patrón ya resuelto en
`contact.blade.php:23,345` vía `Setting::whatsappNumber()` — solo falta el partial en
esta vista. Grid: 2 columnas ≥640px (ícono+texto | botón), 1 columna <640px.

---

## 7. Columna derecha — agrupación y jerarquía

El `<aside class="lat-book">` de hoy **ya tiene la misma jerarquía de contenedores** que
pide el mockup — no hay que reestructurar nada acá, solo llenar componentes:

```
<aside class="lat-book">                     ← sticky ≥980px, static <980px
  <div class="lat-book-card">                ← tarjeta 1: precio + formulario
    <div class="lat-book-head">              ·  cabecera roja "Desde $69"
    <form class="lat-book-body">             ·  selector Grupo/Privado (NUEVO, bloqueado)
                                              ·  campo de fecha (existe)
                                              ·  contador de viajeros (ADAPTAR: select→+/-)
                                              ·  precio total (existe)
                                              ·  línea de cancelación (existe)
                                              ·  botón "Reservar ahora" (existe)
                                              ·  "Reserva segura"/aviso real (existe, ya honesto)
  </div>
  <div class="lat-more-tours-card">          ← tarjeta 2: hermana de la anterior, NO anidada
    <h3>También te puede interesar</h3>         (hoy dice "Más tours" — copy, no estructura)
    3× <a class="lat-mini-tour">              ·  ya reales (Miraflores $45, no $49; los otros 2 no existen)
    <button>Ver más tours</button>            ← NUEVO, el mockup lo tiene y hoy no existe
  </div>
</aside>
```

**Selector Grupo/Privado — spec del componente, aunque esté bloqueado:** 2 botones
lado a lado (`display:flex`, apilan a 1 columna <480px), estado activo = borde+texto
`$lat-red` sobre `$lat-surface`, estado inactivo = borde `$lat-line`, texto `$lat-muted`,
ícono outline. **No se implementa la lógica de cambio de precio** hasta que exista
`tours.price_private` — se puede maquetar la caja ya mismo (es CSS + 2 botones estáticos
o deshabilitados) pero el clic no debe prometer un precio distinto que no existe. Igual
tratamiento que ya se le dio a "Pago 100% seguro": la caja se ve, pero no miente.

---

## 8. Comportamiento responsive — tabla de quiebres

| Breakpoint | Qué cambia | Ya resuelto / nuevo |
|---|---|---|
| ≥980px | Grid a 2 columnas, aside sticky (`top:104px`) | ya resuelto, sin cambios |
| <980px | Grid a 1 columna, aside static, orden: todo el main primero, luego aside completo (book-card + more-tours-card) | ya resuelto, sin cambios |
| <980px | Fila de 3 cajas nueva (§5) apila a 1 columna | nuevo, mismo breakpoint que el grid mayor por consistencia |
| ≥768px / <768px | Descripción/Incluye: 2 columnas / 1 columna | nuevo, breakpoint ya definido en `02-tour-y-blog.md` #13 |
| ≥640px / <640px | Caja "¿Tienes dudas?": 2 columnas / 1 columna | nuevo |
| <1023.98px | `padding-bottom: 118px` en el contenedor para dejar espacio a la barra fija móvil (`.lat-sticky-book`) | ya resuelto (`_lat-tour.scss:806, 820`), **no se toca** — el hero rediseñado no cambia esta convivencia porque la barra fija es un componente `position:fixed` independiente del flujo del grid |
| <900px | Paginación por dots de la galería se oculta (queda swipe + flechas) | ya resuelto, sin cambios |
| <480px | Ribbon "Más vendido" se reduce de tamaño (aplicar mismo criterio que hoy usa `.lat-detail-badge` en su contexto anterior) | adaptar breakpoint existente al nuevo contexto de ribbon absoluto |

Verificado sin overflow horizontal en 390px en el sitio **actual** (`__overflow()` →
`horizontalOverflow:false`, único "offender" es un input honeypot fuera de pantalla a
propósito, no un defecto). El rediseño del hero no agrega nuevo contenido ancho (sigue
siendo una foto a `aspect-ratio` + una barra de texto), así que no debería introducir
overflow nuevo, pero **hay que volver a medir esto después de implementar**, no asumirlo.

---

## 9. Tipografía, color y radio — a nivel de token (no hex por elemento)

| Elemento | Token | Valor |
|---|---|---|
| H1 del hero | `$lat-font-serif` (= Raleway, pese al nombre), weight 800, `clamp(1.9rem, 3.6vw, 2.7rem)`, `line-height:1.1` | ya existe (`_lat-tour.scss:267-273`), **solo cambia el color** a blanco cuando queda sobre la foto |
| Cuerpo (rating, ubicación, barra de datos, Descripción/Incluye) | `$lat-font-sans` (Open Sans) | sin cambios |
| Fondo de la barra oscura de 5 datos | `$lat-ink` (`#171412`) | blanco sobre `$lat-ink` ≈15,6:1, sobrado |
| Ribbon "Más vendido" | `$lat-red` (`#cb101e`) fondo + blanco texto, `$lat-r-pill` | ya resuelto en el listado, reusar tal cual |
| Caja "¿Tienes dudas?" | superficie `$lat-surface` o `$lat-red-tint` para el ícono | reusar tokens existentes, no crear un rosa/rojo nuevo |
| Radios de tarjetas (3 cajas, dudas, more-tours-card) | `$lat-r-md` (18px) | consistente con `.lat-book-card`, `.lat-more-tours-card` ya existentes |
| Sombra de tarjetas | `$lat-shadow-sm` | consistente con el resto del archivo |
| Contador +/- de viajeros | reusar `$lat-line-strong` para el borde, `$lat-red` para el texto/ícono de los botones ± | nuevo componente, tokens existentes |

**Nota de fuente:** el título del mockup se ve como un sans bold, consistente con
Raleway 800/900 ya usado en todo `h1` del sitio (a diferencia del mockup de blog, que sí
traía una serif editorial a traducir). Este mockup **no** exige ninguna traducción de
familia tipográfica.

---

## 10. Restricciones de contenido — la caja se especifica, el dato no se inventa

| Elemento del mockup | Por qué no se publica tal cual | Qué va en su lugar | Caja/componente |
|---|---|---|---|
| `4.9 ★★★★★ (238 reseñas)` | `rating` sembrado en 4.8 uniforme para 24 tours, `reviews_count=0` real | Rating agregado del **tour** se oculta si no hay `$tourReviews`; se puede mostrar el agregado del **sitio** (`ReviewAggregator`, ya usado en Home) con link a `/resenas` | misma posición en la línea de rating sobre la foto |
| "Tour a Miraflores y Barranco... $49" | precio real es $45 | $45 real (`$related`, ya resuelto en el controlador) | tarjeta 1 de "También te puede interesar" |
| "Catacumbas de San Francisco $30" / "Tour Nocturno de Lima $45" | ninguno de los dos existe en el catálogo de 24 tours | los 2 tours reales que sí devuelva `$related` | tarjetas 2 y 3 de la misma caja |
| "Pago seguro" / "Tus datos protegidos" (franja de 4 sellos) | implica pasarela activa; hoy las llaves son de prueba | copy ya resuelto en `00-VALIDACION-STAGING.md`: "Sin cobro ahora: confirmamos tu reserva por WhatsApp o correo" | franja de garantías, sello 3 |
| "Atención 24/7" (franja de 4 sellos) | horario real Lun-Vie 9:00-19:00 | `Setting::contactHours()`, ya resuelto y deployado (ver `00-VALIDACION-STAGING.md`) | franja de garantías, sello 4 |
| Selector Grupo/Privado con precio implícito | `tours.price` es una sola columna, no existe `price_private` | caja construida (ver §7), sin lógica de precio hasta que el jefe decida el modelo de datos | sidebar, bajo la cabecera de precio |

---

## 11. Lista de deltas priorizada por impacto estructural

| # | Delta | Impacto | Horas | Bloqueo |
|---|---|---|---|---|
| 1 | Reestructurar el hero: título+rating+ubicación pasan de "debajo de la galería" a "encimados en la foto"; la barra de 5 datos pasa de tarjeta blanca separada a banda oscura pegada al borde inferior de la foto | **Crítico** — reordena el DOM de 3 componentes existentes en 1 nuevo | 4 (ya estimado en `02-tour-y-blog.md` #4) + 1 (reforzar scrim medido, §3.3) | ninguno, es maquetación pura |
| 2 | Mover la tira de miniaturas de "antes de la barra de info" a "después del hero completo" | **Alto** — es un reorden de DOM dentro de `.lat-gal`, no solo CSS | 0,5 (ya estimado, #8) | ninguno |
| 3 | Sacar Descripción e Incluye del sistema de tabs (siempre visibles). El destino de Itinerario/Qué llevar/Info importante **depende de la opción A/B/C que elija el jefe** (§4.3) — no es solo maquetación, cambia cuánto se alarga la página en las 24 fichas | **Crítico** — decisión estructural del componente central de la ficha, con impacto medido: opción B agrega ≈2.700px a 390px de ancho en el 100% de los tours publicados | 3 (Descripción/Incluye, ya estimado #13/#14) + 1/1,5/2 según se elija A/B/C (§4.3) | **decide el jefe entre A/B/C antes de maquetar** |
| 4 | Construir la fila nueva de 3 cajas (destacado / mapa / reseña) e insertarla entre miniaturas y Descripción/Incluye | **Alto** — bloque 100% nuevo, con 1 campo de CMS nuevo (`route_map_image`) | 2 + 3 + 1,5 = 6,5 (ya estimado, #10/#11/#12) | migración simple (`route_map_image`) |
| 5 | Adaptar la barra de 5 datos: relabel "Opciones", nuevo campo "Dificultad", mover "Cancelación" adentro, retirar "Salidas" | **Medio** — cambia el contrato de datos del componente | 3 (ya estimado, #7, incluye migración de dificultad) | migración simple (`tours.difficulty`) |
| 6 | Reposicionar el ribbon "Más vendido" de pastilla inline a ribbon absoluto sobre la foto | Medio | 1,5 (ya estimado, #3) | ninguno |
| 7 | Agregar "Lima, Perú" (ubicación con pin) a la línea de rating | Bajo | 0,5 (ya estimado, #5) | ninguno |
| 8 | Construir caja "¿Tienes dudas?" + CTA WhatsApp, al final de la columna principal | Bajo — aislado, no reordena nada existente | 1 (ya estimado, #15) | ninguno |
| 9 | Botón "Ver mapa del recorrido" + botón "Ver video" (modal reutilizado de Home) | Bajo — funcionalidad nueva pero aislada | 2,5 (video) — incluido en el punto 4 (mapa) | video: falta `tours.video_url`; mapa: falta `route_map_image` (mismo punto 4) |
| 10 | Selector Grupo/Privado — construir la caja visual sin lógica de precio | Bajo en maquetación, **alto en decisión de producto** | 4 (UI, ya estimado #18) | **bloqueado**: requiere que el jefe decida el modelo de precio (`price_private`) antes de que el selector cambie algo real |
| 11 | Contador +/- de viajeros (reemplaza el `<select>` actual) | Bajo | 1,5 (ya estimado, #20) | ninguno |
| 12 | Botón "Ver más tours" al final de la tarjeta de relacionados | Bajo | 0,5 (nuevo, no estaba en `02-tour-y-blog.md`) | ninguno |
| 13 | Decidir el destino del eyebrow de categoría (hoy sobre el H1, ausente en el mockup) | Decisión, no horas | 0 | **decide el jefe** — ver nota final de §1 |

**Total maquetación estimada de este lote estructural: ≈29-30 horas** según cuál de las
opciones A/B/C del punto 3 elija el jefe (suma de horas de la tabla, sin contar el punto
10 que depende de una decisión de producto ni el punto 13 que no es trabajo de
maquetación). Es consistente con las ~32h ya estimadas en `02-tour-y-blog.md` para toda
la ficha — esta lista es el mismo trabajo, ordenado por qué tan estructural es el cambio
en vez de por componente. **El punto 3 es el único de la lista que no se puede empezar
a maquetar sin que el jefe elija A, B o C primero** — a diferencia del resto, que se
puede maquetar ya mismo.

---

## 12. Lo que NO se midió al detalle (y por qué)

Por instrucción expresa del jefe, no se barrieron bordes para sacar paddings/gaps al
píxel en: tamaño exacto del ribbon, padding interno de las 3 cajas nuevas, tamaño exacto
de los íconos de la barra de 5 datos, radio exacto de las miniaturas. Todo eso se resuelve
con los tokens ya existentes del archivo (`$lat-r-md`, `$lat-shadow-sm`, el mismo padding
que ya usa `.lat-detail-info`/`.lat-book-card`) — reproducir el token, no medir el mockup
de nuevo para cada caja.
