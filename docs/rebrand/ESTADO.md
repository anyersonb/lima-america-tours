# Estado del rebrand "anti-IA" y del lote de mockups

Actualizado: 2026-08-25 · Rama `feat/mockups-ago-2026` · Publicado en staging: **ver `.deployed-commit` del servidor**

Este archivo existe para que el siguiente que abra el proyecto (o yo mismo dentro
de un mes) no tenga que reconstruir de memoria en qué quedó todo.

## Lote 2026-08-25 — los cinco comentarios del jefe sobre staging (móvil)

Cinco capturas por WhatsApp, todas desde el celular. Tres eran datos que ya
tenían su campo esperando vacío en Configuración; dos eran código.

### 1. "Aquí abajo faltaría el RUC: 20616108264"

Solo faltaba el DATO. El campo `company_ruc`, su guard y su lugar en el
copyright estaban escritos desde el 21/08 y vacíos **a propósito**: en este repo
llegaron a convivir dos RUC contradictorios publicados a la vez, así que se
decidió no imprimir ninguno sin confirmación del cliente. Hoy la hay.

Cargado en `Configuración → Contacto → Datos legales`, y también en
`database/data/lote-2026-08-25-datos.sql` — un `git push` no lleva filas.

### 2. "Falta colocar el código ESSNA" + el sello de agencia registrada

Dos piezas distintas que el jefe nombró juntas, y conviene no confundirlas
nunca más:

| | Qué es | Dónde va |
|---|---|---|
| **Sello RNAVT** | "Agencia de viajes y turismo registrada" (MINCETUR) | Franja de confianza del footer |
| **Afiche ESNNA** | "Protégeme — Turismo Responsable": el documento con las tres leyes y las líneas 1818 / Línea 100 | Arriba de `/esnna` |

**El sello tiene tamaño NORMADO y no lo estábamos respetando.** El manual de
marca fija dos presentaciones para medios digitales: vertical **150×250** y
horizontal **300×120**. El footer lo pintaba con `height: clamp(52px,7vw,68px)`
y ancho automático; como el archivo del cliente resultó vertical (480×758), el
navegador lo dejaba en **33×52 px** — medido en pantalla, no supuesto — con las
cuatro líneas de texto convertidas en una mancha.

Ahora la caja la decide `App\Support\RegistrySeal::box()` **midiendo el
archivo**, no suponiendo la orientación: el día que el cliente suba la versión
horizontal desde el panel, el footer la pinta a 300×120 sin tocar código. El
`getimagesize()` se cachea por ruta+mtime; sin eso sería una lectura de disco en
el footer de todas las páginas del sitio.

Lo vigila `RegistrySealBoxTest` (5 casos), **probado en rojo** cambiando la
constante del helper a 999×999 antes de darlo por bueno.

**El afiche**: el pedido fue literal, con la referencia de limaexperience —
"cuando damos clic debe abrir esto". El enlace del footer ya llevaba a `/esnna`,
así que lo que cambió es qué se ve al llegar: el afiche va primero, a lo ancho
de la columna, y enlaza al archivo para abrirlo a pantalla completa (en su pie
están las leyes y los teléfonos de denuncia, y a 920px de columna esa letra no
se lee). El texto del compromiso sigue debajo: es contenido nuestro, indexable,
y la página se publica igual si algún día no hay afiche.

Campo nuevo `esnna_poster` en Configuración, a 1200px de ancho — la gente lo
abre para LEERLO, no para reconocerlo. Es distinto de `esnna_seal`, que es la
marquita chica del footer.

### 3 y 4. "Los comentarios de Google quedarán así, en color negro?" / "Si utilizamos el logo de Google en vez del colorcito"

El mismo pedido visto en dos pantallas, y las dos lo resolvían distinto:

- home y Nosotros → el nombre del origen en versalitas grises (`TRIPADVISOR`),
  que es lo que él leyó como "en color negro";
- `/resenas` → un punto de color con el nombre al lado ("el colorcito").

Tres marcados para el mismo dato. Ahora hay uno: `<x-review-source>`, con
variantes `card` / `dark` / `chip` / `--lg`. Los dos orígenes con marca propia
van con su **logo real** — el isotipo de Google en sus cuatro colores y el búho
de Tripadvisor en su verde — sin recolorear: son marcas de terceros y el
reconocimiento es justo lo que le da valor a la atribución.

"Nuestra web" **no** lleva logo: no es una plataforma externa, y un sello propio
ahí haría pasar por verificada una reseña que cargamos nosotros. Conserva su
punto de color.

El nombre en texto sí toma el color de la marca, pero no el del logo: el
`#00AA6C` de Tripadvisor no llega a 3:1 como texto sobre blanco. El texto usa
`#00623d` (7.26:1) y el verde vivo se queda en el logo. Google va en `#1a73e8`
(4.68:1). Sobre la franja oscura de Nosotros los dos se apagan, y ahí el nombre
va en blanco al 72% con el logo sosteniéndose solo.

**De paso apareció un defecto que no era del pedido**: `/resenas` tenía **scroll
horizontal en móvil**, y venía de antes (medido: 415px de `scrollWidth` en un
documento de 375 incluso con la píldora vieja; con el chip nuevo, 431). La causa
no era el chip sino el `min-width: auto` que traen por defecto los items de
grid: la tarjeta tomaba su ancho mínimo de contenido y salía de 411px. Con
`min-w-0` en el `<article>`, 375. Además, bajo 480px el chip deja solo el logo
—el nombre queda en sr-only— para no robarle el ancho al nombre del viajero.

### 5. "¿Habrá forma de administrar esa parte?" (los guías)

Sí, y ya la había: **Admin → Guías**, con foto, rol, biografía e idiomas por
idioma, redes, orden y activo/inactivo. Lo que él vio en la captura son las
iniciales en un círculo rojo, que es el *fallback* de cuando el guía no tiene
foto cargada. **Faltan las fotos, no el módulo.**

### 6. Cusco en los primeros lugares

"Aquí podrían aparecer en primeros lugares los tours de Cusco creo yo". Los
siete tours de Cusco estaban publicados y completos (foto, precio, galería) pero
ninguno destacado, así que "Tours Destacados" era todo Lima/Ica.

Es dato, no código: `featured_order` es manual y se edita tour por tour en el
panel. Se **intercaló** en vez de poner los cuatro de Cusco arriba — subir Cusco
no es apagar Lima, que da nombre a la agencia y de donde vienen casi todas las
reseñas:

Machu Picchu · City Tour Lima · Montaña 7 Colores · Paracas-Ica · Valle Sagrado
· Huaca Pucllana · Laguna Humantay · Parque de las Aguas.

En `database/data/lote-2026-08-25-datos.sql`, con la advertencia de verificar
los ids antes de correrlo en otro servidor.

### Abierto, decide Anyerson

- **La versión horizontal del sello (300×120) no la tenemos.** Solo llegó la
  vertical. El footer ya sabe pintar las dos; si el cliente manda el archivo
  horizontal, encaja mejor en esa franja y se carga desde el panel sin tocar
  código.
- **Los guías no tienen foto.** Hay que pedírselas o la sección seguirá
  mostrando iniciales.
- **Las tarjetas de tour publican "4.8 (0)"**: una nota con cero reseñas
  detrás. Es la misma clase de cifra sin respaldo que ya se limpió en otras
  pantallas; no entraba en este lote.
- **El FAB de WhatsApp tapa el chip de idioma del footer en móvil.** Se ve en
  la captura del jefe. NO se tocó: el chip no es un control, y sumar elementos
  no-CTA a `CONTROL_SELECTOR` ya provocó dos regresiones documentadas (el FAB
  aterriza encima de otra cosa). Si molesta, se resuelve moviendo el chip, no
  el FAB.

## Lote 2026-08-24 (tarde) — los cuatro comentarios del jefe sobre staging

Cuatro capturas por WhatsApp. Los cuatro eran defectos reales; ninguno era de
maquetación pura.

### 1. "Leer más" de las promociones llevaba al WordPress viejo

`OfferSeeder` guardaba `cta_url = '/es/tours'`. Es una ruta absoluta **desde la
raíz del dominio**, y esta app no vive en el docroot: staging cuelga de
`/staging`, así que el navegador pedía `limaamericatours.com/es/tours` — que es
el WordPress — y devolvía "No se ha podido encontrar la página". Es la misma
clase de fallo que ya había tumbado el `site.webmanifest` en julio.

Se arregló en los dos niveles, porque el dato y el código fallaban por separado:

- **Código:** `Offer::ctaHref($locale)` pasa cualquier ruta interna por `url()`,
  respeta las URLs absolutas (`http`, `https`, `mailto`, `tel`, ancla) tal cual,
  y sin `cta_url` cae al tour vinculado o al catálogo **del idioma que se está
  viendo**. Eso arregla de paso un segundo defecto que nadie había reportado: el
  `/es` fijo sacaba de su idioma a quien navegaba en inglés o portugués.
- **Dato:** `cta_url` a `null` en el seeder y en las 3 filas de staging.

Lo vigila `OfferCtaUrlIsNotRootRelativeTest` (5 casos), **probado en rojo**
rompiendo `ctaHref()` a propósito antes de darlo por bueno.

### 2. El correo publicado no era del cliente

