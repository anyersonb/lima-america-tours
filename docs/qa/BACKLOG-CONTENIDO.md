# Backlog de contenido (🔵) — Lima América Tours

Hallazgos de **contenido/decisión del cliente**, NO defectos de código. Ningún agente los inventa ni los "tapa".
Son decisión del jefe. Origen: validación ad-hoc CRO + QA experto (2026-07-24/25).

| # | Módulo | Ítem 🔵 | Detalle | Decisión / responsable |
|---|---|---|---|---|
| 1 | Ficha de tour | Fotos equivocadas | El tour "Machu Picchu Full Day + Tren Panorámico desde Cusco" muestra fotos del litoral de Miraflores | Cargar fotos reales de Machu Picchu desde el admin |
| 2 | Ficha de tour | Itinerarios vacíos | Varias fichas muestran "El itinerario detallado estará disponible próximamente" | Cargar itinerario real por tour |
| 3 | Ficha de tour | Comparativa desactivada | El bloque "convencional vs premium" existe en el editor pero está off en todos los tours | Decidir si se activa (y en cuáles) |
| 4 | Ficha de tour | FAQ de prueba | Se agregaron FAQs de prueba (ES/EN/PT) al tour `huacachina-paracas-full-day` solo para validar el acordeón | Editar/limpiar y cargar FAQs reales |
| 5 | Contacto / global | Datos de contacto del seed | Teléfono `+51 925 886 725`, dirección "Jr. Lampa 209, Lima Center" y correos vienen del seeder | Confirmar/poner datos reales en Settings |
| 6 | Home | Secciones del mockup | El Home real omite cifras/galería/CTA final del mockup y tiene un bloque newsletter no previsto | Decidir: implementar las secciones o actualizar el mockup de referencia |
| 7 | Global (higiene) | Datos de prueba en BD | Reservas `LVT-*`, carrito/lead `cro.test@example.com` ("CRO O'Test"), ContactLead "Carlos Prueba" | Purgar antes de producción |
| 8 | Reseñas | Google/TripAdvisor | Las tarjetas muestran 5.0 fijo; no se confirmó si están conectadas a API real o son ejemplo | Definir fuente real o quitar |
| 9 | Repo (interno) | Contexto heredado | `deploy-*.sh` y `CLAUDE.md` aún referencian infra/contexto de Lima View | Reescribir cuando se defina hosting de Lima América |
| 10 | Home | ~~Ofertas sin consumidor~~ (RESUELTO 2026-07-25) | Hallazgo #2 de `panel-filament.md`: `OfferResource` no se veía en ningún lado del front. Evaluado: `HomeController::fetchOffers()` **ya** calculaba `$offers` (activas, orden manual, límite 3) y se lo pasaba a `home.blade.php` sin usarlo — cablear la sección era trivial (sin diseño nuevo, solo reusar el patrón visual de "Tours Destacados"). Se implementó una sección "Ofertas especiales" en `home.blade.php` con datos reales del modelo. | **No requiere decisión del jefe** — ya está wireado (`qa/panel-filament`). El contenido real de cada oferta (título/imagen/precio/vigencia) sigue siendo responsabilidad del editor vía admin → Marketing → Ofertas. |
