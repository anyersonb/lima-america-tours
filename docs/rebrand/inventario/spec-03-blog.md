# Spec 03 — Artículo de blog (estructura, no pixel-perfect)

Mockup: `WhatsApp Image 2026-08-17 at 23.53.39 (1).jpeg`, 1536×1024 px, factor a diseño
1440 = ×0,9375. Medido con el puente CDP propio sobre un `<canvas>` con la imagen
embebida en base64 (`storage/app/spec-blog/probe.html`, borrado al cerrar este lote) y,
para el DELTA, sobre `https://limaamericatours.com/staging/es/blog/mejores-restaurantes-en-lima-guia-gastronomica-para-viajeros`
con el mismo puente.

> **Corrección de alcance (2026-08-20):** este documento prioriza estructura —
> anatomía, solapes, grid, agrupación, comportamiento responsive, delta— sobre medida
> al píxel. Los números que sí aparecen son los que hacen falta para tomar una decisión
> de maquetación (solape, ancho de columna, zona de riesgo de contraste); no hay un hex
> por elemento ni un barrido exhaustivo de bordes. Donde no se pudo medir con certeza
> desde una imagen JPEG aplanada (no un archivo de capas), se dice explícitamente.

---

## 1. Anatomía (de arriba a abajo) y qué monta sobre qué

| # | Bloque | Relación con el siguiente |
|---|---|---|
| 1 | Breadcrumb, 4 niveles con `>` | fila propia, no se monta sobre nada |
| 2 | **Hero split**: columna de texto (eyebrow+H1+bajada) a la izquierda + foto a sangre a la derecha con degradado, botón de play, anotación manuscrita | el hero **reserva su alto completo** para la foto — ver §3 |
| 3 | Línea de firma (avatar+nombre+rol+badge+fecha+lectura+categoría) | vive **dentro** de la columna de texto del hero, es su última fila, no un bloque aparte |
| 4 | **Tarjeta blanca de 4 features** | **se superpone al borde inferior del hero** — ver §2, es la pieza que define todo el bloque |
| 5 | Grid mayor de 2 columnas: cuerpo del artículo (izq.) + sidebar rosa (der.) | arranca **después** de la tarjeta de features, con espacio propio — no continúa el solape |
| 6 | Cita destacada | vive **dentro** del cuerpo del artículo (columna izquierda), no es un bloque独立 |
| 7 | "Artículos relacionados" (3 tarjetas) | ancho completo, debajo de las dos columnas — el sidebar no sigue hasta aquí |
| 8 | CTA final "¿Te animaste a viajar?" | ya existe, ancho completo, no se toca |

**Regla de composición clave:** el hero NO es "foto de fondo con texto encima". Es una
foto a sangre que vive en su propio layer, y el texto flota en una columna angosta a la
izquierda que nunca se superpone a la foto — se apoya en el degradado para que el
límite no se vea duro (detalle en §3).

---

## 2. La tarjeta de 4 features montando sobre el hero

Medido en las tres columnas (texto x=200, mixta x=700, foto x=1300 del raw 1536): en
las tres, el borde superior de la tarjeta blanca aparece en la **misma fila de píxeles**,
y en x=700/x=1300 se pasa de tonos oscuros de foto directo a blanco de tarjeta en 1-2 px
(antialiasing de la sombra), sin ninguna franja intermedia. y ≈ 470 px raw →
**≈ 441 px de diseño** desde el borde superior del hero.

**Límite real de esta medición — decílo explícito:** el mockup es un JPEG ya aplanado
(composite final, no capas). Ese corte recto y parejo en las tres columnas **confirma
que hero y tarjeta comparten el mismo eje de corte** (la tarjeta no está "debajo" del
hero en otro punto en cada columna, como pasaría si fueran bloques independientes mal
alineados) — pero **no permite medir cuánta foto queda oculta detrás de la tarjeta**,
porque un compuesto aplanado nunca muestra lo que un elemento opaco tapa. Eso es
**no medido**, y no se puede medir desde esta imagen.

**Recomendación de implementación** (no es una medida, es la decisión de arquitectura
que sí se puede tomar con lo que hay):

- El `.lat-blog-hero` reserva su alto completo (foto + `padding-bottom`) igual que hoy
  hace el hero del listado (`_lat-blog.scss:24-53`).
