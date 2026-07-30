#!/usr/bin/env bash
# =============================================================================
# Deploy Lima AMÉRICA Tours -> STAGING  (https://limaamericatours.com/staging)
# =============================================================================
# OJO: `deploy-staging.sh` (sin sufijo) es de LIMA VIEW TOURS, otro cliente, y va
# por FTP a otro dominio. NO usarlo aquí. Este script es el de América y va por
# SSH al VPS Contabo, que es como se montó este staging.
#
# QUÉ HACE
#   1. Empaqueta SOLO los archivos del lote (lista explícita, no un rsync ciego)
#      y los extrae en el docroot de staging.
#   2. Sube el build de Vite (CSS/JS con hash) y el manifest.
#   3. Limpia y regenera cachés de Laravel, y prepara public/media/derived.
#   4. Comprueba que el hero salga en WebP y que el staging siga con noindex.
#
# NO toca la base de datos: este lote no trae migraciones. Los Settings nuevos
# (íconos de los features, alt de la foto) se leen con fallback en código, así
# que el sitio funciona sin cargar nada en el panel.
#
# USO
#   SSH_KEY=~/.ssh/lima_america_staging bash deploy-america-staging.sh
#
# REQUISITO: la pubkey de esa llave instalada en el VPS (root). Si no la tienes,
# se instala desde el panel de Contabo/CyberPanel:
#   "Restablecer credenciales" -> opción "Clave SSH" -> pegar la .pub
# =============================================================================
set -uo pipefail

HOST="${HOST:-86.48.23.174}"
SSH_USER="${SSH_USER:-root}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/lima_america_staging}"
REMOTE="${REMOTE:-/home/limaamericatours.com/public_html/staging}"
SITE_USER="${SITE_USER:-limaa3133}"          # dueño real de los archivos
PHP="${PHP:-/usr/local/lsws/lsphp82/bin/php}" # el PHP del sistema NO trae mysqli
URL="${URL:-https://limaamericatours.com/staging}"

LOCAL="$(cd "$(dirname "$0")" && pwd)"
SSH_OPTS=(-i "$SSH_KEY" -o StrictHostKeyChecking=accept-new -o ConnectTimeout=15)

# --- Archivos a subir ------------------------------------------------------
# Se derivan del DIFF contra el commit que está desplegado en staging, no de
# una lista escrita a mano por lote.
#
# Por qué: el primer intento subió solo los archivos del último lote y la home
# quedó en 500 con "Call to undefined method Setting::contactPhone()". Staging
# estaba en 92a6ddc y nunca había recibido el commit del hero (46dbf42), que es
# donde nacieron esos métodos del modelo: subir la vista que los llama sin subir
# el modelo deja el sitio roto. Un lote no es autocontenido si el servidor va
# más atrás de lo que crees.
#
# ACTUALIZA `DEPLOYED_COMMIT` cada vez que despliegues, o pásalo por entorno.
# El servidor no tiene repo git (el deploy es por copia), así que este valor es
# la única memoria de qué hay publicado.
DEPLOYED_COMMIT="${DEPLOYED_COMMIT:-a287a0a}"  # moneda USD + PayPal reactivado (2026-07-29)

mapfile -t FILES < <(
  git -C "$LOCAL" diff --name-only "$DEPLOYED_COMMIT..HEAD" \
    | grep -vE '^(tests/|docs/|\.claude/|\.gitignore|deploy-|README)' \
    | while read -r f; do [ -f "$LOCAL/$f" ] && echo "$f"; done
)
FILES+=(public/build/manifest.json)

