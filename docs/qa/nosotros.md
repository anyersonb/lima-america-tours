# QA — Nosotros

Fecha 2026-07-25/26 · Rama `qa/paginas` (creada desde `qa/ficha-tour`) · Ejecuta `maquetador-frontend`
Cierra el hallazgo #4 de `docs/qa/panel-filament.md` / fila "Página Nosotros" de `ESTADO.md`.

## Resultado: VERIFICADO c/ deuda

| # | Capa | Nivel | Hallazgo | Evidencia | Estado |
|---|------|-------|----------|-----------|--------|
| 1 | L2/Sync | 🟠 | La sección "Misión, visión y valores" de `about.blade.php` usaba siempre el arreglo fijo `$defaultMvv` (o el campo huérfano `$b['mvv']`, sin UI en el admin) en vez del repeater real `blocks.stats` (Misión/Visión/Valores/Equipo) que el admin sí expone y guarda. | Editado `blocks.stats` con 3 items `QA_` (Misión/Visión/Valores) vía admin: antes del fix la sección seguía mostrando el contenido fijo en español ("Misión"/"Convertirnos en una empresa..."); con el fix, `/es/nosotros` mostró los 3 items `QA_` con icono asignado por posición (captura `nosotros-despues-mvv.png`) | **CORREGIDO** — se reemplazó `$mvvItems = $b['mvv'] ?? $defaultMvv;` por lógica que prioriza `$b['stats']` (mapeando `title_es/en/pt` + `desc_es/en/pt`, con icono `target/eye/heart` asignado por posición ya que el repeater no tiene campo de icono), cae a `$b['mvv']` (legado) y por último a `$defaultMvv`. `tests/Feature/PageBlocksCmsSyncTest.php` (2 tests) |
| 2 | L2/Sync | 🔵 | ~20 campos de la sección colapsable "Nosotros — contenido" del admin (`hero_cta_label`, `why_intro`, `why_intro2`, `why_cta_label`, `banner_heading`, `banner_text`, `cultura_heading`, `cultura_intro`, repeater `pillars`, `testimonios_eyebrow`, `testimonios_heading`) y 6 campos de imagen (`img_grid1..4`, `img_banner_cta`, `img_testimonios`) **no tienen ninguna sección viva en el diseño aprobado**. Confirmado contra el mockup real `docs/propuesta/exports/lat-02-nosotros.jpeg` (y el propio comentario de `_lat-about.scss`: "Nosotros — calca de lat-02-nosotros.jpeg"): el diseño aprobado solo tiene Hero → Split "10 años..." → Misión/Visión/Valores → Banda de estadísticas → CTA final. No existe banner "Somos Lima América Tours" como sección aparte, ni tabs "Vive la cultura local"/pilares, ni grid de 4 fotos, ni sección de testimonios propios — nada de eso aparece en el mockup aprobado. | Comparación directa `lat-02-nosotros.jpeg` (1440px) vs. `nosotros-antes.png`/`nosotros-despues-hero.png`: estructura idéntica (Hero, Split, MVV de 3 tarjetas, Stats band, CTA). Grep de `why_intro`/`banner_heading`/`cultura_`/`pillars`/`testimonios_eyebrow`/`img_grid`/`img_banner_cta`/`img_testimonios` en `about.blade.php`: 0 usos, ni antes ni después de este fix (a propósito). | **NO se implementa** — construir ~5 secciones nuevas (banner CTA, tabs de pilares, grid de fotos, testimonios propios) sin un mockup que las respalde violaría la fidelidad al diseño aprobado y arriesgaría inventar layout no revisado. Backlog de contenido: el dueño de producto debe decidir entre (a) encargar el diseño de esas secciones para una fase 2 de "Nosotros", o (b) recortar `PageResource` a los campos que sí tienen slot (ver recomendación original en `docs/qa/panel-filament.md` hallazgo #4). Anotado en `docs/qa/BACKLOG-CONTENIDO.md`. |
| 3 | L2/Sync | 🔵 | En sentido inverso: `about.blade.php` lee `$b['destinations']` y `$b['img_split']`/`split_eyebrow`/`split_heading`/`split_body_1`/`split_body_2`/`split_highlight`, que **no existen** como campos en `PageResource` (son huérfanos del lado del front, sin UI de admin para poblarlos). Ya señalado en `panel-filament.md` hallazgo #4; se confirma que sigue así. | Grep de `destinations`/`split_` en `PageResource.php`: 0 resultados (no hay `Forms\Components` para esas keys). | **NO se implementa** — agregar esos campos al admin es trabajo de `backend-laravel` (nuevos `Forms\Components` en `PageResource`), fuera del alcance de "hacer que el front consuma lo que el admin ya expone". Anotado en backlog. |

No se declara VERIFICADO puro por los hallazgos #2/#3 (deuda de producto/alcance, no de código) — de ahí "VERIFICADO c/ deuda".

## Cubierto

**Campos del CMS probados (Page, slug `nosotros`)**
- `blocks.hero_eyebrow_es`, `blocks.hero_title_es`, `blocks.hero_lead_es` — ya estaban cableados antes de esta sesión (confirmado, sin cambios); se re-verificó que siguen funcionando tras el fix del repeater.
- `blocks.img_hero` — ya estaba cableado (fondo del hero), sin cambios.
- `blocks.stats` (repeater, 3 items `title_es`/`desc_es` cargados: Misión/Visión/Valores) — **recién cableado**, confirmado en navegador.
- Fallback: con `blocks = []` (o Page inexistente), reaparece el hero por defecto ("Nuestro equipo"/"Somos Lima América Tours") y las 3 tarjetas MVV fijas (Misión/Visión/Valores con sus descripciones y tags de Valores).

**Campos del CMS NO cableados (documentados, no implementados — ver hallazgo #2)**
- `hero_cta_label_{es,en,pt}`, `why_intro_{es,en,pt}`, `why_intro2_{es,en,pt}`, `why_cta_label_{es,en,pt}`, `banner_heading_{es,en,pt}`, `banner_text_{es,en,pt}`, `cultura_heading_{es,en,pt}`, `cultura_intro_{es,en,pt}`, `pillars` (repeater), `testimonios_eyebrow_{es,en,pt}`, `testimonios_heading_{es,en,pt}`.
- `img_grid1`, `img_grid2`, `img_grid3`, `img_grid4`, `img_banner_cta`, `img_testimonios`.

**Evidencia de navegador** (`http://127.0.0.1:8002`, BD `lima_america_qa`)
- Antes: `nosotros-antes.png` (hero con fallback "Nuestro equipo"/"Somos Lima América Tours").
- Después del hero: `nosotros-despues-hero.png` — eyebrow "QA_SYNC_TEST_NOSOTROS", H1 "QA_ Somos Prueba", lead "QA_ Lead de prueba de nosotros."
- Después del repeater `stats`: `nosotros-despues-mvv.png` — 3 tarjetas "QA_ Misión Prueba"/"QA_ Visión Prueba"/"QA_ Valores Prueba" con sus descripciones e íconos (target/ojo/corazón) asignados por posición.
- Purga: Page `slug=nosotros` borrada vía `tinker --env=qa`, confirmado recarga de `/es/nosotros` vuelve al fallback original completo (hero + 3 tarjetas MVV con tags de Valores intactas).

**Tests**
- `tests/Feature/PageBlocksCmsSyncTest.php::test_about_page_renders_cms_hero_blocks_when_present` — ya pasaba antes (hero ya estaba cableado), se mantiene verde.
- `tests/Feature/PageBlocksCmsSyncTest.php::test_about_page_renders_cms_stats_repeater_in_mvv_section` — falla sin el fix (`git stash`), pasa con el fix.
- `tests/Feature/PageBlocksCmsSyncTest.php::test_about_page_falls_back_to_default_mvv_when_stats_empty` — pasa en ambos casos, confirma que el fallback (contenido de hoy) nunca se rompió.
- `php artisan test`: **4 failed (baseline Culqi/Checkout, sin cambios) / 110 passed (301 assertions)**. `SmokeTest` 8/8 verde. Cero regresiones nuevas.

## No cubierto (y por qué)

- **EN/PT en navegador**: solo se probó ES visualmente; el mapeo del repeater usa las mismas keys `_es/_en/_pt` sin lógica condicional, riesgo bajo, pero no hay captura de esos locales.
- **4º item del repeater ("Equipo")**: se probaron 3 items (Misión/Visión/Valores, igual que el mockup); no se probó qué pasa visualmente con un 4º item en el grid de 3 columnas (`$lat-mvv` está definido como `grid-template-columns: repeat(3, 1fr)` — un 4º item se envolvería a una segunda fila de 1 columna en vez de crear una fila de 4). Es un comportamiento razonable por defecto pero no fue validado visualmente; si el negocio va a usar 4 items regularmente, `_lat-about.scss` debería ajustarse a `repeat(4, 1fr)` en desktop — anotado como backlog técnico, no bloquea.
- **Tags de "Valores" con datos reales del CMS**: el repeater `stats` no tiene campo de tags (`title_es`/`desc_es` solamente), así que un item de CMS en la posición 2 (Valores) siempre se renderiza como párrafo de descripción, nunca como pills de tags — comportamiento esperado y documentado en el código, pero no ilustrado con captura porque el test de 3 items ya lo cubre indirectamente (ver `nosotros-despues-mvv.png`, donde "QA_ Valores Prueba" se ve como párrafo, no como tags).
- **Secciones no implementadas (hallazgo #2/#3)**: no cubierto a propósito, ver justificación arriba.

## Backlog de contenido (🔵) — no bloquea

- ~20 campos de texto + 6 de imagen de `PageResource` (grupo "Nosotros — contenido") sin sección de diseño aprobada — decisión de producto pendiente (nueva fase de diseño o recorte del formulario).
- Campos huérfanos en sentido inverso (`destinations`, `split_*`) sin UI de admin — decisión de producto/backend pendiente.
- Ajuste de grid CSS de `.lat-mvv` si el negocio decide usar 4 items (Misión/Visión/Valores/Equipo) de forma regular.