- La tarjeta de features se posiciona con `margin-top` negativo sobre ese
  `padding-bottom`, no con `position: absolute` fuera de flujo — así el documento sigue
  midiendo alto real y el `padding-bottom` del hero *es* el presupuesto de solape.
- Punto de partida sugerido: **`padding-bottom` del hero ≈ 60-80px de diseño** y la
  tarjeta con `margin-top: -60px` a `-80px` (la mitad de su propio alto aproximado,
  patrón estándar de "card flotante"), a validar visualmente en navegador — el valor
  exacto es cosmético, no algo que el mockup revele.
- La tarjeta cubre el **ancho completo del contenedor** (texto + foto), no solo la
  columna de texto: por eso el corte se ve como una sola línea recta de punta a punta.

---

## 3. El hero como composición (texto + foto a sangre + degradado)

### 3.1 Estructura de capas (no "imagen de fondo con overlay de texto")

Es una composición de **dos columnas independientes**, no una foto de fondo con un
scrim oscuro y texto encima (ese patrón sí existe hoy en `.lat-blog-hero::before` del
**listado**, pero es OTRO patrón — no confundirlos):

1. **Columna de texto**, en flujo normal dentro del `.lat-wrap` (el mismo contenedor
   que usa el resto del sitio): eyebrow con ícono, H1 a dos líneas, bajada, línea de
   firma. Ancho medido ≈ 430 px raw (90→520) → **≈ 403 px de diseño**, fijo, no fluido
   (razón en §3.3).
2. **Foto a sangre**, en su propio layer (`position: absolute` o `img` de fondo dentro
   de un contenedor con `overflow` propio), pegada al **borde del viewport**, no al
   borde del `.lat-wrap` — llega hasta x=1536 raw (el borde de la imagen), mientras el
   contenedor de texto respeta el gutter del `.lat-wrap`. Es foto a sangre real, no una
   imagen contenida dentro del mismo `max-width` que el texto.

### 3.2 El degradado es una máscara sobre la foto, no un velo oscuro encima

Muestreo horizontal en y=350 (zona sin texto, entre bajada y firma), raw x 0→1536:

| Zona | x raw | Color muestreado | Lectura |
|---|---|---|---|
| Crema plano | 0–500 | `#f9f8f6`…`#faf9f7` | fondo de página, sin foto |
| Transición | 520–680 | `#f5f1ee` → `#ebe6e2` → `#d8d0c5` → `#9f9d9e` → `#92816d` → `#4d422c` | mezcla crema+foto, tonos **cálidos/térreos**, nunca vira a negro puro |
| Foto plena | 700–1536 | tonos propios de la foto (madera, luces, piel) | foto sin ningún velo adicional |

Si fuera un scrim oscuro (como `.lat-blog-hero::before` del listado,
`rgba(10,8,7,.68→0)`), la transición tendría que virar hacia **gris neutro/negro**
conforme sube la opacidad del velo. Acá vira hacia tonos cálidos mezclados con los
colores propios de la foto — eso es consistente con una **máscara de alfa sobre la
propia imagen** (`mask-image: linear-gradient(...)` o un `img` con gradiente de
opacidad), que deja ver el fondo crema de la página *a través* de la foto, no con una
capa de color plantada encima.

**Extensión de la máscara**: la zona de transición mide ≈160 px raw (520→680) →
**≈150 px de diseño**, aproximadamente el **10% del ancho total** — es una franja
angosta en el borde izquierdo de la foto, no un degradado que cubre la mitad de la
imagen. El resto de la foto (desde ≈700 raw / ≈656px diseño hasta el borde) está a
plena opacidad.

### 3.3 Riesgo de contraste — el fondo cambia a lo largo del propio texto

Esto es lo numérico que sí hay que mantener. Se midió el peor caso real dentro del
propio mockup: el segundo renglón del H1 y la bajada llegan a tocar el arranque de la
zona de transición (x≈550-650 raw) con su último tramo de texto.

