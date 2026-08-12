# Lote de mockups — agosto 2026 (Home · Nosotros · Blog · Contacto)

Rama: `feat/mockups-ago-2026` · Base: `e2f6100` · Publicado en staging al arrancar: `e1e3c38`

Los mockups están en `docs/rebrand/mockups/`:

| Archivo | Pantalla | Tema |
|---|---|---|
| `01-home.jpeg` | Home completa | claro con franjas oscuras |
| `02-nosotros-parte1.jpeg` | Nosotros, mitad de arriba | claro, termina oscuro |
| `03-nosotros-parte2.jpeg` | Nosotros, mitad de abajo | oscuro |
| `04-blog.jpeg` | Blog listado | oscuro completo |
| `05-contacto.jpeg` | Contacto | oscuro completo |

`02` y `03` son **una sola página**: `03` continúa donde `02` termina (el timeline oscuro
del final de `02` empalma con Misión/Visión de `03`).

## Decisiones del jefe para este lote (2026-08-10)

1. **Tema mixto, tal cual los mockups.** Home y Nosotros claros con franjas oscuras;
   Blog y Contacto oscuros de punta a punta. No unificar.
2. **Solo cifras verificables.** Hoy se publica `5.0 · 18 opiniones · 24 tours`. Los
   slots de "años de experiencia" y "viajeros felices" quedan **ocultos** hasta que el
   cliente dé el dato. Las cifras de los mockups son inventadas.
3. **Equipo real:** Samira, Nikki y Arturo (Guía Oficial de Turismo) y Augusto (Fundador).
   Sección activada.
4. **Destinos y categorías: solo los que tienen tours publicados.**

   ⚠️ **Corrección del 2026-08-11 — el catálogo real, medido contra la BD:**

   | Región | Activa | Tours publicados |
   |---|---|---|
   | Lima | sí | **15** |
   | Cusco | sí | **7** |
   | Ica | sí | **2** |

   Suman 24, que es el total de tours publicados. Una versión previa de esta tabla decía
   Lima 16: ese conteo no filtraba `deleted_at` y sumaba un tour borrado. **Al contar tours
   siempre hay que excluir los soft-deleted** — hay dos tours de prueba
   (`tour-qa-playwright-de-prueba`, `dbg-cap`) borrados lógicamente que aparecen en
   cualquier consulta SQL cruda que no los filtre, y que hicieron creer que el catálogo
   estaba sucio cuando no lo está.

   Solo existen **esas tres regiones**, y las tres califican. **Cusco SÍ tiene tours**
   (Machu Picchu, Conoce Machu Picchu si no tienes entrada, City Tour Cusco y 4 más):
   una versión anterior de este documento decía lo contrario porque se leyó la lista de
   chips de la página de Nosotros en vez de la tabla `regions`. Cusco entra.

   Arequipa, Paracas, Puno, Pachacámac, Caral y Nazca **no existen como región**, así que
   no se pintan. El guard es automático: cuando el cliente cree la región y le asigne un
   tour publicado, la tarjeta aparece sola.

   Efecto en los mockups: piden 6 tarjetas de destino y hoy se pintan **3**. El grid tiene
   que verse bien con 3, no asumir 6.

   Categorías con tours publicados: **Culturales 13 · Aventura 6 · Culinarias 3 · Otros 2**
   (13+6+3+2 = 24, que cuadra con el total). Una versión previa decía Aventura 7: mismo
   error de no filtrar `deleted_at`. **Este documento se equivocó tres veces por esa causa**
   (Lima 16 en vez de 15, Aventura 7 en vez de 6, y "1 tour publicado sin región" que en
   realidad era un tour borrado). Regla: **todo conteo de tours filtra soft-deleted**, o se
   hace con Eloquent, que ya lo hace.
5. **Todo administrable desde Filament.** Copy visible desde `Setting`/`Page`, no
   hardcodeado en el Blade.

## Lo que NO se publica, y por qué

Los mockups traen afirmaciones que el negocio hoy no puede sostener. Publicarlas es un
defecto, no una configuración pendiente (ver `feedback_cifras_sin_respaldo` y el punto
"Cifras verificables" de `ESTADO.md`).

