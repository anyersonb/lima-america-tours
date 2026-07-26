# Inventario — Panel Filament (F0)

Fecha: 2026-07-25 · Rama: `qa/panel-filament` · Generado por lectura directa de
`app/Filament/Resources/*.php` y `app/Filament/Pages/*.php` (sin navegador).

Checklist para F2 (`cro-validator`): cada campo listado debe quedar marcado como
probado explícitamente, tanto en Create como en Edit, y su sincronía con el front
donde aplique.

---

## Resumen

| # | Resource / Page | Ruta admin | Modelo | Grupo nav. | Campos (create/edit) | Relaciones |
|---|---|---|---|---|---|---|
| 1 | TourResource | `/admin/tours` | `Tour` | Catálogo | ~72 (6 tabs, 3 idiomas + comparativa) | `region` (belongsTo), `category` (belongsTo), `testimonials`/`bookings`/`offers` (hasMany) |
| 2 | RegionResource | `/admin/regions` | `Region` | Catálogo | 16 | `tours` (hasMany) |
| 3 | CategoryResource | `/admin/categories` | `Category` | Catálogo | 10 | `tours` (hasMany) |
| 4 | TestimonialResource | `/admin/testimonials` | `Testimonial` | Contenido | 12 | `tour` (belongsTo, nullable) |
| 5 | OfferResource | `/admin/offers` | `Offer` | Marketing | 16 | `tour` (belongsTo, nullable) |
| 6 | PageResource | `/admin/pages` | `Page` | Contenido | 11 fijos + bloques dinámicos (contacto ~23, nosotros ~90+) | ninguna |
| 7 | ContactLeadResource | `/admin/contact-leads` | `ContactLead` | Marketing | 10 | ninguna |
| 8 | BookingResource | `/admin/bookings` | `Booking` | Marketing | ~25 (2 virtuales no persistidos) | `tour` (belongsTo, nullable — soporta tour personalizado) |
| 9 | AbandonedCartResource | `/admin/abandoned-carts` | `AbandonedCart` | Marketing | 0 (solo lectura — `canCreate()` false, sin form) | ninguna declarada |
| 10 | NewsletterSubscriberResource | `/admin/newsletter-subscribers` | `NewsletterSubscriber` | Marketing | 6 | ninguna |
| 11 | BlogPostResource | `/admin/blog-posts` | `BlogPost` | Contenido | 22 | ninguna |
| 12 | MediaAssetResource | `/admin/media-assets` | `MediaAsset` | Contenido | 3 (difiere create/edit) | ninguna |
| 13 | BlockedDateResource | `/admin/blocked-dates` | `BlockedDate` | Reservas | 5 (2 mutuamente excluyentes por `type`) | `tour` (belongsTo, nullable = "todos los tours") |
| 14 | Settings (Page) | `/admin/settings` | `Setting` (key/value) | Sistema | Singleton, ~140+ campos en 12 tabs | n/a |
| 15 | Maintenance (Page) | `/admin/maintenance` | n/a (acciones Artisan) | Sistema | 0 (5 acciones, sin formulario) | n/a |

Total Resources: **13** · Pages: **2** (Settings + Maintenance).

---

## 1. TourResource — `/admin/tours`

**Modelo**: `App\Models\Tour` (SoftDeletes — `getEloquentQuery()` quita el scope global, tabla soporta `TrashedFilter`).
**Relaciones**: `region()` belongsTo Region, `category()` belongsTo Category, `testimonials()` hasMany, `bookings()` hasMany, `offers()` hasMany.

### Formulario (Tabs)

