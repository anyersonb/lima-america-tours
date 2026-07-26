# Importación de tours reales desde WordPress (limaamericatours.com)

Los 26 tours reales del sitio en producción se migraron desde su WordPress a la
tabla `tours` de esta app. Este documento explica de dónde salió la data, cómo se
mapeó y cómo re-ejecutar la importación.

## Origen

- **Servidor:** VPS Contabo (`86.48.23.174`), Ubuntu 22.04 + CyberPanel +
  OpenLiteSpeed + MariaDB.
- **Sitio:** `/home/limaamericatours.com/public_html` — WordPress con
  **Elementor + JetEngine** (los tours son un CPT `tours`) y **JetBooking**
  (reservas). Pasarelas ya instaladas allá: WooCommerce PayPal + micuentaweb (Izipay).
- **Extracción:** vía WP-CLI (`wp eval-file`) con `lsphp82`, leyendo la BD sin
  exponer credenciales. Salida: `storage/app/wp-import/tours.json` (26 tours,
  campos + meta + URLs de imágenes) + `storage/app/wp-import/images/` (269 imágenes).

> `storage/app/wp-import/` y `storage/app/public/tours/` están fuera de git
> (gitignored). El repo solo versiona el **código** del importador.

## Comando

```bash
php artisan tours:import-wp            # importa/actualiza (idempotente por slug)
php artisan tours:import-wp --dry-run  # muestra el mapeo sin escribir
php artisan tours:import-wp --keep-demo # no despublica los tours demo previos
```

- **Idempotente**: `updateOrCreate` por `slug`; re-ejecutar actualiza sin duplicar.
- Copia las imágenes referenciadas a `storage/app/public/tours/` como
  `tours/AAAA-MM-archivo.ext` y las enlaza en `cover_image` / `gallery` / `seo_image`.
- Despublica los tours **demo** previos (los que no vinieron del WP) para que el
  sitio muestre solo el catálogo real. Reversible desde el panel (`is_published`).

## Mapeo WordPress → tabla `tours`

| WordPress (JetEngine) | Columna `tours` | Notas |
|---|---|---|
| `post_title` | `title_es` | |
| `post_name` | `slug` | |
| `frase-inicial` | `subtitle_es` | texto plano, máx 250 |
| `acerca-del-tour` | `description_es` | HTML → texto con saltos de línea |
| `descripcion-corta-del-tour` | `seo_description` | máx 300 |
| `itinerario` | `itinerary_es` | parseado a `[{time,title,description}]` |
| `que-incluye` | `includes_es` **y** `excludes_es` | se separa por el rótulo "No incluye:" |
| `que-llevar` | `recommendations_es` | |
| `dias-de-salida` + `pasajeros-minimos` | `notes_es` | |
| `_apartment_price` | `price` | **moneda `PEN` (soles)** |
| `duracion` | `duration` | |
| `idiomas` | `language` | normalizado a "Español / Inglés" |
| `salidas` | `departure_time` | |
| `imagen-post` | `cover_image` | copiada a storage |
| `galeria` + `foto_1..5` | `gallery` | copiadas a storage |
| `imagen_header` | `seo_image` | |
| `lugar` + título | `region_id` | inferido: Lima / Ica / Cusco |
| título | `category_id` | inferido: Culturales / Aventura / Culinarias / Otros |

El parseo HTML vive en `App\Support\WpTourParser` (con tests en
`tests/Unit/WpTourParserTest.php`).

## Pendientes / decisiones para el jefe

- **Moneda:** los precios son **soles (PEN)**. Confirmar que el front/checkout
  muestre "S/" y no "$".
- **2 tours en borrador:** "Ruta Gastronómica de Barrio" y "Recorrido por el casco
  histórico" venían con **precio 0** en WP → quedaron `is_published=false` para no
  mostrar "S/0". Cargar su precio y publicarlos desde el panel.
- **Traducción:** todo el contenido está en **español**. Falta EN/PT
  (`title_en`, `itinerary_en`, etc. quedaron vacíos).
- **Inferencia región/categoría:** es heurística; revisar y ajustar casos
  puntuales desde el panel si hiciera falta.
