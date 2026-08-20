# Spec estructural — Nosotros, banda superior (hero + barra de 5 métricas + franja de garantías)

Mockup: `WhatsApp Image 2026-08-17 at 23.53.39.jpeg`, 1536×1024 px, factor a diseño 1440 = ×0,9375.
Comparado contra: `https://limaamericatours.com/staging/es/nosotros` (medido en vivo el 2026-08-20).

**Corrección de alcance (indicación del jefe, recibida a mitad de tarea):** esto ya no es
una ficha pixel-perfect. El entregable es **estructura**: anatomía y orden, grid, agrupación,
comportamiento responsive, y el delta contra lo publicado. Tipografía/color/radio se reportan
a nivel de **token**, no hex por elemento. Los barridos de borde para sacar paddings al
píxel se cortaron a mitad de camino cuando llegó la corrección — lo que ya se había medido
así (límites de sección, columnas del grid, overlaps) se aprovecha igual porque es la
evidencia detrás de la estructura, no relleno pixel-perfect.

**Método**: el mockup se embebió en base64 dentro de un `<canvas>` (`probe.html`, ya borrado
de `storage/app/spec-nosotros/` al cerrar esta tarea) y se leyó con el puente CDP propio
(Chrome headless, puerto 9333 — el MCP de Playwright estaba libre pero se prefirió no abrir
dos sesiones). El staging se midió con el mismo puente, `getBoundingClientRect` y
`getComputedStyle` reales, no capturas.

Contexto leído antes de medir: `01-nosotros-y-menu.md` (Pantalla 1), `00-VALIDACION-STAGING.md`,
`LOTE-MOCKUPS-AGO-2026.md`. Dato clave de esos documentos: **el trabajo de la Pantalla 1 ya
se ejecutó parcialmente entre el 18 y el 20 de agosto** — varios ítems que el inventario del
18 marcaba como "NUEVO" ya existen hoy en staging (badge de marca, micro-features, collage de
4 fotos). Esta spec mide el estado real de hoy, no repite el inventario de hace dos días.

---

## 1. Anatomía y orden (de arriba a abajo)

| # | Elemento | En el mockup | En staging hoy | ¿Monta sobre el anterior? |
|---|---|---|---|---|
| 1 | Breadcrumb ("Inicio") | No visible en este crop | **Sí existe**, antes del badge | No |
| 2 | Badge de marca (píldora + estrella) | "LIMA VIEW TOURS" | **Sí existe**, texto "Lima América Tours" ya correcto | No |
| 3 | H1 a dos líneas | 2 líneas, 2ª en rojo brillante | **1 línea de texto plano**, blanco, sin quiebre forzado ("Más de 10 años mostrando lo mejor del Perú") | No |
| 4 | Bajada / párrafo | 1 párrafo | Existe | No |
| 5 | 4 micro-features | 3 arriba + 1 abajo (ver §3) | Existen, agrupados 2×2 (ver §3) | No |
| 6 | 2 CTA (Explorar Tours + Ver Video) | Los dos | **Solo "Explorar Tours"**. "Ver Video" no está en el DOM | No |
| 7 | Tira de avatares + "Miles de viajeros…" + 5 estrellas | Sí, bajo los CTA | **No existe** en el hero | No |
| 8 | Collage: 1 foto grande + 3 en fila | Sí | **Sí existe**, misma estructura (1 grande arriba + 3 en fila abajo) | — |
| 9 | Badge flotante rojo "10+ años de experiencia" | Flotando sobre la esquina donde la foto grande se junta con la fila de 3 (monta sobre ambas) | **No existe** (`.lat-split__badge`: 0 elementos) — oculto correctamente porque `company_started_year` está vacío | Sí, en el mockup |
| 10 | Píldora oscura "Más de 2.500 viajeros satisfechos" | Flotando, apoyada sobre el borde inferior de la fila de 3 (traslape visual ligero, no medido al píxel) | **No existe** | Sí, en el mockup |
| 11 | Barra oscura de 5 métricas | Separada limpiamente del hero, sin traslape (ver §1.1) | **Sí existe, pero SE ENCIMA sobre el borde inferior del hero** (ver §1.1) | **No** en el mockup / **Sí** en staging |
| 12 | Franja roja de 4 garantías | Después de la barra de métricas, sin espacio | Igual: pegada a la barra de métricas, 0px de separación | No (correcto, coincide) |

### 1.1 El overlap de la barra de métricas — la pregunta específica del jefe