**Tab General**
| Campo | Tipo | Requerido | Notas |
|---|---|---|---|
| `region_id` | Select (relationship) | No | |
| `category_id` | Select (relationship) | No | |
| `slug` | TextInput | No (pero DB-level probablemente sí) | Deshabilitado en edit, solo se guarda en create |
| `duration` | TextInput | No | |
| `language` | TextInput | No | default "Español / Inglés" |
| `group_type` | TextInput | No | default "Grupal" |
| `departure_time` | TextInput | No | |
| `return_time` | TextInput | No | |
| `max_capacity` | TextInput numeric | No | |
| `price` | TextInput numeric | **Sí** | |
| `price_before` | TextInput numeric | No | activa oferta si > price |
| `currency` | TextInput | **Sí** | default USD |
| `discount_preview` | Placeholder (computado) | n/a | solo lectura, vista previa |
| `badge_text` | TextInput | No | |
| `badge_type` | Select | No | warn/error/success |
| `rating` | TextInput numeric | No | default 4.8 |
| `reviews_count` | TextInput numeric | No | default 0 |
| `order` | TextInput numeric | No | default 0 |
| `featured_order` | TextInput numeric | No | posición en "Más Comprados" |
| `is_published` | Toggle | No | default true |
| `is_featured` | Toggle | No | |
| `show_best_seller` | Toggle | No | default true |
| `show_offer_badge` | Toggle | No | default true |

**Tabs Español / English / Português** (idéntica estructura ×3; solo ES tiene requeridos):
| Campo (sufijo `_es/_en/_pt`) | Tipo | Requerido (solo ES) |
|---|---|---|
| `title` | TextInput | Sí (ES) |
| `subtitle` | TextInput | No |
| `description` | Textarea | No |
| `itinerary` | Repeater (`time`, `title`, `description`) | No |
| `includes` | TagsInput | No |
| `excludes` | TagsInput | No |
| `recommendations` | Textarea | No |
| `notes` | Textarea | No |
| `faqs` | Repeater (`question` req, `answer` req) | No (repeater vacío por defecto) |

**Tab Comparativa**
| Campo | Tipo | Requerido |
|---|---|---|
| `comparison.enabled` | Toggle | No |
| `comparison.color` | Select (teal/orange) | No |
| Por idioma (`_es/_en/_pt`, 9 campos c/u): `badge`, `title`, `title_hl`, `intro` (textarea), `conv_title`, `prem_title`, `conv` (tagsinput), `prem` (tagsinput), `footer` (textarea) | Mixto | No |

**Tab Imágenes**: `cover_image` (FileUpload, WebP 1600px), `gallery` (FileUpload múltiple, reorderable, WebP 1920px).

**Tab SEO**: `seo_title`, `seo_description` (maxLength 160), `seo_keywords` (TagsInput), `seo_image` (FileUpload, WebP 1200px).

### Columnas del listado
`cover_image` (imagen), `title_es`, `region.name_es`, `category.name_es`, `price`, `has_offer` (calculado), `rating`, `is_featured`, `is_published`, `order`, `featured_order` (editable inline). Filtros: región, categoría, publicado, destacado, `TrashedFilter`. Reordenable por `order` (drag&drop).

---

## 2. RegionResource — `/admin/regions`

**Modelo**: `Region`. **Relación**: `tours()` hasMany.

| Campo | Tipo | Requerido |
|---|---|---|
| `slug` | TextInput | Sí |
| `name_es` | TextInput | Sí |
| `name_en` | TextInput | No |
| `name_pt` | TextInput | No |
| `description_es/en/pt` | Textarea | No |
| `hero_image` | FileUpload | No |
| `eyebrow_es/en/pt` | TextInput | No |
| `is_active` | Toggle | Sí |
| `order` | TextInput numeric | Sí (default 0) |
| `seo_title` | TextInput | No |
| `seo_description` | TextInput (maxLength 320) | No |
| `seo_image` | FileUpload | No |

Columnas: slug, name_es, name_en, hero_image, eyebrow_es, eyebrow_en, is_active, order, seo_title, seo_description, seo_image, created_at, updated_at.

---

## 3. CategoryResource — `/admin/categories`

**Modelo**: `Category`. **Relación**: `tours()` hasMany.

| Campo | Tipo | Requerido |
|---|---|---|
| `slug` | TextInput | Sí |
| `name_es` | TextInput | Sí |
| `name_en/pt` | TextInput | No |
| `description_es/en/pt` | Textarea | No |
| `icon` | TextInput | No |
| `is_active` | Toggle | Sí |
| `order` | TextInput numeric | Sí (default 0) |

