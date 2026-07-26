# F7 — Validación con personas reales (a ciegas)

**Fecha:** 2026-07-26
**Entorno:** `http://127.0.0.1:8002` (BD `lima_america_qa`, rama `design/rojo-acento`)
**Método:** dos personas ficticias, a ciegas, un solo navegador, secuencial. Persona 1 en escritorio (1366×768), Persona 2 en móvil (375×812). Se aplicó "torpeza obligatoria" (doble clic, mayúsculas, tildes/ñ, comas en precios, PDF donde va imagen, foto de 8.5MB, campos vacíos, etc.).

> Nota sobre el entorno: durante la prueba el navegador controlado se cerró y relanzó varias veces por causas ajenas al sitio (perfil de automatización huérfano). Cuando eso pasó se perdió texto no guardado de un formulario a medias — eso NO se reporta como defecto del producto, es un artefacto de la herramienta de prueba, no algo que un usuario real experimente.

---

## a) Relato en primera persona

### Doña Rosa (58 años, dueña de la agencia, usa Facebook y WhatsApp)

Entro a `/admin` con el correo y la clave que me dieron. Entra a la primera — qué alivio, porque le tenía miedo a esto. La pantalla dice "Entre a su cuenta", con "Correo electrónico" y "Contraseña", nada raro.

Al entrar veo un menú a la izquierda: Catálogo, Contenido, Marketing, Sistema, Reservas. Bastante claro, aunque no sé qué es "Marketing" a primera vista.

**Cambiar teléfono y dirección.** Voy a "Sistema → Configuración" y luego a la pestaña "Contacto" (ésa la entiendo). Cambio el teléfono y la dirección, y aquí me distraigo y hago doble clic en "Guardar cambios" sin querer. No veo ningún mensajito verde de "guardado" — me quedo insegura de si funcionó. Cuando voy a la web, el **teléfono sí cambió**, tanto arriba como abajo. Pero la **dirección sigue diciendo "Jr. Lampa 209, Lima Center"**, que ni siquiera es la que puse antes ni la nueva. No entiendo por qué cambié algo que no se ve reflejado en ningún lado.

Antes de seguir, noto que la pantalla de Configuración tiene un montón de pestañas que no entiendo para nada: "GEO", "AEO / FAQ", "APIs", "reCAPTCHA"... Si hubiera estado sola de verdad, aquí me hubiera detenido a preguntar qué es cada una.

**Publicar un tour nuevo.** Voy a "Catálogo → Tours → Crear Tour". El formulario pide Región y Categoría (fácil, son listas para elegir), duración, precio... Al precio le pongo "120,50" como estoy acostumbrada a escribir los precios, separando con coma. El sistema no me deja escribir la coma pero yo no me doy cuenta, y termino con "12050" en el campo — ¡mi tour hubiera quedado en $12,050 en vez de $120.50 y nadie me hubiera avisado! Menos mal que lo revisé.

Para la oferta especial pongo el precio de antes y me sale un mensajito verde clarísimo: "OFERTA ESPECIAL -20% activa — el card mostrará ANTES US$150 tachado y AHORA US$120". Eso sí me gustó, se entiende perfecto.

Lleno el título, la descripción, el itinerario, qué incluye y qué no. Subo las fotos: una portada bien pesada (a propósito, de las que uno saca con el celular sin comprimir) y el sistema la aceptó sin decirme nada de que pesaba mucho. En la galería subo dos fotos normales y, por error (a propósito para probar), un PDF. El sistema sí me avisó "Archivo de tipo inválido" — bien ahí. Pero cuando le doy a "Crear", **no pasa absolutamente nada**. Ni error, ni mensaje, ni se guarda. Aprieto de nuevo, tampoco. Aquí me hubiera rendido y llamado a alguien, porque no tenía ni idea de qué estaba mal — el PDF rechazado seguía apareciendo en la lista con una "X", pero no imaginé que eso era lo que bloqueaba todo el formulario. Alguien tuvo que borrar manualmente esa entrada roja para que "Crear" funcionara.