**En el mockup no monta.** Se barrió la columna de fondo (`colEdges`) desde x=20 hasta
x=1350 en varias franjas verticales: el fondo del hero se mantiene negro puro
(`#000000`/`#010101`) de forma continua hasta y≈737px (crudo) — incluso por debajo del
collage, que termina antes (~y=605). La barra de métricas empieza exactamente en ese punto
(y≈737-741, cambia a `#141414-#171717`) **en las tres posiciones muestreadas** (x=50, x=1200,
x=1350). No hay superposición: la barra arranca donde el hero termina, borde a borde.

**En staging sí monta**, y es consistente en tres viewports:

| Viewport | Borde inferior del hero (`.lat-about-hero`) | Borde superior de la barra (`.lat-hero-stats`) | Overlap |
|---|---|---|---|
| 1440px | 880px | 846px | **34px** |
| 1024px | 837px | 803px | **34px** |
| 390px (móvil) | 1223px | 1201px | **22px** |

Es decir, la barra de métricas tiene hoy un `margin-top` negativo (o equivalente) que la
mete 34px dentro del hero en desktop/laptop y 22px en móvil. El mockup pide **0px**: la
barra debe ser la siguiente sección en el flujo normal, no una card flotante sobre el borde.
Esto es el mismo defecto que ya señalaba `01-nosotros-y-menu.md` ("hoy se ve como una card
blanca flotando sobre el borde del hero") — **sigue vivo**, solo que ya no es una card
blanca sino oscura (`rgb(23,20,18)` = exactamente `$lat-ink`).

La franja roja de garantías, en cambio, **ya no monta**: en el mockup y en staging arranca
exactamente donde termina la barra de métricas (0px de separación, 0px de overlap) en
ambos casos. No hay delta ahí.

---

## 2. El grid

### 2.1 Hero: 2 columnas

| | Mockup (crudo → diseño ×0,9375) | Staging @1440px |
|---|---|---|
| Columna de texto | ≈683px diseño | 665px |
| Columna de collage | ≈707px diseño | 665px |
| Relación | ≈49% / 51% | **50% / 50%** |
| Gap entre columnas | no medido con precisión (texto no llena la columna) | **48px** (consistente en 1024 y 1440 — es `gap-12`/3rem de Tailwind) |

Prácticamente equivalentes: el mockup reparte el ancho casi igual entre texto y collage, y
staging ya lo hace 50/50. **No hay delta relevante en la proporción de columnas.**

### 2.2 Breakpoint donde el grid de 2 columnas se rompe (staging)

| Viewport | `grid-template-columns` de `.lat-about-hero__grid` | Resultado |
|---|---|---|
| 390px | `342px` (1 valor) | 1 columna, texto arriba → collage abajo |
| 640px | `577px` (1 valor) | 1 columna |
| 768px | `705px` (1 valor) | **1 columna** (el collage se pinta después del texto, ancho 680px casi completo) |
| 1024px | `456.5px 456.5px` | **2 columnas** |
| 1440px | `665px 665px` | 2 columnas |

**El quiebre es entre 768 y 1024 — coincide con el breakpoint `lg` (1024px) de Tailwind.**
No hay overflow horizontal en 390px (`overflowX: false`).

### 2.3 Collage: estructura interna (staging, ya construida así)

```
┌─────────────────────────────────────┐
│         foto grande (665×415px)      │
├──────────┬──────────┬─────────────────┤
│ 214×214  │ 214×214  │   214×214       │
└──────────┴──────────┴─────────────────┘
```

- Gap entre foto grande y fila de 3: **12px**.
- Gap entre las 3 fotos de la fila: **11-12px** cada uno.
- Aspecto de la foto grande: 665:415 ≈ **1,60:1** (más cuadrada que ancha).
- En el mockup, la foto grande se ve proporcionalmente **más ancha/baja** (banner panorámico,
  visualmente ≈2,4:1) — es una diferencia de proporción real entre mockup y staging, no solo
  de tamaño. **No se barrió al píxel exacto** porque cae fuera del alcance corregido; queda
  como observación visual, no como medida.
- A 390px la misma estructura se mantiene (1 grande de 342×214 + 3 de 109×109 con ~8px de
  gap): el patrón "1 grande + 3 en fila" **sí es responsive y no se rompe**, solo se
  reescala. No hay delta estructural aquí, solo el matiz de proporción de arriba.

---

## 3. Agrupación y jerarquía

### 3.1 Los 4 micro-features: 3+1 en el mockup, 2×2 en staging — **delta real**

**Mockup** (confirmado por inspección visual directa, alineación de columnas):

```
Fila 1:  [Guías Expertos]   [Experiencias]   [Grupos Reducidos]
Fila 2:  [Seguridad Total]
```
3 columnas, 4 ítems: la fila 2 solo tiene 1 elemento, alineado con la primera columna.

**Staging** (confirmado por `grid-template-columns` computado = `318.25px 318.25px`, 2 valores):

```
Fila 1:  [Guías Expertos]      [Experiencias]
Fila 2:  [Grupos Reducidos]    [Seguridad Total]
```
2 columnas, 2×2 estricto.

Es una diferencia de **estructura de grid**, no de contenido (los 4 textos/íconos ya
coinciden). Cambiar de `grid-template-columns: repeat(2,1fr)` a `repeat(3,1fr)` en desktop
resuelve el patrón del mockup; a móvil ambos colapsan a 1 columna igual (confirmado:
`342px` a 390px), así que el cambio es solo de breakpoint ≥ el punto de quiebre del hero
(1024px en adelante).

### 3.2 Jerarquía de contenedores (staging, vía DOM)

```
.lat-about-hero
└── .lat-wrap.lat-about-hero__grid   (único hijo directo — grid de 2 columnas)
    ├── (columna 1 — texto, hermanos directos dentro de un contenedor de texto)
    │   ├── breadcrumb "Inicio"
    │   ├── .lat-hero-badge
    │   ├── h1
    │   ├── .lat-about-hero__sub
    │   ├── .lat-hero-features (4 × .lat-hero-features__item)
    │   └── CTA "Explorar Tours"
    └── .lat-hero-collage (columna 2 — 4 <img> hermanos)

.lat-hero-stats        ← hermano de .lat-about-hero, NO hijo (por eso puede montarse encima)
.lat-guarantee (.lat-gt × 4)  ← hermano de .lat-hero-stats
```

Nota estructural: como `.lat-hero-stats` es hermano del hero y no está anidado dentro, el
overlap del §1.1 se resuelve con un margen negativo en el propio `.lat-hero-stats` (o
`padding-bottom` reducido en `.lat-about-hero`), no con un cambio de jerarquía DOM.

---

## 4. Comportamiento por breakpoint

| Breakpoint | Columnas del hero | Orden de apilado | Micro-features | Collage | Overlap barra de métricas |
|---|---|---|---|---|---|
| **390px** (móvil) | 1 | texto → collage → barra de métricas → garantías | 1 columna | 1 grande + 3 en fila, reescalado | 22px |
| **640px** | 1 | igual que 390 | 1 columna | igual | no medido en este viewport, esperar ≈22-34px |
| **768px** (tablet) | 1 | igual que 390 (`gridCols` = 1 valor) | 1 columna | igual, ancho 680px | no medido |
| **1024px** (laptop) | **2** | texto \| collage lado a lado | 2×2 hoy / 3+1 en el mockup | 1 grande + 3 en fila | 34px |
| **1440px** (desktop) | 2 | igual que 1024 | 2×2 hoy / 3+1 en el mockup | igual | 34px |

Nada se oculta entre breakpoints (ni la tira de avatares ni la píldora ni el badge flotante
porque ya están ocultos por falta de dato en los tres tamaños, no por CSS responsive). El
único cambio real de estructura entre tamaños es el quiebre de 1→2 columnas del hero entre
768 y 1024px, y el colapso de la grilla de micro-features a 1 columna en todos los tamaños
menores a ese mismo quiebre.

Sin overflow horizontal en 390px (verificado).

---

## 5. Tokens (no hex por elemento)

| Elemento | Token | Confirmado |
|---|---|---|
| Fondo del hero | `linear-gradient(160deg, #1c1815, #171412 60%)` — ya es `$lat-ink`/`$lat-ink-2` | Medido con `getComputedStyle` en staging |
| Fondo de la barra de métricas | `rgb(23,20,18)` = **`$lat-ink`** exacto | Medido |
| Fondo de la franja de garantías | `rgb(203,16,30)` = **`$lat-red`** exacto | Medido |
| Separador vertical entre métricas | `1px solid rgba(255,255,255,.16)` | Medido — es el separador que pide el mockup, ya existe |
| Borde del badge de marca | `rgba(203,16,30,.55)` (`$lat-red` al 55%) | Medido |
| Subtítulos de la franja de garantías | `rgba(255,255,255,.86)` | Medido — **ya aplica el .86 recomendado** en `01-nosotros-y-menu.md` §E, no el .72 que fallaba AA |
| Tipografía de titulares | **Raleway** (confirmado `font-family` computado), no hay serif en este mockup específico | El mockup usa un sans-serif grueso (sin remates visibles) — a diferencia de los mockups de Blog/Contacto del mismo lote (que sí traen serif), **este no exige traducción serif→Raleway**: ya coincide en familia. Falta calibrar peso/tracking exacto de la 2ª línea si se agrega en rojo |
| Tipografía de cuerpo | **Open Sans** | Confirmado por `getComputedStyle(body).fontFamily` |

No se reporta un hex por elemento adicional: los que sí se muestrearon (arriba) ya
resuelven a un token existente, sin necesidad de crear ninguno nuevo — coincide con lo que
ya anticipaba la sección C de `01-nosotros-y-menu.md`.

---

## 6. Contraste WCAG (lo único numérico que se mantiene)

| Elemento | Fondo | Texto | Ratio | ¿Pasa? | Token alternativo |
|---|---|---|---|---|---|
| 2ª línea del H1 en rojo (si se implementa como pide el mockup) | `$lat-ink` (#171412) | `$lat-red` (#cb101e) | **≈3,17:1** (cálculo WCAG sobre hex, ya validado en `00-VALIDACION-STAGING.md`) | Pasa gráfico (3:1), **no pasa texto** (4.5:1) | Usar crema `#f2e9de` o blanco para la 2ª línea; el rojo queda de acento (subrayado/ícono), nunca como color de texto — mismo criterio ya aplicado en el hero de Home |
| Subtítulos de garantías | `$lat-red` (#cb101e) | blanco a 86% | — | **Ya resuelto** (staging usa `.86`, no el `.72` que fallaba) | Sin cambios |
| Separador vertical de la barra de métricas | `$lat-ink` | blanco a 16% | Elemento decorativo, no exige AA de texto | — | Sin cambios |
| Badge de marca (borde) | `$lat-ink` | `$lat-red` a 55% | Gráfico, ≥3:1 esperado (el rojo sólido ya da 3,17:1; con alpha .55 sobre fondo oscuro el contraste efectivo sube ligeramente porque se mezcla menos rojo puro — no se recalculó el alpha exacto) | Probablemente pasa, no crítico | Sin cambios |

**Nota**: el H1 no tiene hoy ninguna palabra en rojo (es un bloque de texto plano blanco), así
que el riesgo de contraste de la fila 1 **no está vivo en staging todavía** — solo se
activa si se implementa la 2ª línea roja del mockup. Se mantiene en la tabla porque es
un defecto que se introduciría al construir ese punto, no uno que ya exista.

---

## 7. Cifras prohibidas — la trampa, confirmada y ya resuelta

El mockup relabela los números reales:

| Mockup dice | Cifra real detrás | Trampa |
|---|---|---|
| "13 Guías Locales Expertos" | 13 son **opiniones** de viajeros, no guías (`Guide::count()` = 4) | Sí, coincide con lo señalado en el brief |
| "8 Años de Experiencia" | 8 son **valores** del CMS, no años (`company_started_year` vacío) | Sí |
| "10+ años de experiencia" (badge flotante) y "Más de 10 años…" (H1) | Sin `company_started_year` cargado | Cifra sin respaldo |
| "Más de 2.500 viajeros satisfechos" | No existe esa cifra en ninguna tabla | Cifra sin respaldo |
| "Reserva 100% Segura" / "Atención 24/7" (franja de garantías) | Sin pasarela activa / horario real 9:00-18:30 | Cifras ya prohibidas por el proyecto |

**Verificado en staging hoy — la trampa NO se coló:**

```
"5.0 Valoración de viajeros"
"13 Opiniones de viajeros"      ← correcto, no "13 Guías"
"24 Tours disponibles"
"3 Destinos"
"8 Valores que nos guían"       ← correcto, no "8 Años de Experiencia"
```

Y en la franja de garantías: `"Reserva sin cobro inmediato"`, `"Atención al cliente"` (sin
"24/7") — también ya corregidas. El badge flotante de años y la píldora de 2.500 viajeros
están ausentes (ocultas por falta de dato), no rellenas con el número inventado.

**Ningún número prohibido está publicado hoy en esta banda.** El único texto que sigue
citando "10 años" es el H1 (`about.blade.php:283`), y es una decisión ya tomada por el jefe
de mantenerlo como copy de marca (`about.blade.php:111-112`) — no es un defecto nuevo de
esta spec, es el mismo conflicto sin resolver que ya documentó `00-VALIDACION-STAGING.md`
("el badge se oculta por falta de dato, pero el H1 con la misma afirmación no se toca").

---

## 8. Delta estructural priorizado (de mayor a menor impacto)

| # | Delta | Impacto | Horas est. |
|---|---|---|---|
| 1 | **La barra de métricas se encima 34px (desktop/laptop) / 22px (móvil) sobre el borde del hero.** El mockup no la monta: es la siguiente sección en flujo normal, borde a borde. Quitar el margen negativo (o el padding-bottom recortado del hero) es el cambio de mayor impacto visual de todo el lote — hoy corta visualmente el CTA/avatares o el collage según el breakpoint | Alto — cambia el balance de toda la banda | 1.5 |
| 2 | **Micro-features en 2×2, el mockup pide 3+1.** Cambiar `grid-template-columns` de `repeat(2,1fr)` a `repeat(3,1fr)` en el breakpoint ≥1024px (mismo punto donde el hero pasa a 2 columnas) | Medio-alto — reordena visualmente los 4 ítems | 1 |
| 3 | **Falta el CTA "Ver Video".** El mecanismo (modal, normalizador de URL) ya existe en Home; falta generalizar el id y sumar la clave de Setting para Nosotros — **bloqueado por URL real del cliente**, como ya señalaba `01-nosotros-y-menu.md` | Medio — cambia el layout de la fila de CTA (hoy 1 botón, no 2) | 3 (mismo estimado del inventario anterior, no cambia) |
| 4 | **Falta la tira de avatares + "Miles de viajeros…" + estrellas.** No existe en el hero hoy | Medio — **bloqueado**: 0 de 13 testimonios activos tiene avatar real cargado | 3 (sin cambios respecto al inventario previo) |
| 5 | **Falta la píldora oscura "Más de 2.500 viajeros satisfechos".** No existe | Medio — **bloqueado**: la cifra no existe en ninguna tabla; si se construye, debe decir "13 opiniones verificadas" (`ReviewAggregator::overallStats()`), no 2.500 | 2 |
| 6 | **Proporción de la foto grande del collage** (mockup más ancha/panorámica ≈2,4:1, staging ≈1,6:1) | Bajo — es un matiz visual, no una ruptura de la estructura 1+3 que ya coincide | 0.5 (ajuste de `aspect-ratio`/crop, si se decide perseguirlo) |
| 7 | **H1 en 1 línea de texto plano** vs. 2 líneas con la 2ª en rojo — **no se resuelve como fix de maquetación**: choca con la decisión ya tomada de no tocar el copy del H1, y si se toca, la 2ª línea roja necesita ir en crema/blanco (no pasa AA en rojo puro sobre `$lat-ink`, ver §6) | Bajo-medio, pero es una decisión de negocio antes que de CSS | 0.5 (solo el markup de 2 `<span>`, si el jefe aprueba el copy) |

**Total estimado de los deltas ejecutables sin depender de terceros (1, 2, 6, 7): ~3,5 h.**
**Total de los deltas bloqueados por dato/decisión del cliente (3, 4, 5): ~8 h, sin poder
arrancar hasta que lleguen la URL de video, los avatares reales, o la decisión sobre la
cifra de la píldora.**

No incluido arriba porque ya está resuelto y no requiere trabajo: badge de marca (texto
correcto), stats bar con cifras reales sin trampa, franja de garantías sin "24/7" ni "100%
segura", subtítulos de garantías ya en el token de opacidad correcto, separador vertical de
la barra de métricas, quiebre de grid del hero en el breakpoint `lg`, y la estructura interna
del collage (1 grande + 3 en fila, incluida su versión móvil).

---

## Lo que no se midió (y no se inventó)

- El overlap exacto del badge flotante rojo "10+ años" y de la píldora oscura sobre el
  collage en el mockup: se describe por inspección visual (§1, ítems 9-10), no por barrido
  de píxel, porque cayó fuera del corte de alcance a mitad de tarea.
- La proporción exacta de la foto grande del collage en el mockup (§2.3): estimado visual,
  no barrido.
- El overlap de la barra de métricas a 640px y 768px: no se abrió ese viewport contra
  staging; se interpola entre el valor de 390px (22px) y el de 1024px (34px) pero no está
  medido.
- Contraste efectivo del borde del badge de marca con su alpha real (`rgba(...,.55)`): se
  da el criterio, no el número exacto — "no medido".
