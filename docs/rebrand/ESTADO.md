# Estado del rebrand "anti-IA" y del lote de mockups

Actualizado: 2026-08-03 · Rama `feat/mockup-home-ficha` · Publicado en staging: **e1e3c38**

Este archivo existe para que el siguiente que abra el proyecto (o yo mismo dentro
de un mes) no tenga que reconstruir de memoria en qué quedó todo.

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
4. **Nombre de cada uno de los 5 guías** de las fotos de equipo. Sin ese
   emparejamiento la sección no se activa. Ahí está **Samira**, elogiada por
   nombre en 15 de 20 reseñas entre Google y TripAdvisor, hoy invisible en el sitio.
5. **Dos RUC contradictorios**: el footer dice `20616108264 / Viaja con LAT S.A.C.`
   y los Términos dicen `10720481826 / Díaz Córdova Augusto Manuel`. Es legal, no
   estético.
6. **Foto con marca de agua de `cuscoperu.com`**: es de otro y debería salir.
7. **Publicar la página ESNNA**, hoy en borrador con el afiche oficial ya subido.
   Es la señal de confianza más barata del proyecto.

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