| Aparece en el mockup | Por qué no va | Qué va en su lugar |
|---|---|---|
| `10+` / `11+` años de experiencia | Se contradicen entre mockups y no hay `company_started_year`. El sitio dice 2024, las fotos llegan a 2022 | Slot oculto |
| `+15,000` / `5000+` / `10,000` viajeros felices | No existe la cifra en ninguna fuente: WooCommerce tiene 3 pedidos y las 94 filas de JetBooking son bloqueos de disponibilidad | Slot oculto |
| `4.9/5` y `Basado en 1,500+ reseñas` | El agregado real es `5.0` sobre `18` opiniones | `5.0 · 18 opiniones`, vía `ReviewAggregator` |
| `Atención 24/7` y `Soporte 24/7` | El horario publicado es 9:00–18:30. Prometer 24/7 es falso | El horario real, desde el panel |
| `Pago seguro en línea`, `Diversos métodos de pago`, logos VISA/Mastercard/Amex/PayPal en el footer | El alcance v1 **no tiene pasarela activa**: las rutas de PayPal están desactivadas server-side y Culqi espera llaves del cliente. La reserva se cierra por WhatsApp/correo | Los sellos que sí son ciertos (reserva fácil, confirmación, sin cargos ocultos) |
| Timeline `2014 → 2024` con 6 hitos | Los años y el "más de 10,000 viajeros" de 2022 los inventó quien armó el mockup | Sección construida pero **apagada** hasta que el cliente dé los hitos reales |
| 9 valores incluyendo Respeto, Humildad, Pasión | Los valores reales del CMS son 8 y no incluyen esos tres; el mockup omite Laboriosidad y Honradez | Los 8 valores reales del panel |
| `Más de 5,000 viajeros felices` + tira de avatares (Contacto) | Cifra inventada y los avatares son personas que no son sus clientes | El agregado real, o la franja se oculta |
| Foto de la Montaña de 7 Colores como card de asesor (Contacto) | Vinicunca es Cusco y no venden tours ahí: vender una foto de lo que no ofreces | Una foto de un tour propio |
| Destinos Arequipa, Paracas, Puno | No existen como región: el clic cae en un listado de 0 resultados. **Cusco sí va** (7 tours) | Solo destinos con tours, con el guard automático |

## Navegación: los mockups se contradicen

- `01-home` muestra 5 ítems (sin Servicios).
- `02-nosotros` muestra 7 (agrega **Destinos**).
- `04-blog` y `05-contacto` muestran 6 = **el menú actual**.

**Se mantiene el menú actual de 6** (Inicio · Nosotros · Tours · Servicios · Blog ·
Contacto). Es una decisión deliberada del 2026-08-03 y es posterior a estos mockups; no
se revierte por una inconsistencia entre imágenes. "Destinos" no se agrega al nav.

---

# Home — `01-home.jpeg`

Estado actual: `resources/views/home.blade.php`. Lo que hay hoy y no está en el mockup:
tira de 3 imágenes desalineada bajo el hero, 3 tarjetas de PROMOCIÓN (las tres con
"Desde $200"), buscador de tours, "Tours Destacados", **galería "Descubre la belleza del
Perú" que se renderiza VACÍA** (franja oscura sin una sola imagen — defecto visible hoy),
franja roja "Tu próxima aventura empieza aquí", categorías con imágenes repetidas de
Machu Picchu y una en gris roto.

Lo que pide el mockup, de arriba a abajo:

1. **Header oscuro** con logo lockup, nav centrado y "Reservar Ahora" rojo. El mockup no
   dibuja la topbar de teléfono/correo que hoy existe: **se conserva** (es contacto útil y
   ya está aprobado), no se elimina por omisión del mockup.
2. **Hero a sangre** con foto oscurecida. Eyebrow rojo `SOMOS`, H1 blanco "Lima América
   Tours" en dos líneas, subtítulo rojo "10 años mostrando lo mejor del Perú", párrafo
   "Creamos experiencias auténticas que conectan viajeros con la historia, la cultura y la
   esencia de nuestro país.", y **dos CTAs**: "Reservar Ahora" (rojo) y "Ver Tours"
   (outline blanco). El rojo sobre foto es `#ff1f2d`, **no** el rojo de marca — el de marca
   mide 3.1–3.4:1 y no pasa AA.
   - **Card roja a la derecha** con los stats. Se alimenta de `HomeStatsResolver`: los
     slots sin dato no se pintan y el alto de la card se adapta. No inventar filas.
   - **Es un slider**: 4 dots abajo al centro y flechas ←→ abajo a la derecha.
   - El FAB de WhatsApp va **abajo a la izquierda** en el mockup (hoy está a la derecha).
     Muévelo solo si no colisiona con la barra fija de precio de la ficha.