Una vez resuelto eso, el tour se creó bien y lo vi en la web con su foto, su precio tachado, su -20% y su itinerario. Ahí sí, contenta.

**Blog.** Voy a "Contenido → Blog → Crear Artículo". Lleno título, extracto y el texto. Cuando reviso, veo que hay una pestaña "Publicación" con un interruptor "Publicado" que viene **apagado** — a diferencia del Tour, donde venía prendido de entrada. Si no me fijaba, mi artículo se hubiera guardado como borrador sin que yo supiera que faltaba un paso. Lo prendo, guardo, y aparece en el blog público al toque.

**Leer un mensaje.** No había ningún mensaje, así que entré a la página de Contacto como si fuera una visitante y mandé una consulta. Volví al panel y ya me aparecía un aviso "Mensajes 1" en el menú — eso me gustó, se nota fácil que hay algo nuevo. Pero cuando entro a "leerlo", me encuentro con una pantalla llena de campos raros: "IP", "Navegador", "Origen"... como si fuera a editar la computadora de mi cliente. Yo solo quería leer lo que me escribió, no tocar nada técnico. Me dio un poco de miedo tocar algo ahí sin saber qué hace.

**Bloquear una fecha.** Esto fue lo más fácil de todo: "Reservas → Fechas bloqueadas → Crear". Me sale un calendario en español con los días de la semana abreviados (lun., mar., mié...), elijo el día, pongo el motivo, y listo. Fui a la web y probé reservar justo ese día: me salió "Esa fecha no está disponible para reservar. Elige otra." Perfecto, tal cual lo esperaba.

**Borrar el tour de prueba.** Entro a Tours, busco el mío, le doy "Borrar", y me pregunta "¿Está segura/o de hacer esto?" — qué bien que pregunte antes. Confirmo y desaparece. Voy a la web a ver si seguía ahí y me sale una página bonita de "404 — Página no encontrada", nada feo ni roto.

### Miguel (34 años, turista limeño, en el celular, con prisa)

Entro a la página desde el celular. Se ve limpio: un buscador arriba con "Destino", "Fecha", "Pasajeros" y un botón "Buscar". Escribo "Machu Picchu" en Destino y aprieto Buscar. Al toque me sale "1 resultado para 'Machu Picchu'" con la tarjeta del tour. Rapidísimo, ni un segundo de duda.

Antes de decidir, entro a ver el tour de Machu Picchu y también el de "Full day a las Líneas de Nazca" para comparar precio y duración. Se navega fácil, entro y salgo sin drama. Eso sí, la foto grande del tour de Machu Picchu... no es de Machu Picchu, parece una vista aérea de una ciudad al lado del mar. Raro, pero sigo, capaz es solo una foto de portada genérica.

Me decido por Machu Picchu. Dejo 2 pasajeros (ya venía así) y elijo una fecha del próximo mes. El precio total se actualiza clarito: $840. Aprieto "Reservar ahora" y me lleva a un carrito de compra. Ahí abajo hay una barra negra flotante con el total y un botón "Continuar" que **tapa parte del texto de precios** de mi tour (el "ANTES/AHORA"), medio feo pero se entiende el total igual.

Sigo a "Detalles de la Reserva". Me pide nombre, apellidos, correo, país, teléfono. Ahí, en el resumen de mi compra al costado, en vez de decir la fecha que elegí, dice **"Fecha por confirmar"**. Yo ya había elegido una fecha, ¿por qué no la ve?

Por apurado, aprieto "Confirmar reserva por WhatsApp" sin llenar nada. Me sale una alertita del navegador (no del diseño de la página) diciendo que tengo que aceptar los términos. Marco la casilla y vuelvo a apretar el botón **sin llenar mi nombre, correo ni teléfono** — y para mi sorpresa, **se abre WhatsApp igual**, con un mensaje que solo dice el tour y el total, nada de mis datos. Recién después la página me muestra en rojo que "el campo customer name es obligatorio" (así, en inglés a medias, ni siquiera dice "nombre").

