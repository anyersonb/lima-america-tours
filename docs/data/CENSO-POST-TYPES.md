# Censo completo de post_types — producción WP (dump local)

**Fuente:** `storage/app/wp-import/prod-dump/wp-db.sql` (dump del 2026-07-27), cargado a BD scratch `lima_wp_dump`. **NO** se consultó el VPS (acceso ya revocado). Query: `SELECT post_type, post_status, COUNT(*) FROM wp_posts GROUP BY post_type, post_status`.

## Tabla completa — qué existe, cuánto importamos, qué queda fuera

| post_type | estado | # | ¿Contenido? | ¿Importado? | Destino / decisión |
|---|---|---|---|---|---|
| **tours** | publish | 26 | ✅ | ✅ **Sí** | tabla `tours` (26) — hecho |
| **blog** | publish | 10 | ✅ | ✅ **Sí** | tabla `blog_posts` (10) — hecho |
| **reviews** | publish | 13 | ✅ | ⏳ **Pendiente** | reseñas globales — próxima pasada |
| **page** | publish/draft | 16 + 2 | ✅ | ⏳ **Pendiente/revisar** | 18 páginas WP; la app usa páginas propias (CMS). Revisar cuáles mapear (legales, nosotros, contacto) |
| **free_tours** | publish | 2 | ⚠️ dup | ❌ **NO** | **Slugs IDÉNTICOS a 2 tours ya importados** (ver §Free tours). NO importar como nuevos: sobrescribirían los pagos con precio 0 |
| **tours_45** | publish | 1 | ⚠️ dup | ❌ **NO** | 3ª copia del "casco histórico" (artefacto de rename de CPT JetEngine). Legacy, descartar |
| **product** | publish | 1 | ⚠️ | ❌ **Revisar** | 1 producto WooCommerce. La app no usa Woo. Confirmar si es real |
| **attachment** | inherit | 463 | media | ✅ parcial | imágenes; se copian bajo demanda (tours+blog ya). Todas en `uploads-extract/` |
| elementor_library | pub/draft | 31 + 3 | ❌ interno | — | plantillas Elementor — no aplica |
| jet-theme-core | publish | 9 | ❌ interno | — | JetEngine — no aplica |
| jet-engine | publish | 5 | ❌ interno | — | definiciones de CPT/meta — no aplica |
| jet-page-template | publish | 5 | ❌ interno | — | no aplica |
| jet-form-builder | pub/draft | 3 + 1 | ❌ interno | — | formularios — no aplica |
| jet-smart-filters | publish | 3 | ❌ interno | — | no aplica |
| jet-popup | publish | 1 | ❌ interno | — | no aplica |
| nav_menu_item | publish | 10 | ❌ interno | — | menús — no aplica |
| xpro-themer | publish | 4 | ❌ interno | — | addon de tema — no aplica |
| envato_tk_import | publish | 3 | ❌ interno | — | importador de demo del tema — no aplica |
| shop_order_placehold | draft | 3 | ❌ interno | — | placeholders Woo — no aplica |
| custom_css | publish | 1 | ❌ interno | — | no aplica |
| wp_global_styles | publish | 1 | ❌ interno | — | no aplica |
| wp_navigation | publish | 1 | ❌ interno | — | no aplica |
| revision | inherit | 1366 | ❌ interno | — | revisiones de posts — no aplica |

## Conclusión del censo
**No quedó ningún tipo de contenido real sin identificar.** Lo pendiente de contenido es: **reviews (13)** y **pages (18, a filtrar)**. `free_tours` y `tours_45` NO son contenido nuevo: son **duplicados** de tours ya cargados. `product` (1) es residuo de WooCommerce a confirmar.

## Free tours — el hallazgo (§ para decisión de Anyerson)
Los 2 `free_tours` tienen **la misma estructura meta que los tours** (`acerca-del-tour`, `itinerario`, `que-incluye`, `duracion`, `salidas`, `agendar`, `lugar`…) **pero sin `_apartment_price`** (precio 0). Y sus **slugs coinciden exactamente** con 2 tours pagos ya importados:

| free_tours (precio 0) | ¿existe en `tours`? |
|---|---|
| `recorrido-por-el-casco-historico-de-lima-degustaciones-de-pisco-sour` | ✅ sí (con precio) |
| `ruta-gastronomica-de-barrio-por-lima-street-food-prepara-anticucho-y-degusta-el-pisco-sour` | ✅ sí (con precio) |

Además `tours_45` (1) es una tercera copia del "casco histórico". Todo apunta a **rename/duplicación de CPT en JetEngine**, no a un producto aparte.

**Propuesta (decide Anyerson):**
- **Opción A (recomendada):** NO importar `free_tours` ni `tours_45`. Son duplicados; importarlos por slug **sobrescribiría** los tours pagos con precio 0. Se quedan solo los 26 tours pagos.
- **Opción B:** si el negocio SÍ ofrece esos 2 como *free walking tours* reales (aparte de la versión paga), crearlos como **tours con precio 0 y slug propio** (p.ej. `-free`) + su propia URL, no pisando los pagos. Requiere confirmar con la clienta que la oferta gratuita existe.
- **Opción C:** tratarlos como una **variante/flag** del mismo tour (badge "Free tour"). Más trabajo de modelo; solo si es un patrón comercial real.

Sin confirmación de la clienta sobre si el "free tour" es una oferta real y distinta, **no se toca el catálogo** (Opción A por defecto).
