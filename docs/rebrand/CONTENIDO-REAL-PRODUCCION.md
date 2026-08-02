# Contenido real extraído de producción (limaamericatours.com)

Fecha de extracción: 2026-08-02 · Autor: AnyersonDev
Objetivo: reemplazar cifras inventadas, copy genérico y ausencia de prueba humana
por material que el cliente **ya publicó** en su sitio actual.

## Cómo se obtuvo este material

- Volcado completo de WordPress: `storage/app/wp-import/prod-dump/wp-db.sql` (151 MB),
  importado a una base de datos temporal `lima_scratch` (creada y **eliminada** al terminar).
  No se tocó `lima_america` ni el servidor de producción.
- Biblioteca de medios: `storage/app/wp-import/prod-dump/uploads-extract/uploads/`.
- El sitio usa **Elementor + JetEngine**: el texto casi nunca está en `post_content`,
  está en `wp_postmeta._elementor_data` (JSON) y en campos JetEngine en español.
- Cada dato de este documento lleva su fuente (tabla + criterio). Lo que no existe
  está marcado explícitamente como **"no está, hay que pedírselo al cliente"**.

**Nota de contexto que cambia el diagnóstico:** el problema del sitio nuevo no es
que falte material. Es que el material real estaba en producción y no se usó. Hay
historia de origen escrita por el cliente, hay 255 reseñas verificables de terceros,
hay una guía con nombre propio elogiada en cuatro idiomas, hay cinco retratos del
equipo con uniforme, y hay una obligación legal peruana (ESNNA/Mincetur) que ninguna
plantilla tiene.

---

# 1. Página "Nosotros" (post ID 25, slug `nosotros`)

**Fuente:** `wp_posts.ID = 25` (page, publish, creada 2024-01-07 16:42:30, última
modificación 2026-06-10 15:42:57) · texto en `wp_postmeta.meta_key = '_elementor_data'`.

`post_content` está vacío de texto propio: todo vive en widgets de Elementor.
La página tiene tres bloques: **Nuestro Equipo** (solo fotos), **Somos Lima América
Tours** (el texto) y **Visión / Misión / Valores**.

## 1.1 Bloque "Somos Lima América Tours" — transcripción literal

Eyebrow (widget `heading`, h6):

> Somos Lima América Tours

Titular (widget `heading`, en mayúsculas por CSS, color `#101F46`):

> agencia de turismo con 10 años en el mercado

Cuerpo (widget `text-editor`, tres párrafos, transcritos tal cual incluida la
puntuación original):

> Bienvenidos a Lima América Tours, una agencia de viajes fundada por dos
> emprendedores limeños apasionados por el turismo. Con más de 10 años de
> experiencia en el sector, nuestro propósito es compartir con ustedes las
> maravillas y experiencias únicas que ofrece el Perú.
>
> En Lima América Tours queremos mostrar al mundo la diversidad de destinos
> turísticos que posee nuestro país, brindando siempre un servicio personalizado
> y de alta calidad. Ofrecemos una amplia variedad de tours y excursiones hacia
> destinos emblemáticos como Ica, Paracas, el Circuito Mágico del Agua, Caral,
> Pachacámac, Nazca, además de tours gastronómicos que resaltan lo mejor de
> nuestra cultura culinaria.
>
> **¡No esperes más y vive una experiencia inolvidable en el Perú!**

## 1.2 Visión / Misión / Valores — transcripción literal

**Visión** (widget `icon-box`, `description_text`):

> Convertirnos en una empresa de proyección internacional que impulse el
> desarrollo turístico, creando oportunidades transformadoras para estudiantes y
> fortaleciendo el crecimiento de instituciones públicas y privadas

**Misión** (widget `icon-box`, `description_text`):

> Contribuir a la calidad del desarrollo de la industria turística en base a la
> experiencia de los tours con el principal objetivo de crecer competitivamente
> y equilibrados.

**Valores** (dos widgets `icon-list`, ocho ítems en total, en este orden):

> Responsabilidad · Laboriosidad · Honestidad · Honradez
> Dignidad · Justicia · Solidaridad · Humanismo

## 1.3 Lo aprovechable para reemplazar el copy genérico

Lo que hoy dice el sitio nuevo — *"Creamos experiencias auténticas que conectan
viajeros con la historia, la cultura y la esencia de nuestro país"* — tiene
sustituto literal en el material del cliente:

| Hueco actual | Material real disponible |
|---|---|
| Historia de origen | "una agencia de viajes fundada por **dos emprendedores limeños** apasionados por el turismo" |
| Antigüedad | "**más de 10 años de experiencia en el sector**" / "10 años en el mercado" |
| Qué hacen | Lista concreta de destinos: Ica, Paracas, Circuito Mágico del Agua, Caral, Pachacámac, Nazca + tours gastronómicos |
| Diferenciación declarada | "servicio personalizado y de alta calidad"; ver también §1.4, que es más concreto |
| Propósito | "mostrar al mundo la diversidad de destinos turísticos que posee nuestro país" |

## 1.4 Diferenciación en palabras del cliente (fuente adicional: blog)

**Fuente:** `wp_posts.ID = 1931` (post_type `blog`, publicado 2025-12-10),
campo JetEngine `texto-1`. Este texto es más concreto que el de Nosotros y sirve
directamente para la sección "por qué nosotros". Transcripción literal de los
bloques útiles:

> **✔ Experiencia y Profesionalismo**
> Nuestro equipo cuenta con años guiando a viajeros de todo el mundo. Somos una
> agencia formal y registrada, lo que garantiza seguridad y calidad.

> **✔ Guías Certificados y Carismáticos**
> Nuestros guías destacan por su preparación, dominio de idiomas y habilidad para
> contar historias de manera entretenida. Cada tour es dinámico, único y
> personalizado.

> **✔ Grupos Adecuados y Bien Organizados**
> A diferencia de otros servicios improvisados, en Lima América Tours manejamos
> grupos óptimos para que todos puedan escuchar, interactuar y disfrutar el
> recorrido.