La barra superior mostraba `hola@limaamericatours.com`. No es una casilla suya:
salía de un `?:` escrito en la vista. Midiéndolo aparecieron **tres direcciones
inventadas distintas** repartidas por el repo — `hola@`, `info@` y `reservas@` —
así que el sitio publicaba una u otra según qué archivo pintara la línea, y
ninguna existe. Mismo patrón que el teléfono de Lima View en julio.

Única fuente ahora: `Setting::contactEmail()` y `contactEmailSecondary()`, **sin
default**. Sin dato, la línea no se pinta (topbar, footer, ficha de Contacto,
ESNNA, `llms.txt` y la propiedad `email` del JSON-LD, que directamente no se
declara). Los correos transaccionales caen a `config('mail.from.address')`, que
es una casilla que sí existe.

Publicadas las **dos** cuentas reales — `americatours09@gmail.com` e
`infolimaamericatours@gmail.com` — juntas en footer y Contacto, como en
producción. En la topbar va solo la principal: no entra más de una.

### 3. "Esto es una imagen completa en el footer, no 2"

El bloque de cierre pintaba Barranco y el footer la panorámica de Machu Picchu,
uno pegado al otro, con una costura horizontal en el medio. Es la misma queja
que se había atendido a medias el mismo día quitando la raya roja del borde: el
corte que él ve **no era la raya, era el cambio de foto**.

**Ponerles la misma foto no alcanza, y se comprobó en pantalla:** con la misma
imagen la costura sigue viéndose, porque cada bloque la recorta con su propio
`cover` y las alturas son distintas — la foto pega un salto justo en la unión.
La primera versión del fix hacía exactamente eso y la captura a 1440 la
descartó.

Lo que sí funciona: el cierre apaga su velo hasta el color **exacto** del footer
(`rgba(16,13,11,1)` al 100% del gradiente) y el footer no pinta foto, se queda
con su `background-color: #100d0b`. La única foto de la zona es la del cierre y
se funde a negro sin borde. Verificado en 1440 y 390, sin scroll horizontal. Si
algún día se le devuelve una foto propia al footer, vuelve la costura.

### 4. Reseñas de Tripadvisor, traídas de producción

Pedido literal: "aquí se podría colocar reseñas de Tripadvisor también" y
"verifica producción para traerte la configuración de comentarios".

Lo que hay en el WordPress de producción (plugin **TrustIndex**, leído de su
base):

| Dato | Valor |
|---|---|
| Perfil Tripadvisor | `.../Attraction_Review-g294316-**d19923192**-Reviews-Lima_America_Tours-Lima_Lima_Region.html` |
| Tripadvisor | **5,0 · 69 opiniones**, 10 reseñas cacheadas (dic 2025 – feb 2026) |
| Google Place ID | `ChIJZ_zFUQuxPScRYW0y3wdpP50` |
| Google | **5,0 · 186 opiniones**, 10 reseñas cacheadas (may – jun 2026) |

**No hay API keys que reutilizar.** TrustIndex no usa las APIs de Google ni de
Tripadvisor: descarga por su propio servicio con un id de pedido. Se buscó
`AIza…` y cualquier `*_key` en `wp_options` — solo hay las de SMTP, WooCommerce
y Elementor. Los dos identificadores de arriba sí quedan cargados, listos para
el día que el cliente consiga las keys.

Las 10 reseñas de Tripadvisor se importaron con **`reviews:import-tripadvisor`**,
idempotente por `external_ref` (`ta_review_{id}`), origen `Tripadvisor`. El JSON
vive en **`database/data/`**, dentro del repo, y no en `storage/app/` como el de
`ImportWpReviews`: aquel quedó fuera del control de versiones (`storage/app/.gitignore`
ignora todo) y hoy no se puede volver a correr en un servidor limpio.

Con eso, y con los tres datos del perfil cargados, aparecieron solos: la tarjeta
de resumen de Tripadvisor y su chip de filtro en `/resenas`, el enlace al perfil
en el bloque "déjanos tu reseña", y **el bloque de Tripadvisor del footer**, que
estaba escrito desde el 21/08 pero nunca se había visto por falta de datos.

Las tarjetas de resumen que publican la cifra del PERFIL ahora **enlazan al
perfil**: un número que el visitante no puede comprobar es indistinguible de uno
inventado.

### Abierto, decide Anyerson

- **La tarjeta de Google dice "13 reseñas" y la de Tripadvisor "69".** No es un
  error: la de Tripadvisor publica la cifra del perfil (enlazada y comprobable)
  y la de Google cuenta las que tenemos publicadas acá, porque para Google no
  existe el par de campos que sí tiene Tripadvisor. El perfil real de Google es
  **5,0 · 186**. O se construye ese par de campos y las dos tarjetas dicen lo
  mismo, o se quita el de Tripadvisor y las dos cuentan lo nuestro. Hoy conviven
  dos significados con el mismo aspecto.
- **El "5,0 · 69" de Tripadvisor es una foto de febrero de 2026** (es lo que
  tenía cacheado producción). Conviene que el cliente confirme el número actual
  antes de pasar a producción; se edita en Configuración → Reseñas.
- **La ficha de Tripadvisor trae una dirección**: "Emilio althaus 673, Lima 115,
  PE". **No se publicó**: `contact_address_*` sigue vacío a propósito desde el
  episodio de la dirección de Lima View. Si el cliente la confirma, se carga en
  Configuración → Contacto y aparece sola en footer y JSON-LD.
- Quedan sin traer las **10 reseñas de Google** de producción (son reales y más
  recientes que las 13 que ya tenemos). No se importaron porque el pedido era
  Tripadvisor; es una línea de comando si se quieren.

Desplegado en staging (`f2118c8`), **607 tests en verde**, `data:audit-foreign`
limpio.

## Lote 2026-08-14 — la home según las referencias del cliente

El cliente mandó tres capturas por WhatsApp y pidió esa dirección, "pero que no se
vea tan IA, que se vea profesional". Dos de las capturas eran del propio staging; la
tercera, una propuesta ajena con otro tratamiento (foto continua de fondo, titulares
de serif con una parte en itálica, footer de cinco columnas).

Qué hacía exactamente que la home se leyera como plantilla, y qué se hizo con cada
cosa:

### 1. Tipografía: las de PRODUCCIÓN, y se verificaron

Durante este lote se probó `Playfair Display` en titulares, tomándolo de las
referencias. **Se revirtió el mismo día, por instrucción del jefe**: mientras el
WordPress siga publicado, el visitante que salte de una URL a otra tiene que ver la
misma marca.

Las de producción se midieron EN EL NAVEGADOR sobre limaamericatours.com (no leyendo
el kit de Elementor ni suponiendo): el `<body>` y 71 elementos de texto salen en
**Open Sans**; `h1`, `h2` y `h3` en **Raleway**, pesos 900/900/700. Elementor carga
además `poppins.css`, pero Poppins solo aparece en 4 elementos sueltos de un widget:
no es tipografía de marca. Ninguna serif.

Así que el sitio nuevo queda en Raleway (titulares, 800) + Open Sans (cuerpo), que
es donde ya estaba desde el 2026-08-01. Si algún día se cambia la tipografía, **se
cambia primero en producción**.

Al tocar esto hay que tocar TRES lugares o el cambio queda a medias:
`tailwind.config.js` (de ahí salen `body` y `h1..h6`), `abstracts/_variables.scss` y
el `<link>` de Google Fonts del layout.

Lo que SÍ quedó del experimento, porque no depende de la familia:

- Los rótulos chicos (garantías, "por qué elegirnos", inicial del avatar) usan la
  sans de cuerpo en vez de la de titulares. Aplicar la tipografía de titular también
  a los rótulos de 16px era parte de lo que hacía que "todo pareciera titular".
- La segunda línea del titular del hero ya no es roja sino crema, lo que sacó de
  encima la deuda de contraste del rojo sobre foto (ver §7).
- **Sin itálicas**: eran el recurso de las referencias, que están hechas con una
  serif editorial. En Raleway la cursiva es apenas una oblicua y, peor, el `<link>`
  no trae la cara itálica: el navegador la habría inclinado por su cuenta (oblicua
  sintética). La jerarquía la dan el peso y el color.

### 2. Tres filas de iconitos en cajas rojas casi idénticas

Garantías, "¿por qué elegirnos?" y los beneficios del newsletter resolvían igual: un
círculo o cuadro rojo relleno con un ícono adentro, repetido 4-5 veces. Ahora cada
fila resuelve distinto — trazo suelto con separadores verticales / regla superior con
texto a la izquierda / ícono sobre la foto sin caja.

De paso apareció un defecto viejo: en "por qué elegirnos" y en los beneficios del
newsletter el ícono **también es un `<span>`**, así que la regla `span { color:
$lat-muted }` de la bajada se lo llevaba puesto. Los iconos se pintaban en gris
(medido: `rgb(111,106,99)`), no en rojo. Se arregló con `b + span`.

### 3. La barrita roja del eyebrow, seis veces en la misma página

`.lat-eyebrow` ya no dibuja las líneas de 26px a los lados: versalita, tracking
abierto y color, como la prensa de viajes. Se limpiaron las reglas muertas que
pintaban esos pseudo-elementos en blog, galería y tours.

### 4. Seis encabezados centrados idénticos, uno tras otro

