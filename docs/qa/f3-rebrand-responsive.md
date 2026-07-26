# F3 — Auditoría responsive del rebrand (header + hero), 375px / 768px

**Fecha:** 2026-07-26
**Rama:** `design/rojo-acento` (sin push, sin merge)
**Alcance:** Header (`_lat-header.scss` + `header.blade.php`) y Hero de Home (`_lat-home.scss` + `home.blade.php`) recién rehechos — nadie había validado su responsive hasta ahora.
**Herramienta de verificación:** Playwright MCP contra `http://127.0.0.1:8002/es` (servidor QA, `.env.qa` / BD `lima_america_qa`, solo lectura). Todas las capturas y mediciones de este informe se generaron con `browser_navigate` / `browser_resize` / `browser_evaluate` / `browser_take_screenshot` reales — no hay estimaciones.

> Nota operativa: la sesión de Chrome controlada por Playwright MCP se cayó/reconectó varias veces durante la auditoría (proceso `chrome.exe` del perfil `mcp-chrome-*` quedando huérfano, probablemente por presión de memoria del equipo — había ~40 procesos `chrome.exe` corriendo en paralelo). Cada vez que ocurrió se detectó el proceso principal huérfano, se mató (`taskkill /F`) y se repitió `browser_navigate` desde cero antes de continuar. Se documenta para que quede claro que ninguna medición de este informe es inventada: cuando una llamada fallaba, se repetía hasta obtener un resultado real.

---

## Resumen ejecutivo

| # | Hallazgo | Breakpoint | Gravedad | Estado |
|---|----------|------------|----------|--------|
| 1 | Tarjeta blanca superpuesta (`.lat-hero-card-wrap`, `margin-top:-42px`) corta el texto de la última fila de features ("Atención 24/7 — Estamos siempre para ayudarte") | ≤520px (375px) | 🔴 Alto | Corregido — solo CSS |
| 2 | Drawer móvil no bloqueaba el scroll del `<body>` de fondo mientras estaba abierto | 375px y 768px (burger visible hasta 1040px) | 🟠 Medio | Corregido — markup + lógica, con test |
| 3 | Overflow horizontal de página | 375px y 768px | — | No encontrado (confirmado limpio) |
| 4 | FAB de WhatsApp vs CTAs | 375px y 768px | — | No encontrado (la lógica JS de auto-evasión ya existente funciona) |
| 5 | Header/topbar/drawer visual (logo, nav, "Reservar Ahora", burger) | 375px y 768px | — | Sin problemas — no requirió cambios |
| 6 | Placeholder del buscador del drawer se recorta sin elipsis | 375px | 🔵 Bajo | No corregido — ver "No cubierto" |
| 7 | 1440px (desktop) | — | — | Sin regresión, confirmado con captura |

---

## Detalle por breakpoint

### 375px

#### Hallazgo 1 — Solape de la tarjeta blanca sobre la fila de features (🔴 Alto)

**Causa:** `.lat-hero-card-wrap { margin-top: -42px; }` es un valor fijo pensado para el `padding-bottom: 52px` de `.lat-hero-features` en desktop/tablet (52 − 42 = 10px de aire). En `≤520px`, `.lat-hero-features` cambia a `padding: 22px 0 34px` (una sola columna, filas apiladas), pero el `margin-top` de la tarjeta seguía siendo -42px, comiéndose 8px del contenido real de la última fila.

**Medición ANTES** (`browser_evaluate`, viewport 375×812):
```
lastHfRect.bottom = 1015.59
cardRect.top       = 1007.59
overlap = 1015.59 − 1007.59 = 8.0px  →  la tarjeta tapa 8px del texto "Estamos siempre para ayudarte"
```

**Captura ANTES:** `375-features-card-overlap-before.png` — se ve literalmente el texto de "Atención 24/7" cortado por el borde superior de la tarjeta blanca.

**Fix aplicado** (`resources/scss/pages/_lat-home.scss`, solo CSS):
```scss
.lat-hero-card-wrap {
    position: relative;
    z-index: 6;
    margin-top: -42px;
    margin-bottom: 20px;

    @media (max-width: 520px) { margin-top: -24px; }
}
```
Se redujo el pull a -24px en `≤520px`, dejando el mismo colchón de ~10px que existe en desktop (34 − 24 = 10px), sin perder el efecto visual de tarjeta flotante.

**Medición DESPUÉS:**
```
lastHfRect.bottom = 1015.59
cardRect.top       = 1025.59
gap = 1025.59 − 1015.59 = 10.0px  →  sin solape
```

**Captura DESPUÉS:** `375-features-card-overlap-after.png` — texto completo visible, tarjeta flotando limpiamente por debajo.

**¿Lleva test unitario?** No — es un fix 100% SCSS/CSS (cambio de un valor en un `@media`). Evidencia: medición + captura antes/después arriba.

---

#### Hallazgo 2 — Drawer sin bloqueo de scroll de fondo (🟠 Medio)