> **✔ Seguridad y Confianza**
> Guiamos por rutas seguras y bien estudiadas, priorizando la comodidad y
> protección de cada viajero.

> **✔ Experiencias Auténticas**
> Mostramos la ciudad desde una perspectiva local, cercana y real. No vendemos un
> guion, sino una experiencia viva que conecta con la esencia de Lima.

La frase *"No vendemos un guion, sino una experiencia viva"* es del cliente y es
exactamente el tono que le falta al sitio nuevo.

---

# 2. Cifras verificables

## 2.1 Reseñas de terceros — el dato más fuerte que hay

**Fuente:** `wp_options`, filas `trustindex-google-page-details` y
`trustindex-tripadvisor-page-details` (snapshots que el plugin TrustIndex guarda
al descargar reseñas del perfil real).

| Plataforma | Nº de reseñas | Puntuación | Fecha del snapshot | Identificador del perfil |
|---|---|---|---|---|
| Google Business Profile | **186** | **5.0** | 2026-06-22 17:23 UTC (`timestamp` 1782148987) | place_id `ChIJZ_zFUQuxPScRYW0y3wdpP50`, coords −12.0266383 / −76.9877792 |
| TripAdvisor | **69** | **5.0** | 2026-03-10 16:05 UTC (`trustindex-tripadvisor-download-timestamp` 1773158708) | `d19923192` — *Lima_America_Tours*, Lima Region (`.com.pe`) |
| **Total verificable** | **255** | **5.0** | | |

Enlaces reales guardados en la base:
- Ficha TripAdvisor: `https://www.tripadvisor.com.pe/Attraction_Review-g294316-d19923192-Reviews-Lima_America_Tours-Lima_Lima_Region.html`
- Escribir reseña en Google: `https://www.google.com/maps/place//data=!4m3!3m2!1s0x273db10b51c5fc67:0x9d3f6907df326d61!12e1`
- Ver reseñas en Google: `https://www.google.com/maps/place//data=!4m7!3m6!1s0x273db10b51c5fc67:0x9d3f6907df326d61!8m2!3d-12.0266383!4d-76.9877792!9m1!1b1`

**Reemplazo directo:** el "4.9" y el "100%" del hero salen y entra
**"5.0 sobre 255 reseñas verificadas en Google y TripAdvisor"**, con enlace a ambas
fichas. Ese dato es auditable por cualquiera; el "4.9" no.

Advertencia honesta: son snapshots de junio y marzo de 2026. Antes de publicarlos
conviene refrescar el conteo (una consulta a la ficha basta) o redactarlos como
"más de 250 reseñas".

## 2.2 Reseñas cargadas en el CMS del sitio

**Fuente:** `wp_posts.post_type = 'reviews'`, 13 filas, todas `publish`,
todas creadas el 2024-04-01 (carga manual en bloque).
Promedio real de `wp_postmeta.meta_key = 'valoracion'`: **5.0 sobre 13 reseñas**
(las 13 tienen valoración 5; no hay ninguna con otro valor). Detalle en §5.

## 2.3 Viajeros atendidos — NO hay dato real en la base

Se revisaron todas las fuentes posibles de transacciones:

| Fuente | Qué contiene | Sirve como "viajeros atendidos" |
|---|---|---|
| `wp_wc_orders` | **3 pedidos** en total: 1 `wc-processing` (2026-01-26, USD 184) y 2 `wc-cancelled` (2026-06-14 y 2026-06-16, USD 250 sumados) | **No** |
| `wp_wc_order_stats` | Las mismas 3 órdenes, 6 ítems vendidos | No |
| `wp_wc_customer_lookup` | **3 clientes** | No |
| `wp_posts` `post_type='shop_order_placehold'` | 3 borradores (los mismos pedidos) | No |
| `wp_woocommerce_order_items` | 6 filas | No |
| `wp_jet_fb_records` (JetFormBuilder) | **0 filas** | No |
| `wp_e_submissions` (formularios Elementor) | **0 filas** | No |
| `wp_wpforms_payments` | **0 filas** | No |
| `wp_jet_apartment_bookings` (JetBooking) | 94 filas, pero **69 en estado `created` sin email ni usuario**: son bloqueos de disponibilidad, no reservas. Solo 18 filas tienen email (11 `pending`, 4 `cancelled`, 2 `processing`, 1 `failed`), rango 2024-02-07 → 2026-06-22 | **No** |

**Conclusión:** el checkout del sitio prácticamente no se usa. La operación real
ocurre por WhatsApp, Viator, GetYourGuide y TripAdvisor (los uniformes del equipo
llevan esos tres logos, ver §3). El número de viajeros atendidos **no está en el
volcado: hay que pedírselo al cliente**, y conviene pedirlo desglosado por canal
(directo / OTA) porque es la única forma de que salga un número defendible.

Mientras tanto, el sustituto honesto del "50K+" es la cifra de reseñas (§2.1), no
una estimación.

## 2.4 Cifras que el propio cliente publica hoy en su home

**Fuente:** `wp_postmeta._elementor_data` del post 19 (Inicio), widgets `counter`.

| Etiqueta (texto literal del cliente) | Valor publicado |
|---|---|
| Clientes Satisfechos | **5000+** |
| Viajes Realizados | **3000+** |
| Años de Experencia *(sic, con la errata)* | **10+** |

Estas tres cifras **tampoco tienen respaldo en la base de datos** — son las mismas
cifras a mano que estamos criticando, solo que más modestas. Dato relevante para la
conversación con el cliente: el "50K+" del sitio nuevo es **diez veces** lo que él
mismo afirma. Si se van a mantener cifras redondas, al menos deben ser las suyas y
él debe confirmarlas por escrito.

## 2.5 Antigüedad online