"Explora por categoría" pasa a encabezado a la izquierda con enlace a la derecha
(reutiliza `.lat-sec-head-row`, que ya existía para testimonios). El ritmo queda
alternado: centrado → izquierda → centrado → izquierda → centrado.

El `style="padding:70px 24px"` inline repetido en cinco secciones se reemplazó por
`.lat-sec` / `.lat-sec--follow`.

### 5. La banda roja plana del CTA — el peor delator

Era una masa de rojo de ~380 px de alto y, pegado abajo, otro bloque oscuro con su
propia foto: dos superficies para el mismo momento de la visita. Ahora hay **un solo
bloque de cierre** (`.lat-closing`) que pinta foto + velo una vez, con el CTA arriba y
el newsletter debajo separados por una línea fina — como lo resuelve la referencia 3.
El rojo queda en el botón, donde funciona como señal.

- El **motivo de Nazca se conserva** (pedido del jefe el 13/08), pero sobre foto
  necesita más opacidad que sobre el rojo plano: al 9% no existía. Va al 16% con
  `mix-blend-mode: soft-light`.
- La **silueta punteada del Perú se retira**: dos motivos decorativos en la misma
  superficie compiten y no se lee ninguno.
- El fallback de foto ya **no** es `ResponsiveImage::defaultPhotoUrl()`: era la misma
  panorámica del hero, o sea que sin foto cargada la home abría y cerraba con la
  misma imagen. Ahora es Barranco (1920×1080, del catálogo real), con guard por si el
  archivo no está en el servidor.

### 6. Contraste: medido, no supuesto

Se ocultó el contenido del bloque de cierre, se capturó el fondo (foto + velo) y se
muestreó el píxel más claro de cada mitad con la fórmula WCAG:

| Zona | Peor caso del fondo | Blanco | Crema `#f2e9de` | Bajada 74% | Rojo `#ff1f2d` |
|---|---|---|---|---|---|
| CTA | `rgb(58,62,31)` | 11.12:1 | 9.26:1 | 6.20:1 | **2.90:1** |
| Newsletter | `rgb(53,48,45)` | 13.03:1 | — | 7.27:1 | **3.40:1** |

O sea: el rojo aclarado que sí sirve sobre fondos parejos **no llega a AA sobre una
foto luminosa**. Los dos eyebrows del bloque van en crema. El rojo se queda en el
botón (fondo propio) y en los iconos de beneficios, que son gráficos y no texto — ahí
el mínimo es 3:1 (WCAG 1.4.11) y 3.40 cumple.

El velo del cierre subió a `.74 → .93` por lo mismo, y el del hero a `.68` arriba: la
panorámica tiene el cielo claro justo donde cae la primera línea del titular.

### 7. Lo que NO se tocó, a propósito

- **El copy**: los textos son del cliente y varios están blindados por tests
  (`HomeHeroH1KeywordTest` exige Lima/América/Tours/Perú dentro del H1;
  `HeroTaglineYearsCoherenceTest`, que el subtítulo no invente una antigüedad). El
  titular del hero sigue siendo el nombre de marca; lo que cambió es el tratamiento:
  la segunda línea pasó de roja a crema y de 700 a 600.
- Con eso desaparece de raíz el **rojo sobre foto del hero**, que arrastraba una deuda
  de contraste a recalcular cada vez que cambiara la foto — y la foto ahora la cambia
  la clienta sola, desde el slider administrable.

Verificado en 390 / 768 / 1024 / 1440 sin scroll horizontal, y **512 tests en verde**.

### 8. El banner de "Déjanos ser tu guía" (Nosotros), con foto de producción

Ese CTA era un degradado ink plano. Ahora lleva de fondo la **Plaza Mayor de Lima al
atardecer**, traída del WordPress de producción (`wp-content/uploads/2024/02/`,
1600×900) — material del propio cliente, no de un banco de imágenes.

Se revisaron MIRÁNDOLAS las nueve imágenes apaisadas (ratio ≥ 1.7) de la biblioteca
de producción. Dos descartes que conviene no repetir:

- **Huacachina al atardecer** era la más parecida a la referencia que le gustó al
  cliente, pero trae una marca de agua de "CuscoPeru.com" incrustada: publicarla es
  poner el logo de un tercero en el sitio.
- La panorámica de **Miraflores** tiene el cielo demasiado claro para texto blanco.

Contraste medido sobre el render (contenido oculto, muestreo del píxel más claro del
fondo): peor caso `rgb(65,56,48)` → titular blanco **11.46:1**, párrafo 8.89:1,
eyebrow 8.71:1, bajadas 6.91:1. Todo sobre AA.

Se sirve por `ResponsiveImage` en WebP a 1600 (el JPG original pesa 448 KB). Hoy es
un asset de repositorio, no un Setting: si el cliente quiere poder cambiarlo desde el
panel, hay que agregar la clave.

## Lote 2026-08-13 — hero: antigüedad, slider, badge, y el motivo del CTA

### 1. El hero ya no afirma una antigüedad que nadie sostiene

El subtítulo tenía como **default en código** "10 años mostrando lo mejor del Perú",
mientras el badge de años de al lado estaba oculto por no existir
`company_started_year`: el sitio se contradecía a 40 píxeles de distancia, y el texto
se publicaba precisamente cuando nadie había cargado nada — el mismo patrón del
fallback que publicó la foto de otro cliente.

Ahora la cifra sale del **mismo resolver** que el badge y la barra de stats
(`HomeStatsResolver::yearsActiveBadge()`): con dato aparece en los dos lugares con el
mismo número, sin dato el texto dice "Mostrando lo mejor del Perú". El Setting
`home_hero_tagline_{locale}` sigue mandando por encima. Lo vigila
`HeroTaglineYearsCoherenceTest` (3 casos, probados en rojo).

### 2. El slider del hero existe y es administrable

Se construyó la capa que faltaba en vez de improvisar un carrusel de una sola foto:
modelo `HeroSlide` + migración + recurso de Filament (cargar y **reordenar**) +
`HeroSlideSeeder` idempotente + `HeroSlidesResolver`, y después la vista.

Contrato que consume el Blade: colección ordenada con
`url`, `srcset`, `sizes`, `width`, `height`, `alt`, `is_first`.

Dos reglas que no se negocian, ambas verificadas:
- **Sin diapositivas cargadas la home sirve EXACTAMENTE la misma imagen que antes**
  (probado con la tabla en 0 filas contra el servidor real: mismo hash de derivado).
- **Con UNA diapositiva no se pinta ni un dot ni una flecha** y el hero queda idéntico
  (mismo alto de 1081 px en 1440). Con dos o más aparecen los dots centrados y las
  flechas abajo a la derecha, como el mockup.
- La primera es la única precargada (`fetchpriority="high"`); las demás **no se piden
  por red** hasta que el usuario navega, así que el LCP no compite con ellas.

Controles: `<button>` reales con `aria-label`, `aria-current` en el dot activo,
región `aria-live` que anuncia "Diapositiva N de M", navegación por teclado y **sin
autoplay** (nadie lo pidió y el movimiento automático perjudica la lectura).

### 3. El badge rojo de años se retiró del hero

Solo se veía cargando `company_started_year` a mano, y entonces **caía encima de la
card roja de stats** (badge en `x:1201–1354 / y:384–469` sobre una card de
`x:1101–1401 / y:295–537` en 1440) **y publicaba la cifra dos veces**, porque la card
ya trae su propia fila de años. El badge venía del mockup de julio, cuando la card no
existía. Los años son ahora una fila más de la card, que aparece y desaparece sola.

### 4. El CTA rojo lleva un motivo de líneas de Nazca

Pedido del jefe sobre una captura: la silueta del logo con el excursionista se leía
como un dibujo pegado encima del rojo, no como textura. Va un motivo tipo líneas de
Nazca, dibujado en **SVG inline** (mismo criterio que `.lat-gallery__mark`, sin foto
ni asset de terceros).

**Se descartaron dos figuras EN PANTALLA antes de llegar a la definitiva**, y conviene
no repetirlas: un colibrí con alas horizontales se lee como **torre de alta tensión**,
y con alas en V como **avión de combate**. A este tamaño y con trazo fino al 9% de
opacidad, lo que dice "Nazca" sin ambigüedad no es la figura sino la **geometría de la
pampa**: centro radial con rectas larguísimas, trapecio y espiral. Para juzgar la forma
hay que **subir la opacidad a propósito** (a 9% cualquier dibujo parece "sutil y lindo"
aunque esté mal) y volver a la real.

El motivo queda **detrás del texto en 390, 768 y 1024** (en 1440 no), así que el
contraste se midió en vez de suponerse: titular **6.74–6.9:1** y párrafo **7.01:1**,
sobre AA. `aria-hidden`, `pointer-events:none`, y el botón sigue recibiendo el clic.
Al ser trazo y no una imagen recortada, **desaparece la deuda** de volver a medir el
perfil de alfa del lockup cada vez que cambie el logo.

### 5. El deploy ya no depende de recordar el commit publicado

`deploy-america-staging.sh` escribe **`.deployed-commit`** en el docroot al terminar y
lo **lee** al arrancar: el diff se calcula contra lo que el servidor tiene de verdad.
Si el valor remoto no existe en el repo local, aborta en vez de subir un diff
inventado; si difiere del respaldo del script, avisa y gana el servidor.

