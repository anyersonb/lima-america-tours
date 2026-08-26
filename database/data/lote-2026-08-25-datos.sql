-- ══════════════════════════════════════════════════════════════════════════
-- Lote 2026-08-25 — la parte que es DATO, no código.
--
-- Los cinco comentarios del jefe de ese día se resolvieron mitad en código y
-- mitad cargando datos que ya tenían su campo esperando en Configuración. Este
-- archivo existe porque un `git push` no lleva filas: sin correrlo en el
-- servidor, el sello y el RUC no aparecen y los tours de Cusco siguen al final.
--
-- Cómo correrlo:
--   mysql -u USUARIO -p BASE < database/data/lote-2026-08-25-datos.sql
--   php artisan cache:clear      # Setting::get() cachea 'settings.all' para siempre
--
-- Los DOS archivos de imagen van aparte, subidos a public/media/legal/ (o
-- cargados desde Configuración → Contacto → Datos legales, que es lo mismo):
--   · legal/rnavt-seal-150x250.webp  — sello "Agencia de viajes y turismo registrada"
--   · legal/esnna-afiche.webp        — afiche "Protégeme" de MINCETUR
-- Sin el archivo en disco, la fila de abajo no rompe nada: el footer y /esnna
-- tienen guard y simplemente no pintan la pieza.
-- ══════════════════════════════════════════════════════════════════════════

-- ── 1. RUC ────────────────────────────────────────────────────────────────
-- Confirmado por el jefe el 2026-08-25. Hasta hoy el campo estaba vacío A
-- PROPÓSITO: en este repo llegaron a convivir dos RUC contradictorios.
INSERT INTO settings (`key`, `value`, `type`, `group`, created_at, updated_at)
VALUES ('company_ruc', '20616108264', 'string', 'contact', NOW(), NOW())
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW();

-- ── 2. Sello de agencia registrada ────────────────────────────────────────
INSERT INTO settings (`key`, `value`, `type`, `group`, created_at, updated_at)
VALUES ('company_registry_seal', 'legal/rnavt-seal-150x250.webp', 'string', 'contact', NOW(), NOW())
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW();

-- ── 3. Afiche ESNNA de MINCETUR ───────────────────────────────────────────
INSERT INTO settings (`key`, `value`, `type`, `group`, created_at, updated_at)
VALUES ('esnna_poster', 'legal/esnna-afiche.webp', 'string', 'contact', NOW(), NOW())
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW();

-- ── 4. Cusco en los primeros lugares de la home ───────────────────────────
-- "Aquí podrían aparecer en primeros lugares los tours de Cusco creo yo".
-- El orden de "Tours Destacados" es MANUAL (featured_order, 1 = primero) y se
-- edita en Admin → Tours, tour por tour. Se INTERCALA Cusco con los de
-- Lima/Ica que ya estaban destacados: subir Cusco no es apagar Lima, que es el
-- destino que da nombre a la agencia y del que vienen casi todas las reseñas.
--
-- OJO: los ids son los de la base que se importó desde el WordPress con
-- `tours:import-wp`. Antes de correr esto en otro servidor, verificar:
--   SELECT id, title_es FROM tours WHERE id IN (12,14,15,16,21,23,24,25);
UPDATE tours SET is_featured = 1 WHERE id IN (21, 23, 24, 25);
UPDATE tours SET featured_order = 1 WHERE id = 21;  -- Machu Picchu (Cusco)
UPDATE tours SET featured_order = 2 WHERE id = 16;  -- City Tour Centro Histórico (Lima)
UPDATE tours SET featured_order = 3 WHERE id = 23;  -- Montaña Arcoíris 7 Colores (Cusco)
UPDATE tours SET featured_order = 4 WHERE id = 14;  -- Full Day Paracas-Ica-Huacachina (Ica)
UPDATE tours SET featured_order = 5 WHERE id = 24;  -- Valle Sagrado (Cusco)
UPDATE tours SET featured_order = 6 WHERE id = 12;  -- Huaca Pucllana (Lima)
UPDATE tours SET featured_order = 7 WHERE id = 25;  -- Laguna Humantay (Cusco)
UPDATE tours SET featured_order = 8 WHERE id = 15;  -- Parque de las Aguas (Lima)