Lleno bien mis datos esta vez (Miguel Quispe Rojas, mi correo, mi celular) y vuelvo a apretar el botón. Ahora sí me lleva a una pantalla de "¡Gracias por tu reserva!" con mi número de reserva, la fecha correcta (15 Aug 2026), 2 personas, $840 y "Pendiente de pago". Todo bien ahí. Pero el WhatsApp que se abre para mandar el mensaje solo dice: *"Hola, quiero confirmar mi reserva... Tour: Machu Picchu... Nombre: MIGUEL QUISPE ROJAS... Total: USD $840.00"* — **sin mi fecha, sin mi teléfono, sin mi correo, sin decir que somos 2 personas**. Voy a tener que escribir todo eso yo mismo en el chat, cuando se supone que ya lo había llenado en la página.

Como quedé con dudas de si mi reserva realmente quedó bien, pruebo si hay otra forma de contactar: el botoncito verde flotante de WhatsApp (abre un chat en blanco, sin problema), el teléfono de abajo (es clickeable para llamar), y el menú de "Contacto". Todo eso funciona sin drama.

Reviso también el menú de hamburguesa del celular: se ve ordenado, con buscador, links, un botón grande "Reservar por WhatsApp" y el teléfono abajo. Nada que reclamar ahí.

**Lo que esperaba vs. lo que pasó:** esperaba que al apretar "Confirmar reserva por WhatsApp" se abriera el chat con TODA mi información lista (tour, fecha, cuántos somos, mis datos) para solo apretar enviar. Lo que pasó es que se abrió un mensaje incompleto que me obliga a escribir de nuevo la fecha y mis datos de contacto — para alguien con prisa, esto es justo el tipo de cosa que hace que uno se frustre y busque llamar en vez de reservar por la web.

---

## b) Tabla de objetivos

| # | Persona | Objetivo | Resultado | Intentos | Tiempo aprox. | Punto de rendición / detalle |
|---|---------|----------|-----------|----------|---------------|-------------------------------|
| 1 | Rosa | Cambiar teléfono y dirección, verlos en la web | **Parcial — NO logrado del todo** | 1 | ~2 min | Teléfono cambia bien; la dirección se guarda pero **no aparece en ningún lugar del sitio** (el footer usa otro valor fijo) |
| 2 | Rosa | Publicar tour nuevo completo con fotos, precio, incluye/no incluye | **Con ayuda** | 2+ (botón "Crear" no reaccionaba) | ~8 min | Se atascó en "Crear" sin ningún mensaje; hubo que detectar y borrar manualmente un archivo rechazado (PDF) que dejaba el formulario bloqueado |
| 3 | Rosa | Poner 20% de descuento con precio tachado | **Logrado sola** | 1 | ~1 min | La vista previa del descuento fue clave para confiar en que estaba bien |
| 4 | Rosa | Escribir y publicar entrada de blog | **Logrado sola** (con detalle a notar) | 1 | ~3 min | El interruptor "Publicado" viene apagado por defecto (distinto a Tours) — fácil de olvidar |
| 5 | Rosa | Leer un mensaje de contacto | **Logrado, con fricción** | 1 | ~2 min | La pantalla de "leer" es en realidad un formulario de edición con campos técnicos (IP, Navegador) |
| 6 | Rosa | Bloquear una fecha sin operación | **Logrado sola** | 1 | ~1 min | Funciona perfecto; se probó en el sitio público y bloqueó la reserva con mensaje claro |
| 7 | Rosa | Borrar el tour de prueba | **Logrado sola** | 1 | ~30 seg | Buena confirmación, buen 404 después de borrar |
| 8 | Miguel | Buscar un tour a Machu Picchu | **Logrado solo** | 1 | <10 seg | Encontrado al toque desde el buscador del home |
| 9 | Miguel | Comparar dos tours y decidir | **Logrado solo** | 1 | ~1 min | Navegación fluida entre tours |
| 10 | Miguel | Reservar 2 adultos, próximo mes, hasta el final | **Parcial — llegó al final pero con resultado incompleto** | 2 (1er intento "pasó" con campos vacíos) | ~5 min | Llegó a "¡Gracias por tu reserva!", pero el mensaje de WhatsApp final no incluye fecha/teléfono/correo/pasajeros |
| 11 | Miguel | Contactar por otro medio si se traba | **Logrado solo** | 1 | ~30 seg | WhatsApp flotante, teléfono clickeable y Contacto, todos funcionan |