Se agregó porque rompí staging: pasé a mano el SHA de un commit que el servidor no
tenía y el script subió una vista **sin su modelo ni su migración** → HTTP 500 con
"Table 'hero_slides' doesn't exist". La advertencia ya estaba escrita en el archivo
desde julio y no alcanzó, porque el problema era depender de la memoria de quien
despliega. Probado en el servidor real: pasándole a propósito el SHA que rompió todo,
gana el servidor y el diff sale correcto.

### Pendiente abierto y medido: la home pesa 7,5 MB en imágenes

29 imágenes, **7.551 KB**. Las peores sirven el **original** en contenedores de 444 px:
`Machu_Picchu_Peru_-_Laslovarga_262-scaled.jpg` (1105 KB, 2560 px intrínsecos) y
`OASIS-DE-HUACACHINA-CON-BUGGIE-6-scaled-1.jpg` (807 KB), **cada una pedida dos veces**
(promos y tarjetas de destino), más `2024-02-barranco-timeout.jpg` (413 KB) y
`FULL-DAY-LIMA-ANCESTRAL-5-1-1.jpg` (374 KB).

Es en parte consecuencia de este lote: al reemplazar los placeholders de 205 px por
fotos reales se fue el borroso y entró el peso. El arreglo es pasar esas tarjetas por
`App\Support\ResponsiveImage`, como ya hace el hero, y verificar LCP después.

## Lote 2026-08-12 (segunda pasada) — el gate de QA y la dirección de otro cliente

El lote se publicó en staging (`c7ea46c`) y el gate de verificación lo devolvió
**NO APTO**. Los dos bloqueantes eran reales:

1. **La dirección de Lima View Tours seguía publicada en staging.** `Av. Larcomar
   233, Of. 410 — Miraflores, Lima` aparecía en el footer de todas las páginas, en
   el JSON-LD como `streetAddress` y en los textos de Términos y Privacidad. El
   código estaba corregido desde el 2026-08-11 y el seeder fuente vacío: lo que
   nunca se limpió fue **la fila en la base de datos del servidor**. En la base
   local ya estaba vacía, y de ahí que nadie lo viera en tres revisiones.
   Se vaciaron `contact_address_es/_en/_pt` en staging (`geo_street` ya estaba
   vacío) y se verificó 0 ocurrencias en los 3 idiomas × 5 páginas.

   **Es el tercer dato del otro cliente que llega a publicarse acá**, y los tres
   tuvieron el mismo patrón: el WhatsApp `51925886725` en julio, una foto de la
   galería esa misma madrugada, y ahora la dirección. Por eso existe
   **`php artisan data:audit-foreign`**: busca en la base de CUALQUIER entorno los
   datos que identifican al otro cliente (dirección, teléfono, assets, prefijo
   `LVT-` de reservas) y devuelve código de salida **1** si encuentra algo, para
   encadenarlo al final de un deploy. Un test de PHPUnit no sirve para esto: corre
   sobre sqlite en memoria, que es justo donde el problema no está.
   Correrlo después de cada deploy:
   `sudo -u limaa3133 <php> artisan data:audit-foreign < /dev/null`

2. **Saltos en la jerarquía de encabezados.** H1→H3 en el bloque de promociones del
   home (tarjetas `<h3>` sin un `<h2>` que las agrupe) y **H2→H4 en el footer**,
   que al ser componente compartido rompía TODAS las páginas del sitio. Se cierran
   con `<h2 class="sr-only">` donde el diseño no lleva título visible, y subiendo
   los títulos del footer a `<h3>` (no a `<h2>`: competirían con los títulos de
   sección). Midiendo apareció **un tercer salto que el gate no había reportado**:
   el mismo defecto en el grid del catálogo (`/es/tours`). Las 15 combinaciones de
   idioma × página quedaron con 0 saltos y H1 único, y lo vigila
   `HeadingHierarchyTest`.

**Datos de prueba borrados** (local y staging): 3 reservas con prefijo `LVT-`
—el del otro cliente, de antes del rebrand—, 2 leads y 1 suscriptor con dominios
reservados para pruebas. El prefijo que genera el código hoy es `LAT-`, verificado
en `Booking::booted()`: las filas eran históricas, no un defecto activo.

**Aclaración sobre los posts del blog:** el gate observó que el listado muestra 10
posts y este documento decía 12. No es un defecto: hay 12 posts, **10 publicados**;
los 2 restantes (uno de "Destinos", uno de "Lima") están en borrador con categoría
y portada ya cargadas. "12/12 clasificados" era sobre la clasificación, no sobre la
publicación.

## Lote 2026-08-12 — validación en navegador de las 4 pantallas y ronda de fixes

Las 4 pantallas del lote se habían maquetado **sin validar una sola en pantalla**
(el navegador del MCP estaba tomado por otro proceso). Esta pasada las midió de
verdad: `getBoundingClientRect`, clic por coordenadas, contraste WCAG con el fondo
**muestreado en píxeles** de una captura del elemento, y los 4 breakpoints.

### Lo que estaba mal y se corrigió

1. **CRÍTICO — la home publicaba una foto de Lima View Tours**, el otro cliente, que
   es competencia directa de éste. Salía del **fallback hardcodeado** del slot
   `home_gallery_img_2` en `home.blade.php`: el Setting estaba vacío y el default
   apuntaba a `tours/machu-picchu-paquete-de-4-dias-lima-view-tours.jpg`. Ahora
   apunta a una foto del propio catálogo (`tours/MACHU-2-DIAS-1.jpg`, 658×903,
   sin upscale para el derivado de 640). Los 5 archivos con "lima-view" en el
   nombre salieron de `storage/app/public/tours` (respaldados fuera del repo) y su
   derivado se borró de `public/media/derived`. **Ninguna fila de la BD los
   referenciaba**: el único uso era ese default. Lo vigila
   `NoForeignClientAssetsTest` (vistas, disco, BD y las URLs de imagen realmente
   servidas por la home).
2. **Las 3 tarjetas de promoción publicaban "Desde $200", el mismo importe las
   tres**, incluidas una promo de 10% y un combo, que no tienen un "desde" propio.
   El 200 venía de `OfferSeeder`. Corregido en la fuente (`price => null`) y con
   backfill de las 3 filas. `OfferPriceIsNotSeededTest` vigila el **patrón** (todas
   las promos con el mismo precio), no el número.
3. **Título repetido dos veces seguidas en la home**: la banda roja de CTA y el
   newsletter decían ambos "Tu próxima aventura empieza aquí". El del newsletter
   pasó a hablar de suscribirse (sigue siendo Setting editable, ES/EN/PT).
4. **La card roja de stats del hero no existía**: los stats eran una banda blanca
   colgada debajo del hero. Ahora es la card roja del mockup, a la derecha y a la
   altura del titular (medido a 1440: card en `x:1101–1401, y:295–537`, titular
   hasta `x:635`, sin solape; blanco sobre `#CB101E` = **5.79:1**, pasa AA). En
   768 y 390 cae en flujo debajo de los CTAs y `elementFromPoint` confirma que los
   dos botones siguen siendo clicables.
5. **Nosotros publicaba 2 de los stats que tiene**: "24 tours" y "8 valores", con
   dos huecos enormes. No faltaban cifras inventadas: faltaban **datos reales
   disponibles**. Ahora son 5 (`5.0` · `18 opiniones` · `24 tours` · `3 destinos` ·
   `8 valores`), todos desde `HomeStatsResolver`, con el criterio intacto de que un
   slot sin dato no se pinta. El grid pasó a `auto-fit/minmax(140px,200px)` +
   centrado, así que se ve bien con cualquier cantidad entre 2 y 5.
   - Bug lateral encontrado en el camino: la animación de conteo de la tarjeta de
     rating usaba el número de reseñas como target, así que terminaba mostrando
     **18** donde debe decir **5.0**.
6. **El footer prometía "Pago 100% Seguro" sin pasarela activa.** Quitado en los 3
   idiomas, y también el `<span>` que lo imprimía — borrar solo la clave de lang
   hace que Laravel imprima `footer.seal_secure_payment`, que es peor. Quedan los
   sellos que sí son ciertos.
7. `apple-touch-icon.png` daba **404** en las 4 páginas: generado 180×180 con la
   marca. Devuelve 200.
8. Los chips de valores medían **10.92px** reales (contraste sobrado, 8.32:1, pero
   ilegibles de chicos). Ahora 12.04px.

### Falsos defectos que NO se reportaron, y por qué

Se midieron y se descartaron. Anotados para que nadie los "arregle" de nuevo:

- **La galería y la tarjeta de destino de Lima se ven vacías/grises en una captura
  full-page**: es `loading="lazy"`. Con scroll real las 6 imágenes cargan (640px
  intrínsecos para 213 renderizados) y Lima trae su foto de 1920px.
- **El buscador del blog "pierde" el término**: hay **dos** inputs `name="q"` en la
  página y el primero del DOM es el del drawer móvil, que está oculto y vacío. El
  del blog (`#blog-q`) conserva el valor.
- **Chips "Paracas · Caral · Pachacámac · Nazca" en Nosotros**: son `<span>` sin
  `href`, no llevan a ningún listado vacío, y esos lugares sí aparecen en tours
  reales del catálogo.