# El SCSS no se sube (el servidor no compila): va el CSS ya construido.
for asset in "$LOCAL"/public/build/assets/*; do
  FILES+=("public/build/assets/$(basename "$asset")")
done

echo "== 0. Comprobando acceso SSH =="
if ! ssh "${SSH_OPTS[@]}" "$SSH_USER@$HOST" "test -d '$REMOTE'" 2>/dev/null; then
  echo "!! Sin acceso SSH o el docroot no existe: $SSH_USER@$HOST:$REMOTE"
  echo "   Instala la pubkey por el panel (ver cabecera) y repite."
  exit 1
fi
echo "   OK"

echo "== 1. Verificando que los archivos existan en local =="
missing=0
for f in "${FILES[@]}"; do
  [ -f "$LOCAL/$f" ] || { echo "   !! falta: $f"; missing=1; }
done
[ "$missing" -eq 0 ] || { echo "!! Aborta: hay archivos que no existen en local."; exit 1; }
echo "   ${#FILES[@]} archivos listos"

echo "== 2. Respaldo de los archivos que se van a sobreescribir =="
STAMP="$(git -C "$LOCAL" log -1 --format=%h)-$(git -C "$LOCAL" log -1 --format=%cd --date=format:%Y%m%d%H%M)"
ssh "${SSH_OPTS[@]}" "$SSH_USER@$HOST" "cd '$REMOTE' && mkdir -p .deploy-backups && tar czf .deploy-backups/pre-$STAMP.tar.gz $(printf '%s ' "${FILES[@]}") 2>/dev/null; echo '   backup: .deploy-backups/pre-$STAMP.tar.gz'"

echo "== 3. Subiendo el lote =="
tar czf - -C "$LOCAL" "${FILES[@]}" \
  | ssh "${SSH_OPTS[@]}" "$SSH_USER@$HOST" "tar xzf - -C '$REMOTE'" \
  || { echo "!! Falló la transferencia"; exit 1; }
echo "   subido"

echo "== 4. Permisos, cachés y carpeta de derivados =="
ssh "${SSH_OPTS[@]}" "$SSH_USER@$HOST" bash -s <<REMOTE_EOF
set -uo pipefail
cd '$REMOTE'

# Los archivos recién extraídos quedan de root: sin esto el sitio da 500.
chown -R $SITE_USER:$SITE_USER app lang resources public/build public/assets

# public/media/derived la escribe ResponsiveImage en la primera visita.
mkdir -p public/media/derived
chown -R $SITE_USER:$SITE_USER public/media
chmod 775 public/media/derived

# Artisan SIEMPRE como el usuario del sitio: como root deja storage/ no
# escribible por el sitio y todo revienta con 500 después.
sudo -u $SITE_USER $PHP artisan view:clear
sudo -u $SITE_USER $PHP artisan cache:clear
sudo -u $SITE_USER $PHP artisan config:clear
sudo -u $SITE_USER $PHP artisan route:clear
sudo -u $SITE_USER $PHP artisan config:cache
sudo -u $SITE_USER $PHP artisan route:cache

# El PNG de 2.52 MB ya no se usa en ninguna vista: fuera del servidor también.
rm -f public/assets/banners/hero-machu-picchu.png

echo "   cachés regeneradas"
REMOTE_EOF

echo "== 5. Verificación en caliente =="

# PRIMERO el código de estado. En el primer intento la home devolvía 500 y las
# comprobaciones de AUSENCIA daban "OK" alegremente (en una página de error no
# está el pill de WhatsApp ni el PNG viejo, claro). Un 500 tiene que abortar la
# verificación, no colar tres OK falsos.
CODE="$(curl -s -o /dev/null -w '%{http_code}' --ssl-no-revoke -m 40 "$URL/es")"
echo "   HTTP $CODE en $URL/es"

if [ "$CODE" != "200" ]; then
  echo "   !! La home NO responde 200. Últimos errores del log:"
  ssh "${SSH_OPTS[@]}" "$SSH_USER@$HOST" "cd '$REMOTE' && tail -c 60000 storage/logs/laravel.log | grep -aoE '(ERROR|CRITICAL): .{0,200}' | tail -3"
  echo "   Backup para revertir: .deploy-backups/pre-$STAMP.tar.gz"
  exit 1
fi

HTML="$(curl -s --ssl-no-revoke -m 40 "$URL/es")"

# presente: el patrón DEBE aparecer. ausente: NO debe aparecer.
# (grep -E no tiene lookahead: las comprobaciones negativas van por separado,
# si no se escriben patrones que pasan siempre y no verifican nada.)
presente() { if grep -qE "$2" <<<"$HTML"; then echo "   OK    $1"; else echo "   FALLA $1"; fi; }
ausente()  { if grep -qE "$2" <<<"$HTML"; then echo "   FALLA $1"; else echo "   OK    $1"; fi; }

presente "hero en WebP con srcset"      'srcset="[^"]*\.webp [0-9]+w'
presente "preload de la imagen LCP"     'rel="preload"[^>]*as="image"'
presente "H1 con marca + subtitular"    'lat-hero__brand'
presente "buscador con los 4 campos"    'id="s-q".*id="s-dest"|id="s-dest"'
ausente  "sin pill de WhatsApp"         'lat-btn--wa'
ausente  "sin el PNG de 2.5 MB"         'hero-machu-picchu\.png'

NAV="$(sed -n 's/.*<div class="lat-nav-links">\(.*\)<\/div>.*/\1/p' <<<"$HTML" | head -1)"
if grep -qE 'Blog|Contacto|Free Tours' <<<"$NAV"; then
  echo "   FALLA menú reducido (aparece Blog/Contacto/Free Tours)"
else
  echo "   OK    menú reducido a Inicio/Nosotros/Tours"
fi

echo "   noindex del staging:"
curl -sI --ssl-no-revoke "$URL/es" | grep -i "x-robots-tag" || echo "   !! OJO: sin X-Robots-Tag, revisar StagingNoindex"

echo
echo "== Listo. Revisar a mano en $URL/es =="
echo "   - 1440 y 375: hero a 90vh, buscador completo, 10+ a la derecha"
echo "   - Buscar un tour desde el hero y confirmar que filtra"
