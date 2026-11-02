#!/usr/bin/env bash
# cambio-carpetas.sh · Cambio a producción con dos `mv` y vuelta atrás con otros dos (runbook F8, rollback R1).
#
# Propósito: sustituir el docroot por la obra en el mismo disco (rename atómico, hueco de
#   microsegundos) detrás de la cortina, dejando el sitio anterior entero en los respaldos con sus
#   permisos y la marca de tiempo en ULTIMO-CAMBIO; y deshacerlo en menos de un minuto.
# Uso (rellena CONFIG; `bash -n`):
#   ssh <SSH_ALIAS_DESTINO> 'bash -s -- comprobar' < cambio-carpetas.sh   [lectura] mismo disco, obra completa, permisos
#   ssh <SSH_ALIAS_DESTINO> 'bash -s -- cambiar'   < cambio-carpetas.sh   [ESCRIBE] dos mv; OK explícito del usuario
#   ssh <SSH_ALIAS_DESTINO> 'bash -s -- volver'    < cambio-carpetas.sh   [ESCRIBE] R1; OK explícito del usuario
#   Justo después de cambiar o volver, desde el Mac: comprobar-http.sh publico (y el reto ACME).
#   Si tras «cambiar» no sale 503 en «/», 200 en la página de la cortina y 200 en el reto ACME: «volver» ya.
# Estado:  probado en HomeMaker (comandos del cambio y de R1); el script con modos es generalización.
# Riesgos: mueve el docroot. No borra nada: el sitio anterior y el WP retirado quedan en R.
#   No funciona entre discos distintos (sería una copia, no un rename): «comprobar» lo detecta.

set -euo pipefail

# ---------------- CONFIG ----------------
PREFIJO=pfx                                # prefijo del proyecto (<PREFIJO>): nombra la obra y los respaldos
D="<WP_PATH>"                              # docroot final, absoluto o "$HOME/…"
W="$HOME/$PREFIJO-wp-nuevo"                # obra
R="$HOME/$PREFIJO-respaldos"               # respaldos (700)
ETIQUETA=prototipo                         # nombre del respaldo del sitio anterior: <docroot>.<ETIQUETA>-<TS>
# ----------------------------------------

N=$(basename "$D")

case "${1:-}" in
comprobar)
	stat -c '%U:%G %a %n' "$D"
	[ "$(stat -c %d "$HOME")" = "$(stat -c %d "$(dirname "$D")")" ] && echo "mismo disco: OK" || echo "DISCO DISTINTO: NO-GO"
	for f in wp-config.php .htaccess .well-known wp-includes; do [ -e "$W/$f" ] && echo "obra: $f OK" || echo "obra: FALTA $f -> NO-GO"; done
	echo "obra: carpetas sin o+x: $(find "$W" -type d ! -perm -o=x | wc -l) · archivos sin o+r: $(find "$W" -type f ! -perm -o=r | wc -l)"
	stat -c '%U:%G %a %n' "$W" "$W/wp-config.php" "$W/.htaccess"
	echo "retos ACME en la obra: $(find "$W/.well-known/acme-challenge" -type f 2>/dev/null | wc -l)"
	[ -f "$R/ULTIMO-CAMBIO" ] && echo "aviso: ya hay un ULTIMO-CAMBIO ($(cat "$R/ULTIMO-CAMBIO"))" || true
	;;
cambiar)
	TS=$(date +%Y%m%d-%H%M)
	mkdir -p "$R" && chmod 700 "$R"
	# Con set -e, en «a && b» solo aborta el fallo del último: de ahí el «|| exit».
	test -d "$D" && test -f "$W/wp-config.php" && test -f "$W/.htaccess" && test -d "$W/.well-known" && test ! -e "$R/$N.$ETIQUETA-$TS" \
		|| { echo "Falta algo en la obra o el respaldo ya existe: NO-GO"; exit 1; }
	[ "$(stat -c %d "$HOME")" = "$(stat -c %d "$(dirname "$D")")" ] || { echo "DISCO DISTINTO: NO-GO"; exit 1; }
	cp -an "$D/.well-known/." "$W/.well-known/" || true  # por si AutoSSL escribió algo desde F3 (en macOS y otros, -n sale con 1 al saltar existentes)
	mv "$D" "$R/$N.$ETIQUETA-$TS"
	mv "$W" "$D" || { mv "$R/$N.$ETIQUETA-$TS" "$D"; echo "Falló el segundo mv: sitio anterior restaurado. NO-GO"; exit 1; }
	echo "$TS" > "$R/ULTIMO-CAMBIO"
	stat -c '%U:%G %a %n' "$D" "$R/$N.$ETIQUETA-$TS"
	echo "Cambio hecho ($TS). Ahora, desde el Mac y en el mismo minuto: puerta C (503 / · 200 cortina · 200 ACME)."
	;;
volver)
	TS=$(cat "$R/ULTIMO-CAMBIO"); P="$R/$N.$ETIQUETA-$TS"; X="$R/$N.wp-retirado-$(date +%Y%m%d-%H%M)"
	test -d "$P" && test -d "$D" && test ! -e "$X" || { echo "No está el respaldo $P (o ya existe $X): NO-GO"; exit 1; }
	mv "$D" "$X"
	mv "$P" "$D" || { mv "$X" "$D"; echo "Falló el segundo mv: WP restaurado. NO-GO"; exit 1; }
	cp -an "$X/.well-known/." "$D/.well-known/" || true  # retos de AutoSSL escritos mientras estuvo el WP; se comprueba con el reto ACME
	stat -c '%U:%G %a %n' "$D"
	echo "Vuelta atrás hecha. El WP queda en $X (y su BD, intacta) para reintentar."
	;;
*) echo "Modo: comprobar | cambiar | volver"; exit 2 ;;
esac