3. **Tira de categorías** con foto + ícono + label, encimada al borde inferior del hero,
   donde arranca el fondo claro. El mockup pinta 6; van **las 4 reales** con su conteo.
4. **"Explora por categoría"** — eyebrow `ELIGE TU EXPERIENCIA`, título centrado, 4
   tarjetas **blancas**: imagen arriba, badge blanco "Tours" arriba a la derecha, ícono
   circular rojo encimado al borde inferior de la imagen, título, bajada, "Ver todos →".
   Ornamentos vegetales tenues en los márgenes laterales.
5. **"Viaja con confianza y vive la mejor experiencia"** — eyebrow `¿POR QUÉ ELEGIRNOS?`,
   5 columnas con ícono circular rojo outline: Guías certificados · Viajes seguros · Mejor
   precio garantizado · Atención personalizada · Cancelación flexible.
6. **Testimonios** — eyebrow `LO QUE DICEN NUESTROS VIAJEROS`, título alineado a la
   izquierda y "Ver todas las reseñas →" a la derecha. 3 tarjetas blancas (avatar, nombre,
   país, estrellas, texto, logo de la fuente) + 4ª tarjeta con el agregado grande. Las
   reseñas salen de las 18 reales con nombre y fecha; los logos, solo de las fuentes que
   existan de verdad.
7. **Destinos** — eyebrow `DESTINOS POPULARES`, tarjetas verticales con overlay y botón
   rojo centrado "Ver todos los destinos →". Solo los que tienen tours.
8. **Tira de garantías** sobre card blanca: 4 sellos. Quitar "Pago seguro en línea"
   mientras no haya pasarela.
9. **Footer oscuro** con newsletter, 4 columnas y barra inferior. El año del copyright es
   **dinámico** (el mockup dice 2025). Sin logos de tarjetas.

**La galería vacía de hoy**: o se llena desde el panel o se oculta con guard. Una franja
oscura de 200px sin contenido no puede quedar publicada.

---

# Nosotros — `02-nosotros-parte1.jpeg` + `03-nosotros-parte2.jpeg`

Estado actual: la ruta arma la vista en `routes/web.php` + `resources/views/about.blade.php`.
Hoy tiene hero simple, bloque de 2 fotos, misión/visión/valores en tarjetas claras,
**la franja oscura con los 4 stats en "0"** (defecto que corrige el backend en esta misma
rama) y un CTA sin features.

Orden completo que piden los dos mockups:

1. **Hero** con breadcrumb, eyebrow `NUESTRA HISTORIA`, H1 "Más de 10 años mostrando lo
   mejor del Perú", párrafo ("No eres un turista, eres nuestro invitado") y **collage de 6
   fotos** en mosaico irregular a la derecha. Las fotos son las reales del cliente
   (`CONTENIDO-REAL-PRODUCCION.md`), incluida la del grupo con el banner de la agencia.
   **Nada con la marca de agua de `cuscoperu.com`**: esa foto es de otro y no va.
2. **Barra de stats** oscura encimada al borde del hero. Mismo criterio: slots con dato.
3. **Equipo** — eyebrow `CONOCE A NUESTRO EQUIPO`, título "Guías locales, amigos y amantes
   de nuestra cultura". 4 tarjetas blancas (foto, nombre, rol, bio, íconos de redes) y a la
   derecha una **card oscura** "¿POR QUÉ VIAJAR CON LIMA AMÉRICA TOURS?" con 6 checks rojos
   y botón "Reservar Ahora". Cambiar "Soporte 24/7" por el horario real.
   Si un guía no tiene foto real, la tarjeta se pinta sin foto: **nunca una foto de stock
   representando a una persona real**.