| Indicio | Fecha | Fuente |
|---|---|---|
| Instalación de este WordPress | **2024-01-06 00:51:55** | `wp_posts` ID 3 (Privacy Policy), el post más antiguo |
| Primeras páginas del sitio actual (Inicio, Contacto, Nosotros) | 2024-01-07 | `wp_posts` IDs 19, 21, 25 |
| Primer tour publicado | 2024-02-13 15:48:36 | `wp_posts` `post_type='tours'` más antiguo |
| Primer post de blog | 2024-04-03 | `wp_posts` ID 923 |
| **Foto de operación más antigua en la biblioteca** | **2022-09-14** | `uploads/2025/10/WhatsApp-Image-2022-09-14-at-4.46.19-PM.webp` (grupo en Plaza Mayor) — subida tarde, pero tomada en 2022 |
| Segunda foto más antigua | 2023-08-02 | `uploads/2025/10/IMG-20230802-WA0000.webp` |
| Ficha TripAdvisor | ID `d19923192` (numeración de altas ~2020) | `trustindex-tripadvisor-page-details` |

**Lectura:** el sitio web actual es de **enero de 2024**, pero la empresa opera
desde antes (fotos de 2022, ficha TripAdvisor previa, y el cliente reivindica
10 años). El año exacto de inicio de operaciones **no está en el volcado: hay que
pedírselo al cliente**. Lo que sí se puede afirmar sin inventar es
*"operando en Lima desde antes de 2022"* o citar su propia frase de 10 años como
declaración suya, no como dato nuestro.

## 2.6 Otras cifras reales, sí verificables desde la base

| Dato | Valor | Fuente |
|---|---|---|
| Tours publicados | **26** | `wp_posts` `post_type='tours'`, `post_status='publish'` |
| Free tours publicados | **2** (casco histórico + ruta gastronómica de barrio) | `post_type='free_tours'` (+1 en `tours_45`) |
| Artículos de blog | **10** (2024-04-03 → 2025-12-22) | `post_type='blog'` |
| Idiomas de los tours | **Español e Inglés en los 24 tours que declaran idioma** (ninguno declara otro) | `wp_postmeta.meta_key='idiomas'` |
| Rango de precios | **USD 20** (City tour con degustaciones de Pisco Sour) a **USD 500** (Machu Picchu) | `wp_postmeta.meta_key='jet_abaf_price'` → `_apartment_price` |
| Horario de atención | Lunes a Domingo, 9:30 am – 7:00 pm | Footer, `wp_posts` ID 1808 |

---

# 3. Personas: guías y equipo

## 3.1 Hay fotos del equipo. NO hay nombres asociados a ellas.

**Fuente:** post 25 (Nosotros), sección con encabezado literal **"Nuestro Equipo"**,
cinco widgets `image`. Los adjuntos son IDs 1944–1948 en `wp_posts`
(`post_type='attachment'`, `post_parent=25`). Se verificó que **ninguno tiene
`post_title` descriptivo** (son "Sin título-2-01 (2)" … "Sin título-2-05 (2)"),
**ninguno tiene `_wp_attachment_image_alt`**, ninguno tiene caption ni descripción,
y en el JSON de Elementor el campo `alt` está vacío en los cinco.

Los cinco retratos, descritos por lo que se ve en el archivo:

| Archivo | ID | Qué muestra |
|---|---|---|
| `uploads/2025/12/Sin-titulo-2-01-2.jpg` | 1944 | Mujer joven sonriendo, polo rojo institucional con logo Lima América Tours y logo **Viator** bordado, lentes de sol en la cabeza. Fondo: Centro Histórico. |
| `uploads/2025/12/Sin-titulo-2-02-2.jpg` | 1945 | Hombre con lentes, casaca roja Lima América Tours con acreditación colgada y logo **TripAdvisor**. Fondo: reja de iglesia del centro. Es el mismo hombre que aparece guiando en varias fotos de grupo (§6) — con alta probabilidad **Augusto** (ver §3.2). |
| `uploads/2025/12/Sin-titulo-2-03-2.jpg` | 1946 | Hombre joven, polo rojo con siglas **LAT**, mochila. Fondo: bahía con embarcaciones (Callao). |
| `uploads/2025/12/Sin-titulo-2-04-2.jpg` | 1947 | Mujer joven con cámara **Canon** al hombro (fotógrafa del tour). Fondo: reja colonial de noche. |
| `uploads/2025/12/Sin-titulo-2-05-2.jpg` | 1948 | Hombre joven, polo rojo Lima América Tours con logo **GetYourGuide** en la manga y logo Viator. Fondo: calle del centro. |

Estas fotos cumplen exactamente lo que pedía `MATERIAL-A-PEDIR.md` §1: persona
trabajando, con uniforme, en el sitio, no foto de estudio. **Son utilizables ya.**

**Lo que falta y hay que pedírselo al cliente:** nombre real, cargo, idiomas, años
en la empresa y una frase propia de cada uno de los cinco. Sin eso, las fotos se
pueden usar como galería de equipo pero no como fichas de guía. **Este es el único
bloqueo real para la sección "quiénes te van a llevar".**

## 3.2 Sí hay nombres de guías, pero vienen de las reseñas, no del CMS

Se buscó "Samira", "Augusto", "guía", "equipo", "staff" en `wp_posts`, `wp_postmeta`
y `wp_options`. Resultado:

### Samira (también "Sami" / "Sam" / "Samida")

**Fuente:** `wp_trustindex_google_reviews` (10 reseñas descargadas) y
`wp_trustindex_tripadvisor_reviews` (10 reseñas). Es la guía **más nombrada del
negocio** y no aparece ni una vez en el sitio.

- **8 de las 10 reseñas de Google la nombran** (dic. 2025 – jun. 2026).
- **7 de las 10 de TripAdvisor la nombran** (dic. 2025 – feb. 2026).
- La elogian en **cuatro idiomas**: inglés, español, alemán y portugués.

Citas literales (con nombre y fecha, listas para publicar):

> "Samira was fantastic! She was personable, knowledgeable and a great storyteller.
> Lima America tours are very lucky to have her! She was really engaging and was a
> highlight of our time in Lima." — **Benny a**, TripAdvisor, 2026-02-02, 5★
> *(título: "Samira was outstanding - great storyteller")*

> "Samira é uma guia espetacular, recomendo demais" — **Letícia Pirolo**, Google,
> 2026-05-22, 5★

