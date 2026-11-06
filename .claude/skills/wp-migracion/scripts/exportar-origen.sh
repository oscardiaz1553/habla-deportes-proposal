#!/usr/bin/env bash
# exportar-origen.sh · Congelación comprobada y exportación del origen por la salida estándar de SSH.
#
# Propósito: runbook F1. El origen queda en solo lectura: la BD y wp-content viajan por la salida
#   estándar de SSH a una carpeta privada del Mac, con comprobaciones de integridad y SHA256SUMS.
# Uso (desde el Mac; copia la plantilla al scratchpad y rellena el bloque CONFIG):
#   ./exportar-origen.sh congelacion     [lectura] última edición, archivos cambiados en 30 min, cortina y blog_public
#   ./exportar-origen.sh exportar        [lectura en origen; ESCRIBE solo en el Mac] volcado + tar + integridad + SHA256SUMS
# Parámetros: bloque CONFIG. RUTA_ORIGEN entre comillas simples si lleva $HOME: se expande en el origen.
# Estado:  generalizado, no probado fuera de HomeMaker (comandos y exclusiones del caso de referencia).
# Riesgos: la copia local contiene datos personales (hashes, correos, envíos): carpeta 700 fuera de
#   Documentos/iCloud y fecha de borrado que decide el usuario. Nada se escribe en el origen.
#   tar sale con 1 si un archivo cambia mientras lo lee: se acepta solo con las comprobaciones de integridad.
# Compatible con el bash 3.2 de macOS.

# ---------------- CONFIG ----------------
SSH_ORIGEN='<SSH_ALIAS_ORIGEN>'
RUTA_ORIGEN='<WP_PATH_ORIGEN>'                         # p. ej. '$HOME/www/<staging>/public_html'
PREFIJO=pfx                                            # prefijo del proyecto (<PREFIJO>): nombra la carpeta local
CHILD='<carpeta del tema hijo>'
TABLAS_FUERA=''                                        # p. ej. '<TABLE_PREFIX>sg_ai_log_events' (tablas de plugins que no viajan; coma entre varias)
EXCLUSIONES_TAR="--exclude=./upgrade --exclude=./upgrade-temp-backup --exclude=./cache --exclude='*.log' --exclude=php_errorlog --exclude=error_log"
EXCLUSIONES_PROVEEDOR=''                               # caso SiteGround: "--exclude=./sgs_encrypt_key.php --exclude=./uploads/siteground-optimizer-assets --exclude=./plugins/sg-ai-studio --exclude=./plugins/sg-cachepress"
NO_VIAJAN=''                                           # regex de carpetas de plugins que no viajan, p. ej. 'sg-ai-studio|sg-cachepress'
CLAVES="themes/$CHILD/functions.php"                   # archivos que tienen que estar en el tar (mu-plugins, un upload concreto, el plugin de seguridad…)
GTID='--set-gtid-purged=OFF'                           # vacíalo si el mysqldump del origen no la conoce (mysqldump --help | grep -c gtid → 0)
# ----------------------------------------

S="ssh -o BatchMode=yes -o ConnectTimeout=15 $SSH_ORIGEN"

case "${1:-}" in
congelacion)
	$S 'bash -s' <<EOF
R=$RUTA_ORIGEN
P=\$(wp --path="\$R" config get table_prefix); echo "prefijo: \$P"
echo "última edición (GMT): \$(wp --path="\$R" db query "SELECT MAX(post_modified_gmt) FROM \${P}posts" --skip-column-names)"
echo "archivos cambiados en 30 min: \$(find "\$R/wp-content/themes/$CHILD" "\$R/wp-content/mu-plugins" "\$R/wp-content/uploads" -type f -newermt '-30 minutes' 2>/dev/null | wc -l)"
wp --path="\$R" db query "SELECT option_name, option_value FROM \${P}options WHERE option_name IN ('elementor_maintenance_mode_mode','blog_public')" --skip-column-names
echo "ahora (UTC): \$(date -u)"
EOF
	echo "Esperado: 0 archivos y la última edición anterior a la hora de congelación."
	;;
exportar)
	TS=$(date +%Y%m%d-%H%M); L="$HOME/$PREFIJO-migracion-local/$TS"
	mkdir -p "$L" && chmod 700 "$L" || exit 1
	echo "carpeta local: $L"
	$S "wp --path=$RUTA_ORIGEN db export - --single-transaction $GTID --default-character-set=utf8mb4${TABLAS_FUERA:+ --exclude_tables=$TABLAS_FUERA}" > "$L/db.sql"
	echo "db export rc=$?"
	$S "tar -C $RUTA_ORIGEN/wp-content -czf - $EXCLUSIONES_TAR $EXCLUSIONES_PROVEEDOR --warning=no-file-changed ." > "$L/wpcontent.tgz"
	rc=$?; echo "tar rc=$rc (1 = aviso de archivo cambiado durante la lectura: aceptable solo si todo lo de abajo cuadra)"
	[ "$rc" -le 1 ] || { echo "tar falló: NO-GO"; exit 1; }
	if $S "test -s $RUTA_ORIGEN/wp-includes/.htaccess"; then $S "cat $RUTA_ORIGEN/wp-includes/.htaccess" > "$L/wp-includes.htaccess"; else : > "$L/wp-includes.htaccess"; fi

	echo "== integridad"
	echo "primera línea: $(head -1 "$L/db.sql" | cut -c1-60)"          # -- MySQL dump … (si no, un aviso de PHP se coló en la salida: repetir)
	echo "última línea:  $(tail -1 "$L/db.sql" | cut -c1-60)"          # -- Dump completed on …
	head -1 "$L/db.sql" | grep -qi 'dump' || { echo "El volcado no empieza por la cabecera de mysqldump: NO-GO"; exit 1; }
	tail -1 "$L/db.sql" | grep -q 'Dump completed' || { echo "El volcado no terminó: NO-GO"; exit 1; }
	echo "CREATE TABLE: $(grep -c '^CREATE TABLE' "$L/db.sql")   (anótalo: F5 compara el nº de tablas importadas)"
	echo "utf8mb4_0900_ai_ci: $(grep -c 'utf8mb4_0900_ai_ci' "$L/db.sql")   (>0 y destino MySQL 5.7 → F5 lo corrige en el esquema)"
	gzip -t "$L/wpcontent.tgz" && echo "tgz íntegro" || { echo "tgz corrupto: NO-GO"; exit 1; }
	LISTA=$(tar -tzf "$L/wpcontent.tgz") && echo "entradas en el tar: $(printf '%s\n' "$LISTA" | wc -l | tr -d ' ')"
	for f in $CLAVES; do printf '%s\n' "$LISTA" | grep -qxF "./$f" && echo "  ok $f" || echo "  FALTA $f"; done
	[ -n "$NO_VIAJAN" ] && echo "plugins que no viajan dentro del tar: $(printf '%s\n' "$LISTA" | grep -cE "^\./plugins/($NO_VIAJAN)/")   (esperado 0)"
	( cd "$L" && shasum -a 256 db.sql wpcontent.tgz wp-includes.htaccess > SHA256SUMS && cat SHA256SUMS )
	ls -la "$L"
	echo "Siguiente: F2 (scp -p a <SSH_ALIAS_DESTINO> y sha256sum -c SHA256SUMS allí). Avisa «UNFROZEN» a las sesiones paralelas."
	;;
*) sed -n '2,16p' "$0"; exit 2 ;;
esac