| Elemento | Color de texto medido | Fondo peor caso muestreado en la transición | Contraste calculado (WCAG) | Veredicto |
|---|---|---|---|---|
| Bajada (excerpt) | ≈ `#3a352f` ($lat-ink-soft) | ≈ `rgb(200,195,186)` (zona de transición, no la foto plena) | **6.93:1** | Pasa AA (4.5:1) con margen |
| H1 (más oscuro/grueso que la bajada) | ≈ `#171412` ($lat-ink) | mismo fondo de transición | mayor que el de arriba (texto más oscuro) | Pasa, con más margen todavía |

**El riesgo real no es el degradado medido, es el ancho de columna si se vuelve
fluido.** Si el `max-width` del texto se deja crecer (títulos largos, o EN/PT con más
caracteres), el texto puede terminar sentado sobre la foto a **plena opacidad**
(x>700 raw), donde se midieron tonos tan oscuros como `#241a01` / `#030301` — contra
eso, cualquier texto oscuro cae a ~1:1, e incluso texto blanco necesitaría el mismo
tipo de refuerzo que ya usa `.lat-blog-hero::before` en el listado.

**Regla de implementación no negociable:** el `max-width` de la columna de texto del
hero debe ser un valor **fijo** (≈400-420px de diseño, con el mismo criterio que ya usa
`.lat-page-hero__sub { max-width: 480px }`), nunca un porcentaje fluido del hueco entre
el borde y la foto. Si el título no entra en dos líneas a ese ancho en algún idioma, se
reduce el tamaño de fuente responsivamente o se permite una tercera línea — nunca se
ensancha la columna hacia la foto.

### 3.4 Qué pasa cuando el viewport se angosta y el texto ya no cabe al lado (<1024)

No hay mockup de esto (el mockup es una sola captura a ~1440-1536). Con lo que sí está
construido y probado en el proyecto, la recomendación de estructura:

- **≥1024px**: composición de dos columnas descrita arriba.
- **<1024px**: la foto a sangre junto al texto deja de tener sentido (no hay ancho para
  mostrar una foto reconocible al lado del texto sin achicarla a una tira irrelevante).
  Recomendado: **no encoger proporcionalmente** — colapsar a una sola columna
  reutilizando el patrón que ya existe y ya está probado en 4 breakpoints:
  `.lat-page-hero` (foto de fondo completo con scrim oscuro, texto encima, el que usan
  tours/privacidad/términos) **o**, más simple todavía y sin reconstruir contraste por
  cada foto nueva, el patrón actual de esta misma pantalla: texto arriba en banda plana
  clara (`.lat-post-head`, ya existe) + foto como banda horizontal debajo
  (`.lat-post-cover`, ya existe). La segunda opción es la de menor riesgo porque ya
  está construida y no exige medir contraste foto-por-foto en cada tour nuevo.
- **<640px**: la anotación manuscrita se oculta (ilegible a ese tamaño, ya lo marcaba
  el inventario anterior); el botón de play y el badge de marca (bloqueado de todas
  formas, ver §7) se mantienen centrados sobre la foto si la foto sigue presente.

---

## 4. El grid mayor: contenido vs. sidebar

Medido a partir del mockup (raw → diseño):

| Medida | raw | diseño (×0,9375) |
|---|---|---|
| Gutter exterior del contenedor | ≈90-96 | **≈85px** |
| Ancho total del área de contenido (entre gutters) | ≈1360 | **≈1275px** |
| Ancho del sidebar (caja rosa) | ≈410 (995→1405) | **≈384px** |
| Ancho de columna de contenido (resto, con gap) | ≈910 (por diferencia) | **≈853px** |
| Gap entre columnas | ≈40-45 (estimado por diferencia, no un borde limpio de medir en JPEG) | **≈37-40px, no medido con precisión — usar el gap estándar del proyecto (24-32px) como punto de partida** |

**Nota sobre el gutter:** 85px de diseño es notablemente más ancho que el gutter actual
del sitio (`$lat-wrap` usa `padding: 0 24px`, `_lat-header.scss:9-13`). O el mockup usa
un contenedor interior más angosto que `$lat-wrap` (1480px) para esta pantalla en
particular, o el gutter real del diseño es mayor al del resto del sitio. **Decisión
para quien maqueta**: no estirar el contenido a los 24px de gutter estándar sin
revisarlo contra el resto de las secciones ya construidas de esta misma página (related
grid, CTA final), que sí usan `.lat-wrap` con 24px. Igualar todo a 24px es la opción de
menor riesgo de inconsistencia entre secciones de la misma página.