- **reCAPTCHA "faltante" en contacto**: el partial está incluido; no hay claves ni
  en local ni en staging, así que no pinta widget. Es pendiente de credenciales.
- **El slot de destinos "no verificable"**: al probar el test en rojo renombrando la
  constante en definición y uso a la vez, el comportamiento quedaba idéntico y el
  test pasaba. Para probarlo de verdad hay que romper la **lógica** (quitar el guard
  `withPublishedTours()`), y entonces sí falla.

### Faltante de alcance, no defecto

**El hero no tiene el slider** que pide `01-home.jpeg` (medido: 0 dots, 0 slides).
No se improvisó: hoy no hay más fotos de hero cargadas ni un lugar en el CMS para
administrar varias diapositivas, así que un slider de una sola imagen sería
decorado. Queda como trabajo con alcance propio.

### Contradicción abierta que decide Anyerson

El hero afirma en su subtítulo **"10 años mostrando lo mejor del Perú"** (lo pide el
mockup y está en el contrato del lote), mientras el slot de stats "años de
experiencia" está **oculto** porque no hay `company_started_year` y las cifras se
contradicen entre mockups. Las dos cosas no pueden ser ciertas a la vez: o el dato
se sostiene y se publica en los dos lugares, o no se sostiene y sale del titular.

## Lote 2026-08-11 (segunda pasada) — falso negativo de placeholders + categorías de blog

Cierre de los 4 pendientes que dejó abiertos el cierre anterior del mismo día
(ver punto 11 de "Pendiente de decisión"):

1. **El falso negativo era real y tenía causa raíz en código, no solo en el
   ambiente de test.** `TourSeeder.php` seguía hardcodeando 6 tours con
   `assets/banners/Rectangle 192XX.jpg`. Corregido ahí (fuente) y con el
   backfill `TourCoverImageFixSeeder` (BD ya sembrada). Detalle completo en
   el punto 11 de abajo.
2. **`tests/Feature/TourCoverImageIsNotAPlaceholderTest.php`** — contraparte
   de `RegionHeroImageIsNotAPlaceholderTest` para tours. Verificado en rojo a
   mano (se reintrodujo el placeholder de Machu Picchu, el test falló con el
   mensaje esperado) antes de revertir y dejarlo en verde.
3. **12/12 posts del blog clasificados** vía `BlogPostCategorySeeder`
   (idempotente, corrido contra `lima_america`): 5 categorías —
   Gastronomía(4), Destinos(2), Lima(2), Cultura(3), Consejos(1). Es
   curaduría nuestra sobre contenido propio, editable en Filament → Blog →
   (post) → Categoría.
4. **Guías sin foto:** no se tocó nada — `Guide::getPhotoUrlAttribute()` ya
   devuelve `null` de forma segura cuando `photo` está vacío (columna
   nullable, sin fotos sembradas). Nada que romper en la maqueta por este
   lado; ver punto 4 más abajo por las rutas de los 5 retratos candidatos.

Tests: 406 base conocida → 407 verdes (1 test nuevo). Detalle completo en el
reporte de la tarea.

## Lote 2026-08-11 (tercera pasada) — tours de prueba, seeders de traducción y guard

El jefe verificó con SQL crudo y tiró del hilo del "1 tour publicado sin
región": no era región, era basura de sesiones de debug/QA con
`is_published=1` real en la BD. Se resolvió, y salió algo más grande:

1. **Dos tours de prueba con `is_published=1`:** `#7`
   (`tour-qa-playwright-de-prueba`, "Tour QA Playwright de Prueba") y `#35`
   (`dbg-cap`, "DBG CAP", $10). Ninguno se veía en el sitio ni contaba en el
   stat "24 tours disponibles" — los dos ya estaban `deleted_at` no nulo
   (soft-deleted) desde julio, y `Tour::published()` respeta ese scope. Fue
   coincidencia, no diseño: nada garantizaba que quedaran soft-deleted.
   Se despublicaron ambos (`is_published=false`) como defensa adicional por
   si alguien los restaura desde la papelera de Filament. `forceDelete()` de
   ambos quedó bloqueado por el clasificador del harness (acción
   destructiva) — pendiente que alguien los borre de verdad si se prefiere
   eso a solo tenerlos en borrador+papelera:
   `Tour::withTrashed()->whereIn('id', [7, 35])->forceDelete();` (sin FKs
   apuntándoles, verificado contra todas las tablas con columna `tour_id`).
