# 03 — Plan de diseño: conflicto mockup del cliente vs. rebrand `design/rojo-acento`

Fecha: 2026-07-29
Rama: `feat/hero-mockup-cms` (sale de `data/tours-reales`, que cuelga de `design/rojo-acento`)
Decisión tomada por: Anyerson. **En el HERO manda el mockup del cliente.** No se propaga al resto del sitio sin su OK.

---

## 1. El conflicto, en concreto

`design/rojo-acento` fijó una dirección visual: **base neutra (crema/ink) y el rojo SOLO como detalle**
(precio, eyebrows, estados activos, etiquetas, links). El mockup que mandó el cliente (`Downloads\hero.png`)
contradice esa regla en tres puntos, y los tres están aplicados en el hero:

| # | Regla de `design/rojo-acento` | Lo que pide el mockup | Estado |
|---|---|---|---|
| C-1 | Titular del hero **todo en blanco, cero color** | Titular **ink sobre panel crema** + subtitular **rojo de peso** (`clamp(1.15rem,2.3vw,1.5rem)`, 700) | Aplicado en el hero |
| C-2 | Íconos **neutralizados a ink** | 4 íconos **rojos** en círculo outline rojo (`.lat-htc__ic`) | Aplicado en el hero |
| C-3 | Rojo = **solo detalle**, nunca una masa de color | **Tarjeta roja sólida** "10+ años" sobre la foto (`.lat-hero__years`, ~190×105 px de rojo pleno) | Aplicado en el hero |

No es un conflicto de gusto: son dos criterios coherentes por separado. El del rebrand busca que el rojo
signifique algo (si todo es rojo, nada destaca); el del cliente busca impacto de marca en la primera pantalla.

---

## 2. Qué queda inconsistente por NO propagarlo

Esto es lo que hay que mirar si algún día se decide propagar (o no):

**Inconsistencias reales, visibles en una misma sesión de navegación:**

1. **Íconos: rojos en el hero, ink en el resto.** Los 4 features del hero llevan círculo rojo; los íconos
   equivalentes de otras secciones siguen la regla del rebrand. El usuario ve los dos tratamientos en la
   misma página al hacer scroll (hero → resto del home).
2. **Masas de rojo.** Hoy el rojo pleno grande existe en: la tarjeta "10+" del hero y la **topbar**. El resto
   del sitio usa rojo en superficies chicas (botones, badges, precios). La tarjeta del hero es la única masa
   de rojo *dentro del contenido*.
3. **Jerarquía de titulares.** El hero es el único bloque con **dos** líneas de titular de peso (marca en ink
   + subtitular rojo). Los encabezados de sección del resto del sitio son de una línea, ink, con eyebrow rojo
   pequeño. Un `h2` de sección al lado del hero se ve de otra familia.

**Lo que NO está en conflicto (y conviene no tocar por error):**

- Los CTA en rojo sólido (`.lat-btn--red`, `.btn--primary`) ya son rojo desde `92a6ddc` (branding América) —
  eso fue una decisión anterior y deliberada, no parte de este conflicto.
- El eyebrow rojo pequeño ("SOMOS") **cumple** la regla del rebrand: es un detalle.
- El verde de WhatsApp no entra aquí: se cambió por contraste (ver §4), no por dirección visual.

---

## 3. Opciones, con su costo

| Opción | Qué implica | Costo | Riesgo |
|---|---|---|---|
| **A. Statu quo** (lo que está hoy) | Hero con el mockup del cliente; resto del sitio con `rojo-acento` | Cero | Las 3 inconsistencias de §2 quedan a la vista |
| **B. Propagar el criterio del cliente** | Íconos rojos en todas las secciones, más masas de rojo permitidas | Medio: repintar íconos de home/ficha/nosotros/contacto + revisar contraste de cada superficie roja | El rojo pierde función de señal; hay que revalidar contraste en cada bloque |
| **C. Contener el hero como excepción declarada** | Se documenta que el hero es una pieza de marca con reglas propias, y el resto mantiene `rojo-acento` | Bajo (esto mismo) | Es lo que ya hay, pero **con la decisión escrita** en vez de parecer un descuido |

**Recomendación técnica: C.** El hero es la única pieza que cumple una función de identidad de marca; el resto
del sitio es catálogo, donde el rojo trabajando como señal ayuda a la conversión. Pero la decisión es de
Anyerson, y B es perfectamente defendible si el cliente quiere una marca más agresiva.

---

## 4. Cambios de este lote que NO son parte del conflicto

Para que no se mezclen en la discusión de diseño:

- **Verde de WhatsApp `#25d366` → `#0f7d3d`** en los 4 botones con texto blanco (hero —luego retirado—,
  nosotros, contacto, checkout) y en el FAB flotante. Motivo: **contraste**, no dirección visual. El verde de
  marca con texto blanco da **1.98:1** y AA pide 4.5:1 para texto normal (3:1 para gráficos, WCAG 1.4.11).
  El nuevo da **5.2:1**. Es reversible en un token (`$lat-wa-strong`), y hay un test que calcula el ratio.
- **Velo del hero cerrado antes (58%→42%)**: la foto nueva tiene el motivo al centro, no a la derecha como la
  del mockup. Sin ese ajuste la ciudadela quedaba debajo del crema.
- **Altura del hero a 90vh** y **topbar con `padding: 15px 0`**: pedidos directos, 2026-07-29.

---

## 5. Decisiones del cliente/jefe registradas el 2026-07-29

1. Foto del hero: **panorámica de Machu Picchu** (1920×960) en lugar del faro del mockup. La del faro nunca
   llegó en alta.
2. Altura del hero: **90vh**.
3. **Fuera el pill de WhatsApp del hero.** Había dos CTA del mismo canal en la primera pantalla (el pill y el
   FAB global). Queda el FAB.
4. Menú reducido a **Inicio · Nosotros · Tours** + logo. Free Tours, Servicios, Blog y Contacto quedan
   **comentados** en `header.blade.php` (las rutas y vistas siguen vivas y accesibles por URL; el footer las
   sigue enlazando).
5. **"Reservar Ahora" → catálogo de tours**, ya no a WhatsApp. Coherente con `docs/pagos/PLAN-PASARELAS.md`
   (WhatsApp es soporte; la reserva se cierra en el sitio).

---

## 6. Revisión diferida

Cuando se reinvoque la ficha de diseño (o la de SEO a 30–90 días), revisar:
- Si las inconsistencias de §2 generaron algún comentario del cliente o del CRO.
- Si el H1 compuesto (marca + subtitular, ver `.claude/proyecto/08-seo.md`) movió las impresiones de la home
  para consultas genéricas vs. de marca.