**Dónde arranca el sidebar — confirmado, no asumido:** el borde superior de la caja
rosa se midió en y≈624-626 raw (**≈585px** de diseño desde el techo del hero), que es
**después** de la tarjeta de features y a la misma altura donde arranca el primer
párrafo del cuerpo — no a la altura del hero ni montado sobre la tarjeta de features.
Esto confirma la lectura del mockup: **el sidebar es par de la columna de contenido**,
no del hero. Estructuralmente, ambas columnas (contenido + sidebar) deben empezar en el
mismo `grid-template-rows` / la misma fila, con `align-items: start`.

**Sticky o estático:** el mockup es una sola captura, no se puede confirmar
comportamiento de scroll desde una imagen. **No medido.** Recomendación por
consistencia con el resto del sitio: la ficha de tour ya tiene un sidebar sticky
(`.lat-book-head` sticky ≥980px, `02-tour-y-blog.md` fila #17) — seguir el mismo
criterio acá (sticky ≥1024px) es coherente con el patrón ya construido, salvo que el
jefe decida lo contrario.

**Breakpoint de colapso:** <1024px el sidebar se apila **debajo** del cuerpo completo
(no al lado, no intercalado) — mismo criterio que la caja "¿Tienes dudas?" de la ficha
de tour, que también pasa de dos columnas a una sola <640/768 sin lógica nueva que
inventar.

---

## 5. Agrupación y jerarquía de los bloques compuestos

### 5.1 Línea de firma — un solo bloque, no elementos sueltos

Orden horizontal: **avatar circular → "Por **Augusto** – Guía Local" → badge de
verificado (check azul en círculo rojo) → separador visual → fecha con ícono →
separador → "N min de lectura" → separador → categoría con ícono**.

Es un único `<div>` flex con `align-items:center`, no dos filas ni un `<dl>` — el
avatar y el nombre+rol+badge son un sub-grupo (`author`), y fecha/lectura/categoría son
otro sub-grupo (`meta`) que hoy YA existe como bloque flex en `.lat-post-meta`
(`blog/show.blade.php:111-132`). La única pieza nueva es anteponerle el sub-grupo de
autor con foto: `avatar (40px circular) + (nombre en negrita, rol debajo en texto
chico) + badge` como una unidad, luego el resto de metadatos igual que hoy.

**Colapso <480px** (recomendado, no medido): si no hay foto de autor (caso de hoy — ver
§7), el bloque colapsa a solo texto, sin espacio reservado para un avatar vacío.

### 5.2 Cita destacada — estructura, no solo estilo

Estructura de tres partes, no un `blockquote` plano:
1. Glifo de comillas grande (`::before`, ya presupuestado como token existente
   `$lat-red`, sin campo nuevo — inventario anterior, sección C).
2. Texto de la cita, **sin cursiva** (ver restricción tipográfica, §8).
3. Atribución ("– Augusto, Guía Local") en una línea aparte, alineada a la derecha
   dentro del mismo bloque — hoy el `blockquote` de `_lat-blog.scss:420-426` no separa
   la atribución del cuerpo de la cita; en el mockup son visualmente dos líneas de
   tratamiento distinto (cita en tamaño mayor, atribución en tamaño menor y alineada a
   la derecha).

Recomendación de marcado: `<blockquote><p>…</p><cite>— Augusto, Guía Local</cite></blockquote>`
si el editor WYSIWYG permite insertar `<cite>`; si no, el "– Nombre" puede ir como
último `<p>` con una clase de modificador vía convención documentada al equipo de
contenido (no requiere campo nuevo en BD, ver §9).

### 5.3 Las 3 tarjetas de "Artículos relacionados" — agrupación, no lista plana

Cada tarjeta es: **imagen con badge de categoría encimado (esquina inferior
izquierda) → título (2 líneas) → fila de meta "fecha · N min"**. Hoy
`.lat-related-card` (`_lat-blog.scss:457-482`) tiene imagen + título + fecha, **sin**
badge de categoría encimado y **sin** minutos de lectura — son los mismos dos campos
que ya usa `.lat-blog-card` del listado (`__badge`, `_lat-blog.scss:181-194`, y
`__meta` con el separador " · " automático, línea 227-235). Es la misma tarjeta del
listado, con menos padding/tamaño para caber en 3 columnas dentro de un sidebar-menos
layout — **no construir una tercera variante de card**, adaptar `.lat-related-card`
para que reciba `category` y `reading_minutes`, reutilizando el patrón de
`.lat-blog-card__badge`/`__meta` ya existente y ya resuelto (separador " · " solo entre
los datos que tengan valor real).

---

## 6. Comportamiento por breakpoint (resumen, construido sobre lo de arriba)

| Breakpoint | Hero | Tarjeta de 4 features | Grid mayor | Related |
|---|---|---|---|---|
| ≥1024 | split texto+foto+degradado, play, anotación | 4 columnas, montada sobre el hero | 2 columnas (contenido+sidebar sticky) | 3 columnas (ya resuelto) |
| 768–1023 | colapsa a patrón existente (foto banda horizontal debajo del texto, sin degradado) | 2×2, ya no monta sobre nada — sigue en flujo normal tras el hero apilado | 1 columna, sidebar apilado debajo del cuerpo | 2 columnas (ya resuelto, breakpoint 900px existente) |
| <680/640 | igual que arriba, anotación manuscrita oculta | 1 columna | 1 columna | 1 columna (ya resuelto, breakpoint 560px existente) |

Los breakpoints de related (900/560) y de blog-grid del listado (1024/680) **ya están
codificados** en `_lat-blog.scss` — se reutilizan tal cual para el nuevo grid mayor, no
se inventan valores nuevos sin motivo.

---

## 7. El hueco de la foto del hero (bloqueado — MAIDO + persona real)

La foto del mockup (Mitsuharu Tsumura sosteniendo un plato, logo MAIDO visible al
hombro) **no se puede publicar**: persona real identificable + marca de un tercero, sin
autorización. Esto es lo que necesita quien elija el reemplazo:

| Requisito | Valor |
|---|---|
| Proporción del recorte a pleno (zona de foto opaca, sin la franja de degradado) | ≈ 936:441 raw → **≈2.12:1** (paisaje ancho) |
| Proporción incluyendo la franja de degradado (ancho total de la "foto", desde donde arranca la máscara) | ≈1016:441 raw → **≈2.30:1** |
| Punto focal recomendado | sujeto/plato centrado en el **tercio derecho** del recorte (la máscara consume el tercio izquierdo, así que el sujeto nunca debe caer ahí) |
| Resolución mínima | para bleed real hasta pantallas anchas (hasta 2560px), **≥2400×1100px** en la proporción de arriba — pedir el archivo más grande posible del catálogo, nunca estirar uno chico (mismo defecto ya señalado para el kit de banners en `LOTE-MOCKUPS-AGO-2026.md`) |
| Fondo del recorte en el borde izquierdo | debe admitir que se le aplique una máscara de opacidad sin verse "cortado" — evitar fotos con un elemento importante (rostro, texto, logo) pegado al borde izquierdo del recorte |
| Contenido | gastronomía peruana, sin rostro identificable de terceros ni marca ajena — catálogo propio o licenciado |

---

## 8. Tipografía — traducción a Raleway, sin inventar una familia nueva

El H1 del mockup usa una **serif editorial de alto contraste** (serifas con bracket,
trazo grueso/fino marcado — compatible con familias tipo Playfair Display, Lora o
Georgia; no se puede confirmar cuál exactamente sin el archivo fuente de la maqueta, y
**no es necesario confirmarlo**: no se va a usar una serif nueva).

Producción usa **Raleway** (titulares, peso 800/900 ya verificado en el navegador,
`tailwind.config.js:22-28`) y **Open Sans** (cuerpo) — **sin cara itálica cargada** en
el `<link>` de Google Fonts. Traducción:

| Elemento del mockup | Tratamiento en el mockup | Traducción a producción |
|---|---|---|
| H1 | serif, ~alto contraste, dos líneas | **Raleway 800-900**, mismo tratamiento que ya usa `.lat-post-title` (`_lat-blog.scss:378-386`) — no cambia nada, ya está resuelto |
| Cita destacada | serif en cursiva | **Raleway 500-600, sin itálica** (una cursiva sintética por `font-style:italic` sobre una fuente sin esa cara sale distorsionada/oblicua fea). Diferenciar la cita del cuerpo por **tamaño mayor + el glifo de comillas grande + color**, no por inclinación. Quitar `font-style: italic` de `_lat-blog.scss:425` |
| Tarjetas relacionadas / eyebrow | serif liviana | ya resuelto con Raleway 700, sin cambios |

---

## 9. Campos de CMS que faltan (estructura de dato, no solo de vista)

| Tabla | Columna propuesta | Tipo | Para qué |
|---|---|---|---|
| `blog_posts` | `features` | `json nullable` — repeater de hasta 4 filas: `icon` (string, nombre de ícono del set ya usado en el sitio), `title_es/en/pt`, `text_es/en/pt` | Tarjeta de 4 features (§1, §2) |
| `blog_posts` | `guide_id` | `foreignId nullable, constrained('guides')->nullOnDelete()` | Avatar+rol+verificado de la línea de firma (§5.1) — reutiliza el modelo `Guide` que ya existe para Nosotros, no crear un segundo sistema de autor |
| `blog_posts` | `video_url` | `string nullable` | Botón de play sobre el hero — reutiliza el modal de Home, mismo patrón que el de la ficha de tour |
| — | **cita destacada: sin columna nueva** | — | Sigue siendo `blockquote` dentro del WYSIWYG de `body`; solo se documenta al equipo de contenido el marcado `<cite>` para la atribución (§5.2) |
| `blog_posts` | `related_category_id` (ya propuesto en `02-tour-y-blog.md`) | `foreignId nullable, constrained('categories')->nullOnDelete()` | CTA del sidebar rosa "Ver experiencias gastronómicas" (§1, bloque 5) |

Ningún post real tiene hoy dato en ninguno de estos campos nuevos — los componentes que
dependen de ellos se ocultan por guard de dato hasta que el cliente cargue contenido
(mismo criterio que ya se aplicó al rating sembrado y a los stats de Nosotros).

---

## 10. DELTA estructural contra lo publicado

Medido en vivo sobre
`https://limaamericatours.com/staging/es/blog/mejores-restaurantes-en-lima-guia-gastronomica-para-viajeros`
(1440px) con el puente CDP:

| Elemento | Medición actual (staging) | Mockup | Diferencia |
|---|---|---|---|
| Header del post | `.lat-post-head`, banda plana clara, alto 198px, **sin foto** | hero split con foto a sangre + degradado + play + anotación | **NO EXISTE** — sección nueva completa |
| Tarjeta de 4 features | no existe ningún elemento | tarjeta blanca montada sobre el hero | **NO EXISTE** |
| Grid mayor (contenido+sidebar) | `.lat-post-body` es **una sola columna, 760px, centrada** (`x=332.5, w=760` sobre contenedor de 1425px) — **no hay sidebar en el DOM** | 2 columnas: contenido ≈853px de diseño + sidebar ≈384px | **NO EXISTE** — es el cambio de mayor impacto: pasar de 1 a 2 columnas reordena todo el `<article>` |
| Sidebar caja rosa | no existe | caja rosa con CTA + avatares/cifra | **NO EXISTE** |
| Línea de firma | `.lat-post-meta`: ícono genérico + `author_name` texto plano, fecha, minutos — **sin avatar, sin rol, sin badge** | avatar 40px + nombre+rol + badge verificado + fecha + lectura + categoría, todo agrupado | **EXISTE PARCIAL** — falta el sub-grupo de autor con foto |
| Bajada (excerpt) visible | no se imprime en la vista (solo `<meta description>`) | visible debajo del H1 | **NO EXISTE** en pantalla, aunque el dato ya existe en BD |
| Botón de play + modal | no existe | sobre la foto del hero | **NO EXISTE** |
| Cita destacada | `blockquote` con borde rojo izq. + **cursiva**, sin glifo de comillas grande, sin atribución separada | comillas grandes, sin cursiva, atribución en línea aparte | **EXISTE PARCIAL** — hay que sacar la itálica y agregar el glifo + la separación de atribución |
| Breadcrumb | 3 niveles (Inicio · Blog · título), separador `&middot;` | 4 niveles (Inicio › Blog › Categoría › título), separador `›` | **EXISTE PARCIAL** — falta el nivel de categoría y cambia el separador |
| Cover del artículo | `.lat-post-cover`, imagen única centrada 900×460, sin overlay | reemplazada por el hero split (ya no es una imagen aparte debajo del header) | se **absorbe** dentro del hero nuevo, no coexisten los dos patrones |
| Related — datos por tarjeta | `.lat-related-card`: imagen + título + fecha. **Sin badge de categoría, sin minutos de lectura** | imagen + badge de categoría encimado + título + fecha · minutos | **EXISTE PARCIAL** — mismos campos que ya usa `.lat-blog-card` del listado, faltan en esta variante |
| Related — grid | 3 tarjetas, 444px cada una, gap 22px — **ya funciona igual que el mockup** | 3 columnas | **SIN CAMBIOS** |
| CTA final | presente, funcionando, contraste ya corregido | no aparece en este mockup (corta antes) | **SIN CAMBIOS**, no se toca |
| Tags | presentes en el componente pero `null` en este post puntual (no tiene tags cargados) | no aparecen en el mockup | **SIN CAMBIOS**, comportamiento correcto (se oculta sin dato) |

---

## 11. Lista priorizada de deltas (para que el maquetador ejecute)

Ordenada por impacto estructural, con horas heredadas de `02-tour-y-blog.md` donde
aplica (no se remiden acá, ya estaban ahí) y ajustadas donde este documento cambió el
enfoque:

| # | Delta | Impacto | Horas | Bloqueo |
|---|---|---|---|---|
| 1 | Grid mayor 2 columnas (contenido+sidebar) — reordena todo el `<article>` | **Crítico** — todo lo demás de la mitad inferior depende de esto | 3 (incluido en #14 de `02-tour-y-blog.md`, se aísla acá porque es la pieza que más reordena HTML) | Ninguno |
| 2 | Hero split (foto a sangre + máscara + columna de texto fija) | **Crítico** — define el resto del hero | 6 | Foto de reemplazo (§7) — se puede maquetar con foto placeholder mientras se resuelve |
| 3 | Tarjeta de 4 features + su solape sobre el hero | **Alto** — depende del hero (#2) para tener dónde montar | 4 | Campo `blog_posts.features` (§9) — sin dato, componente oculto |
| 4 | Sidebar caja rosa "¿Listo para vivirlo?" | **Alto** — depende del grid (#1) | 2 | `related_category_id` para el CTA con mapeo real |
| 5 | Línea de firma con avatar/rol/badge | **Medio** | 3 | `guide_id` — sin autor asignado, se muestra sin foto (no con foto de stock) |
| 6 | Botón de play + modal de video | **Medio**, reutiliza modal existente | 2 | `video_url` — sin dato, botón oculto |
| 7 | Cita destacada: quitar itálica, agregar glifo grande y separar atribución | **Bajo** | 1 | Ninguno |
| 8 | Bajada (excerpt) visible en el header | **Bajo** | 0.5 | Ninguno, el dato ya existe |
| 9 | Breadcrumb a 4 niveles con separador `›` | **Bajo** | 1 | Ninguno |
| 10 | Related cards: agregar badge de categoría + minutos de lectura | **Bajo** | 1 | Ninguno, mismo patrón que el listado |
| 11 | Anotación manuscrita con flecha | **Bajo**, decorativo, opcional | 1.5 | Ninguno (SVG inline, sin asset de terceros) |

**Total ≈ 25 horas** de maquetación estructural (no incluye backend de las 3
migraciones nuevas ni la resolución de la foto de reemplazo, que quedan en
`02-tour-y-blog.md` y en la decisión del jefe respectivamente).

---

## Archivos de referencia

- Vista actual: `resources/views/blog/show.blade.php`
- SCSS actual: `resources/scss/pages/_lat-blog.scss` (bloque "Artículo individual (show)",
  línea 361 en adelante)
- Tokens: `resources/scss/abstracts/_variables.scss`, `tailwind.config.js`
- Contexto previo: `docs/rebrand/inventario/02-tour-y-blog.md` (sección 2),
  `docs/rebrand/inventario/00-VALIDACION-STAGING.md`, `docs/rebrand/LOTE-MOCKUPS-AGO-2026.md`