Columnas: slug, name_es, name_en, icon, is_active, order, created_at, updated_at.

---

## 4. TestimonialResource — `/admin/testimonials`

**Modelo**: `Testimonial`. **Relación**: `tour()` belongsTo (nullable).

| Campo | Tipo | Requerido |
|---|---|---|
| `name` | TextInput | Sí |
| `country` | TextInput | No |
| `avatar` | TextInput | No |
| `quote_es` | Textarea | Sí |
| `quote_en/pt` | Textarea | No |
| `rating` | TextInput numeric | Sí (default 5.0) |
| `source` | TextInput | Sí (default "Google") |
| `is_featured` | Toggle | Sí |
| `is_active` | Toggle | Sí |
| `order` | TextInput numeric | Sí (default 0) |
| `tour_id` | Select (relationship, buscable) | No |

Columnas: name, quote_es (con tooltip), tour.title_es, rating, source, is_featured, is_active (toggle inline), created_at. Filtros: aprobado/pendiente (`is_active`), origen (`source`: Web/Google/Tripadvisor).

---

## 5. OfferResource — `/admin/offers`

**Modelo**: `Offer`. **Relación**: `tour()` belongsTo (nullable).

| Campo | Tipo | Requerido |
|---|---|---|
| `title_es` | TextInput | Sí |
| `title_en/pt` | TextInput | No |
| `description_es/en/pt` | Textarea | No |
| `image` | FileUpload | No |
| `price` | TextInput numeric | No |
| `cta_label_es` | TextInput | Sí (default "Leer más") |
| `cta_label_en/pt` | TextInput | No |
| `cta_url` | TextInput | No |
| `tour_id` | Select (relationship, searchable+preload) | No |
| `is_active` | Toggle | Sí |
| `order` | TextInput numeric | Sí (default 0) |
| `valid_until` | DateTimePicker | No |

Columnas: title_es, title_en, image, price, cta_label_es, cta_label_en, cta_url, tour.title_es, is_active, order, valid_until, created_at, updated_at.

---

## 6. PageResource — `/admin/pages`

**Modelo**: `Page` (contenido "flexible" de páginas estáticas + bloques JSON en `blocks`).
**Relaciones**: ninguna.

**Tab General**: `slug` (req, único), `is_published` (toggle, default true), `show_in_sitemap` (toggle, default true), `sitemap_priority` (default 0.5), `sitemap_changefreq` (default "monthly").

**Tab Títulos y contenido**: `title_es` (req), `content_es`, `title_en`, `content_en`, `title_pt`, `content_pt`.

**Tab SEO**: `seo_title`, `seo_description`, `hero_image` (FileUpload), `seo_image` (FileUpload).

**Tab "Contenido de la página"** — solo visible si `slug` es `contacto` o `nosotros`:

- **Si slug=contacto** (imágenes + textos hero, ~14 campos): `blocks.img_hero`, `blocks.img_collage_1..4` (5 FileUpload); `blocks.hero_eyebrow_es/en/pt`, `blocks.hero_title_es/en/pt`, `blocks.hero_lead_es/en/pt` (9 TextInput/Textarea).
- **Si slug=nosotros** (imágenes + textos + repeaters, ~90+ campos):
  - Imágenes: `blocks.img_hero`, `blocks.img_grid1..4`, `blocks.img_banner_cta`, `blocks.img_testimonios` (7 FileUpload).
  - Hero: `blocks.hero_eyebrow_es/en/pt`, `blocks.hero_title_es/en/pt`, `blocks.hero_lead_es/en/pt` (9).
  - `blocks.why_intro_es/en/pt` (3).
  - Grupo "Nosotros — contenido" (colapsable): `blocks.hero_cta_label_es/en/pt` (3); `blocks.why_intro2_es/en/pt` + `blocks.why_cta_label_es/en/pt` (6); `blocks.banner_heading_es/en/pt` + `blocks.banner_text_es/en/pt` (6); `blocks.cultura_heading_es/en/pt` + `blocks.cultura_intro_es/en/pt` (6); `blocks.stats` Repeater (`title_es` req, `title_en`, `title_pt`, `desc_es` req, `desc_en`, `desc_pt`); `blocks.pillars` Repeater (`key` req, `label_es` req/`label_en`/`label_pt`, `heading_es` req/`heading_en`/`heading_pt`, `body_es` req/`body_en`/`body_pt`); `blocks.testimonios_eyebrow_es/en/pt` + `blocks.testimonios_heading_es/en/pt` (6).