**Resumen:** Doña Rosa logró **4 de 7** objetivos completamente sola (3, 6, 7, y 4 con una salvedad); **1 con ayuda directa** (publicar tour); **2 parciales por fallas del sistema, no de ella** (dirección, y la sensación de "leer" mensajes). Miguel logró **3 de 4** objetivos completamente solo; **1 parcial** (reservar, porque el paso final de WhatsApp no cumple lo prometido).

---

## c) Mapa de fricción (ordenado por gravedad)

Para cada ítem: **¿esto haría que un cliente real llame por teléfono o abandone?**

### Bloqueante

1. **El mensaje de WhatsApp de la reserva no lleva fecha, teléfono, correo ni número de pasajeros.**
   → *Sí, esto hace que un cliente llame o escriba de nuevo, y que Doña Rosa reciba mensajes de "quiero reservar" sin poder saber para cuándo.* Es el corazón del negocio (reservar → WhatsApp) y falla justo en la entrega final.

2. **El campo de precio descarta comas silenciosamente ("120,50" se vuelve "12050") sin ningún aviso.**
   → *Sí, un tour podría publicarse con un precio 100 veces mayor al real* y nadie se enteraría hasta que un cliente se queje o deje de reservar.

3. **Subir un archivo inválido (PDF) en la galería de un tour deja el formulario completo trabado, sin ningún mensaje, y "Crear/Guardar" deja de funcionar.**
   → *Sí, se siente como que el sistema se congeló*; una persona no técnica se hubiera rendido ahí mismo y llamado a soporte.

4. **Cambiar la dirección en Configuración → Contacto no se refleja en ningún lugar del sitio público.**
   → *Sí, Doña Rosa cree que actualizó su dirección y en realidad el cliente sigue viendo la vieja*, lo cual es un problema serio de confianza (dirección incorrecta en un negocio real).

5. **El botón "Confirmar reserva por WhatsApp" abre WhatsApp ANTES de validar los campos obligatorios; solo después muestra el error.**
   → *Sí, un cliente apurado puede terminar enviando un mensaje de reserva sin haber puesto su nombre, correo ni teléfono*, y la agencia no tiene forma de contactarlo de vuelta salvo por el mismo WhatsApp.

### Mayor

6. **Los mensajes de error de validación usan nombres técnicos en inglés** ("El campo customer name es obligatorio").
   → Genera desconfianza inmediata ("¿esto está roto?"), aunque no bloquea el flujo una vez corregido.

7. **La pantalla para "leer" un mensaje de contacto es en realidad un formulario de edición con campos técnicos (IP, Navegador, Origen) editables.**
   → No detiene la tarea, pero intimida y podría llevar a que alguien edite algo por error sin saber qué hace.

8. **No hay validación ni aviso de tamaño al subir fotos pesadas (8.9MB aceptado sin queja).**
   → No bloquea, pero puede hacer que el sitio cargue lento con el tiempo si nadie comprime las fotos.

9. **El toggle "Publicado" viene apagado por defecto en Blog, pero prendido por defecto en Tours.**
   → Riesgo de que alguien publique "creyendo" que ya se ve en la web y no sea así.

10. **La barra flotante de Total/Continuar en el carrito (móvil) tapa el texto de precios de la tarjeta.**
    → Molesta visualmente, no impide continuar.

### Menor

11. Pestañas técnicas en Configuración (GEO, AEO/FAQ, APIs, reCAPTCHA) sin explicación para un usuario no técnico.
12. Campos "Contact email" y "Tagline" sin traducir al español.
13. Mensaje de error de subida de PDF menciona "image/*" (notación técnica).
14. La alerta de "debes aceptar los términos" es un pop-up nativo del navegador, no parte del diseño del sitio.
15. El botón "Guardar cambios" en Configuración no deja ninguna confirmación visible perceptible tras guardar (se guarda bien, pero no queda claro para el usuario).

