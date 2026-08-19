# Inventario de diseño — Nosotros (hero rediseñado) y Menú móvil

Rama `feat/mockups-ago-2026` · Repo `G:\laragon\www\lima-america` · 2026-08-18

Trabajo documental. No se abrió navegador (MCP Playwright/Chrome está en uso por otro
agente en paralelo — ver `feedback_playwright_navegador_compartido`). Todo lo marcado
como "medido" viene de leer código/CSS o de correr `php artisan tinker` contra la BD
local; los ratios de contraste de la sección E son **cálculo WCAG a mano sobre los
valores hex de los tokens**, no muestreo de píxeles en navegador — se declara así en
cada fila, tal como pide el proyecto.

Verificado contra BD local (`php artisan tinker`):
- `Testimonial::active()->count()` → **13**
- `Testimonial::active()->whereNotNull('avatar')->where('avatar','!=','')->count()` → **0**
- `Guide::count()` → **4**
- `Setting::get('company_started_year')` → `''` (vacío)
- `Setting::get('home_hero_video_url')` → `''` (vacío)

---

# Pantalla 1 — Nosotros (hero rediseñado, mockup `23.53.39.jpeg`)

## Contexto importante antes de la tabla

Este mockup **no es una pantalla nueva**: es una propuesta de rediseño del **hero +
franja de stats + franja de garantías** de `about.blade.php`, que hoy ya existen pero
con menos contenido:

- El hero actual (`about.blade.php:213-238`, `.lat-about-hero` en
  `_lat-about.scss:263-270`) **ya es oscuro** (`linear-gradient(160deg,#1c1815,$lat-ink)`),
  con breadcrumb + eyebrow + H1 + párrafo + collage de **6** fotos
  (`about.blade.php:161-172`, `.lat-hero-collage` en `_lat-about.scss:328-349`). Le
  faltan: badge de marca, los 4 micro-features, los 2 CTA, la tira de avatares y la
  píldora de confianza.
- La barra de 5 métricas del mockup **ya existe como mecanismo de datos**: es
  `$aboutStatsBand`/`$aboutStatsVisible`, resuelto por
  `HomeStatsResolver::resolveAboutBand()` (`app/Services/HomeStatsResolver.php:103-106,
  184-193`) y pintado en `about.blade.php:245-257`. Hoy se ve como una **card blanca**
  flotando sobre el borde del hero (`.lat-hero-stats` en `_lat-home.scss:598-608`,
  fondo `$lat-surface`), no como la barra oscura de punta a punta del mockup.
- La franja roja de garantías **ya existe como componente**, pero en Home, con otra
  piel: `.lat-guarantee` (`home.blade.php:956-975`, `_lat-home.scss:1028-1064`) es una
  franja **clara** (`$lat-surface-2`) con íconos a trazo sin caja. El mockup la pide
  **roja sólida** con textos blancos.
- El botón "Ver Video" + su modal **ya están construidos y funcionando** en Home
  (`home.blade.php:203-240` normaliza la URL, `home.blade.php:878-890` es el `<div
  id="heroVideoModal">`, `home.blade.php:1395-1440` es el script). Están atados a
  `Setting::get('home_hero_video_url')` y al id fijo `heroVideoModal` — reusar el
  mecanismo en Nosotros exige una clave de Setting propia y generalizar el id (dos
  modales con el mismo id en la misma página rompen `document.getElementById`).

## A. Anatomía (de arriba a abajo, dentro del hero oscuro)

1. Badge de marca (píldora con borde + estrella) — "LIMA VIEW TOURS" en el mockup
2. Titular H1 a dos líneas, 2ª línea en rojo brillante `#ff1f2d`
3. Bajada / párrafo
4. Grid 2×2 de 4 micro-features (ícono circular + título + subtítulo)
5. Dos CTA: "Explorar Tours" (rojo) + "Ver Video" (outline, ícono play)
6. Tira de avatares apilados + texto + 5 estrellas ("Miles de viajeros…")
7. Collage de 4 fotos (1 grande arriba + 3 chicas en fila) con badge flotante rojo
   "10+ años de experiencia"
8. Píldora oscura flotante con 3 avatares — "Más de 2,500 viajeros satisfechos"
9. Barra oscura de 5 métricas con separadores verticales (5.0 · 13 · 24 · 3 · 8)
10. Franja roja de 4 garantías (ícono + título + subtítulo)

## B. Tabla de componentes

