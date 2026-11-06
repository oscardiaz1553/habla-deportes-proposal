#!/usr/bin/env bash
# search-replace.sh · Importación (opcional) y search-replace en cuatro formas y en orden, hasta 0 restos.
#
# Propósito: runbook F5, en la BD nueva de la obra (nunca en producción viva ni en el origen).
#   Orden: 1) ruta física de origen → ruta FINAL del docroot (normal y escapada en JSON);
#   2) dominio de staging SIN esquema (cubre https://, http://, JSON escapado y no-reply@ de los
#   formularios); 3) http://<DOMINIO> → https://; 4) http:\/\/<DOMINIO> → https:\/\/.
#   Después busca restos y los desglosa por meta_key y opción.
# Uso: copia la plantilla, rellena CONFIG, `bash -n search-replace.sh`, y en el destino:
#   ssh <SSH_ALIAS_DESTINO> 'bash -s' < search-replace.sh
#   Primero con MODO=seco (--dry-run, no escribe; las cifras de 3 y 4 salen bajas porque 2 no se ha
#   aplicado). Después, con el OK del usuario, MODO=real.
# Parámetros: bloque CONFIG. IMPORTAR=si importa antes el volcado, cambiando utf8mb4_0900_ai_ci por
#   utf8mb4_unicode_520_ci solo en las líneas de esquema (MySQL 5.7 no conoce la colación de MySQL 8).
# Estado:  generalizado, no probado fuera de HomeMaker (comandos y orden del caso de referencia).
# Riesgos: escribe en la BD de la obra (MODO=real). Guardas: table_prefix y DB_NAME esperados.
#   Siempre --skip-plugins --skip-themes: ningún plugin escribe durante el reemplazo.
#   Los avisos «Skipping an uninitialized class Elementor\Core\Logger\Items\Base» vienen de
#   elementor_log y son inocuos. No imprime secretos.

set -euo pipefail

# ---------------- CONFIG ----------------
PREFIJO=pfx                                  # prefijo del proyecto (<PREFIJO>): nombra la obra y la carpeta de migración
W="$HOME/$PREFIJO-wp-nuevo"                  # obra
PREFIX_ESPERADO='<TABLE_PREFIX>'
DB_ESPERADA='<DB_NAME>'
VOLCADO="$HOME/$PREFIJO-migracion/db.sql"
IMPORTAR=no                                  # si | no
MODO=seco                                    # seco | real
RUTA_ORIGEN='<RUTA_FISICA_ORIGEN>'           # sin barra final (en SiteGround: /home/customer/www/<staging>/public_html)
RUTA_FINAL='<WP_PATH absoluta>'              # la del docroot final (cd <docroot> && pwd -P), NO la de la obra
VIEJO='<STAGING_DOMINIO>'                    # sin esquema
NUEVO='<DOMINIO>'                            # sin esquema
FRAGMENTO_RUTA='<fragmento sin barras que solo exista en la ruta de origen>'
PHPBIN=/opt/cpanel/ea-php82/root/usr/bin/php # el PHP de la web; vacío = el `wp` del PATH
WPBIN=/usr/local/bin/wp
# ----------------------------------------

wpc() { if [ -n "$PHPBIN" ]; then "$PHPBIN" "$WPBIN" "$@"; else wp "$@"; fi; }
WP()  { wpc --path="$W" --skip-plugins --skip-themes "$@"; }
esc() { printf '%s' "$1" | sed 's#/#\\/#g'; }   # /a/b → \/a\/b (como lo guarda Elementor en JSON)

# Un marcador <…> sin rellenar acabaría escrito en la BD (p. ej. la ruta final): NO-GO antes de tocar nada.
for v in "$PREFIX_ESPERADO" "$DB_ESPERADA" "$RUTA_ORIGEN" "$RUTA_FINAL" "$VIEJO" "$NUEVO" "$FRAGMENTO_RUTA"; do
	case "$v" in *'<'*|'') echo "CONFIG sin rellenar ($v): NO-GO"; exit 1 ;; esac