4. **Testimonios** en franja **oscura**, 4 tarjetas con estrellas doradas y logo de fuente.
5. **Destinos** en fondo claro, 6 tarjetas + botón "Ver todos los destinos".
6. **Timeline** en franja oscura: se maqueta pero queda **apagada** por falta de hitos
   reales. Que el guard sea de dato, no un comentario en el HTML.
7. **Misión, visión y valores sobre foto oscura** (`03`): eyebrow con líneas laterales
   `— NUESTRO PROPÓSITO —`, tres tarjetas glass con borde tenue **dorado**, íconos de
   línea (compás, montaña con bandera, corazón en mano) y los **8 valores reales** como
   chips. El dorado es el acento de esta sección; **mide el contraste** de los chips (texto
   chico sobre fondo fotográfico) y súbelo hasta pasar AA — el precedente del teal
   `#0c8f96` y del rojo de marca es exactamente este error.
8. **"Miles de viajeros ya confiaron en nosotros"**: texto + botón verde de WhatsApp a la
   izquierda, 4 tarjetas de stat + card de trust (Google `5.0`, TripAdvisor, "Empresa
   registrada") a la derecha, y foto a sangre. Sellos con guard de dato.
9. **CTA "Déjanos ser tu guía en tu próxima aventura"** con dos botones y 4 features
   debajo. Cambiar "Atención 24/7" por el horario real y quitar "Pago seguro / Diversos
   métodos de pago".

---

# Blog — `04-blog.jpeg` (oscuro completo)

Estado actual: `resources/views/blog/index.blade.php`, hero "Post Recientes" y grid de 2
columnas donde el título va encimado sobre la foto. Hay **12 posts**, dos de ellos sin
portada (salen como un rectángulo gris): "Visitemos el museo Larco en Lima" y "Desayuno
Bueno Bonito y Barato en Lima".

1. **Hero**: foto a la derecha con degradado hacia la izquierda, eyebrow rojo
   `INSPÍRATE PARA VIAJAR`, H1 serif "Blog de viajes", bajada y **buscador pill** con lupa
   roja ("Buscar artículos, destinos o consejos…"). Es el buscador real que hace el
   backend, no decorativo.
2. **"Explora nuestros artículos"** a la izquierda y **filtros pill** a la derecha:
   "Todos" activo en rojo relleno, el resto outline. Las categorías salen de BD.
3. **Grid de 3 columnas**: imagen arriba con radio, **badge de categoría rojo** encimado
   abajo a la izquierda de la imagen, título serif de 2 líneas, y fila de meta
   `autor · fecha · N min`. **Botón circular blanco con flecha** abajo a la derecha de la
   tarjeta. Borde tenue en la tarjeta.
   - El mockup pinta 6 tarjetas y hay 12 posts: pagina, no recortes el catálogo.
   - Si un post no tiene autor cargado, no imprimas la fila de autor vacía.
4. **CTA final**: card oscura con ícono de avión rojo, "¿Listo para vivir tu propia
   historia?", botón rojo "Ver tours disponibles →", separador y botón outline "Habla con
   un asesor".
5. El mockup no dibuja footer: se conserva el del sitio.

El tema oscuro es de la **página**, no un override global: no rompas el claro del resto.

---

# Contacto — `05-contacto.jpeg` (oscuro completo)

> ⚠️ **Este mockup es de otra plantilla.** Trae el logo "PERÚ EXPERIENCIAS", el teléfono
> `+51 987 654 321`, el correo `hola@peruexperiencias.com` y habla de recojo en Cusco.
> **Se copia el layout y nada más.** Marca, teléfono, correo, horario y textos son los
> reales de Lima América, desde el panel. Que no se filtre un solo dato de esa plantilla.

Estado actual: `resources/views/contact.blade.php`, fondo claro, sin hero fotográfico, con
el formulario ya funcionando (nombre, celular, correo, asunto, mensaje, checkbox legal).

1. **Hero oscuro fotográfico** con eyebrow `— ESTAMOS PARA AYUDARTE`, H1 serif
   "Contáctanos", bajada y **3 chips** con ícono circular rojo y separadores verticales:
   Respuesta rápida · Atención personalizada · Viaja con confianza. El "menos de 24h" solo
   si el cliente lo sostiene; si no, el texto sale del panel.
2. **Formulario** en card oscura con ícono cuadrado rojo de sobre. Mismos campos que hoy
   —no rompas el envío ni el reCAPTCHA— más el **selector de país con bandera** en el
   teléfono. Revisa si ya hay librería de teléfono internacional en el proyecto antes de
   sumar una dependencia. El botón es rojo, ancho completo, con ícono de avión.
   Los links de Política de privacidad y Términos van rojos y subrayados, a las rutas
   reales que ya existen.
3. **Columna derecha**: 4 tarjetas oscuras con ícono circular rojo (Teléfono/WhatsApp,
   Correo, Horario, Punto de recojo) **con los datos reales del panel** — el horario es
   9:00–18:30, no "8:00 am – 8:00 pm". Debajo, card con **foto de un tour propio** y
   overlay: "¿Necesitas ayuda para elegir tu tour?" + botón "Hablar con un asesor".
4. **Franja inferior** de social proof: va **solo con el agregado real**. Sin "5,000
   viajeros felices" y sin avatares de personas que no son sus clientes.

---

## Problema transversal: el kit de imágenes está degradado

Auditado el 2026-08-11 sobre `public/assets/banners` y `storage/app/public/tours`
(424 archivos, 79 por debajo de 600px de ancho). No lo redescubra cada uno:

**12 archivos miden 205×123 píxeles reales** y se están sirviendo estirados a ~1900px:
`assets/banners/Rectangle 19210 · 19211 · 19212 · 19214 · 19215 · 19216 · 19217 · 19218 ·
19219 · image.jpg` y sus gemelos en `tours/Rectangle-*.jpg`, `tours/image.jpg`,
`tours/image-1.jpg`. Son los placeholders del kit de maqueta.

Dónde duele hoy:

- **Son la portada de 5 tours publicados**: Huacachina + Islas Ballestas, Full Day Lima
  Ancestral, Líneas de Nazca + Huacachina, Full day Líneas de Nazca, y Machu Picchu Full
  Day. El producto principal se muestra con una imagen de 205px estirada.
- **Son el `hero_image` de las 3 regiones** (Lima → `Rectangle 19216`, Ica → `19219`,
  Cusco → `19218`), o sea las tarjetas de destino de Home y Nosotros.
- Explican la galería sucia del Home y las tarjetas de categoría borrosas o en gris.

**Hay material real para reemplazarlos**: 345 imágenes por encima de 600px en
`storage/app/public/tours`, muchas del propio cliente. Al sustituir, verifica el ancho
intrínseco contra el ancho renderizado: si el intrínseco es menor, se va a ver borroso
igual.

**Dos archivos ajenos o fuera de lugar, a revisar antes de producción:**

- `tours/paquete-en-cusco-de-4-dias-lima-view-tours.jpg` — el nombre delata que es un
  asset de **Lima View Tours, otro cliente**. Hoy no está asignado como portada de ningún
  tour, pero no debería estar en el proyecto.
- `tours/Quito_-Ecuador_.jpeg` — una foto de Quito, Ecuador, en el catálogo de una agencia
  peruana.

Y sigue pendiente de `ESTADO.md` la foto con **marca de agua de `cuscoperu.com`**: es de
otro y no puede publicarse.

## Cómo se valida (aplica a las 4 páginas)

- **Medición real, no capturas.** `getBoundingClientRect`, clic de verdad en cada control,
  y contraste calculado con WCAG. Un screenshot no prueba que un botón funcione.
- **Breakpoints obligatorios**: 390 (móvil), 768 (tablet), 1024 (laptop), 1440 (desktop).
  Cero overflow horizontal en 390.
- **Al medir tras scroll**: `scroll-behavior: smooth` y los listeners con debounce dan
  falsos OK y falsos defectos. Scroll instantáneo, doble disparo del evento y
  `elementFromPoint`.
- **Fondos**: mide el fondo real de la sección antes de elegir el color del texto. No
  deduzcas el fondo de una regla vecina.
- **Tipografía**: el default de `body` y `h1..h6` vive en `tailwind.config.js`, no solo en
  el SCSS. Raleway en titulares, Open Sans en cuerpo. La serif de los mockups es de la
  maqueta; manda producción.
- **Consola y red limpias**, y sin mixed content (staging va en https).
- `php artisan test` verde antes de cerrar.