| # | Componente | Clase CSS | Archivo:línea | Estado | Fuente de dato | Responsive | Horas |
|---|---|---|---|---|---|---|---|
| 1 | Badge de marca (píldora+estrella) | *(nueva)* `.lat-hero-badge` | — | **NUEVO** | Estático (nombre de marca, no editable — igual que el logo) | 1 línea, se envuelve si el nombre es largo; oculta el ícono de estrella <380px si no entra | 1 |
| 2 | H1 dos líneas | `.lat-about-hero h1` (sin clase propia hoy) | `about.blade.php:220` (contenido), `_lat-about.scss:263-270` (contenedor) | **ADAPTAR** — hoy es 1 bloque de texto sin segunda línea diferenciada; el mockup pide 2 líneas con la 2ª en rojo | `$bl('hero_title', …)`, ya editable desde `Page->blocks` | clamp de tamaño ya usado en `.lat-split-title`; replicar aquí | 1 |
| 3 | Bajada / párrafo | `.lat-about-hero__sub` | `about.blade.php:222-227`, `_lat-about.scss:319-325` | **EXISTE** — se usa tal cual | `$bl('hero_lead', …)` | `max-width:480px`, ya responsive | 0.5 |
| 4 | 4 micro-features (2×2) | *(nueva)* `.lat-hero-features` | — | **NUEVO** — pero reutiliza la píldora circular de ícono ya existente `.lat-htc__ic` (`_lat-home.scss:651-659`, círculo 42px borde rojo) y el patrón texto `b`+`span` de `.lat-gt` (`_lat-home.scss:1066-1088`) | Repeater nuevo en `Page->blocks.hero_features` (mismo patrón con fallback que `why_travel_items`, `about.blade.php:121-135`); íconos del catálogo cerrado `HeroIcons` (`guide`, `award`/`star`, `group`, `shield` ya existen) | 2×2 en móvil pasa a 1 columna <480px | 4 |
| 5a | CTA "Explorar Tours" | `.lat-btn.lat-btn--red` | `_lat-home.scss:27` (definición), uso análogo en `about.blade.php:390` | **EXISTE** | Enlace a `route('tours.index', …)`, texto vía `$L`/Setting | ya responsive (botones existentes) | 0.5 |
| 5b | CTA "Ver Video" + modal | `.lat-video-modal` | Mecanismo completo en `home.blade.php:203-240` (normalización URL), `:878-890` (modal), `:1395-1440` (JS) | **ADAPTAR** — el mecanismo sirve, pero está atado a `home_hero_video_url` y al id fijo `heroVideoModal`; hace falta clave `about_hero_video_url` + id único (`aboutHeroVideoModal`) o generalizar el script a un data-atributo | **BLOQUEADO hasta que el cliente dé la URL** — `Setting::get('home_hero_video_url')` ya está vacío hoy, y "video de Nosotros" no tiene ni siquiera esa clave | El botón se oculta solo si no hay URL (guard ya probado en Home) | 3 |
| 6 | Tira de avatares + rating | *(nueva)* `.lat-avatar-strip` | — | **NUEVO** | `Testimonial::active()` con `avatar` no vacío — **hoy 0 de 13 tienen avatar cargado** (verificado en BD) | Colapsa a 3 avatares en móvil | 3 |
| 7a | Collage de 4 fotos | `.lat-hero-collage` (regrid) | `about.blade.php:230-236`, `_lat-about.scss:328-349` (hoy 6 fotos, mosaico 3 columnas) | **ADAPTAR** — recortar a 4 de las 6 fotos curadas ya existentes (`about.blade.php:161-168`) y cambiar el `grid-template` a "1 grande + 3 chicas en fila" | Mismas 6 fotos reales del cliente ya elegidas (`CONTENIDO-REAL-PRODUCCION.md` §6.1), se usan 4 | Grid ya con `grid-auto-flow:dense`, adaptar breakpoints | 3 |
| 7b | Badge flotante "10+ años" | `.lat-split__badge` (reubicado) | `_lat-about.scss:28-41`, wiring en `about.blade.php:273-278` | **EXISTE el mecanismo, ADAPTAR la posición** — hoy vive sobre `.lat-split__media` (sección de más abajo), no sobre el collage del hero | `HomeStatsResolver::yearsActiveBadge()` — **oculto hoy** porque `company_started_year` está vacío (verificado) | ya responsive (mismo componente) | 1.5 |
| 8 | Píldora "Más de 2,500 viajeros" | *(nueva)* `.lat-trust-pill` | — | **NUEVO el componente** — **BLOQUEADO el número**: "2,500" no existe en ninguna tabla (mismo patrón ya prohibido en `LOTE-MOCKUPS-AGO-2026.md`, tabla "Lo que NO se publica") | Reemplazar por el agregado real vía `ReviewAggregator::overallStats()` (ya usado en `about.blade.php:199, 619-624`) → "13 opiniones verificadas", o retirar la píldora si no cabe con esa cifra | Se oculta si no hay avatares reales (mismo guard que el punto 6) | 2 |
| 9 | Barra de 5 métricas (dark) | `.lat-hero-stats` / `.lat-htc` (reskin) | `about.blade.php:245-257` (uso), `_lat-home.scss:598-608` (hoy fondo blanco `$lat-surface`), resolver en `HomeStatsResolver.php:103-106,184-193` | **ADAPTAR** — la capa de datos ya es correcta y ya evita las cifras prohibidas (no incluye "13 guías" ni "8 años": esos slots no existen en `resolveAboutBand`); solo falta un modificador visual oscuro en vez de la card blanca flotante | `rating_real` (5.0) · `reviews_count` (13) · `tours_count` (24) · `destinations_count` (3) · `values_count` (8 valores del CMS) — **los 5 son reales hoy** | Grid ya con `min-width:0` y `border-right` responsivo | 2.5 |
| 10 | Franja roja de 4 garantías | `.lat-guarantee`/`.lat-gt` (reskin) | `home.blade.php:956-975`, `_lat-home.scss:1028-1088` (hoy piel clara `$lat-surface-2`) | **ADAPTAR** — mismo componente que Home, nuevo modificador con fondo `$lat-red` sólido, íconos en blanco/`$lat-red-tint`, y **cambiar "Atención 24/7" por el horario real** (`Setting::contactHours()`, ya usado en `about.blade.php:153,386-388,709`) | Copy hoy hardcodeado en Blade (ni en Home ni aquí sale de `Setting`/`blocks` — gap preexistente, no de este lote, pero si se construye de nuevo para Nosotros conviene que sí sea administrable) | Grid 4→2→1 igual que `.lat-guarantee__grid` | 3 |
| — | QA responsive + contraste de todo el hero nuevo | — | — | — | — | 390/768/1024/1440, sin overlap entre badge flotante y collage | 3 |