---

## d) Las 5 cosas que más urge arreglar (en lenguaje de negocio)

1. **Arreglar el mensaje de WhatsApp de las reservas para que incluya la fecha, el teléfono, el correo y cuántas personas van.** Hoy el cliente llena todo ese formulario para nada: cuando llega el WhatsApp a la agencia, solo dice el nombre del tour, el nombre del cliente y el total. Esto obliga a preguntar todo de nuevo por chat, generando demora y mala primera impresión.

2. **Proteger el precio de los tours contra el error de escribir con coma en vez de punto.** Ahora mismo, si alguien escribe "120,50" pensando que así se pone el precio, el sistema guarda "12050" sin avisar. Un error de tipeo podría dejar un tour publicado a un precio absurdo sin que nadie se entere a tiempo.

3. **Arreglar que subir un archivo equivocado (por ejemplo un PDF donde va una foto) no trabe todo el formulario del tour.** Hoy, si eso pasa, el botón de guardar deja de responder sin ninguna explicación — parece que el sistema se congeló, y quien no sepa "adivinar" el problema se queda sin poder publicar nada.

4. **Hacer que cambiar la dirección de la empresa en el panel realmente cambie la dirección que ve el cliente en la web.** Hoy ese campo no sirve para nada: se guarda pero en el sitio siempre se ve otra dirección distinta, fija.

5. **Simplificar la pantalla donde se leen los mensajes de los clientes.** Hoy parece una pantalla de edición de sistema (con IP, navegador, etc.) en vez de sentirse como "leer una carta que me mandaron". Esto puede intimidar a cualquier persona sin experiencia técnica y generar miedo a tocar algo por error.

---

## e) Glosario — términos que confundieron a Doña Rosa

| Lo que dice el sistema | Qué debería decir |
|---|---|
| Tagline (ES) / Tagline (EN) | Frase corta / Eslogan |
| Contact email | Correo de contacto |
| GEO (pestaña de Configuración) | Ubicación en el mapa (o quitarla de la vista principal) |
| AEO / FAQ | Preguntas frecuentes |
| APIs (pestaña de Configuración) | Conexiones externas (o esconder de usuarios no técnicos) |
| reCAPTCHA | Protección anti-robots en formularios |
| "El campo customer name es obligatorio" | "El nombre es obligatorio" |
| "El campo customer email es obligatorio" | "El correo es obligatorio" |
| "El campo customer phone es obligatorio" | "El teléfono es obligatorio" |
| "Espera image/*" (error al subir PDF) | "Solo se permiten imágenes (JPG, PNG o WEBP)" |
| "slug" | *(ya está bien resuelto: "URL del tour (slug)" con buena explicación — ejemplo a seguir)* |

---

## f) Backlog 🔵 (contenido, no defecto de sistema)

- La foto de portada del tour "Machu Picchu Full Day" no muestra Machu Picchu, sino lo que parece una vista aérea de una ciudad costera. Es contenido de prueba (seed), pero se marca aparte porque afecta directamente la confianza del cliente al comparar tours — conviene corregirlo antes de producción real.
- Calificación "4.9 (0 comentarios)" en varios tours — inconsistente pero es dato de prueba.
- El placeholder del buscador del home ("Lima, Ica, Paracas…") no menciona Cusco/Machu Picchu como ejemplo, aunque el buscador sí encuentra ese tour correctamente.

---

## g) Defectos accionables

### [CÓDIGO] — requieren fix con test

