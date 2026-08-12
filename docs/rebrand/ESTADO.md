# Estado del rebrand "anti-IA" y del lote de mockups

Actualizado: 2026-08-12 · Rama `feat/mockups-ago-2026`

Este archivo existe para que el siguiente que abra el proyecto (o yo mismo dentro
de un mes) no tenga que reconstruir de memoria en qué quedó todo.

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
