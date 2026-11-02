#!/usr/bin/env bash
# componer-htaccess.sh · Compone el .htaccess y el .user.ini de la obra por bloques (runbook F7).
#
# Propósito: escribir <obra>/.htaccess a partir de htaccess-bloques.plantilla, con la cortina 503
#   copiada de servidor a servidor SIN imprimirla (y con paso libre a wp-cron.php) y el mapa 301/410
#   de SEO; comprobar con cmp que la cortina es idéntica a la actual; escribir .user.ini.
# Uso: sube este script, htaccess-bloques.plantilla, el mapa de SEO y (si hace falta)
#   cortina-503.plantilla a ~/<PREFIJO>-migracion/; rellena CONFIG; `bash -n`; y en el destino:
#   ssh <SSH_ALIAS_DESTINO> 'bash -s' < componer-htaccess.sh
# CORTINA=docroot   copia la cortina que ya protege el docroot actual (debe empezar en la línea 1 de
#                   su .htaccess y cerrar con una línea que contenga CORTINA_FIN).
# CORTINA=plantilla usa cortina-503.plantilla y genera la llave en el servidor (openssl rand -hex 8);
#                   la llave no se imprime (pasa un instante como argumento de sed, visible solo para
#                   la propia cuenta). El usuario la lee él en su terminal cuando la necesite.
# CORTINA=ninguna   sin cortina (solo si el dominio aún no es público o ya se decidió lanzar).
# Estado:  generalizado, no probado fuera de HomeMaker. Bloques, copia con sed/awk y cmp: probados en
#   HomeMaker (Apache 2.4 + mod_lsapi, cPanel). La composición desde plantilla y CORTINA=plantilla: no.
# Riesgos: escribe solo en la obra (W): se niega si W es el docroot o si ya hay .htaccess (salvo
#   SOBRESCRIBIR=si). Deja la plantilla ya rellenada en B/htaccess.renderizada (no la borra).

set -euo pipefail

# ---------------- CONFIG ----------------
PREFIJO=pfx                                  # prefijo del proyecto (<PREFIJO>): carpetas y marcas BEGIN/END
W="$HOME/$PREFIJO-wp-nuevo"                  # obra
D="<WP_PATH>"                                # docroot actual, absoluto o "$HOME/…" (de aquí se copia la cortina)
B="$HOME/$PREFIJO-migracion"                 # plantillas, mapa y scripts
DOMINIO='<DOMINIO>'                          # sin esquema; la variante canónica (con o sin www, D8)
CORTINA=docroot                              # docroot | plantilla | ninguna
CORTINA_FIN='FIN DE LA CORTINA'              # texto de la línea que cierra la cortina actual
PARAM_LLAVE='<PARAM_LLAVE>'                  # solo CORTINA=plantilla
COOKIE_LLAVE='<COOKIE_LLAVE>'                # solo CORTINA=plantilla
PAGINA_CORTINA='<PAGINA_CORTINA>'            # solo CORTINA=plantilla (sin .html)
MAPA="$B/$PREFIJO-301.conf"                  # solo líneas RewriteCond/RewriteRule (y comentarios), sin <IfModule>
PLANTILLA="$B/htaccess-bloques.plantilla"
PLANTILLA_CORTINA="$B/cortina-503.plantilla"
MAX_INPUT_VARS=3000
SOBRESCRIBIR=no
# ----------------------------------------

H="$W/.htaccess"
[ -d "$W/wp-includes" ] || { echo "$W no parece la obra (sin wp-includes): NO-GO"; exit 1; }
[ "$(cd "$W" && pwd -P)" != "$(cd "$D" && pwd -P)" ] || { echo "W es el docroot: NO-GO"; exit 1; }
[ ! -e "$H" ] || [ "$SOBRESCRIBIR" = si ] || { echo "$H ya existe (SOBRESCRIBIR=si para rehacerlo)"; exit 1; }
[ -f "$PLANTILLA" ] || { echo "Falta $PLANTILLA"; exit 1; }

DOM_ESC=$(printf '%s' "$DOMINIO" | sed 's/[.]/\\\\&/g')   # en el .htaccess queda example\.com
R="$B/htaccess.renderizada"
grep -v '^##' "$PLANTILLA" | sed -e "s/<DOMINIO_ESCAPADO>/$DOM_ESC/g" -e "s/<DOMINIO>/$DOMINIO/g" -e "s/<PREFIJO>/$PREFIJO/g" > "$R"

cortina_actual() { sed -n "1,/$CORTINA_FIN/p" "$D/.htaccess"; }