done
[ "$(wpc --path="$W" config get table_prefix)" = "$PREFIX_ESPERADO" ] || { echo "table_prefix distinto: NO-GO"; exit 1; }
[ "$(wpc --path="$W" config get DB_NAME)" = "$DB_ESPERADA" ] || { echo "DB_NAME distinta: NO-GO"; exit 1; }
case "$MODO" in seco|real) ;; *) echo "MODO debe ser seco o real"; exit 1 ;; esac
echo "obra=$W · prefijo=$PREFIX_ESPERADO · modo=$MODO"

if [ "$IMPORTAR" = si ]; then
	echo "== importación"
	echo "CREATE TABLE en el volcado: $(grep -c '^CREATE TABLE' "$VOLCADO")"
	if [ "$MODO" = real ]; then
		V57="${VOLCADO%.sql}-57.sql"
		sed -E '/^INSERT INTO/!s/utf8mb4_0900_ai_ci/utf8mb4_unicode_520_ci/g' "$VOLCADO" > "$V57"
		if grep -q 'utf8mb4_0900_ai_ci' "$V57"; then echo "Queda utf8mb4_0900_ai_ci: NO-GO"; exit 1; fi
		WP db import "$V57"
		echo "tablas importadas: $(WP db query 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()' --skip-column-names)"
	else
		echo "(seco) utf8mb4_0900_ai_ci en el volcado: $(grep -c 'utf8mb4_0900_ai_ci' "$VOLCADO"); no se importa"
	fi
fi

sr() { # descripción, buscar, reemplazar
	if [ "$MODO" = seco ]; then
		printf '%-34s %s\n' "$1" "$(WP search-replace "$2" "$3" --all-tables-with-prefix --precise --dry-run | tail -1)"
	else
		printf '%-34s %s reemplazos\n' "$1" "$(WP search-replace "$2" "$3" --all-tables-with-prefix --precise --report-changed-only --format=count)"
	fi
}

echo "== reemplazos (en este orden)"
sr "1a ruta física → ruta final"      "$RUTA_ORIGEN"       "$RUTA_FINAL"
sr "1b ruta física escapada (JSON)"   "$(esc "$RUTA_ORIGEN")" "$(esc "$RUTA_FINAL")"
sr "2  dominio sin esquema"           "$VIEJO"             "$NUEVO"
sr "3  https (forma normal)"          "http://$NUEVO"      "https://$NUEVO"
sr "4  https (forma escapada)"        "http:\\/\\/$NUEVO"  "https:\\/\\/$NUEVO"

echo "== restos (esperado: Found 0 matches)"
echo "$VIEJO → $(WP db search "$VIEJO" --all-tables-with-prefix --stats 2>/dev/null | tail -1)"
echo "$FRAGMENTO_RUTA → $(WP db search "$FRAGMENTO_RUTA" --all-tables-with-prefix --stats 2>/dev/null | tail -1)"
echo "-- postmeta con restos, por meta_key:"
WP db query "SELECT meta_key, COUNT(*) n, MIN(post_id) ejemplo FROM ${PREFIX_ESPERADO}postmeta WHERE meta_value LIKE '%${VIEJO}%' GROUP BY meta_key"
echo "-- opciones con restos (elementor_log es un registro: inocuo):"
WP db query "SELECT option_name FROM ${PREFIX_ESPERADO}options WHERE option_value LIKE '%${VIEJO}%'"
echo "-- posts con restos: $(WP db query "SELECT COUNT(*) FROM ${PREFIX_ESPERADO}posts WHERE post_content LIKE '%${VIEJO}%' OR guid LIKE '%${VIEJO}%'" --skip-column-names)"

echo "== comprobaciones"
echo "home=$(WP option get home) · siteurl=$(WP option get siteurl)"
echo "ruta del logo en Yoast: $(WP option pluck wpseo_titles company_logo_meta path 2>/dev/null || echo '(no definida)')"
echo "Recuerda: valores en base64 (conectores de plugins) no los ve search-replace; si su plugin no viaja, se borran sus opciones en F6."