> "Die Tour war sehr interessant und kurzweilig. Samira war eine tolle Führerin.
> Sehr zu empfehlen." — **Asor avla**, Google, 2026-05-25, 5★

> "Nos encantó el tour con Samira. Explica genial!" — **Mariangeles Espiñeira**,
> Google, 2026-05-23, 5★

> "Samira was a great guide, she was very informative and friendly. She knew lots of
> trivia about the city and kept us engaged throughout the whole tour!" —
> **Bob Robert**, Google, 2026-06-09, 5★

> "Mi amiga y yo hemos hecho hoy un tour andando por el centro de Lima con Samira, y
> nos ha encantado! La guía tiene mucho conocimiento sobre la ciudad y nos ha
> explicado muchas cosas no sólo de historia sino también de comidas y la cultura
> peruana. La degustación de Pisco al final es top. Totalmente recomendable.
> Gracias Samira ❤️" — **Pilar G**, TripAdvisor, 2025-12-06, 5★

### Augusto

**Fuente doble, y esto es lo importante: es un nombre confirmado por documento legal,
no solo por reseñas.**

1. **Legal.** Páginas *Términos y Condiciones* (`wp_posts` ID 731) y *Privacy Policy*
   (ID 3), texto idéntico en ambas, literal:
   > LIMA AMÉRICA TOURS es una empresa constituida en Perú con:
   > RUC:10720481826
   > Razón Social: **Díaz Córdova Augusto Manuel**
   > Correo: americatours09@gmail.com

2. **Reseñas.** Nombrado en 4 de las 13 reseñas del CMS y en 2 reseñas de TripAdvisor:
   > "Han sido unas horas de descubrimiento cultural y gastronómico de la ciudad de
   > Lima. […] **Augusto**, nuestro guía ha sido fantástico." — Beatriz, 29 Feb 2024
   >
   > "Muy bien, **Augusto** es una persona con mucho conocimiento de historia. Nos
   > ayudó mucho con información sobre la historia del Perú. 10/10. Muchos éxitos
   > Saludos desde Ecuador" — Santiago A, TripAdvisor, 2026-01-11, 5★
   >
   > "Excelente servicio para turismo!!! Fui con unos amigos de Austria y República
   > Checa para un recorrido histórico por el centro de Lima y el guía **augusto** fue
   > súper servicial y con inglés muy bueno y fluido!!!" — Lilian V, TripAdvisor,
   > 2026-02-08, 5★

**Augusto Manuel Díaz Córdova es a la vez titular del RUC y guía en activo.** Eso
encaja con el "fundada por dos emprendedores limeños" de la página Nosotros: es uno
de los dos fundadores y sigue guiando. Es la mejor historia que tiene el sitio y hoy
no aparece por ningún lado.

## 3.3 Lo que NO hay

- **No hay página de equipo con nombres.** Solo la tira de cinco fotos sin pie.
- **No hay campos JetEngine tipo `guia` / `equipo` / `staff`.** Se listaron todas las
  meta_keys de los CPT `tours`, `free_tours` y `reviews`: no existe ningún campo de
  guía asignado.
- **No hay usuarios reales del equipo en `wp_users`.** Hay 43 usuarios pero solo uno
  es del cliente (`limatours.adm`, registrado 2024-01-06); los otros 42 son registros
  de spam (dominios `dont-reply.me`, `noreply0.com`, `b1tches@gunna.bio`, etc.).
  Como efecto colateral: **conviene purgar esos 42 usuarios antes de migrar nada.**
- **No hay biografía, cargo, idiomas ni antigüedad de ningún guía.**

**Resumen del punto 3 en una frase:** hay caras (5 retratos con uniforme) y hay dos
nombres (Samira y Augusto Manuel Díaz Córdova), pero **no están vinculados entre sí
en ninguna parte de la base**. Emparejar nombre↔foto y obtener los tres datos
restantes (cargo, idiomas, frase propia) es lo único que hay que pedirle al cliente,
y es una conversación de diez minutos, no un pedido de material nuevo.

---

# 4. Certificaciones y confianza

## 4.1 ESNNA / Mincetur — página borrador ID 2607

**Fuente:** `wp_posts` ID 2607, `post_type='page'`, **`post_status='draft'`**, slug
`codigo-de-conducta-esnna`, creada 2026-07-04, modificada 2026-07-10.
`post_content` contiene únicamente la imagen; el Elementor de la página está sin
contenido (solo el heading heredado "Package Detail" de la plantilla).

Imagen: **`uploads/2026/07/Esnna.webp`** (727×1024, `wp_posts` ID 2609).

Es el afiche oficial **"Protégeme – Turismo Responsable"** del **Ministerio de
Comercio Exterior y Turismo (MINCETUR)**. Texto legible en el archivo, transcrito:

> EN ESTA AGENCIA **NO PROMOVEMOS NI PERMITIMOS LA EXPLOTACIÓN SEXUAL DE NIÑAS,
> NIÑOS Y ADOLESCENTES**, NI CUALQUIER OTRO ILÍCITO PENAL DEL CUAL TOMEMOS
> CONOCIMIENTO EN EL DESARROLLO DE NUESTRA ACTIVIDAD, CONFORME A LO DISPUESTO EN LA
> **LEY N° 29408**
>
> IN THIS TRAVEL AGENCY, WE DO NOT PROMOTE OR PERMIT THE SEXUAL EXPLOITATION OF
> GIRLS, CHILDREN AND TEENAGERS, NOR ANY OTHER CRIME OF WHICH WE TAKE KNOWLEDGE IN
> DEVELOPMENT OF OUR ACTIVITY, ACCORDING TO LAW Nº 29408

Marco legal citado en el propio afiche:
- **Ley N° 29408** "Ley General de Turismo" y su Reglamento, **D.S. N° 003-2010-MINCETUR**
- **Ley N° 30963** (sanciones al delito de explotación sexual)
- **Ley N° 30802** (ingreso de menores a establecimientos de hospedaje)
- Denuncias: **Línea 1818** y **Línea 100** (gratuitas, 24 h)
- Pie: Ministerio de Comercio Exterior y Turismo · "El Perú Primero"