**Total Pantalla 1 (Nosotros): ~27 horas.**

## C. Tokens nuevos

**No se necesita ningún token de color nuevo.** Todo el hero rediseñado se resuelve
con la paleta ya definida en `_variables.scss`:

- El rojo brillante `#ff1f2d` del mockup **no se adopta** — es el mismo caso ya
  documentado en `LOTE-MOCKUPS-AGO-2026.md` ("El rojo sobre foto es `#ff1f2d`, no es
  el rojo de marca"). Se usa `$lat-red` (`#cb101e`).
- Círculo de ícono de micro-features/stats → `$lat-red` + `#fff`/`$lat-red-tint`, ya
  existen.
- Franja de garantías en rojo → `$lat-red` de fondo, `#fff` y `rgba(255,255,255,.7-.8)`
  para texto, mismo patrón que usa `.lat-btn-reservar:hover` y `.lat-cta-final`.
- Píldora de confianza oscura → `rgba($lat-ink, .xx)` o `$lat-ink` con blur, sin token
  nuevo.

Lo único que **sí** falta es infraestructura, no color: la clave de Setting
`about_hero_video_url` y el bloque `hero_features` en `Page->blocks` (ver tabla B).

## D. Assets requeridos

| Asset | Cantidad | Medida mínima | ¿Ya hay algo en `public/storage`? |
|---|---|---|---|
| Fotos del collage | 4 (de las 6 ya curadas) | Ya verificadas ≥467px de ancho intrínseco (`about.blade.php:158-160`) | **Sí** — reusar 4 de los 6 archivos de `$heroCollage` (`about.blade.php:161-168`), todos en `storage/tours/` |
| Foto de fondo del hero | 0 — hoy es gradiente sólido, no foto | — | No aplica, y no hace falta: cambiarlo a foto reabriría la deuda de contraste que el hero actual evita a propósito |
| Avatares de viajeros reales | mínimo 3-5 | cuadrados, ≥88×88px, cara reconocible con permiso de uso | **No hay ninguno** — 0 de 13 testimonios activos tiene `avatar` cargado (verificado). Bloqueante real para los puntos 6 y 8 |
| Video (YouTube/Vimeo) | 1 URL | — | **No hay URL cargada** en ninguna clave de Setting (verificado `home_hero_video_url` vacío; `about_hero_video_url` no existe todavía) |
| Ícono de estrella para el badge de marca | 1 SVG inline | — | Trivial, se dibuja igual que el resto de íconos del sitio (Lucide, trazo 2px) |

## E. Riesgos de contraste

| Elemento | Fondo | Texto | Ratio (estimado a mano, no medido en navegador) | Token que sí cumple AA |
|---|---|---|---|---|
| 2ª línea del H1 en rojo sobre el hero | `$lat-ink` (`#171412`, sólido, no foto) | `$lat-red` (`#cb101e`) | **≈3.17:1** — calculado con la fórmula WCOM sobre los hex (L1=0.132, L2=0.007): pasa el mínimo de texto grande (3:1) muy al filo, **no pasa** si el peso/tamaño bajara de "grande" en algún breakpoint | Usar el mismo arreglo que ya se aplicó en el hero de Home (`ESTADO.md`, lote 2026-08-14, punto 1): la 2ª línea se pinta en **crema `#f2e9de` o blanco**, y el rojo queda solo como acento puntual (subrayado, ícono), nunca como color de texto sobre este fondo. **No reabrir la misma deuda que ya se cerró una vez.** |
| Badge de marca (borde rojo + texto) | `$lat-ink` gradiente | Texto blanco + borde `$lat-red` | Texto blanco sobre ink ≈ 15.9:1, sin problema. El borde rojo es decorativo (no es texto): mínimo 3:1 de WCAG 1.4.11 para elementos gráficos — `$lat-red` sobre `$lat-ink` da los mismos ≈3.17:1 de arriba, **al filo pero pasa** el umbral gráfico | OK, no requiere cambio |
| Franja roja de garantías | `$lat-red` (`#cb101e`) | Blanco `#fff` para títulos | Blanco sobre `$lat-red` ≈ **5.6:1** (mismo cálculo que ya usa el proyecto para validar `$lat-red` como fondo de botón) — pasa AA | Sin cambios, mismo criterio que `.lat-btn--red` |
| Franja roja de garantías | `$lat-red` | Subtítulos en `rgba(255,255,255,.7)` | Se diluye el blanco al 70% sobre un fondo ya no tan claro: ratio cae a **≈3.9:1**, no pasa AA para texto normal (4.5:1) | Subir la opacidad mínima a **.86** sobre este fondo específico (no reusar el `.72` que usa `.lat-cta-final__feature span` sobre `$lat-ink`, que es más oscuro y sí tolera más transparencia) |
| Ícono de "10+ años" flotante sobre el collage | `$lat-red` (fondo de la card) | Blanco | Mismo ≈5.6:1 que arriba, ya validado por el componente existente | Sin cambios |
| Barra de 5 métricas si se reskinéa a oscuro | `$lat-ink` o similar | Números en blanco, labels en `$lat-muted`/gris | `$lat-muted` (`#6f6a63`) sobre `$lat-ink` da un contraste bajo (grises apagados sobre casi-negro) — **hay que medirlo en el navegador antes de shippear**, no asumir que el gris de fondo claro sirve igual sobre fondo oscuro | Usar `rgba(255,255,255,.7-.8)` para los labels en vez de `$lat-muted`, que está calibrado para fondo claro |

Todos estos números son **cálculo manual sobre los valores hex**, no medición de
píxeles en pantalla — el proyecto exige lo segundo antes de publicar (ver
`feedback_fondo_seccion_medirlo` y `feedback_falsos_defectos_medicion`); esta fila
queda para que el maquetador que lo construya sepa qué volver a medir con Playwright
cuando el navegador esté libre.

## F. Reutilización (lo que sirve sin tocar o casi sin tocar)

- `.lat-about-hero` ya es oscuro — no hay que crear un tema nuevo, solo llenarlo.
- `$aboutStatsBand`/`HomeStatsResolver::resolveAboutBand()` — la capa de datos de las
  5 métricas está resuelta, probada y **ya excluye las cifras prohibidas** (no hay
  slot de "guías" ni de "años" en este resolver). Reskinearla es mucho más barato que
  construir una barra nueva.
- `HomeStatsResolver::yearsActiveBadge()` + `.lat-split__badge` — el badge flotante
  rojo "N+ años" ya existe entero, con su guard de dato. Solo se reubica.
- El botón "Ver Video" + modal + normalizador de URL de YouTube/Vimeo de Home
  (`home.blade.php:203-240,878-890,1395-1440`) — no hay que reinventar el parseo de
  URLs ni el modal, solo generalizar el id y sumar la clave de Setting.
- `HeroIcons` (`app/Support/HeroIcons.php`) ya trae `guide`, `shield`, `group`,
  `award`, `star`, `camera` — cubre los 4 íconos de micro-features sin dibujar SVG
  nuevo.
- El patrón "repeater con fallback" (`why_travel_items`, `about.blade.php:121-135`) es
  la plantilla exacta para el nuevo `hero_features`: copiar la receta, no inventar
  otra.
- `.lat-btn--red` / `.lat-btn--outline-white` (`_lat-home.scss:27,36`) cubren los 2 CTA
  sin CSS nuevo.
- `ReviewAggregator::overallStats()` ya calcula el agregado real que debe reemplazar
  la cifra "2,500 viajeros" de la píldora.
- `.lat-guarantee`/`.lat-gt` de Home son la base de la franja roja: se necesita un
  modificador de color, no una franja nueva desde cero.

## G. Decisiones que no corresponden al maquetador

1. **Conflicto de tema (ya señalado por el proyecto, no se resuelve aquí).** La
   decisión del jefe del 2026-08-10 fija Nosotros "claro con franjas oscuras". Este
   mockup nuevo describe una franja de arriba **enteramente oscura** con mucho más
   contenido que la franja oscura actual del hero. Como el hero YA es oscuro, esto no
   contradice la decisión de fondo — pero si se aprueba tal cual, la franja oscura del
   arranque de la página crece bastante (de ~460px a posiblemente 900-1000px con
   avatares + collage + stats + garantías todo dentro), y eso sí cambia el balance
   claro/oscuro que pactó el jefe para el resto de la página. Se necesita su OK
   explícito sobre el tamaño de esa franja, no solo sobre el color.
2. **Duplicar la barra de stats.** Si se pinta la barra de 5 métricas dentro del hero
   (punto 9) Y se deja la banda "Miles de viajeros ya confiaron" más abajo
   (`about.blade.php:563-647`, que muestra las MISMAS 4-5 cifras), la página repite el
   mismo dato dos veces en la misma visita. Hay que decidir: ¿la barra del hero
   reemplaza a la de abajo, o se diferencian (arriba resumen corto, abajo detalle con
   foto)? Es una decisión de arquitectura de contenido, no de CSS.
3. **Ver Video: URL real.** El botón queda construido pero oculto hasta que el
   cliente entregue una URL de YouTube/Vimeo real de Lima América Tours (no de Lima
   View).
4. **Avatares reales de viajeros.** Los puntos 6 y 8 del mockup no se pueden publicar
   sin fotos reales con consentimiento de uso — hoy 0 de 13 testimonios las tiene. Es
   una decisión de negocio (pedir las fotos, usar solo iniciales, u ocultar ambos
   bloques), no de maquetación.
5. **Cifra "2,500 viajeros".** No existe ninguna fuente. El jefe/cliente decide si se
   sustituye por el agregado real (13 opiniones) o si el bloque se retira.
6. **"Años en el mercado" (badge flotante y quinta métrica "8 años").** Ambos dependen
   de que el cliente confirme `company_started_year`. Sin ese dato, los dos quedan
   ocultos por diseño — no es un bug a corregir, es un dato pendiente del cliente.

---

# Pantalla 2 — Menú móvil (referencia `10.47.38.jpeg`, patrón de Lima View Tours)

## Contexto

La captura es del sitio EN VIVO de Lima View Tours (otro cliente), circulada en verde
por el jefe para señalar el **patrón** (círculo+ícono, título, subtítulo, chevron).
**No se copia marca ni copy**, solo la estructura visual. El drawer de Lima América ya
existe y funciona (`header.blade.php:126-174`, `.lat-drawer` en
`_lat-header.scss:215-357`); lo que falta es únicamente el tratamiento del **nav
interno** (`.lat-drawer__nav`, hoy enlaces de texto plano en
`header.blade.php:152-156` / `_lat-header.scss:292-313`).

## A. Anatomía

1. Header del drawer: logo + botón cerrar circular oscuro
2. Buscador pill con lupa
3. Lista de ítems de navegación: círculo con ícono + título + subtítulo + chevron
   (Inicio · Tours · Mis reservas · Carrito · Blog · Reseñas · Nosotros · Contacto ·
   Ingresar)
4. Botón WhatsApp
5. Pie: teléfono + selector de idioma

## B. Tabla de componentes

| # | Componente | Clase CSS | Archivo:línea | Estado | Fuente de dato | Responsive | Horas |
|---|---|---|---|---|---|---|---|
| 1 | Header del drawer (logo + cerrar circular) | `.lat-drawer__head`, `.lat-drawer__close` | `header.blade.php:138-145`, `_lat-header.scss:232-266` | **EXISTE** — ya es circular, oscuro, con ícono X, igual que la referencia | Logo estático, ruta `route('home', …)` | ya responsive (drawer fijo 340px/86vw) | 0 |
| 2 | Buscador con lupa | `.lat-drawer__search` | `header.blade.php:147-150`, `_lat-header.scss:268-290` | **EXISTE** — pill, ícono lupa, placeholder real, envía a `tours.results` | `__('ui.search_tours_placeholder')` (`lang/es/ui.php:272`) | ya responsive | 0 |
| 3 | Fila "Inicio" | `.lat-drawer__nav a` (a rediseñar) | `header.blade.php:38,153-155` (dato), `:292-313` (CSS actual, sin círculo/subtítulo/chevron) | **ADAPTAR** | `nav.home` = "Inicio" (`lang/es/nav.php:11`). Falta subtítulo → nueva clave `nav.home_subtitle` | 1 fila, se apila con el resto | 0.3 |
| 4 | Fila "Tours" | idem | `header.blade.php:40,153-155` | **ADAPTAR** | `nav.tours` = "Tours". Subtítulo: **ya existe** `nav.tours_regions` = "Lima, Ica y Cusco" (`lang/es/nav.php:28`) — hoy huérfana, sin usar en ninguna vista (verificado) | idem | 0.3 |
| 5 | Fila "Mis reservas" | idem | — (no existe en `$navItems`, `header.blade.php:37-49`) | **NUEVO** | Ruta `customer.account` (`routes/web.php:195`, `/mi-cuenta`) ya existe y funciona; falta la entrada en `$navItems` + claves `nav.my_bookings` / `nav.my_bookings_subtitle` | idem | 0.5 |
| 6 | Fila "Carrito" | idem | — | **NUEVO** | Ruta `cart.index` (`routes/web.php:77`) ya existe; `nav.cart` = "Carrito" **ya existe** (`lang/es/nav.php:7`), falta el subtítulo y sumarla a `$navItems` | idem | 0.5 |
| 7 | Fila "Blog" | idem | `header.blade.php:48` (label hardcodeado `'Blog'`, sin pasar por `__()`) | **ADAPTAR** — hoy ya está en el nav de 6, pero como string literal sin traducir a EN/PT, y sin subtítulo | Ruta `blog.index` ya resuelta. Falta `nav.blog` (reemplazar el literal) + `nav.blog_subtitle` | idem | 0.4 |
| 8 | Fila "Reseñas" | idem | — | **NUEVO** | Ruta `reviews` (`routes/web.php:122`, `/resenas`) ya existe; faltan `nav.reviews` / `nav.reviews_subtitle` y sumarla a `$navItems` | idem | 0.5 |
| 9 | Fila "Nosotros" | idem | `header.blade.php:39,153-155` | **ADAPTAR** | `nav.about` = "Nosotros" ya existe. Subtítulo NUEVO — **nunca** "Conoce Lima View Tours" del mockup, va "Conoce Lima América Tours" o similar, vía `nav.about_subtitle` | idem | 0.3 |
| 10 | Fila "Contacto" | idem | `header.blade.php:49,153-155` | **ADAPTAR** | `nav.contact` ya existe. Falta `nav.contact_subtitle` | idem | 0.3 |
| 11 | Fila "Ingresar" | idem | — | **NUEVO** | Ruta `customer.login` (`routes/web.php:170`, `/ingresar`) ya existe; faltan `nav.login` / `nav.login_subtitle` y sumarla a `$navItems`. **Sin estado "ya autenticado"**: hoy no hay ninguna comprobación de `auth('customer')->check()` en el header (verificado, 0 resultados) — si el cliente ya inició sesión, el ítem debería decir "Mi cuenta" en vez de "Ingresar". Fuera del alcance visual estricto de este inventario, pero es un hueco funcional real | idem | 0.6 (solo la fila; el estado autenticado es aparte, ver sección G) |
| 12 | Círculo de ícono (fondo) | *(nueva)* `.lat-drawer__nav-ic` | — | **NUEVO** modificador — reusa `$lat-surface-2` (círculo) y `$lat-red` (ícono), ambos ya tokens | Estático (9 SVG inline, ninguno editable desde panel — son navegación fija de código, igual que hoy) | círculo fijo 40-44px, no escala con texto | 1.2 (incluye dibujar/adaptar 9 SVG: `home`, `map`/`compass` ya no está en `HeroIcons` con ese trazo — se dibuja nuevo; `ticket`, `cart` nuevos; `newspaper` nuevo; `star` ya existe en `HeroIcons`; `group`/`users` ya existe; `pin` ya existe; `login`/`arrow` nuevo) |
| 13 | Subtítulo por fila | *(nueva)* `.lat-drawer__nav-desc` | — | **NUEVO** | 9 claves nuevas × 3 locales (ES/EN/PT) = 27 strings en `lang/{es,en,pt}/nav.php` | line-clamp 1 línea, trunca si el idioma es más largo (EN/PT suelen serlo) | 1.5 |
| 14 | Chevron `>` a la derecha | *(nueva)* `.lat-drawer__nav-chevron` | — | **NUEVO** | Estático, decorativo (`aria-hidden="true"`) | igual en todos los breakpoints del drawer | 0.4 |
| 15 | Reflow de `.lat-drawer__nav a` (flex→grid con 3 zonas) | `.lat-drawer__nav a` | `_lat-header.scss:298-312` (hoy `display:flex; gap:12px` con solo ícono 20px + texto) | **ADAPTAR** | — | el drawer ya es de ancho fijo (340px/86vw), cada fila pasa de ~46px a ~68px de alto → el drawer con 9 ítems + buscador + WhatsApp + pie puede necesitar scroll interno, que **ya existe** (`overflow-y:auto` en `.lat-drawer`, `_lat-header.scss:227`) | 1.2 |
| 16 | Botón WhatsApp del drawer | `.lat-drawer__wa` | `header.blade.php:158-163`, `_lat-header.scss:337-356` | **EXISTE** — no lo toca este lote (la referencia lo recorta fuera de cuadro, pero está debajo en el sitio real) | `Setting::whatsappNumber()` | ya responsive | 0 |
| 17 | Pie: teléfono + selector idioma | `.lat-drawer__foot` | `header.blade.php:165-173`, `_lat-header.scss:315-335` | **EXISTE** | `Setting::contactPhone()`, `<x-lang-switcher />` | ya responsive | 0 |
| — | i18n: 9 claves nuevas + 1 fix (`Blog`) en 3 locales | `lang/{es,en,pt}/nav.php` | `lang/es/nav.php` (84 líneas hoy) | **NUEVO/ADAPTAR** | — | — | incluido en filas 3-11 arriba |
| — | QA responsive + accesibilidad (foco, aria, contraste círculo/chevron) | — | — | — | — | 390/768 (el drawer no cambia de layout entre breakpoints, es siempre el mismo panel de ancho fijo — solo valida que el scroll interno funcione y que no rompa en pantallas bajas tipo 640×360) | 1 |

**Total Pantalla 2 (Menú móvil): ~10 horas.**

## C. Tokens nuevos

**Ninguno.** El círculo de ícono reutiliza `$lat-surface-2` (fondo) + `$lat-red`
(ícono) o `$lat-ink-soft` según se decida (ver riesgo de contraste en E); el chevron
reutiliza `$lat-muted` o `$lat-red`. La referencia usa un ámbar/naranja para los
chevrones porque es la paleta de Lima View — **no se traslada**, en Lima América todo
lo interactivo/de acento es rojo.

## D. Assets requeridos

| Asset | Cantidad | Detalle |
|---|---|---|
| Íconos SVG nuevos (inline, trazo Lucide 2px, mismo criterio que el resto del sitio) | 6 nuevos + 3 reusables | Nuevos: `home`, `compass`/`map` (el `map` de `HeroIcons` es un pin de ubicación, no un compás de "explorar" — no calza para "Tours"; se dibuja uno propio), `ticket` (Mis reservas), `cart` (Carrito), `newspaper` (Blog), `login`/`arrow-right-to-bracket` (Ingresar). Reusables de `HeroIcons`: `star` (Reseñas), `group` (Nosotros), `pin` (Contacto, ya usado en el ícono de ubicación del footer/team) |
| Fotos | 0 | Esta pantalla no lleva fotografía, solo íconos de línea |

## E. Riesgos de contraste

| Elemento | Fondo | Texto/ícono | Ratio (estimado a mano) | Token que cumple AA |
|---|---|---|---|---|
| Título de cada fila | `$lat-surface` (blanco, fondo del drawer) | `$lat-ink-soft` (`#3a352f`) — mismo que hoy usa `.lat-drawer__nav a` | Alto, ya en uso hoy sin reporte de problema | Sin cambios |
| Subtítulo nuevo | `$lat-surface` | Candidato `$lat-muted` (`#6f6a63`) | `$lat-muted` sobre blanco ≈ **4.6:1** — pasa AA para texto normal por muy poco margen; si el subtítulo se pinta más chico que el cuerpo base (probable, es texto secundario ~13px) sigue calificando como "normal" y necesita el 4.5:1 completo | OK tal cual, pero no bajar más la opacidad ni aclarar el gris — ya está al límite |
| Ícono dentro del círculo | `$lat-surface-2` (`#f4efe7`, círculo) | `$lat-red` (`#cb101e`) | Ícono es gráfico, mínimo WCAG 1.4.11 = 3:1. `$lat-red` sobre `$lat-surface-2` (ambos claros/oscuro medio) ≈ **4.1:1** — pasa cómodo | OK |
| Chevron | `$lat-surface` (fondo del drawer) | Candidato `$lat-muted` | ≈4.6:1 sobre blanco, pasa como elemento gráfico (3:1) con margen | OK, o subir a `$lat-red` si se quiere más peso visual (mejora el ratio, no lo empeora) |

Ningún elemento de esta pantalla queda cerca de fallar — la única fila que exige
cuidado real es el subtítulo (4.6:1 justo sobre el mínimo de 4.5:1 para texto normal);
si el diseño final lo pinta en un tamaño ≥18.66px o negrita ≥14px, pasa a contar como
"texto grande" (mínimo 3:1) y el margen deja de ser un problema. **Medir en navegador
el tamaño final antes de cerrar esto**, no asumir.

## F. Reutilización

- El drawer entero (backdrop, transición, cierre por click/Escape/backdrop, bloqueo de
  scroll del body) **no se toca** — ya funciona (`header.blade.php:83-136`,
  `_lat-header.scss:200-229`).
- El array `$navItems` (`header.blade.php:37-49`) sigue siendo la única fuente para
  desktop Y móvil — no hay que duplicar datos, solo **agregar claves** (`icon`,
  `subtitle`) que el nav de escritorio simplemente ignora (ya solo imprime
  `$item['label']`, línea 94).
- `nav.tours_regions` y `nav.cart` ya existen en `lang/es/nav.php` y no se usan en
  ningún Blade hoy (verificado) — se activan aquí en vez de crearlos de cero.
- Las 5 rutas que "faltan" (`customer.account`, `cart.index`, `blog.index`, `reviews`,
  `customer.login`) **ya existen y funcionan** en `routes/web.php` — este lote es
  puramente de navegación/presentación, cero trabajo de backend.
- 3 de los 9 íconos salen del catálogo `HeroIcons` sin dibujar nada nuevo.

## G. Decisiones que no corresponden al maquetador

1. **6 ítems vs. 9 ítems — hay una decisión previa que contradice este pedido.**
   `LOTE-MOCKUPS-AGO-2026.md` fija expresamente "Se mantiene el menú actual de 6
   (Inicio · Nosotros · Tours · Servicios · Blog · Contacto)" el 2026-08-03, y aclara
   que no se revierte por inconsistencias de mockups. La referencia de este lote pide
   9 (agrega Mis reservas, Carrito, Reseñas, Ingresar) y **no muestra "Servicios"**.
   El jefe tiene que decidir explícitamente: ¿se amplía el menú a 9?, ¿se queda
   "Servicios" o se retira?, ¿esto aplica solo al drawer móvil o también al nav de
   escritorio (que hoy son la misma fuente de datos, `header.blade.php:37-49`)?
2. **Estado "ya autenticado" del ítem "Ingresar".** El header no tiene hoy ninguna
   lectura de `auth('customer')->check()`. Publicar "Ingresar" siempre, incluso para
   un cliente ya logueado, es un hueco funcional (no solo visual) que alguien tiene
   que decidir si entra en este lote o se abre como ticket aparte para
   `backend-laravel`.
3. **Subtítulos: copy final.** Los 9 subtítulos propuestos en el brief ("Volver al
   inicio", "Lima, Ica y Cusco", "Consulta tus reservas", etc.) son borrador de
   trabajo; falta que el jefe/cliente los apruebe como copy definitivo de marca antes
   de cargarlos en `lang/*/nav.php`.

---

## Totales

| Pantalla | Horas |
|---|---|
| Nosotros — hero rediseñado | **~27 h** |
| Menú móvil | **~10 h** |
| **Total del lote** | **~37 h** |

No incluye: construcción del campo Filament para `hero_features` si se decide que
sea 100% administrable desde cero (estimado dentro de las 4h de la fila 4 de la
Pantalla 1, asumiendo reuso directo de la UI de repeater que ya tiene
`why_travel_items`); ni la revisión visual real en navegador de ambas pantallas, que
queda pendiente de que el MCP de Chrome/Playwright quede libre.
