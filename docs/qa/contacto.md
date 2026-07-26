# QA — Contacto

Fecha 2026-07-25/26 · Rama `qa/paginas` (creada desde `qa/ficha-tour`) · Ejecuta `maquetador-frontend`
Cierra el hallazgo #3 de `docs/qa/panel-filament.md` / fila "Página Contacto" de `ESTADO.md`.

## Resultado: VERIFICADO c/ deuda

| # | Capa | Nivel | Hallazgo | Evidencia | Estado |
|---|------|-------|----------|-----------|--------|
| 1 | L2/Sync | 🟠 | `contact.blade.php` extraía `$b = $page->blocks` pero nunca volvía a usar `$b` — el eyebrow, el H1 y el lead del hero estaban hardcodeados (`$L(...)`/`__('ui.contact_us')`), ignorando los 9 campos de texto que el admin sí guarda (`hero_eyebrow`/`hero_title`/`hero_lead` ×3 idiomas). | Reproducido con Page `QA_` (eyebrow_es="QA_ RESOLVEMOS TUS DUDAS", título_es="QA_ Contáctanos Prueba", lead_es="QA_ Este es el lead..."): antes del fix no aparecía nada; después de cablear `$bl()`, apareció en `/es/contacto` (captura `contacto-despues.png`) | **CORREGIDO** — se agregó el helper `$bl(key, fallback)` (mismo patrón que `about.blade.php`) y se sustituyeron los 3 literales del hero por `$bl('hero_eyebrow', ...)`/`$bl('hero_title', ...)`/`$bl('hero_lead', ...)`. `tests/Feature/PageBlocksCmsSyncTest.php` (2 tests de Contacto) |
| 2 | L2/Sync | 🔵 | Los 5 campos de imagen del admin (`blocks.img_hero`, `blocks.img_collage_1..4`, tab "Imágenes — Contacto") **no tienen ningún slot en el diseño aprobado**. Confirmado contra el mockup real `docs/propuesta/exports/lat-07-contacto.jpeg`: el hero de Contacto es deliberadamente plano — el propio código lo documenta (`{{-- HERO plano (sin imagen) — replica lat-07-contacto.jpeg --}}`, línea 27 de `contact.blade.php`) y el mockup no muestra imagen ni collage en ningún punto de la página. | Comparación directa del mockup `lat-07-contacto.jpeg` (1440px) vs. `contacto-antes.png`: idénticos, sin imagen de fondo ni fotos. Grep de `img_hero`/`img_collage` en `contact.blade.php`: 0 usos (ni antes ni después del fix, a propósito). | **NO se implementa** — inventar una sección de imagen/collage sin un mockup que la respalde violaría la fidelidad al diseño aprobado. Backlog de contenido: el dueño de producto debe decidir entre (a) diseñar una nueva sección para esas 5 imágenes, o (b) eliminarlas de `PageResource` (ver recomendación en `docs/qa/panel-filament.md` hallazgo #3). Anotado en `docs/qa/BACKLOG-CONTENIDO.md`. |

No se declara VERIFICADO puro por el hallazgo #2 (deuda de producto, no de código) — de ahí "VERIFICADO c/ deuda".

## Cubierto

**Campos del CMS probados (Page, slug `contacto`)**
- `blocks.hero_eyebrow_es` → cablea y aparece (control positivo).
- `blocks.hero_title_es` → cablea y aparece.
- `blocks.hero_lead_es` → cablea y aparece.
- Fallback: con `blocks = []` (o Page inexistente), reaparecen los 3 textos por defecto ("ESTAMOS PARA AYUDARTE" / "Contáctanos" / "¿Tienes dudas...").

**Campos del CMS NO cableados (documentados, no implementados)**
- `blocks.img_hero`, `blocks.img_collage_1`, `blocks.img_collage_2`, `blocks.img_collage_3`, `blocks.img_collage_4` — sin sección viva en el diseño aprobado (ver hallazgo #2).
- Los campos `_en`/`_pt` de `hero_eyebrow`/`hero_title`/`hero_lead` usan el mismo helper `$bl()` y por tanto quedan cableados por construcción (mismo código, distinto locale) — no se verificó cada idioma por separado en navegador (solo ES), pero `$bl()` no discrimina por idioma en su lógica, así que el comportamiento es idéntico. Confirmado por lectura de código.

**Evidencia de navegador** (`http://127.0.0.1:8002`, BD `lima_america_qa`)
- Antes (`blocks` vacío/página inexistente): `contacto-antes.png` — eyebrow "ESTAMOS PARA AYUDARTE", H1 "Contáctanos".
- Después (Page `QA_` creada vía admin con los 3 campos ES): `contacto-despues.png` — eyebrow "QA_ RESOLVEMOS TUS DUDAS", H1 "QA_ Contáctanos Prueba", lead "QA_ Este es el lead de prueba del hero de contacto."
- Purga: Page `slug=contacto` borrada vía `tinker --env=qa` (`Page::whereIn('slug', [...])->delete()`), confirmado recarga de `/es/contacto` vuelve al fallback original.

**Tests**
- `tests/Feature/PageBlocksCmsSyncTest.php::test_contact_page_renders_cms_hero_blocks_when_present` — falla sin el fix (`git stash`), pasa con el fix.
- `tests/Feature/PageBlocksCmsSyncTest.php::test_contact_page_falls_back_to_default_copy_when_blocks_empty` — pasa en ambos casos (no depende del fix), confirma que el fallback nunca se rompió.
- `php artisan test`: **4 failed (baseline Culqi/Checkout, sin cambios) / 110 passed (301 assertions)**. `SmokeTest` 8/8 verde. Cero regresiones nuevas.

## No cubierto (y por qué)

- **EN/PT en navegador**: solo se probó ES visualmente; EN/PT se apoyan en el mismo helper `$bl()` sin lógica condicional por idioma, por lo que el riesgo de que fallen específicamente es bajo, pero no hay captura de esos dos locales.
- **Responsive (375/768/1440) del hero cableado**: no se corrió breakpoint por breakpoint porque el cambio es solo de contenido de texto (mismo markup/CSS que ya existía y ya era responsive); no se tocó ningún selector de `_lat-contact.scss`.
- **Imágenes (hallazgo #2)**: no implementado a propósito, ver justificación arriba.

## Backlog de contenido (🔵) — no bloquea

- 5 campos de imagen de `PageResource` (tab Contacto) sin sección de diseño aprobada — decisión de producto pendiente (agregar diseño nuevo o recortar el formulario).
