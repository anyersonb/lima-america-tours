# Lima América Tours — Propuesta navegable (maqueta visual)

Rediseño web propuesto para **limaamericatours.com**, elaborado por **AnyersonDev**.

## Contenido
- `propuesta-lima-america.html` — maqueta HTML navegable, autocontenida (imágenes y fuentes embebidas). Servir con cualquier estático, p. ej.:
  ```
  php -S 127.0.0.1:8080
  ```
  y abrir `http://127.0.0.1:8080/propuesta-lima-america.html`.
- `exports/` — capturas JPG de cada pantalla e informe PDF de presentación.

## Pantallas
1. Inicio — hero con buscador, categorías, tours destacados, confianza y CTA WhatsApp
2. Nosotros — hero, historia, misión/visión/valores, estadísticas
3. Tours (catálogo) — buscador + filtros por categoría
4. Ficha de tour — galería, pestañas y caja de reserva con total automático
5. Blog — artículos gestionables
6. Reserva — sin pago en línea (v1): confirmación por WhatsApp/correo
7. Contacto — formulario, WhatsApp y punto de recojo (sin mapa)

Alcance v1 según la propuesta: sin pasarela de pago, sin reseñas y sin mapa (activables después). Misma estructura/tecnología prevista para el desarrollo (Laravel + panel de administración).