**Esto es oro y está en borrador, invisible para el visitante.** Publicarlo, con un
sello en el footer que enlace a la página, es la señal de confianza más barata y más
diferenciadora del proyecto: acredita que la agencia está formalizada y adherida al
código de conducta de Mincetur. Ninguna plantilla lo tiene y ninguna agencia informal
lo pone.

## 4.2 Identificación fiscal — hay dos RUC distintos y no coinciden

Este hallazgo es un problema de coherencia que hay que resolver con el cliente antes
de publicar nada.

| Dónde aparece | RUC | Titular | Fuente exacta |
|---|---|---|---|
| **Footer del sitio** | **20616108264** | "Viaja con LAT S.A.C" | `wp_posts` ID 1808 (`elementor_library` "Footer"), widget `text-editor`: *"R.U.C. 20616108264 Viaja con LAT S.A.C"* |
| **Términos y Condiciones** y **Política de Privacidad** | **10720481826** | "Díaz Córdova Augusto Manuel" | `wp_posts` ID 731 y ID 3, `post_content` |

Un RUC empieza en `20` (persona jurídica: **Viaja con LAT S.A.C.**) y el otro en `10`
(persona natural con negocio: **Augusto Manuel Díaz Córdova**). Ambos son verificables
en SUNAT. Lo más probable es que la S.A.C. sea la constitución nueva y el `10` el
histórico, pero **hay que preguntarle al cliente cuál es el vigente** y usar uno solo
en todo el sitio. Publicar dos RUC contradictorios resta credibilidad en vez de sumar.

También conviene pedirle el **número de RUC vigente + constancia de inscripción como
Prestador de Servicios Turísticos ante Mincetur**, que es lo que convierte "somos una
agencia formal y registrada" (§1.4) en un dato con respaldo.

## 4.3 Datos de contacto y ubicación verificables

**Fuentes:** footer (ID 1808), página Contacto (ID 21), `trustindex-tripadvisor-page-details`,
`trustindex-google-page-details`, `wp_options`.

| Dato | Valor | Fuente |
|---|---|---|
| Razón comercial | Lima América Tours | `wp_options.blogname` |
| Dirección (ficha TripAdvisor) | **Emilio Althaus 673, Lima 115, PE** (Lince) | `trustindex-tripadvisor-page-details` → `address` |
| Coordenadas (ficha Google) | −12.0266383, −76.9877792 | `trustindex-google-page-details` → `review_url` |
| Teléfono / WhatsApp 1 | **+51 957 299 438** | Footer y Contacto |
| Teléfono / WhatsApp 2 | **+51 917 244 856** | Footer y Contacto |
| Email comercial | americatours09@gmail.com | Footer, Contacto, T&C, Privacidad |
| Email info | infolimaamericatours@gmail.com | Footer, Contacto |
| Email admin WP | limaamericatours.adm@gmail.com | `wp_options.admin_email` |
| Horario | Lunes a Domingo, 9:30 am – 7:00 pm | Footer |
| Facebook | `https://www.facebook.com/p/Lima-Am%C3%A9rica-Tours-61588600558264/` | Contacto |
| Instagram | `https://www.instagram.com/limaamericatours` | Contacto |
| TikTok | `https://www.tiktok.com/@lima.atours` | Contacto |
| YouTube | `https://www.youtube.com/@LimaamericatoursP` | Contacto |
| Punto de encuentro free tour | **Jirón de la Unión 926**, Centro de Lima | `wp_postmeta` post 1782, campo `que-incluye` |

Nota: la dirección de TripAdvisor (Emilio Althaus 673, Lince) es la única dirección
física registrada. El campo `address` del perfil de Google trae la URL del sitio, no
una calle. **Confirmar con el cliente si Emilio Althaus 673 sigue siendo la oficina**
antes de publicarla: una dirección equivocada es peor que ninguna.

## 4.4 Sellos de plataformas — evidencia visual en los uniformes

No hay ningún widget de "certificaciones" en el sitio, pero los uniformes del equipo
(§3.1) llevan bordados los logos de **Viator**, **GetYourGuide** y **TripAdvisor**, y
en `caption-*` se ve la acreditación colgada al cuello. Es prueba de que operan en
esas plataformas. **Falta pedirle al cliente los enlaces a sus fichas de Viator y
GetYourGuide** (solo tenemos la de TripAdvisor y la de Google).

## 4.5 Lo que se buscó y NO existe

Búsqueda literal sobre `wp_posts.post_content`, `wp_posts.post_title`,
`wp_postmeta.meta_value` y `wp_options.option_value`:

| Término buscado | Resultado |
|---|---|
| MINCETUR / Mincetur (como texto) | **0 coincidencias** — solo aparece dentro de la imagen del afiche ESNNA |
| CANATUR / Canatur | **0 coincidencias** |
| APAVIT / APOTUR / AGOTUR (gremios) | **0 coincidencias** |
| "licencia municipal" | **0 coincidencias** |
| "Ley 29408" como texto | **0 coincidencias** — solo dentro de la imagen |
| "seguro de viaje" | 1 coincidencia, en el tour Islas Palomino (ID 1153), como parte del "qué incluye" del tour, no como póliza de la agencia |
| Certificado de guía oficial / carné de guía | **0 coincidencias** |

**Hay que pedirle al cliente:** constancia Mincetur, afiliación a gremios si la tiene,
licencia de funcionamiento, y la póliza de seguro de responsabilidad civil / asistencia
al viajero si existe. Nada de esto está en el volcado.

---

# 5. Las 13 reseñas del CMS, normalizadas

**Fuente:** `wp_posts.post_type='reviews'` (13 filas, todas `publish`, cargadas el
2024-04-01) + `wp_postmeta` con los campos JetEngine `nombre`, `foto`, `fecha`,
`procedencia`, `viajo-en`, `valoracion`, `comentario`. Coinciden 1:1 con
`storage/app/wp-import/reviews.json`.