Columnas: slug, title_es, title_en, hero_image, seo_title, is_published, show_in_sitemap, created_at, updated_at.

---

## 7. ContactLeadResource — `/admin/contact-leads`

**Modelo**: `ContactLead`. Sin relaciones.

| Campo | Tipo | Requerido |
|---|---|---|
| `name` | TextInput | Sí |
| `lastname` | TextInput | No |
| `email` | TextInput email | Sí |
| `phone` | TextInput tel | No |
| `message` | Textarea | Sí |
| `source` | TextInput | Sí (default "contact_form") |
| `locale` | TextInput | Sí (default "es") |
| `ip` | TextInput | No |
| `user_agent` | TextInput | No |
| `is_read` | Toggle | Sí |
| `is_archived` | Toggle | Sí |

Columnas: name, lastname, email, phone, source, locale, ip, user_agent, is_read, is_archived, created_at, updated_at. Badge de navegación = mensajes no leídos.

---

## 8. BookingResource — `/admin/bookings`

**Modelo**: `Booking`. **Relación**: `tour()` belongsTo (nullable — soporta "tour personalizado").

| Campo | Tipo | Requerido | Notas |
|---|---|---|---|
| `use_custom_tour` | Toggle | No | **Virtual, no se persiste** (`dehydrated(false)`) |
| `tour_id` | Select (relationship) | Sí si NO custom | Autocompleta nombre/precio |
| `tour_title_snapshot` | TextInput | Sí | Auto (catálogo) o editable (personalizado) |
| `custom_tour_details` | Textarea | Sí si custom | |
| `customer_name` | TextInput | Sí | |
| `customer_email` | TextInput email | Sí | |
| `customer_phone` | TextInput tel | No | |
| `travel_date` | DatePicker | Sí | |
| `adults` | TextInput numeric | Sí | default 1, min 1 |
| `children` | TextInput numeric | Sí | default 0 |
| `unit_price` | TextInput numeric | Sí | readonly si NO custom |
| `discount_type` | Select (percent/fixed) | No | |
| `discount_value` | TextInput numeric | No | visible si hay discount_type |
| `discount_amount` | Hidden | n/a | calculado, default 0 |
| `total_price` | TextInput numeric | Sí | readonly, calculado |
| `currency` | TextInput | Sí | default USD |
| `status` | Select (pending/confirmed/cancelled/completed) | Sí | default pending |
| `payment_status` | Select (pending/paid/refunded) | Sí | default pending; al marcar "paid" auto-confirma `status` |
| `payment_method` | Select (pay_later/paypal/card/payment_link/cash/transfer) | No | default pay_later |
| `payment_link_url` | TextInput url | No | visible si method=payment_link |
| `payment_reference` | TextInput | n/a | disabled, no dehydrata, solo visible en edit |
| `pickup_point` | TextInput | No | condicional a que exista la columna en BD |
| `pickup_detail` | TextInput | No | condicional a que exista la columna en BD |
| `notes` | Textarea | No | |
| `locale` | Select (es/en/pt) | Sí | default es |
| `send_emails` | Toggle | No | **Virtual, no se persiste**; solo visible en create; default true |

Columnas: reference, tour_title_snapshot, customer_name, customer_email, customer_phone, travel_date, adults, children, unit_price, discount_amount, total_price, status, payment_status, payment_method, payment_reference, payment_link_url, pickup_point (condicional), locale, created_at, updated_at. Acción custom: "Reenviar correo" (modal con email editable). Badge de navegación = reservas `pending`.

---

## 9. AbandonedCartResource — `/admin/abandoned-carts`