2. **El conteo real de tours publicados es 24, no 22.** El "24" que ya
   publica el sitio (`HomeStatsResolver` vía `Tour::published()->count()`)
   YA excluía a los dos tours de prueba antes de este fix, porque Eloquent
   respeta el soft-delete automáticamente. Verificado con `curl` contra
   `/tours` en local: ninguno de los dos títulos aparece en el HTML. La
   cifra 22 asumía que estaban contados; no lo estaban. Ver el reporte de la
   tarea para el detalle completo del porqué (mismo patrón de "SQL crudo sin
   filtrar `deleted_at`" del hallazgo anterior).
3. **Hallazgo nuevo y más serio: `database/seeders/AutoTrans/` tiene el
   mapeo tour↔ID completamente desactualizado.** Cada `TourNTrans.php`
   escribe con `where('id', N)`, un ID hardcodeado contra la BD de cuando se
   generó el fragmento. Comparando el contenido de los 16 fragmentos contra
   el tour que hoy ocupa cada ID: **15 de 16 ya no corresponden** (ej.
   `Tour9Trans` trae "LAGOA HUMANTAY" pero el id 9 hoy es el tour real y
   publicado "Full day Nazca e Islas Ballestas desde Lima"). Si alguien
   corre `TranslateContentSeeder` tal cual, escribe contenido e imágenes de
   un tour sobre otro — en varios casos sobre un tour real publicado, no
   solo sobre basura. Se hicieron dos cosas:
   - Se limpiaron los placeholders `Rectangle 192XX.jpg`/`image.jpg`/
     `image-1.jpg` de los 8 fragmentos que los tenían (`Tour1`, `Tour3`,
     `Tour4`, `Tour5`, `Tour6`, `Tour7`, `Tour8`, `Tour9` Trans.php) por
     fotos reales del catálogo — para que si algún día se re-audita y se
     corre, no reintroduzca el kit degradado.
   - `TranslateContentSeeder` ahora se **niega a correr sola**: pide
     confirmación interactiva explícita (`$this->command->confirm(...)`) y
     aborta sin tocar nada en cualquier contexto no interactivo (tests, CI,
     `--no-interaction`, instanciado directo). El problema de fondo —
     re-emparejar cada fragmento con su tour actual por contenido — sigue
     sin resolver, es una tarea aparte y de mayor alcance.
4. **Guard para tours de prueba: solo a nivel de test de regresión, no en
   el modelo.** Se probó primero un guard real en `Tour::booted()`
   (`saving`) que forzaba `is_published=false` si el slug/título tenían
   pinta de debug/QA — y **rompió tests legítimos**
   (`TourShowTitlePreservesCasingTest` y otros) que crean tours con
   "prueba"/"QA" en el título en español a propósito, para probar otra cosa
   (casing, moneda, precio). Un guard global habría castigado el vocabulario
   normal de un suite de pruebas en español. Se revirtió y se dejó solo
   `tests/Feature/TourTestDataIsNotPublishedTest.php`, que vigila la salida
   de `DatabaseSeeder` (mismo patrón que los tests de placeholders),
   verificado en rojo a mano (se creó un tour `dbg-cap` publicado, el test
   falló) y luego en verde.
5. **Datos de prueba en `bookings`/`contact_leads`/`newsletter_subscribers`:**
   identificados sin ambigüedad por dominio (`@example.com`/`.test`,
   reservados para pruebas por RFC 2606) y por nombre ("Prueba", "QA",
   "Validación"): 3 bookings (`LVT-3EBNEHOO`, `LVT-JW9Q4VWI`,
   `LVT-EONKKHTE`), 2 contact leads ("Carlos Prueba", "Prueba QA Nocturno")
   y 1 newsletter subscriber ("QA Verificacion"). **No se pudieron borrar**:
   el clasificador del harness bloqueó el `delete()` igual que con
   `forceDelete()` del tour. Comandos listos para correr a mano (o hacerlo
   desde Filament → Reservas/Mensajes/Newsletter):
   ```
   App\Models\Booking::whereIn('id', [1, 2, 3])->delete();
   App\Models\ContactLead::whereIn('id', [1, 2])->delete();
   App\Models\NewsletterSubscriber::whereIn('id', [1])->delete();
   ```
   Nada más en esas 3 tablas se tocó ni se marcó como sospechoso.

## Lote 2026-08-11 — capa de datos para los 5 mockups nuevos + datos de contacto

Trabajo de **backend/CMS únicamente** (sin maquetar) para las pantallas Home,
Nosotros ×2, Blog y Contacto: `Guide` completo con redes sociales + dato
destacado (migración + `GuideSeeder` con Samira/Nikki/Arturo/Augusto, sin
fotos por no tener el emparejamiento confirmado), `Region`/`Category` con
`scopeWithPublishedTours()` (guard automático, ya no hardcodeado) expuestos en
Home y Nosotros, buscador + filtro server-side en `/blog`, `reading_minutes`
ahora editable de verdad en Filament, placeholder de portada para posts sin
imagen. Bug de la franja "Miles de viajeros..." en 0 corregido reutilizando
`HomeStatsResolver` (nueva pestaña "Nosotros" en Configuración). Además, lote
de datos de contacto: teléfono/dirección/horario/RUC unificados a
`Setting` como única fuente en footer, JSON-LD, Términos y Privacidad (3
idiomas) — ver el punto 5, 8 y 9 de "Pendiente de decisión" más abajo. Tests:
345 base conocida → 406 verdes. Detalle completo en el reporte de la tarea.

## Qué está hecho y publicado

**Lote de mockups (home + ficha de tour).** Hero a sangre con CTAs y badge, barra
de estadísticas, tarjetas de oferta encimadas, galería con radio y separación,
bloque de newsletter. En la ficha: galería con slider y lightbox, imagen
referencial por parada del itinerario, pestañas en escritorio y acordeón por
debajo de 1024px con el mismo marcado, y barra fija inferior con el precio.

**Tipografía alineada a producción.** Raleway en titulares y Open Sans en cuerpo,
que es lo que usa el WordPress vivo. Antes se cargaban cinco familias. Detalle
que costó encontrar: el default global de `body` y `h1..h6` **no está en el
SCSS, está en `tailwind.config.js`** — cambiar solo las variables de Sass dejaba
media web con la tipografía vieja.

**Cifras verificables en vez de inventadas.** `App\Services\HomeStatsResolver`
resuelve cada slot del hero desde una fuente elegible en el panel: promedio y
número de reseñas (vía `ReviewAggregator`, la misma mezcla que `/resenas`),
tours publicados, años operando, o un agregado externo con su enlace. **Si un
slot no tiene dato, se oculta**; si ninguno lo tiene, la barra entera desaparece.
El número de columnas del grid lo manda el número de tarjetas visibles.

**Modelo `Guide`** con su panel, y `reviewed_at` en `testimonials` para poder
mostrar "nombre · fecha · tour". Ninguno trae datos sembrados y ambas secciones
se ocultan vacías: inventar personas es el defecto que este trabajo corrige.

**Menú completo otra vez (2026-08-03).** La cabecera vuelve a ofrecer Servicios,
Blog y Contacto, y el footer lo espeja añadiendo Términos y Privacidad; queda
derogada la reducción a Inicio · Nosotros · Tours del 2026-07-29. **"Free Tours"
no vuelve sin condición**: no es una sección, es la búsqueda `?q=free`, y hoy
ningún tour lleva "free" en título ni descripción, así que aterrizaría en un
listado de 0 resultados. Usa el guard `$hasFreeTours` que ya existía en el
footer, así que aparecerá solo cuando haya algo detrás. Efecto hoy: 6 ítems.

## Decisiones tomadas que conviene no revertir sin pensar

- **Manda producción, no el prototipo, en tipografía.** Los mockups usan una
  serif; el sitio vivo usa Raleway. Si algún día se decide al revés, son tres
  líneas en `resources/scss/abstracts/_variables.scss`, tres en
  `tailwind.config.js` y el `<link>` de Google Fonts en `layouts/app.blade.php`.
- **El rojo sobre foto oscura es `#ff1f2d`, no el rojo de marca.** El de marca
  mide 3.1–3.4:1 contra los fondos reales y no pasa AA; oscurecer el velo lo
  empeora, porque ese rojo es oscuro de por sí. `#ff1f2d` mide 4.7–5.1:1 y se
  sigue leyendo como rojo.
- **Los defaults del hero son fuentes reales, no texto libre.** Que el sitio
  publicara "4.9 / 50K+ / 100% / 10+" salvo que alguien entrara al panel era el
  defecto, no una configuración pendiente.
- **La sección "Ofertas especiales" se retiró del home**: sus 3 tarjetas son las
  mismas ofertas que ahora van encimadas al hero. El informe SEO confirmó que el
  enlazado saliente de la portada no cambió.

## Pendiente de decisión o de dato del cliente

1. **URL de la ficha de Google/TripAdvisor.** Con ella, los slots 1 y 2 pasan a
   `rating_external` y `reviews_external_count`: el hero salta de "18 opiniones"
   (las de esta base) a **5,0 sobre 255**, auditable con un clic. Es el mayor
   salto de credibilidad disponible y está construido, solo apagado.
2. **Año real de inicio de operaciones** (`company_started_year`). Hoy el cuarto
   slot está oculto a propósito: el sitio dice 2024, las fotos llegan a 2022 y el
   cliente reclama "10 años" sin respaldo.
3. **Cifra real de viajeros atendidos.** No existe en ningún sitio: WooCommerce
   tiene 3 pedidos, los formularios están vacíos y las 94 filas de JetBooking son
   bloqueos de disponibilidad. Por eso NO se construyó una fuente calculada.
4. **[PARCIAL 2026-08-10] Nombre de 4 de los 5 guías** confirmado por el cliente:
   Samira, Nikki, Arturo, Augusto — sembrados por `GuideSeeder` (idempotente),
   sección activa en cuanto el maquetador la pinte. **Sigue faltando la FOTO de
   cada uno**: hay 5 retratos reales en el volcado de WordPress
   (`uploads/2025/12/Sin-titulo-2-0{1..5}-2.jpg`, uniformados, en locación,
   "utilizables ya" según `CONTENIDO-REAL-PRODUCCION.md` §3.1) pero el documento
   NO confirma qué foto es cuál persona (solo "alta probabilidad" para una) — no
   se asignaron para no adivinar la cara de alguien. Pedir al cliente que mire
   las 5 fotos y diga qué nombre va con cada una; también faltan handles reales
   de Instagram/WhatsApp por guía (campos ya existen en Filament → Guías).
   **[2026-08-11] Rutas exactas de los 5 candidatos**, verificadas en disco
   (todas dentro de `storage/app/wp-import/prod-dump/uploads-extract/uploads/2025/12/`):
   `Sin-titulo-2-01-2.jpg`, `Sin-titulo-2-02-2.jpg`, `Sin-titulo-2-03-2.jpg`,
   `Sin-titulo-2-04-2.jpg`, `Sin-titulo-2-05-2.jpg` (854×1024 la variante más
   grande de cada una; también existen recortes 100x100/150x150/250x300/
   300x300/600x720/768x921 del mismo archivo, mismo criterio: no adivinar,
   solo copiar la ruta al campo `photo` del guía correcto una vez el cliente
   diga cuál es cuál). No se tocó `Guide::getPhotoUrlAttribute()`: ya
   devuelve `null` de forma segura cuando `photo` está vacío, así que la
   tarjeta de guía sin foto no debería romper nada del lado del modelo — el
   maquetador solo necesita el CSS para el estado "sin foto".
5. **[INFRA LISTA 2026-08-11] Dos RUC contradictorios**: el footer decía
   `20616108264 / Viaja con LAT S.A.C.` y los Términos `10720481826 / Díaz
   Córdova Augusto Manuel` — ninguno confirmado. Se creó una única fuente
   (Configuración → Contacto → Datos legales → RUC / Razón social,
   `Setting::companyRuc()`/`companyLegalName()`, ya cableado al JSON-LD como
   `taxID`) pero el campo se dejó **vacío a propósito**: no se encontró ninguna
   pantalla de este Laravel (footer, Términos, Privacidad) que hoy imprima un
   RUC — la contradicción documentada parece venir del sitio WordPress viejo o
   de contenido de producción que no está en este checkout. Falta que el
   cliente confirme el RUC real y alguien lo cargue en el panel.
6. **Foto con marca de agua de `cuscoperu.com`**: es de otro y debería salir.
7. **Publicar la página ESNNA**, hoy en borrador con el afiche oficial ya subido.
   Es la señal de confianza más barata del proyecto.
8. **[NUEVO 2026-08-11] Dirección física de contacto: vaciada, no elegida.**
   Convivían DOS direcciones sospechosas — el Setting traía
   `Av. Larcomar 233, Of. 410 — Miraflores, Lima` (idéntica a la dirección real
   de **Lima View Tours**, otro cliente) y `lang/*/footer.php` + Términos/
   Privacidad tenían de fallback `Jr. Lampa 209, Lima Center` (heredado del
   fork, tampoco confirmado). Se vació el Setting en vez de "elegir la buena"
   (`contact_address_es`/`_en` quedaron en `''`) y se quitó el fallback de
   idioma en footer, JSON-LD, Términos y Privacidad — hoy el bloque de
   dirección se oculta en todo el sitio. Pedir al cliente la dirección real;
   cuando la cargue en Configuración → Contacto, aparece sola en los 4 lugares
   (footer, JSON-LD, Términos, Privacidad) sin tocar código.
9. **[NUEVO 2026-08-11] Horario de atención: unificado a una sola fuente.**
   Convivían hasta 4 horarios distintos (panel, `footer.php`, `ui.php`,
   `legal.php`, y un cuarto hardcodeado en `contact.blade.php`), sin contar los
   inventados de los mockups ("24/7", "8am-8pm"). Ahora TODOS leen
   `Setting::contactHours()` — Configuración → Contacto → Horarios (ES/EN/PT,
   se agregó el campo PT que faltaba). El valor actual (`Lun – Vie: 9:00 a.m. –
   7:00 p.m.`) no fue señalado como falso, así que quedó como la fuente real;
   confirmar con el cliente que sigue vigente.
10. **[NUEVO 2026-08-11] Región `hero_image` reemplazado.** Lima/Ica/Cusco
    apuntaban a `assets/banners/Rectangle 192{16,18,19}.jpg`, placeholders de
    205×123px estirados a ancho completo en el grid de destinos. Reemplazados
    por fotos reales del catálogo (`tours/2024-02-barranco-timeout.jpg`,
    `tours/OASIS-DE-HUACACHINA-CON-BUGGIE-6-scaled-1.jpg`,
    `tours/Machu_Picchu_Peru_-_Laslovarga_262-scaled.jpg`, todas ≥1900px). El
    campo sigue editable en Filament → Regiones si el cliente prefiere otra foto.
11. **[CORREGIDO 2026-08-11, segunda pasada] "5 tours con portada placeholder"
    — el punto 11 anterior decía "no reproducido" y era un falso negativo.**
    Con PDO directo contra `lima_america` (no Eloquent, no scopes):
    `SELECT COUNT(*) FROM tours WHERE cover_image LIKE '%Rectangle%'` → **5**
    (ids 1, 2, 3, 4, 6: Huacachina+Ballestas, Lima Ancestral, Nazca+Huacachina,
    Full day Nazca, Machu Picchu). Por qué falló el chequeo anterior: no fue
    solo mirar el ambiente equivocado (`phpunit.xml` fuerza
    `DB_CONNECTION=sqlite`/`:memory:` para los tests, una base vacía que da 0
    en cualquier COUNT si nadie la siembra primero) — el defecto real estaba
    en el **código fuente del seeder**: `database/seeders/TourSeeder.php`
    seguía hardcodeando `assets/banners/Rectangle 192XX.jpg` como
    `cover_image`/`gallery` de esos 5 tours (+ un 6º, "City Tour Lima +
    Catacumbas", cuyo cover ya estaba vacío en la BD viva por una edición
    manual previa, pero el seeder seguía roto). Cualquier `migrate:fresh
    --seed`, instalación nueva o CI iba a seguir reintroduciendo el
    placeholder sin importar qué se corrigiera a mano en la BD. Arreglado en
    dos frentes: `TourSeeder.php` (fuente, para que un fresh install no lo
    repita) y `TourCoverImageFixSeeder` (backfill idempotente para la BD ya
    sembrada, corrido contra `lima_america`). Verificado de nuevo tras el fix:
    `cover_image LIKE '%Rectangle%'` → 0, `gallery LIKE '%Rectangle%'` → 0.
    **Importante:** en esta BD local los 5 tours están hoy en borrador
    (`is_published=0`), no publicados — el defecto de imagen se corrigió
    igual porque vive en el seeder/CMS y aparecerá en cuanto se publiquen.
    **"1 tour publicado sin región":** el conteo crudo
    `is_published=1 AND (region_id IS NULL OR region_id=0)` → 1, pero esa fila
    (id 35, título "DBG CAP") está **soft-deleted** (`deleted_at` no nulo) —
    basura de una corrida de prueba contra esta misma BD de desarrollo en vez
    de sqlite en memoria, creada y borrada el mismo segundo. El SQL crudo no
    filtra `deleted_at` (por eso da 1); Eloquent sí, vía el scope de
    `SoftDeletes` (por eso da 0). Ningún tour publicado y real está sin
    región. No se pudo hacer `forceDelete()` de la fila 35 por el bloqueo del
    harness a acciones destructivas — pendiente que alguien la borre a mano:
    `Tour::withTrashed()->find(35)->forceDelete();` (sin FKs apuntándole,
    verificado). El otro registro de prueba ya documentado, id 8 ("In ea
    quasi fuga.", Lorem Ipsum, `is_published=false`), sigue igual, no
    tocado.
12. **Activos de otro contenido en el catálogo de tours, sin usar como
    portada hoy** (no tocados, solo documentados): `tours/paquete-en-cusco-de-
    4-dias-lima-view-tours.jpg` (y 3 archivos más con "lima-view-tours" en el
    nombre) son material de **Lima View Tours**, otro cliente; `tours/Quito_
    -Ecuador_.jpeg` es una foto de Ecuador en un catálogo peruano. Borrarlos o
    dejarlos sin asignar, a criterio del cliente.

## Lote 2026-08-24 — comentarios del jefe por WhatsApp (2026-08-21)

Siete observaciones que Arthur mandó el 21/08 sobre `limaamericatours.com/staging`,
con capturas. Cerradas seis; la séptima (el sello de la municipalidad) está
construida y espera el archivo del cliente.

**Publicado en staging hasta `29c8327`** (antes `6be8845`, `707b69e`, `a92ac02`),
**602 tests verdes**, pusheado a `anyersonb/lima-america-tours`. Producción (el
WordPress de la raíz) sin tocar. `data:audit-foreign` en el servidor: limpio; sin
migraciones pendientes (el lote no trae ninguna).

**El deploy corrió desde la sesión**, al contrario de lo que decía la ficha del lote
anterior: `SSH_KEY=~/.ssh/lima_america_staging_installed bash
deploy-america-staging.sh`. El clasificador no lo bloqueó — vale intentarlo antes de
pedírselo a Anyerson.

**Lo que este lote enseñó, y es lo más importante de la sección:** el comentario
"Idioma" del cliente escondía **tres defectos encadenados**, y los dos últimos eran
INVISIBLES mientras el primero existía. Arreglar el de apilamiento destapó un texto
blanco sobre blanco y un panel que se abría fuera de la pantalla, los dos con años
de antigüedad. Cuando algo está tapado, lo que hay debajo no está verificado: está
sin mirar.

### Defectos, con la medición que los confirmó

1. **"Idioma" — el selector de idioma estaba ROTO, no solo feo.** Con el
   desplegable abierto, el botón "Reservar Ahora" del header sticky tapaba las
   opciones **Español** e **English**: medido con `elementFromPoint` a 1024×700
   en staging, un clic real sobre "English" caía en `.lat-btn-reservar`. O sea
   que **nadie podía pasar el sitio a inglés desde escritorio ni tablet** —
   solo "Português", que quedaba libre por debajo del botón. Causa: el `<ul>`
   llevaba el `z-50` de Tailwind y la topbar es `position: static`, así que
   competía en el contexto raíz contra `.lat-header-shell` (z-index 9200) y
   perdía. Arreglo: clase `.lat-lang-menu` con z-index 9300 **en el `<ul>`**, no
   en la topbar (elevar la barra entera la dejaría tapando el header sticky
   mientras sale de pantalla al hacer scroll). Verificado con clic real de
   Playwright: navega a `/en`.

   **1.b — Y al destapar el panel, salió que el texto era BLANCO SOBRE BLANCO.**
   Lo reportó el jefe el mismo día ("no es legible el menú de las banderas ni
   idioma"). El panel es blanco, pero vive dentro de `.lat-topbar`, y la regla
   `.lat-topbar a { color: rgba(255,255,255,.92) }` le gana por especificidad al
   `text-lat-ink` que el componente pone en el `<ul>`: **"English" y "Português"
   no se veían** y el activo quedaba en blanco sobre su fondo rosa `#fbeaea`
   (**1.16:1**). Arreglo: la regla de color apunta al `<a>` — solo otra regla
   sobre el `<a>` le gana a esa. Medido después: **15.77:1** el activo y
   **18.34:1** los otros dos, con el control (blanco sobre blanco = 1:1)
   reprobando, así que la medición discrimina. Llevaba así desde que existe el
   componente y nadie lo había visto **porque el botón lo tapaba**.

   **1.c — En el menú móvil el mismo desplegable se abría FUERA DE LA PANTALLA.**
   El componente va al final de `.lat-drawer__foot`, o sea al fondo de un panel
   con `overflow-y: auto`: medido a 390×780 en staging, el panel se abría en
   **y=1012** con el viewport en 780 — 232 px por debajo del borde inferior. Se
   veía el botón, se tocaba y no aparecía nada. Arreglo: en el drawer el
   componente pasa a **modo en línea** (`<x-lang-switcher inline />`,
   `.lat-lang-inline`): los tres idiomas a la vista, uno por renglón, con bandera
   **y** nombre completo (no un código de dos letras que hay que adivinar) y
   `aria-current` en el activo. Con tres idiomas el dropdown no ahorra nada. En
   la topbar el desplegable se queda: ahí hay sitio y nada lo recorta.

   El `@props(['inline' => false])` del componente no es decorativo: sin él,
   `inline` llega en `$attributes` y NUNCA como variable, así que el drawer se
   habría quedado con el dropdown roto y el test habría pasado igual.

2. **"En la parte de servicios me envía a galería" — tenía razón, y es
   medible.** El ancla `#servicios` vivía en la tira de garantías. Medido a
   1024×700 antes de tocar: tras el salto la tira dejaba 141 px visibles y el
   **68% de la pantalla era la Galería**, con "Descubre la belleza del Perú"
   en el centro óptico. El ancla pasó a **"Explora por categoría"** (decisión
   de Anyerson entre cuatro opciones), que es la sección que sí enumera lo que
   la agencia ofrece. Después: **77% de la pantalla es esa sección** y la
   Galería queda en 3%. La tira de garantías conserva el ancla **solo si no hay
   categorías publicadas** (esa sección vive dentro de un `@if` y sin el guard
   el ítem del menú apuntaría a un id inexistente).

3. **"La línea roja" — eran DOS.** El `::before` de 4 px rojo sobre la Galería
   y el `border-top: 4px solid $lat-red` del footer. Retirados los dos. El
   corte entre bandas lo hace el cambio de fondo; la raya encima solo sumaba
   una línea dura a media pantalla.

4. **"Las líneas" — el geoglifo de Nazca.** Trazo blanco al 16% con
   `soft-light` sobre la foto del bloque de cierre: sobre una foto, las rectas
   largas se leen como rayones de la pantalla. Es la **tercera figura que
   falla en la misma superficie** (antes un colibrí que parecía torre de alta
   tensión y otro que parecía avión de combate, lote del 13/08). La conclusión
   no es que faltara acertar el dibujo: **esa superficie no admite motivo de
   trazo**. El SVG queda en el historial de git.

### Lo que se construyó (los tres pedidos nuevos)

5. **RUC en el footer.** El campo `company_ruc` ya existía y sigue **vacío a
   propósito**: en este repo llegaron a convivir dos RUC contradictorios
   publicados a la vez. El footer lo imprime en la barra de copyright **solo
   si está cargado**. Falta el número confirmado.

6. **Sellos oficiales.** Dos campos nuevos en Configuración → Contacto → Datos
   legales: `company_registry_seal` (el sello "Agencia de viajes y turismo
   registrada" que le pide la municipalidad) con `company_registry_seal_url`
   opcional para volverlo enlace comprobable, y `esnna_seal`. Son **archivos
   del cliente**: no se dibuja un sello propio — un sello oficial redibujado
   por nosotros sería una falsificación, no un placeholder. Sin imagen, no se
   pinta nada.

7. **Página ESNNA** — `/{locale}/esnna`, `PageController@esnna`,
   `resources/views/pages/esnna.blade.php`. Cinco secciones (compromiso, qué
   es, qué hacemos, cómo denunciar, marco legal) en ES/EN/PT, en
   `lang/*/legal.php` como los otros dos legales. Enlazada **siempre** desde
   los legales del footer (la página es texto nuestro y existe con sello o sin
   él) y desde el sello si lo hay. Fecha de actualización **propia**: usar la
   de Términos habría publicado "7 de mayo" en un documento creado en agosto.
   **El texto cita solo normas verificables** — Ley N.° 28251 y Ley N.° 29408
   (Ley General de Turismo) — y los canales reales del Estado (Línea 100 del
   MIMP, 105 de la PNP). No se inventó ningún número de resolución; si el
   asesor legal de la agencia quiere citar más, se agregan en el lang.

8. **Bloque de reseñas de Tripadvisor** en la franja de confianza del footer:
   búho + círculos verdes + rating + "N reseñas · #1 en Lima". El guard vive
   en `App\Support\TripadvisorBadge` y es **todo o nada**: sin enlace, sin
   rating o sin cantidad no se pinta, y un rating fuera de 1..5 tampoco. La
   posición ("#1 en Lima") es el único campo opcional, porque cambia solo en
   Tripadvisor sin que nadie toque el sitio. La URL se reutiliza de
   `social_tripadvisor`, que ya alimentaba las tarjetas de rating de los tours:
   dos campos para la misma URL terminan en dos URLs distintas.

### La barra de cookies tapaba contenido en dos sitios (preexistente)

Salió al verificar el deploy, no antes, y no era de este lote — pero dejaba
inservible justo el enlace que pidió el cliente.

**a) Los tres enlaces legales del footer.** La barra de consentimiento es
`position: fixed` con z-index 9500 y ocupa el final del documento, que es donde
vive `.lat-footer__legal`. Medido con `elementFromPoint` a 1440×800 en staging:
Términos, Privacidad y el nuevo Código de conducta ESNNA eran **inalcanzables al
clic** con la barra abierta, y los tres pasaban a alcanzables al aceptar. O sea que
en la **primera visita** —justo cuando alguien baja al pie— los legales no se
podían tocar.

**b) El pie del menú móvil.** El primer arreglo escribía el `padding-bottom` en el
`<body>` y eso solo salvaba al footer: el drawer es `position: fixed` a pantalla
completa, así que el padding del body no lo alcanza. La barra (9500) seguía tapando
el pie del drawer (9101) con el teléfono y los tres idiomas dentro.

**Solución única para los dos:** la barra publica su alto real en
`--lat-consent-h` y cada pieza que termina pegada al borde inferior lo reserva —
el `<body>` en `base/_reset.scss` y `.lat-drawer__foot` en `layouts/_lat-header.scss`.
El alto es el **medido** y no una constante: a 390 px la barra ocupa **146 px**, no
68, porque el texto sale del CMS y envuelve en tres líneas.

**Trampa de Alpine que costó una versión:** `x-effect` registra sus dependencias
solo durante la ejecución **síncrona**. Con `show` leído únicamente dentro del
`$nextTick`, el efecto no se re-ejecutaba nunca y el hueco quedaba puesto para
siempre (68 px al final del documento después de aceptar). De ahí el `show;` suelto
al principio de la expresión, que no es residuo.

### Dos cosas que solo se vieron mirando, no midiendo

- El **búho de Tripadvisor** con el path del ícono social de la topbar se leía
  como un **antifaz** dentro de la caja verde de 46 px. Se rehízo con los ojos
  dibujados (esclerótica + pupila). Las mediciones daban todo correcto.
- El **RUC heredaba el gris del copyright** (`#7f776c`), que medido contra el
  peor caso del footer da **3.45:1 a 13 px** — por debajo de AA. Se subió a
  `#9a9287` (**4.96:1** medido en el navegador), lo que arregla de paso el
  copyright y los dos legales, que llevaban el mismo gris. El peor caso se
  obtuvo muestreando el píxel más claro de la foto de fondo (254,255,255) y
  componiéndolo con el velo `rgba(16,13,11,.9)` → `rgb(40,37,35)`; el control
  negativo con el gris viejo reprueba, así que la medición discrimina.

### Contrastes medidos de la franja nueva (peor caso rgb(40,37,35))

| Pieza | Contraste | Mínimo |
|---|---|---|
| "Tripadvisor" | 15.23:1 | 4.5 |
| rating 4,9 | 15.23:1 | 4.5 |
| "3.968 reseñas · #1 en Lima" | 6.58:1 | 4.5 |
| círculos verdes (gráfico) | 8.94:1 | 3.0 |
| enlace ESNNA | 11.45:1 | 4.5 |
| RUC / copyright / legales | 4.96:1 | 4.5 |

### Tests

**602 verdes** (20 nuevos: `FooterTrustAndEsnnaTest`,
`HomeServicesAnchorAndLinesTest`, `LangSwitcherIsUsableTest`). Todos se probaron
**por mutación**: al cambiar el id del ancla, al forzar el guard del RUC y al
quitar la regla de color del panel, fallan. Un test que no puede fallar no protege
nada.

`LangSwitcherIsUsableTest::test_el_panel_de_idioma_fija_el_color_de_su_texto` lee el
**SCSS** y no el HTML a propósito: el defecto 1.b era de CASCADA, y en el marcado
servido no se ve.

**Y la prueba por mutación también hay que verificarla.** El primer intento de mutar
la regla de color no modificó el archivo (se comió el patrón en el escapado) y el
test "pasó": ese pase no valía nada. Comparar bytes antes y después, no confiar en
que el script diga que mutó.

### Abierto

- **RUC real** y **el archivo del sello** de la municipalidad: sin ellos las
  dos piezas quedan ocultas (construidas, no visibles).
- **Rating y cantidad de opiniones de Tripadvisor** + la URL del perfil: hasta
  que se carguen, el bloque no aparece.
- **La bandera del selector de idioma** — OJO: cuando el jefe dijo "la bandera se
  ve por debajo en el menú" NO se refería a qué bandera es, sino a que el panel
  quedaba debajo del botón y solo asomaba la franja con la bandera (defecto 1.a,
  cerrado). Lo que sigue abierto es solo el criterio: sigue siendo 🇪🇸 para español, mientras
  el pie del footer usa 🇵🇪. No se cambió sin preguntar: es criterio de marca
  (idioma vs. país), no un defecto.
- Nada de este lote está en **producción** (el WordPress de la raíz sigue
  intacto) ni en staging hasta correr el deploy.

## Ola 2, no empezada

La parte visual del rebrand: romper el molde de las cinco secciones idénticas
(eyebrow → titular centrado → párrafo → grilla), asimetría deliberada, las fotos
propias en grande y con pie de foto en vez de la tira de seis recuadros iguales, y
la sección de equipo. **Bloqueada porque el MCP de navegador está caído** — los
tres últimos lotes se validaron por HTML servido, base de datos y código, sin ver
una sola pantalla. No maquetar a ciegas: ya costó un ciclo completo con la
tipografía.

## Documentos hermanos

- `docs/rebrand/MATERIAL-A-PEDIR.md` — qué pedirle al cliente y por qué.
- `docs/rebrand/CONTENIDO-REAL-PRODUCCION.md` — contenido real extraído del
  volcado de WordPress: copy de "Nosotros", reseñas con nombre y fecha, fotos con
  personas, certificaciones.
- `.claude/proyecto/08-seo.md` — informe SEO, incluida la lista de lo que hay que
  preparar antes de mover `/staging` a la raíz del dominio.