Promedio real: **5.0** (las 13 con valoración 5).
**Ninguna tiene foto:** el campo `foto` está vacío en las 13 y `featured_image_url`
es `null` en las 13. Si el diseño nuevo pide avatar, hay que usar iniciales o un
placeholder neutro — **no hay archivo de foto en `uploads-extract` para ninguna**.

| # | ID | Nombre | Fecha | Procedencia | Viajó | ★ | Foto | Comentario (literal) |
|---|---|---|---|---|---|---|---|---|
| 1 | 833 | Mary | 03 Mar 2024 | Greystones (IE) | en Pareja | 5 | — | Very interesting and informative tour. A lot of interesting places covered in justo ver 2 hours. Definitely recommended. |
| 2 | 849 | Margherita | 02 Mar 2024 | *(vacío)* | en Pareja | 5 | — | Una guida molto esperta e gentilissima. |
| 3 | 851 | Beatriz | 29 Feb 2024 | España la Vieja | en Pareja | 5 | — | Han sido unas horas de descubrimiento cultural y gastronómico de la ciudad de Lima. La experiencia ha sido enriquecedora y Augusto, nuestro guía ha sido fantástico. Ha resuelto todas nuestras dudas y nos ha dados unas recomendaciones para comer muy buenas. |
| 4 | 852 | Cezar | 28 Feb 2024 | Colonia | Solo | 5 | — | Top, would highly recommend. |
| 5 | 853 | Isuru | 26 Feb 2024 | Chelsford | en Familia | 5 | — | Really Good tour guide, definitely recommend him! |
| 6 | 854 | Anja | 13 Feb 2024 | Belgrado | Solo | 5 | — | Nice walk and an informative tour. Augusto is friendly, kwnodgable and accomodating |
| 7 | 855 | Katie | 24 Ene 2024 | Londres | en Grupo | 5 | — | We really enjoyed this walking tour. The guide was knowledgeable and very charismatic. Talking to us throughout the tour. |
| 8 | 856 | Constanza | 20 Mar 2024 | Santiago | en Familia | 5 | — | Muy agradable y entretenida experiencia con Augusto; guía muy dedicado y entretenido para explicarnos de la cultura peruana. Se agradece tanta experiencia y detalles en todo el recorrido. Algunos lugares no pudimos ingresar porque estaban en arreglos para la próxima semana santa, pero aun as, Augusto se dio el tiempo en casa sitio para que aprendamos de historia del Perú. |
| 9 | 857 | Emma | 23 mar 2024 | Rizensart | en Pareja | 5 | — | The tour was amazing. We wew lucky enough just to be of the two of us, and we had a lot of fun and learn alot from Augusto! Honestly. Canno recommend enough. Nice Little touch with the pisco and chocolate workshops. If you want to visit Lima Centro go to him! |
| 10 | 858 | Otylia | 08 mar 2024 | Galway | en Grupo | 5 | — | Thank you very much that was pleasure walking with you. Lots of historical information was talk in a really nice way. Best of luck Otylia. |
| 11 | 859 | Benajmin *(sic)* | 26 Marzo 2024 | Cancún | Solo | 5 | — | Trato amable, recorrido ameno y muy interesante 100/10 |
| 12 | 860 | Victor | 22 mar 2024 | *(vacío)* | en Pareja | 5 | — | ¡Muchas gracias Augusto! Conocimos mas de lo que esperábamos de lima. Una hermosa e interesante ciudad. Además que vivimos una gran experiencia. Super recomendado |
| 13 | 861 | **Bogotá** ⚠ | 15 mar 2024 | Bogotá | en Grupo | 5 | — | Se trata de un tour histórico y la prueba del pisco tiene el toque extra!. |

### Defectos de datos a corregir al cargar

- **#13 (ID 861):** el campo `nombre` dice "Bogotá" (repite la procedencia). El
  `post_title` del registro es **"Aura"** → el nombre correcto es **Aura**, procedencia
  Bogotá. Es un error de tipeo del cliente, no un dato faltante.
- **#11:** "Benajmin" está mal escrito en origen; el nombre real es Benjamin.
- **#2 y #12:** procedencia vacía (en #12 el campo contiene un espacio en blanco).
- **#1, #9, #10:** el texto en inglés tiene erratas del propio viajero
  ("justo ver 2 hours", "wew lucky", "Canno recommend"). **No corregirlas**: son la
  prueba de que la reseña es de una persona y no redactada por la agencia.
- Formato de fecha inconsistente ("03 Mar 2024", "23 mar 2024", " 26 Marzo 2024").
  Normalizar a `date` al importar; conservar el mes en español.
- **Ninguna indica qué tour hizo.** Por el contenido (caminata por el centro, pisco,
  chocolate, 2 horas) todas corresponden al **recorrido a pie por el Centro Histórico
  con degustación de Pisco Sour**, pero eso es inferencia, no dato. Si el diseño exige
  mostrar el tour, hay que confirmarlo con el cliente.

### Recomendación

Las 13 del CMS son de **febrero–marzo de 2024** y todas de Augusto. Las 20 de
TrustIndex (§2.1, §3.2) son de **diciembre 2025 – junio 2026**, más recientes, con
foto de perfil real del autor alojada en Google/TripAdvisor, y cubren a Samira. Para
el sitio nuevo conviene **cargar las dos tandas**: las 13 dan volumen y las 20 dan
actualidad, nombres de guía y respaldo de plataforma. El texto completo de las 20
está en las tablas `wp_trustindex_google_reviews` y `wp_trustindex_tripadvisor_reviews`
de la BD volcada.

---

# 6. Fotos con personas reconocibles

Base de rutas: `storage/app/wp-import/prod-dump/uploads-extract/uploads/`

Criterio: gente real en cámara, de la operación del cliente. Excluidas postales y
stock. La atribución a tour viene de `wp_posts.post_parent` del adjunto, que es dato
duro, no suposición.

## 6.1 Las mejores 20 (ordenadas por lo que aportan)