**Causa:** El `<header>` usa Alpine (`x-data="{ open: false }"`) para el drawer, pero nada tocaba `document.body` al abrir/cerrar. Con el drawer abierto, la página de fondo seguía siendo scrolleable (mala práctica de UX en overlays móviles, y lo que el protocolo QA pedía confirmar explícitamente).

**Medición ANTES** (clic real en el burger vía `browser_evaluate` + `.click()`):
```
Drawer abierto → getComputedStyle(document.body).overflow === "visible"
```

**Fix aplicado:**
- `resources/views/components/header.blade.php` — se agregó `x-effect="document.body.classList.toggle('lat-drawer-open', open)"` al mismo `<header>` que ya tenía `x-data="{ open: false }"` (el `x-effect` puede tocar `document.body` aunque esté fuera del árbol del componente, porque ejecuta JS arbitrario, no solo bindings internos).
- `resources/scss/layouts/_lat-header.scss` — nueva regla:
  ```scss
  body.lat-drawer-open { overflow: hidden; }
  ```

**Medición DESPUÉS:**
```
Drawer abierto  → hasClass('lat-drawer-open') === true,  body.overflow === "hidden"
Drawer cerrado  → hasClass('lat-drawer-open') === false, body.overflow === "visible"
```
Confirmado que el `overflow: hidden` **no queda colgado**: al cerrar el drawer (botón × probado) la clase se remueve y el scroll del body se restaura automáticamente vía la reactividad de Alpine (mismo mecanismo cubre cierre por backdrop, Escape o navegación, ya que todos esos triggers ya seteaban `open = false` antes del fix).

**¿Lleva test unitario?** Sí — este fix toca markup + lógica (atributo Alpine nuevo en `header.blade.php`). Se agregó `tests/Feature/HeaderDrawerScrollLockTest.php` con dos tests:
- `test_header_wires_body_scroll_lock_via_alpine_effect` — falla si el atributo `x-effect` se borra del `<header>`.
- `test_drawer_toggle_button_still_controls_the_same_open_state` — confirma que el burger sigue atado al mismo `x-data`.

Es un test estructural (Feature test de Laravel no ejecuta Alpine en navegador real), pensado como red de seguridad de regresión de markup; la verificación **funcional** real (que el scroll efectivamente se bloquea y se libera) se hizo con Playwright MCP y está documentada arriba con números reales.

```
$ php artisan test --filter=HeaderDrawerScrollLockTest
PASS  Tests\Feature\HeaderDrawerScrollLockTest
✓ header wires body scroll lock via alpine effect
✓ drawer toggle button still controls the same open state
Tests: 2 passed (5 assertions)
```

---

#### Sin problemas encontrados en 375px

- **Scroll horizontal:** se recorrió toda la página en pasos de 400px (`document.documentElement.scrollWidth` vs `window.innerWidth` en 23 puntos de scroll, de 0 a 8800px) — `scrollWidth` se mantuvo en 360px (≤375px) en TODOS los puntos. Sin overflow horizontal.
- **Header:** logo blanco visible y bien alineado, "Reservar Ahora" + burger sin solape (ver `375-top-before.png`).
- **Drawer:** logo blanco en cabecera, ítems legibles, botón cerrar accesible, backdrop funcional (ver `375-drawer-open-before.png`).
- **Hero:** título sin cortes, buscador con labels/valores completos ("Lima, Ica, Paracas…", "Cuándo viajas", "2 pasajeros" — nada truncado en esta anchura), chips en 2 filas sin overflow.
- **FAB de WhatsApp vs CTAs:** revisado el código de auto-evasión existente en `layouts/app.blade.php` (detecta colisión con `.lat-btn`, `.lat-btn-reservar`, etc. y sube el `bottom` del FAB dinámicamente) — con el botón "Ver Tours" fuera de viewport en la posición de scroll probada, no hay solape; la lógica de colisión ya existente se considera adecuada.
- **Tours Destacados** (fuera del alcance del rebrand, pero se revisó por si acaso): badges, estrellas, precio y "Ver Detalles" se ven completos sin overflow (ver `375-destacados-section.png`).

---

### 768px

**Medición de scroll horizontal:** recorrido en pasos de 500px (0 a 5500px) — `scrollWidth` se mantuvo en 753px (≤768px) en todos los puntos. Sin overflow horizontal.

**Header/topbar:** a diferencia de 375px, en 768px el topbar (teléfono, correo, redes, selector de idioma) SÍ se muestra (se oculta recién en `≤620px`) y se ve completo sin desbordes ni cortes (ver `768-header-top-before.png`). Nav horizontal sigue oculta (se activa el burger hasta `1040px`), "Reservar Ahora" + burger sin solape.

**Tarjeta blanca vs features:** en este ancho `.lat-hero-features` conserva `padding-bottom: 52px` (el breakpoint que lo reduce a 34px es `≤520px`, no aplica a 768px), así que el pull de -42px deja el mismo colchón de 10px que en desktop:
```
lastHfRect.bottom = 777
cardRect.top       = 787
gap = 10px → sin solape (confirmado, no requería fix en este ancho)
```

