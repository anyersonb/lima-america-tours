#!/usr/bin/env bash
# Deploy fix reservas 2026-07-16
#   #2 CMS multi-destinatario avisos de reserva
#   #3 fecha blindada (flatpickr disableMobile + escritura directa del campo)
#   #4 imágenes editables (FileUpload) en 3 secciones del home
#   #1 ruta de diagnóstico SMTP temporal (/_diag/mail)
#
# Uso:  bash deploy-fix-reservas-2026-07-16.sh
set -u

HOST="ftp.limaviewtours.com"
USER="limaweb@limaviewtours.com"
PASS="limaweb@limaviewtours.com"
BASE="/limaprogramacion"
CURL="curl -sS --ssl-no-revoke --ftp-create-dirs --connect-timeout 30 -u ${USER}:${PASS}"

cd "$(dirname "$0")"

put () {
  local local_path="$1" remote_path="$2"
  echo ">> $remote_path"
  $CURL -T "$local_path" "ftp://${HOST}${remote_path}" && echo "   OK" || echo "   FALLO"
}

# --- Subir archivos cambiados (mismo path local -> remoto) ---
put "app/Services/BookingNotifier.php"      "${BASE}/app/Services/BookingNotifier.php"
put "app/Filament/Pages/Settings.php"        "${BASE}/app/Filament/Pages/Settings.php"
put "app/Support/ImagePath.php"              "${BASE}/app/Support/ImagePath.php"
put "resources/views/tours/show.blade.php"   "${BASE}/resources/views/tours/show.blade.php"
put "resources/views/home.blade.php"         "${BASE}/resources/views/home.blade.php"
put "routes/web.php"                          "${BASE}/routes/web.php"

# --- Limpiar caches en servidor (borrando archivos por FTP) ---
echo ">> Limpiando bootstrap/cache/{routes,config}.php"
$CURL -Q "-DELE ${BASE}/bootstrap/cache/routes.php" "ftp://${HOST}/" 2>/dev/null && echo "   routes.php borrado" || echo "   (routes.php no existía)"
$CURL -Q "-DELE ${BASE}/bootstrap/cache/config.php" "ftp://${HOST}/" 2>/dev/null && echo "   config.php borrado" || echo "   (config.php no existía)"

echo ""
echo "NOTA: borra por FTP los .php de ${BASE}/storage/framework/views/ (compilados Blade)"
echo "      para que tomen los cambios de home.blade.php y show.blade.php."
echo ""
echo "Luego prueba el diagnóstico de correo en:"
echo "  https://limaviewtours.com/_diag/mail?key=lvt-mail-diag-2026&to=TU_CORREO@dominio.com"