| # | Ruta | Tour / lugar (por `post_parent`) | Qué se ve |
|---|---|---|---|
| 1 | `2025/10/IMG_0686.webp` | Free tour Casco Histórico (post 1767) | **Guía de espaldas con el polo rojo y el logo "LIMA AMÉRICA TOURS" perfectamente legible**, brazo levantado señalando, grupo escuchando en Plaza Mayor. La foto más valiosa del archivo: marca + trabajo + gente, en una sola imagen. |
| 2 | `2025/10/1000385825.webp` | Free tour Casco Histórico (1767) | Grupo de ~16 viajeros internacionales con el guía en polo rojo agachado al frente, Plaza Mayor con la Pileta y el Palacio. Composición de foto de grupo clásica y creíble. |
| 3 | `2025/10/1000385828.webp` | Free tour Casco Histórico (1767) | Grupo de ~15 sobre el cruce peatonal frente a la Catedral, guía en polo rojo agachado con los brazos abiertos. Muy alegre, buena para hero. |
| 4 | `2025/10/caption.jpg` (idéntica: `caption-1.jpg`) | City Tour Lima + Gastronomía y Catacumbas (1634) | Grupo de 8 sosteniendo **el cartel rojo con el logo Lima América Tours**, Catedral de fondo, luz de mediodía. Foto subida por un viajero a TripAdvisor. |
| 5 | `2025/12/caption-9.jpg` | City Tour + Huaca Pucllana de noche (1910) | **Guía mujer con casaca roja explicando a un grupo frente al Palacio de Gobierno.** Es la única foto clara de una guía mujer en acción — muy probablemente Samira, que hay que confirmar con el cliente. |
| 6 | `2025/10/IMG_0978.webp` | Free tour Casco Histórico (1767) | Guía en casaca roja con 4 viajeros, Plaza Mayor con la bandera peruana. Escala humana, buena para testimonial. |
| 7 | `2025/10/IMG-20230802-WA0000.webp` | Free tour Casco Histórico (1767) | **Selfie del guía con dos viajeras**, polo rojo con logo TripAdvisor. Espontánea, sin pose de catálogo. Fecha en el nombre: 02-08-2023. |
| 8 | `2025/10/caption-2.jpg` | City Tour + Gastronomía y Catacumbas (1634) | Guía con casaca roja levantando el pulgar junto a dos viajeros comiendo picarones en la calle. Prueba de la parte gastronómica. |
| 9 | `2024/10/IMG_6843.jpeg` | Lima Highlights: Miraflores, Barranco y Centro (1199) | Grupo de ~18 en Plaza Mayor con el guía en polo rojo. Archivo grande (4032×3024). *Ojo: la imagen está rotada 90°, hay que corregir la orientación antes de usarla.* |
| 10 | `2025/10/1000385834.webp` | Free tour Casco Histórico (1767) | Grupo sentado en la escalinata de un edificio neoclásico del centro, muy relajado. Rompe el patrón de "todos de pie mirando a cámara". |
| 11 | `2025/10/WhatsApp-Image-2022-09-14-at-4.46.19-PM.webp` | Free tour Casco Histórico (1767) | Grupo de ~17 frente a la Catedral. **Es la foto de operación más antigua del archivo (sep. 2022)** → sirve para respaldar la antigüedad. |
| 12 | `2025/10/Imagen-de-WhatsApp-2024-04-02-a-las-10.30.31_c11e3f74.webp` | Free tour Casco Histórico (1767) | Grupo posando sentado sobre las esferas negras de la Alameda Chabuca Granda, guía en polo rojo haciendo el payaso. Foto con personalidad. |
| 13 | `2025/10/caption-5.jpg` | City Tour + Gastronomía y Catacumbas (1634) | 4 viajeros con el guía en casaca roja frente a la Catedral y el Palacio Municipal. Cara del guía bien visible. |
| 14 | `2025/10/caption-4.jpg` | City Tour + Gastronomía y Catacumbas (1634) | Tres viajeras y un viajero con una guía joven de casaca roja frente a la Basílica de San Francisco. |
| 15 | `2025/12/caption-8.jpg` | City Tour + Huaca Pucllana de noche (1910) | **Familia de 8 personas, tres generaciones, con un bebé en coche**, frente al talud de la Huaca Pucllana. Prueba de que el tour es apto para familias. |
| 16 | `2025/05/caption-2.jpg` | Ruta Gastronómica por mercados de Lima (1404) | Seis viajeros sentados en banquitos rojos comiendo anticuchos en la calle, guía a la derecha. Foto de TripAdvisor, la más "auténtica" del lote gastronómico. |
| 17 | `2025/10/caption-6.jpg` (idéntica: `caption-7.jpg`) | Tour Casa Aliaga y Catacumbas (1643) | Primer plano de dos viajeros sonriendo con picarones en la mano, Jirón de la Unión. Retrato alegre, funciona como pieza suelta. |
| 18 | `2025/10/1000385822.webp` | Free tour Casco Histórico (1767) | Grupo de 10 sentados sobre las letras "LIMA" de la Plaza Mayor. Muy reconocible, buena para redes. |
| 19 | `2025/10/IMG_3280.webp` | Free tour Casco Histórico (1767) | Grupo de ~14 frente a la Catedral en día nublado, guía incluido. Vertical, útil para móvil. |
| 20 | `2025/10/WhatsApp-Image-2023-10-19-at-8.14.31-PM-5.webp` | Free tour gastronómico de barrio (1791) | Viajeros comiendo en un puesto ambulante con vecinos alrededor. Nada turística, muy real. Fecha en el nombre: 19-10-2023. |

Complementarias del mismo nivel, por si hacen falta más:
`2025/10/1000385813.webp`, `2025/10/1000385837.webp` (grupos Plaza Mayor),
`2025/10/IMG_0828.webp` (guía conversando con un viajero, Plaza San Martín),
`2025/10/caption-3.jpg` (grupo caminando de espaldas por una calle empedrada de
Barranco — buena para banda ancha), `2025/12/caption-2-1.jpg` (guía señalando a un
viajero mayor en la Huaca Pucllana), `2025/05/caption.jpg` / `caption-4.jpg`
(viajero probando anticucho en primer plano, tour 1404),
`2024/10/IMG_9471.jpeg`, `2024/10/IMG_2202.jpeg` (tour 1199).