**FAB de WhatsApp vs "Ver Tours":**
```
fabRect.bottom     = 920
verToursRect.top   = 934.9
gap ≈ 15px → sin solape
```

No se encontraron problemas nuevos exclusivos de 768px — los dos hallazgos corregidos (tarjeta blanca y scroll-lock del drawer) aplican también aquí porque son el mismo código; el drawer en 768px usa el mismo burger/Alpine (nav horizontal solo aparece desde 1040px) y por tanto queda cubierto por el mismo fix.

---

## Confirmación 1440px (sin regresión)

Captura `1440-header-hero-after.png` tomada después de aplicar ambos fixes y rebuildear (`npm run build`):
- `document.documentElement.scrollWidth` = 1425px vs `innerWidth` = 1440px → sin overflow horizontal.
- Header: topbar completo, nav horizontal completa (Inicio activo con el punto rojo, Nosotros, Tours, Free Tours, Servicios, Blog, Contacto), logo blanco, "Reservar Ahora" — todo sin solapes.
- Hero: título, buscador de 4 columnas, chips, fila de 4 features en una sola fila, tarjeta blanca de 4 columnas (3 items + botón "Ver Tours") flotando correctamente sobre el borde del hero — el `margin-top:-42px` original permanece intacto en este ancho (el `@media (max-width:520px)` del fix no lo toca), tal como se esperaba.

Sin cambios visuales respecto al diseño aprobado en desktop.

---

## Estado de `php artisan test`

```
Tests:    4 failed, 116 passed (316 assertions)
Duration: 53.47s
```

Los 4 fallos son exactamente el baseline aceptado (`Tests\Feature\CheckoutTest`, relacionados a Culqi):
1. `payment form renders with items` — esperaba 200, recibió 302.
2. `process payment with valid token creates booking and charge` — redirect a `checkout.gracias` no coincide.
3. `process payment with failed token marks booking failed` — falta `session('error')`.
4. `booking email is queued after success` — `BookingConfirmed` no se encoló.

Ningún fallo nuevo. Los 116 tests restantes (incluidos los 2 nuevos de `HeaderDrawerScrollLockTest`) pasan en verde. Sin regresiones causadas por los cambios de header/hero de esta tarea.

---

## No cubierto

- **Placeholder del buscador del drawer** (`Buscar tours, destinos o experiencias`) se ve recortado a nivel de carácter en 375px sin elipsis (comportamiento nativo del navegador para overflow de `placeholder`, no hay `text-overflow` configurado en ese input). Severidad 🔵 Baja — no es parte de los hallazgos reportados como bloqueantes por el brief y no se tocó para no desviarse del alcance (header/hero); queda como sugerencia para un futuro ajuste de copy o `font-size` en ese campo específico.
- No se probaron breakpoints intermedios fuera de los pedidos explícitamente (375, 768, 1440) — p. ej. 400px, 600px, 900px, 1024px no se auditaron individualmente, aunque las reglas de `@media` involucradas (`900px`, `1040px`) se revisaron por lectura de código y la lógica es consistente con lo medido en los puntos sí auditados.
- El FAB de WhatsApp se verificó en un punto de scroll representativo por breakpoint (tope del hero); no se recorrió cada posición de scroll posible de toda la página para confirmar la evasión dinámica en cada micro-estado, dado el alcance (header/hero) y el tiempo de la sesión.
- No se probó el drawer/header en otras páginas del sitio (Tours, Nosotros, Blog, etc.) — el componente es compartido, así que el fix aplica globalmente, pero la verificación visual de esta tarea se limitó a Home según lo pedido.
- Navegación por teclado del botón de cerrar el drawer: se confirmó por lectura de código que tiene `aria-label` y `:focus-visible` definido en SCSS, pero no se ejecutó una prueba en vivo de tabulación con Playwright (la inestabilidad de la sesión de Chrome de esta corrida hizo priorizar las mediciones de los hallazgos principales).

---

## Archivos modificados/creados

- `G:\laragon\www\lima-america\resources\scss\pages\_lat-home.scss` — fix del solape de `.lat-hero-card-wrap` en `≤520px`.
- `G:\laragon\www\lima-america\resources\scss\layouts\_lat-header.scss` — regla `body.lat-drawer-open { overflow: hidden; }`.
- `G:\laragon\www\lima-america\resources\views\components\header.blade.php` — `x-effect` para el scroll-lock del drawer.
- `G:\laragon\www\lima-america\tests\Feature\HeaderDrawerScrollLockTest.php` — test nuevo (estructural) para el fix del scroll-lock.
- `G:\laragon\www\lima-america\docs\qa\f3-rebrand-responsive.md` — este informe.

Capturas de evidencia (carpeta home del usuario, generadas por Playwright MCP):
`375-top-before.png`, `375-features-card-overlap-before.png`, `375-features-card-overlap-after.png`, `375-drawer-open-before.png`, `375-destacados-section.png`, `768-header-top-before.png`, `1440-header-hero-after.png`.