emitir_cortina() {
	case "$CORTINA" in
	docroot)
		grep -q "$CORTINA_FIN" "$D/.htaccess" || { echo "No hay «$CORTINA_FIN» en $D/.htaccess" >&2; return 1; }
		echo "# BEGIN $PREFIJO-CORTINA  (se retira con: sed -i '/^# BEGIN $PREFIJO-CORTINA/,/^# END $PREFIJO-CORTINA/d' .htaccess)"
		if cortina_actual | grep -q 'wp-cron'; then
			cortina_actual
		else
			cortina_actual | awk '{print} !x && index($0,"!^/\\.well-known/") {print "  RewriteCond %{REQUEST_URI}  !^/wp-cron\\.php$"; x=1}'
		fi
		echo "# END $PREFIJO-CORTINA"
		;;
	plantilla)
		[ -f "$PLANTILLA_CORTINA" ] || { echo "Falta $PLANTILLA_CORTINA" >&2; return 1; }
		local k
		k=$(openssl rand -hex 8)
		grep -v '^##' "$PLANTILLA_CORTINA" | sed -e "s/<LLAVE>/$k/g" -e "s/<PARAM_LLAVE>/$PARAM_LLAVE/g" \
			-e "s/<COOKIE_LLAVE>/$COOKIE_LLAVE/g" -e "s/<PAGINA_CORTINA>/$PAGINA_CORTINA/g" -e "s/<PREFIJO>/$PREFIJO/g"
		k=''
		;;
	ninguna) echo "# (sin cortina)" ;;
	*) echo "CORTINA debe ser docroot, plantilla o ninguna" >&2; return 1 ;;
	esac
}

emitir_mapa() {
	if [ -s "$MAPA" ]; then
		grep -E '^[[:space:]]*(#|RewriteCond|RewriteRule)' "$MAPA"
	else
		echo "  # (sin mapa 301 todavía: se añade antes de lanzar)"
	fi
}

{
	while IFS= read -r linea || [ -n "$linea" ]; do
		case "$linea" in
		'# <<CORTINA>>') emitir_cortina ;;
		'# <<MAPA-301>>') emitir_mapa ;;
		*) printf '%s\n' "$linea" ;;
		esac
	done < "$R"
} > "$H"
chmod 644 "$H"
printf 'max_input_vars = %s\n' "$MAX_INPUT_VARS" > "$W/.user.ini"
chmod 644 "$W/.user.ini"

echo "== comprobaciones (no muestran la llave)"
if [ "$CORTINA" = docroot ]; then
	if cmp -s <(cortina_actual) <(sed -n "/^# BEGIN $PREFIJO-CORTINA/,/^# END $PREFIJO-CORTINA/p" "$H" | sed '1d;$d' | grep -vF 'wp-cron\.php'); then
		echo "cortina: idéntica a la del docroot (+ wp-cron)"
	elif cortina_actual | grep -q 'wp-cron'; then
		cmp -s <(cortina_actual) <(sed -n "/^# BEGIN $PREFIJO-CORTINA/,/^# END $PREFIJO-CORTINA/p" "$H" | sed '1d;$d') \
			&& echo "cortina: idéntica a la del docroot (ya dejaba pasar wp-cron)" || { echo "CORTINA DISTINTA: NO-GO"; exit 1; }
	else
		echo "CORTINA DISTINTA: NO-GO"; exit 1
	fi
fi
echo "reglas wp-cron: $(grep -cE 'RewriteCond.*wp-cron' "$H" || true)   (esperado 1 con cortina)"
echo "reglas del mapa: $(sed -n "/301 del sitio anterior (dueña/,/^# END $PREFIJO · 301/p" "$H" | grep -cE '^[[:space:]]*RewriteRule' || true)   (el número del mapa de SEO)"
[ -s "$MAPA" ] && echo "líneas del mapa descartadas (no eran RewriteCond/RewriteRule/comentario): $(grep -cvE '^[[:space:]]*(#|RewriteCond|RewriteRule|$)' "$MAPA" || true)"
n=$(grep -cE '<[A-Z][A-Z0-9_]*>' "$H" || true)
echo "marcadores sin rellenar: $n   (esperado 0)"
grep -nE '^# (BEGIN|END)|^# SGS|^ErrorDocument' "$H"
cat "$W/.user.ini"
[ "$n" -eq 0 ] || { echo "Quedan marcadores <…> en $H: NO-GO"; exit 1; }
echo "Siguiente: puerta B (estado-sitio.php en la obra) y F8."