1. El mensaje de WhatsApp generado en el checkout no incluye fecha del tour, teléfono, correo ni número de pasajeros (aunque esos datos SÍ quedan guardados correctamente en la reserva y se ven bien en "¡Gracias por tu reserva!" y en el panel de Reservas — es solo la plantilla del mensaje la que los omite).
2. El campo de precio (`type="number"`) descarta el carácter coma sin validar ni avisar, resultando en valores erróneos (ej. "120,50" → "12050").
3. Un archivo rechazado en el campo de galería (ej. PDF) deja el `<input type="file">` subyacente en estado inválido a nivel de navegador, bloqueando el envío de TODO el formulario sin ningún mensaje visible al usuario.
4. El resumen "Detalles de la Reserva" muestra el texto fijo "Fecha por confirmar" en lugar de la fecha real seleccionada por el cliente (el dato sí existe y se usa correctamente más adelante en el flujo).
5. Cambiar "Dirección (ES)" en Configuración → Contacto no se refleja en ninguna página del sitio público; el footer usa otro valor (posiblemente de otra fuente/configuración de mapa o GEO) que no se pudo editar desde esa pantalla.
6. El botón "Confirmar reserva por WhatsApp" dispara la apertura de WhatsApp antes de que termine la validación de campos obligatorios (nombre/correo/teléfono); la validación de servidor llega después, cuando el usuario ya fue redirigido.
7. Los mensajes de validación de backend usan nombres de campo técnicos en inglés ("customer name", "customer email", "customer phone") en lugar de las etiquetas en español ya usadas en el formulario.
8. No hay validación ni aviso de tamaño máximo al subir imágenes de portada/galería de tours (probado con archivo de 8.9MB, aceptado sin aviso).
9. El toggle "Leído" en Mensajes de contacto no se activa automáticamente al abrir/ver el registro; requiere acción manual + guardar.

### [LABEL/AYUDA] — renombrar etiqueta o agregar texto de ayuda

1. Pantalla "Editar Mensaje" (Mensajes de contacto) debería presentarse como vista de lectura simple, no como formulario de edición con IP/Navegador/Origen editables.
2. Pestañas técnicas en Configuración (GEO, AEO/FAQ, APIs, reCAPTCHA) necesitan texto de ayuda o reordenamiento para usuarios no técnicos.
3. Campo "Contact email" sin traducir → "Correo de contacto".
4. Campos "Tagline (ES)"/"Tagline (EN)" sin traducir → "Frase corta / Eslogan".
5. Mensaje de error al subir tipo de archivo inválido menciona "image/*" → cambiar a lenguaje llano.
6. La alerta de "acepta los términos" usa un `alert()` nativo del navegador, inconsistente con el diseño del resto del sitio.
7. Inconsistencia entre el valor por defecto del toggle "Publicado" en Tours (activado) vs. Blog (desactivado) — unificar criterio o hacerlo más visible.
8. Barra flotante "Total/Continuar" en el carrito (móvil) se superpone al texto de precios de la tarjeta del tour — ajustar espaciado.
9. El botón "Guardar cambios" de Configuración no deja una confirmación visible clara y persistente de que se guardó.

### [🔵 contenido]

1. Foto de portada de "Machu Picchu Full Day" no corresponde a Machu Picchu.
2. Calificación "4.9 (0 comentarios)" — dato de seed inconsistente.
3. Placeholder del buscador del home no menciona Cusco/Machu Picchu como destino de ejemplo.

---

## Cierre — estado de la base de datos

Se purgaron todos los registros creados durante la prueba:

- Tour `QA_TOUR CAÑÓN DE HUAROCHIRÍ Y BAÑOS TERMALES` — borrado desde el panel (parte de la tarea de Doña Rosa).
- Artículo de blog `QA_5 razones para visitar Machu Picchu este año` — borrado.
- Mensaje de contacto de `925886725` / `qa_rosa_prueba@example.com` — borrado.
- Fecha bloqueada del 31/07/2026 (`QA_MANTENIMIENTO DE VEHÍCULOS...`) — borrada.
- Reserva `LVT-GDEDMYB8` (Miguel Quispe Rojas) — borrada.
- Configuración → Contacto: "Teléfono principal" y "Dirección (ES)" revertidos a sus valores originales (`+51 925 886 725` y `Av. Larcomar 233, Of. 410 — Miraflores, Lima`).

Verificado tras la limpieza: listados de Tours (6/6, igual que al inicio), Blog, Mensajes, Fechas bloqueadas y Reservas todos sin rastro de `QA_`, y el teléfono/dirección del sitio público muestran de nuevo los valores originales. **La base de datos quedó igual a como se encontró.**