## 6.2 Retratos del equipo

Los cinco de §3.1 (`2025/12/Sin-titulo-2-01-2.jpg` … `-05-2.jpg`). Se listan aparte
porque su uso depende de conseguir los nombres.

## 6.3 Duplicados a tener en cuenta

WordPress guardó varias veces el mismo archivo al re-importar galerías. Antes de
migrar, deduplicar:
- `2025/10/caption.jpg` == `2025/10/caption-1.jpg`
- `2025/10/caption-6.jpg` == `2025/10/caption-7.jpg`
- `2025/05/caption.jpg` == `2025/05/caption-4.jpg`
- El set `2025/12/caption-*.jpg` repite parcialmente el set `2025/10/caption-*.jpg`
- Los `-scaled.jpeg` son la versión de WordPress del mismo original

## 6.4 Imágenes que PARECEN del cliente y NO lo son — no usarlas como prueba

Este es un aviso importante, porque son las que hoy alimentan varias secciones:

| Ruta | Por qué no sirve |
|---|---|
| `2024/02/WhatsApp-Image-2026-01-10-at-9.15.11-AM.jpeg` | Postal de stock de la Plaza Mayor al atardecer, descargada. Adjunta al tour 552 pero no es suya. |
| `2024/02/WhatsApp-Image-2026-01-10-at-9.18.54-AM.jpeg` | Foto aérea de stock del Malecón de Miraflores. |
| `2024/02/WhatsApp-Image-2026-01-10-at-9.27.03-AM.jpeg` | Huacachina de noche **con marca de agua visible de `cusco peru.com`**. Sacarla del sitio: es material de un tercero. |
| `2025/12/*-utc.jpg` (p. ej. `asian-women-friend-travel-together-...-utc.jpg`, `backpacker-couple-travel-adventure-...-utc.jpg`, `family-vacation-travel-rv-holiday-...-utc.jpg`) | Stock de Envato que vino con el template kit. **Una de ellas es hoy el fondo de la cabecera de la página Nosotros** — literalmente, la página que cuenta quiénes son está ilustrada con un banco de imágenes. |
| `2024/01/guidetravex_*.jpg`, `Maldives.jpg`, `niagara.jpg`, `travex-*.jpg`, `LDG_0000_dubrovnik-traveler-*.jpg` | Demo content del tema. Nada que ver con Perú. |

## 6.5 La galería "Fotos-Turistas" del home: buena, pero no es lo que promete

**Fuente:** post 19 (Inicio), widget `image-carousel`, 10 imágenes
(`2025/05/Fotos-Turistas-*.webp`, adjuntos 1382–1394, `post_parent=19`).
El titular del bloque es *"Mejores fotos Compartidas por Nuestros Pasajeros"*.

Revisadas una a una: son fotos reales tomadas por viajeros, pero **casi todas son
paisaje urbano y street art, no personas**: mirador del Cerro San Cristóbal, Catedral
al atardecer, murales de Barranco, esculturas del Jr. de la Unión, Iglesia de Santo
Domingo. Solo `Fotos-Turistas-12.webp` incluye un grupo, y de espaldas.

Es exactamente el problema que señala `MATERIAL-A-PEDIR.md` §2: el paisaje se compra,
la gente no. **La galería del home debe alimentarse del lote de §6.1, no de este.**

---

# Resumen: qué se resolvió y qué sigue pendiente del cliente

## Resuelto con material real (ya no hace falta esperar a nadie)

1. **Copy de "Nosotros"** — historia de origen, misión, visión y valores, literales (§1).
2. **Diferenciación concreta** — cinco bloques escritos por el cliente (§1.4).
3. **Cifra de confianza** — 5.0 sobre 255 reseñas en Google + TripAdvisor, con enlaces (§2.1).
4. **Reseñas cargables** — 13 del CMS + 20 de Google/TripAdvisor con nombre, fecha, idioma y puntuación (§5, §3.2).
5. **Un nombre de guía verificado** — Augusto Manuel Díaz Córdova, confirmado por documento legal (§3.2).
6. **Sello ESNNA / Mincetur** — la imagen existe, solo hay que publicar la página (§4.1).
7. **20 fotos con gente real**, atribuidas a su tour (§6.1) y 5 retratos de equipo (§3.1).
8. **Datos de contacto y ubicación** verificados (§4.3).

## Pendiente de pedirle al cliente (lista corta y concreta)

1. **Nombre, cargo, idiomas y una frase propia de cada una de las 5 personas
   fotografiadas** en `Sin-titulo-2-0X-2.jpg`. Es el único bloqueo real de la sección
   de equipo.
2. **Confirmar si la guía de `caption-9.jpg` es Samira** y pedirle una frase suya.
   Es la persona más elogiada del negocio y hoy no existe en el sitio.
3. **Cuál de los dos RUC es el vigente** (20616108264 "Viaja con LAT S.A.C" vs.
   10720481826 "Díaz Córdova Augusto Manuel") y unificarlo en todo el sitio.
4. **Año real de inicio de operaciones** (el sitio es de 2024, las fotos llegan a 2022,
   él dice 10 años).
5. **Número real de viajeros del último año**, desglosado directo / OTA. La base no lo
   tiene: solo hay 3 pedidos en WooCommerce.
6. **Constancia Mincetur, licencia municipal, gremios y póliza de seguro**, si existen.
7. **Enlaces a sus fichas de Viator y GetYourGuide** (los logos están en los uniformes
   pero no hay URL en ninguna parte).
8. **Confirmar la dirección** Emilio Althaus 673, Lince, antes de publicarla.
9. **Autorización de imagen** de los viajeros que aparecen en las fotos de grupo, si
   se van a usar en portada. Las fotos son suyas y ya estaban publicadas, pero conviene
   dejarlo dicho.
