# Reconciliación con producción — Blog (WordPress → app)

**Fecha:** 2026-07-26 · **Origen:** export local `storage/app/wp-import/blog.json` (10 posts reales de `limaamericatours.com`) + `storage/app/wp-import/prod-dump/uploads-extract/uploads/` · **Destino:** tabla `blog_posts`.
**Acceso:** sin VPS — solo archivos ya preparados localmente.

---

## A) Censo

**10 posts, todos `status = publish`.** Ninguno oculto en draft/pending.

### Meta keys usadas (patrón JetEngine, igual que tours)

| meta_key | # posts | ¿Importado? |
|---|---|---|
| `titulo-general` | 10 | ✅ `title_es` |
| `titulo-1` | 10 (2 vacíos) | ✅ `excerpt_es` (si vacío, fallback a `texto-1` truncado) |
| `texto-1` | 10 | ✅ `body_es` (HTML preservado, no se convierte a texto plano) |
| `titulo-2` / `texto-2` | 10 (siempre vacíos en este export) | ✅ soportado por `WpBlogMapper::buildBody()` (se concatena como `<h2>` + cuerpo si trajera contenido) — no se activó en ningún post real |
| `images.foto-1.file` | 10 | ✅ `cover_image` (copiada a `storage/app/public/blog/`) |
| `images.foto-2.file` | 1 (`visitemos-el-museo-larco-en-lima`) | ➖ no usado (el destino solo tiene una portada; ver nota) |
| `_aioseo_keywords` / `_aioseo_og_article_tags` | 5 (todos `a:0:{}`, array serializado vacío) | ✅ mapeado, pero da `null` en los 10 — no había tags reales que perder |
| `_aioseo_title` / `_aioseo_description` | 0 (no existen en ningún post) | ✅ mapeado (`meta_title_es`/`meta_description_es`), siempre `null` |
| `author.display_name` | 10 (todos `limatours.adm`) | ✅ `author_name` → se reemplaza por **"Lima América Tours"** (cuenta de servicio, no un autor real) |

**Nota `foto-2`:** solo 1 de los 10 posts trae una segunda imagen (`museo-larco`); el esquema de `blog_posts` no tiene un campo de segunda imagen/galería, así que no se pierde nada mostrable — la portada (`foto-1`) es la única que pinta la vista `blog/show.blade.php`.

---

## B) Fidelidad — ¿se truncó contenido?

Longitud en caracteres de `texto-1` + `texto-2` (origen, con HTML) vs `body_es` importado:

| Post (slug) | orig (texto-1+texto-2) | body_es importado | Diferencia |
|---|---|---|---|
| como-se-prepara-el-ceviche-peruano | 3233 | 3232 | -1 |
| huacachina-…-con-lima-america-tours | 4319 | 4318 | -1 |
| free-tours-…-con-lima-america-tours | 5042 | 5041 | -1 |
| que-hacer-en-barranco-y-miraflores | 6864 | 6863 | -1 |
| mejores-restaurantes-…-para-viajeros | 5967 | 5966 | -1 |
| que-hacer-en-barranco-lima | 1990 | 1989 | -1 |
| los-mejores-restaurantes-en-lima | 1429 | 1428 | -1 |
| que-transporte-tomar-en-tu-visita-a-lima | 1489 | 1488 | -1 |
| visitemos-el-museo-larco-en-lima | 1801 | 1800 | -1 |
| desayuno-bueno-bonito-y-barato | 1234 | 1233 | -1 |

**Diferencia constante de -1 carácter en los 10 posts = el `trim()` de un solo espacio/salto de línea al final del HTML de origen. Cero pérdida de contenido real** (ningún post cortado a la mitad ni con bloques faltantes). `titulo-2`/`texto-2` nunca aportó contenido adicional en este export (siempre vacío), así que la lógica de concatenación con `<h2>` quedó cubierta solo por tests unitarios (`WpBlogMapperTest::test_build_body_appends_texto_2_*`), no por datos reales.

---

## C) Imágenes

Las **10 portadas** (`foto-1`) existen en el extract local, se copiaron a `storage/app/public/blog/` y **responden HTTP 200** vía `/storage/blog/...` (spot-check con `curl` sobre las 10, sin 404):

```
blog/2025-12-ceviche.webp
blog/2025-05-WhatsApp-Image-2025-05-06-at-10.51.51-AM.jpeg
blog/2025-10-caption-1.jpg
blog/2024-02-IMG_3390.JPG.jpg
blog/2025-12-maido.jpg
blog/2024-12-5-e1745442592378.jpg
blog/2024-12-facebook3-e1745442628924.jpg
blog/2024-12-3-e1745442672212.jpg
blog/2024-02-Museo-Larco-13.webp
blog/2024-04-imagen_2024-04-03_123627735-e1745442786212.png
```

---

## D) Hallazgo para Anyerson — posts demo duplicados en la BD local

La BD local de desarrollo ya tenía **4 posts demo** cargados por `database/seeders/BlogPostSeeder.php` (sesión anterior, 2026-07-25). De esos 4, **2 coinciden en slug** con posts reales del WP (`como-se-prepara-el-ceviche-peruano`, `que-hacer-en-barranco-y-miraflores`) y el importador los **actualizó correctamente** con el contenido real (sin duplicar). Los otros **2 demo tienen un slug distinto** al del post real equivalente:

| Slug demo (seeder) | Slug real (WP, se creó aparte) |
|---|---|
| `huacachina-el-oasis-imperdible-de-ica` | `huacachina-el-oasis-imperdible-de-ica-aventura-y-encanto-con-lima-america-tours` |
| `free-tours-en-lima-la-mejor-forma-de-conocer-la-ciudad` | `free-tours-en-lima-la-mejor-forma-de-conocer-la-ciudad-con-lima-america-tours` |

Como el importador es idempotente **por slug**, no tocó esos 2 demo (slugs distintos) y creó el post real al lado → quedan **2 pares de posts duplicados en tema** (12 filas en vez de 10) en la BD de desarrollo local. **No se borró nada** porque no estaba en el alcance de esta tarea (a diferencia de `tours:import-wp`, que sí tiene un flag `--keep-demo` explícito para eso). **Acción sugerida:** decidir si `BlogPostSeeder` se retira antes de ir a producción, o si esos 2 demo se despublican/borran a mano desde el panel.