**Modelo**: `AbandonedCart`. Sin relaciones declaradas. **Solo lectura**: `canCreate()` retorna `false`, `form()` vacío, solo página `index`.

Columnas: email (con descripción=nombre), items_count, total, status (active/converted/expired, badge coloreado), reminders_sent, locale (oculto por defecto), last_activity_at, last_reminder_at. Filtro: status. Acción custom: "Reenviar recordatorio" (envía `AbandonedCartReminder` mail, visible solo si `isRecoverable()`). Navegación condicionada a `Schema::hasTable('abandoned_carts')` (defensivo si falta la migración).

---

## 10. NewsletterSubscriberResource — `/admin/newsletter-subscribers`

**Modelo**: `NewsletterSubscriber`. Sin relaciones.

| Campo | Tipo | Requerido |
|---|---|---|
| `name` | TextInput | No |
| `email` | TextInput email | Sí |
| `locale` | TextInput | Sí (default es) |
| `is_active` | Toggle | Sí |
| `subscribed_at` | DateTimePicker | No |
| `unsubscribed_at` | DateTimePicker | No |

Columnas: name, email, locale, is_active, subscribed_at, unsubscribed_at, created_at, updated_at.

---

## 11. BlogPostResource — `/admin/blog-posts`

**Modelo**: `BlogPost`. Sin relaciones.

| Campo | Tipo | Requerido |
|---|---|---|
| `title_es` | TextInput | Sí |
| `excerpt_es` | Textarea | Sí |
| `body_es` | RichEditor | Sí |
| `title_en` | TextInput | No |
| `excerpt_en` | Textarea | No |
| `body_en` | RichEditor | No |
| `title_pt` | TextInput | No |
| `excerpt_pt` | Textarea | No |
| `body_pt` | RichEditor | No |
| `cover_image` | FileUpload (WebP 1600px) | No |
| `slug` | TextInput | No (auto de title_es si vacío) |
| `category` | TextInput | No |
| `tags` | TagsInput | No |
| `author_name` | TextInput | No |
| `meta_title_es/en/pt` | TextInput (maxLength 70) | No |
| `meta_description_es/en/pt` | Textarea (maxLength 160) | No |
| `is_published` | Toggle | No (default false) |
| `published_at` | DateTimePicker | No |

Columnas: title_es, category (badge), is_published (toggle inline), published_at, reading_minutes. Filtro: publicado (ternary).

---

## 12. MediaAssetResource — `/admin/media-assets`

**Modelo**: `MediaAsset`. Sin relaciones.

| Campo | Tipo | Requerido | Notas |
|---|---|---|---|
| `files` | FileUpload múltiple | No | visible solo en `create`; tipos aceptados explícitos (jpeg/png/webp/gif/pdf/mp4), máx 10MB, sin SVG (XSS) |
| `name` | TextInput | No | visible solo en `edit` |
| `collection` | TextInput | No | |

Columnas: thumbnail (imagen si aplica), name, collection (badge), mime (badge), size (formateado), url (copiable), created_at. Filtro: colección.

---

## 13. BlockedDateResource — `/admin/blocked-dates`

**Modelo**: `BlockedDate`. **Relación**: `tour()` belongsTo (nullable = aplica a todos los tours).

| Campo | Tipo | Requerido | Notas |
|---|---|---|---|
| `type` | Radio (date/weekday) | Sí | **Virtual**, se elimina antes de guardar (`unset` en `normalizeData`) |
| `date` | DatePicker | Sí si type=date | Se anula si type=weekday |
| `weekday` | Select (0-6) | Sí si type=weekday | Se anula si type=date |
| `tour_id` | Select (Tours publicados) | No | vacío = todos los tours |
| `reason` | TextInput | No | |

Columnas: type_display (calculado: fecha o "Todos los [día]s"), tour.title_es (default "Todos los tours"), reason (default "—"), created_at.

---

## 14. Settings (Page singleton) — `/admin/settings`

**Modelo**: `Setting` (tabla key/value, no es CRUD de registros — un solo formulario que lee/escribe todas las keys). 12 tabs, ~140+ campos. No se lista campo por campo aquí (ver código para el detalle exacto); resumen por tab:

| Tab | Contenido |
|---|---|
| General | nombre del sitio, tagline ES/EN, descripción ES/EN |
| Contacto | email, teléfonos, WhatsApp, dirección, horarios, emails de aviso de reserva (con validación de lista de correos), descripción footer ES/EN/PT |
| Redes sociales | Instagram, Facebook, TikTok, YouTube, enlaces Google Reviews / Tripadvisor |
| Pagos | modo PayPal (sandbox/live), client id, secret (password), webhook id |
| SEO | title/description/keywords por defecto, OG image, Search Console, Bing, GA4, GTM, FB Pixel |
| Home | **el tab más grande**: imágenes del home (hero, destinos, tipos de tour, experiencias — todas FileUpload de nivel superior), textos hero, stats (4 indicadores), sección "Más Comprados", sección "Tours en Lima/Ica/Cusco", página "Gracias" (imagen+badge+título+cuerpo+cta ×3 idiomas), sección "Más Visitados" + Repeater `home_destinos`, sección "¿Por qué elegirnos?" + Repeater `home_why_items`, sección "¿Qué tipo de tour?" + Repeater `home_tour_type_tabs`, Repeater `home_footer_features`, sección "Experiencias únicas" + Repeater `home_exp_tours`, sección "Opiniones", sección FAQs home + Repeater `home_faqs`, sección "Verificado y Recomendado" + Repeater `home_reco_items` |
| GEO | nombre negocio, dirección, ciudad, región, cód. postal, país, lat/lng, region_code, price_range (schema.org LocalBusiness) |
| AEO / FAQ | Repeater `faqs` (question/answer ×3 idiomas) — schema FAQ estructurado, **distinto** de `home_faqs` |
| APIs | Google Maps API Key (password), Google Place ID, toggle reseñas Google, Tripadvisor API Key (password), Location ID, toggle reseñas Tripadvisor, ratings/counts manuales |
| Recogida | toggle `pickup_enabled`, Repeater `pickup_zones` (label, lat, lng, radius_km, type) con autocompletado Google Maps vía Alpine (`x-init`) |
| reCAPTCHA | toggle enabled, versión (v2/v3), site key, secret key (password), umbral v3 |
| Cookies | toggle banner, textos ES/EN/PT |

Notas técnicas relevantes para F2: los Repeaters (`faqs`, `home_destinos`, `home_why_items`, `home_tour_type_tabs`, `home_footer_features`, `home_exp_tours`, `home_reco_items`, `home_faqs`, `pickup_zones`) se serializan a JSON string manualmente en `save()`. Los toggles de `BOOLEAN_KEYS` (`google_reviews_enabled`, `tripadvisor_reviews_enabled`, `recaptcha_enabled`, `cookie_banner_enabled`, `pickup_enabled`) se guardan con tipo `boolean`. Las imágenes del home son FileUpload de **nivel superior** (no dentro de Repeater) a propósito, porque los FileUpload anidados en Repeater no deshidrataban bien (comentario en el código).

---

## 15. Maintenance (Page) — `/admin/maintenance`

**Modelo**: n/a. Sin formulario — 5 acciones de cabecera que ejecutan Artisan:

| Acción | Comando(s) | Confirmación |
|---|---|---|
| Limpiar caché | `cache:clear`, `config:clear`, `route:clear`, `view:clear` | Sí |
| Optimizar (producción) | `config:cache`, `view:cache` | Sí |
| Recompilar assets | `filament:assets` | Sí |
| Ejecutar migraciones | `migrate --force` | Sí |
| Ver sitemap | abre `/sitemap.xml` en nueva pestaña | No |

---

## No cubierto en F0 (por diseño de esta fase)

- No se probó el guardado real de ningún campo (eso es L2, de `cro-validator`, con navegador).
- No se abrió el navegador en ningún momento (F0/F1 son sin navegador, según la delegación).
- El modelo `Customer` tiene relación `bookings()` pero **no existe** un `CustomerResource` en el panel — no es parte de este inventario porque no hay Resource que lo exponga.
