# Validación en staging del inventario de diseño — 2026-08-18

Entorno medido: **`https://limaamericatours.com/staging`**
Commit publicado en el servidor (`.deployed-commit`): **`708ed8a`** — es el mismo HEAD de
la rama `feat/mockups-ago-2026`. **Staging NO está desfasado**: lo que se midió es el
estado vigente, no una foto vieja.

Cómo se midió: con el puente CDP propio (Chrome headless en el puerto 9333), porque el
navegador del MCP estaba tomado por otra sesión. Clic real por coordenadas para abrir el
drawer, no `.click()`. Cada afirmación de abajo sale de una medición en el DOM del sitio
publicado, no de una captura ni de leer el código.

> ⚠️ La BD de staging **no es la local**. Todas las cifras de esta acta se leyeron del
> sitio publicado. Donde el inventario citaba la BD local, se marca la diferencia.

## Lo que el inventario afirmaba, y qué devolvió staging

| # | Afirmación del inventario | Medición en staging | Veredicto |
|---|---|---|---|
| 1 | `/nosotros` publica "10 años mostrando lo mejor del Perú" | `.lat-split-title` = **"10 años mostrando lo mejor del Perú"** | **CONFIRMADO — defecto vivo** |
| 2 | El badge flotante de años está correctamente oculto | `.lat-split__badge` ausente | CONFIRMADO (bien resuelto) |
| 3 | La ficha de tour publica "Atención al cliente 24/7" | Texto presente: **"Atención al cliente 24/7"** | **CONFIRMADO — defecto vivo** |
| 4 | La ficha promete pago en línea que no existe | Texto presente: **"Pago 100% seguro"** | **CONFIRMADO — defecto vivo** |
| 5 | El rating por tour es un valor sembrado sin respaldo | `.lat-stars__rate` = **4.8**, `.lat-stars__cnt` = **"(0 reseñas)"** | **CONFIRMADO — defecto vivo** |
| 6 | El defecto del rating se propaga a los relacionados | Las 3 tarjetas de "También te puede interesar" muestran **`4.8 (0)`** | **CONFIRMADO — alcance mayor al reportado** |
| 7 | El label de `group_type` es incorrecto | Barra de info: **"Grupal / Tamaño del grupo"** | CONFIRMADO |
| 8 | El botón de la galería no imprime el número real de fotos | Botón = **"Ver todas las fotos"** (texto fijo) con **10** miniaturas | CONFIRMADO |
| 9 | No existe selector Grupo/Privado | Ningún control de modalidad en el formulario | CONFIRMADO |
| 10 | El precio del mockup es correcto | `.lat-amt` = **"$69 por persona"** | CONFIRMADO |
| 11 | El artículo de blog no tiene avatar de autor, tarjeta de features, cita ni sidebar | Los cuatro ausentes; sí están hero, 3 relacionados y CTA final | CONFIRMADO |
| 12 | El menú móvil no usa el patrón círculo+ícono+subtítulo+chevron | 6 ítems, **0 con `<svg>`**, **0 con subtítulo** | CONFIRMADO |

## Correcciones a los inventarios, detectadas al validar

1. **El artículo de blog ya es CLARO en staging** (`body` = `rgb(255,255,255)`). El
   "conflicto de tema" que planteaba `02-tour-y-blog.md` es menor de lo que decía: la
   decisión de agosto ("Blog oscuro completo") se tomó sobre el mockup del **listado**, y
   el artículo nunca se pasó a oscuro. El mockup nuevo **coincide** con lo que ya está
   publicado. Sigue habiendo que decidir si listado y artículo comparten tema, pero no
   hay que rehacer nada para adoptar el mockup.

2. **El menú sí tiene Blog.** `01-nosotros-y-menu.md` listó el array `$navItems` sin él;
   está en `resources/views/components/header.blade.php:48` y se pinta en staging. Los 6
   ítems reales son: Inicio · Nosotros · Tours · Servicios · Blog · Contacto.
   (`Free Tours` es condicional a `$hasFreeTours`, hoy falso en staging.)
   Faltan respecto a la referencia de Lima View: **Mis reservas · Carrito · Reseñas ·
   Ingresar** — 4 ítems, no 3.

3. **La etiqueta "Blog" está hardcodeada** en ese array, sin pasar por `lang/*/nav.php`,
   a diferencia de los otros cinco ítems. No rompe nada (la palabra es igual en ES/EN/PT)
   pero se sale del patrón del archivo.

4. **El conteo de posts del blog era 14 y son 12** (10 publicados + 2 borradores;
   `blog_posts` no tiene `deleted_at`). Ya corregido en `02-tour-y-blog.md`.

5. **La barra de stats de Nosotros está bien.** Publica solo cifras verificables:
   `5.0 · 13 opiniones · 24 tours · 3 destinos · 8 valores`. Ninguna inventada.
   No aparecen ni "2.500 viajeros", ni "13 guías", ni "24/7" en esa página.

6. **El contraste que reportó el frente 1 es correcto.** Recalculado con la fórmula WCAG:
   `#cb101e` sobre `#171412` = **3.171:1**. Pasa el mínimo de 3:1 para gráficos
   (WCAG 1.4.11) y **no** llega a 4.5:1 para texto normal.

## Estado de los defectos vivos

Los cuatro están confirmados en el entorno publicado, no solo en el código:

| Defecto | Dónde | Regla que incumple |
|---|---|---|
| "10 años mostrando lo mejor del Perú" | `about.blade.php:283` (default hardcodeado; `blocks.split_heading_es` vacío en el CMS) | `LOTE-MOCKUPS-AGO-2026.md`, "Lo que NO se publica": no hay `company_started_year` |
| "Atención al cliente 24/7" | `tours/show.blade.php:586` + `lang/{es,en,pt}/ui.php` | Horario real publicado: Lun–Vie 9:00–19:00 |
| "Pago 100% seguro" | `tours/show.blade.php:537-540` | El alcance v1 no tiene pasarela activa (claves de prueba) |
| `4.8` con `(0 reseñas)`, en ficha y en 3 relacionados | `tours/show.blade.php:269-277` y tarjetas de relacionados | Cifra sembrada uniforme en los 24 tours, sin respaldo |

## Archivos de este inventario

- `01-nosotros-y-menu.md` — Nosotros (hero rediseñado) y menú móvil. ~37 h.
- `02-tour-y-blog.md` — ficha de tour y artículo de blog. ~60,5 h.
- `00-VALIDACION-STAGING.md` — esta acta.

Total inventariado: **~97,5 h**, con las partidas bloqueadas marcadas aparte (selector
Grupo/Privado, mapa del recorrido, foto de terceros del blog) porque dependen de
decisiones y de datos que hoy no existen.
