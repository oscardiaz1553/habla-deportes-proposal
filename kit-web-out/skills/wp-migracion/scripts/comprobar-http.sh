#!/usr/bin/env bash
# comprobar-http.sh · Batería curl de migración y lanzamiento (solo lectura, desde el Mac).
#
# Propósito: códigos, redirecciones, mapa 301/410, noindex/canonical/hreflang y cabeceras del
#   dominio, en tres momentos: con la cortina puesta visto por el público («publico»), con la
#   cortina puesta visto con la llave de revisión («llave», runbook F10) y ya lanzado («lanzado»).
# Uso:
#   DOMINIO=<DOMINIO> ./comprobar-http.sh publico
#   DOMINIO=<DOMINIO> SSH_ALIAS=<SSH_ALIAS_DESTINO> WP_PATH=<WP_PATH> PARAM_LLAVE=<PARAM_LLAVE> ./comprobar-http.sh llave
#   DOMINIO=<DOMINIO> ./comprobar-http.sh lanzado
# Variables opcionales:
#   RUTAS=archivo     una ruta o URL por línea que debe dar 200 (todas las publicadas, en todos los idiomas).
#                     Se obtiene con: wp --path=<WP_PATH> --url=https://<DOMINIO> post list
#                     --post_type=page,<CPT> --post_status=publish --field=url --lang=
#                     (sin --skip-themes si el CPT lo registra el tema hijo: saldrían /?p=<id>).
#   MAPA=archivo      TSV «ruta<TAB>código<TAB>destino» del mapa 301/410 (destino vacío en los 410).
#   VIEJO=<STAGING_DOMINIO>   IDIOMA_2=<IDIOMA_2>   LOGIN_SLUG=<LOGIN_SLUG>
#   FUENTE=/wp-content/themes/<child>/fonts/<archivo>.woff2   ACME=<archivo real de .well-known/acme-challenge>
#   WWW=1             prueba también la variante www.   UA=…   (por defecto, UA de Chrome: algunos hostings retan al de curl)
# Estado:  generalizado, no probado fuera de HomeMaker (cada comprobación sale de los curl del caso de referencia).
# Riesgos: ninguno; solo GET y HEAD. En modo «llave», la llave se lee por SSH dentro de una
#   sustitución de shell, se cambia por una cookie en un archivo temporal y nunca se imprime;
#   el archivo se borra al salir. No envía formularios.
# Compatible con el bash 3.2 de macOS.

MODO=${1:-}
case "$MODO" in publico|llave|lanzado) ;; *) sed -n '2,24p' "$0"; exit 2 ;; esac
[ -n "${DOMINIO:-}" ] || { echo "Falta DOMINIO"; exit 2; }
UA=${UA:-'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36'}
B="https://$DOMINIO"
J=""
FALLOS=0

peticion() { if [ -n "$J" ]; then curl -sS -A "$UA" -b "$J" "$@"; else curl -sS -A "$UA" "$@"; fi; }
codigo()   { peticion -o /dev/null -w '%{http_code}' "$1"; }
cabecera() { peticion -o /dev/null -D - "$1" | tr -d '\r' | grep -i "^$2:" | head -1 | cut -d' ' -f2-; }
esperar()  { # etiqueta esperado obtenido
	if [ "$2" = "$3" ]; then printf '  OK     %-44s %s\n' "$1" "$3"
	else printf '  FALLO  %-44s obtenido «%s», esperado «%s»\n' "$1" "$3" "$2"; FALLOS=$((FALLOS + 1)); fi
}
ver()      { printf '  ·      %-44s %s\n' "$1" "$2"; }
seccion()  { printf '\n== %s\n' "$1"; }

obtener_jar() {
	for v in SSH_ALIAS WP_PATH PARAM_LLAVE; do eval "[ -n \"\${$v:-}\" ]" || { echo "Falta $v"; exit 2; }; done
	J=$(mktemp); trap 'rm -f "$J"' EXIT
	local t
	t=$(ssh -o BatchMode=yes -o ConnectTimeout=15 "$SSH_ALIAS" "grep -oE '${PARAM_LLAVE}=[a-z0-9]{8,}' ${WP_PATH}/.htaccess | head -1 | cut -d= -f2")
	[ -n "$t" ] || { echo "No hay llave en ${WP_PATH}/.htaccess (¿cortina retirada? usa «publico» o «lanzado»)"; exit 2; }
	curl -sS -A "$UA" -o /dev/null -c "$J" "$B/?${PARAM_LLAVE}=$t"
	t=''
	echo "cookie de revisión obtenida (la llave no se muestra)"
}

canonico() {
	seccion "Canónico: http y www → https://$DOMINIO"
	local r
	r=$(peticion -o /dev/null -w '%{http_code} %{redirect_url}' "http://$DOMINIO/")
	esperar "http://$DOMINIO/" "301 $B/" "$r"
	if [ "${WWW:-0}" = 1 ]; then
		r=$(peticion -o /dev/null -w '%{http_code} %{redirect_url}' "https://www.$DOMINIO/")
		esperar "https://www.$DOMINIO/" "301 $B/" "$r"
	fi
}

acme() {
	[ -n "${ACME:-}" ] || return 0
	seccion "Reto ACME (renovación del SSL)"
	esperar "http  /.well-known/acme-challenge/…" 200 "$(codigo "http://$DOMINIO/.well-known/acme-challenge/$ACME")"
	esperar "https /.well-known/acme-challenge/…" 200 "$(codigo "$B/.well-known/acme-challenge/$ACME")"
}

rutas() {
	[ -n "${RUTAS:-}" ] || { seccion "Rutas publicadas: define RUTAS para comprobarlas"; return 0; }
	seccion "Rutas publicadas (esperado 200 en todas)"
	local n=0 malas=0 u c
	while IFS= read -r u || [ -n "$u" ]; do
		u=${u%$'\r'}; [ -n "$u" ] || continue
		u=${u#"$B"}; n=$((n + 1))
		c=$(peticion -o /dev/null -w '%{http_code} %{redirect_url}' "$B$u")
		case "$c" in 200*) ;; *) printf '  FALLO  %-44s %s\n' "$u" "$c"; malas=$((malas + 1)) ;; esac
	done < "$RUTAS"
	ver "rutas comprobadas" "$n · distintas de 200: $malas"
	FALLOS=$((FALLOS + malas))
}

especiales() { # $1 = llave|lanzado
	seccion "Rutas especiales"
	local ct rb
	esperar "/robots.txt" 200 "$(codigo "$B/robots.txt")"
	ct=$(cabecera "$B/robots.txt" content-type); ver "/robots.txt content-type" "$ct"
	case "$ct" in text/plain*) ;; *) echo "  FALLO  robots.txt no es text/plain (¿la cortina HTML de «Próximamente»?)"; FALLOS=$((FALLOS + 1)) ;; esac
	rb=$(peticion "$B/robots.txt" | tr -d '\r')
	if [ "$1" = lanzado ]; then
		printf '%s\n' "$rb" | grep -q '^Sitemap:' && ver "robots.txt" "tiene Sitemap:" || { echo "  FALLO  robots.txt sin línea Sitemap:"; FALLOS=$((FALLOS + 1)); }
		printf '%s\n' "$rb" | grep -qE '^Disallow: /$' && { echo "  FALLO  robots.txt con «Disallow: /» (¿blog_public=0?)"; FALLOS=$((FALLOS + 1)); } || ver "robots.txt" "sin «Disallow: /»"
	else
		ver "robots.txt (blog_public=0)" "$(printf '%s' "$rb" | grep -c '^Disallow: /$') línea(s) «Disallow: /»"
	fi
	esperar "/sitemap_index.xml" 200 "$(codigo "$B/sitemap_index.xml")"
	esperar "/wp-json/" 200 "$(codigo "$B/wp-json/")"
	esperar "/no-existe-$$/ (404 real)" 404 "$(codigo "$B/no-existe-$$/")"
	[ -n "${LOGIN_SLUG:-}" ] && esperar "/<LOGIN_SLUG>" 302 "$(codigo "$B/$LOGIN_SLUG")"
	ver "/wp-login.php sin sesión" "$(peticion -o /dev/null -w '%{http_code} %{redirect_url}' "$B/wp-login.php")"
	ver "/wp-admin/ sin sesión" "$(peticion -o /dev/null -w '%{http_code} %{redirect_url}' "$B/wp-admin/")"
	esperar "/xmlrpc.php" 403 "$(codigo "$B/xmlrpc.php")"
}

mapa() {
	[ -n "${MAPA:-}" ] || { seccion "Mapa 301/410: define MAPA para comprobarlo"; return 0; }
	seccion "Mapa 301/410 del sitio anterior"
	local ruta cod dest r esp
	while IFS=$'\t' read -r ruta cod dest || [ -n "$ruta" ]; do
		ruta=${ruta%$'\r'}; dest=${dest%$'\r'}
		case "$ruta" in ''|'#'*) continue ;; esac
		r=$(peticion -o /dev/null -w '%{http_code} %{redirect_url}' "$B$ruta")
		if [ -n "$dest" ]; then case "$dest" in http*) esp="$cod $dest" ;; *) esp="$cod $B$dest" ;; esac
		else esp="$cod "; fi
		esperar "$ruta" "$esp" "$r"
	done < "$MAPA"
}

contenido() { # $1 = llave|lanzado
	seccion "Contenido: dominio viejo, lang, robots, canonical y hreflang"
	local u html
	for u in / ${IDIOMA_2:+/$IDIOMA_2/}; do
		html=$(peticion "$B$u")
		echo "  -- $u"
		[ -n "${VIEJO:-}" ] && esperar "menciones de $VIEJO" 0 "$(printf '%s' "$html" | grep -cF "$VIEJO")"
		ver "html lang" "$(printf '%s' "$html" | grep -oE '<html[^>]*lang="[^"]+"' | grep -oE 'lang="[^"]+"' | head -1)"
		ver "meta robots" "$(printf '%s' "$html" | grep -oiE "<meta name=.robots. content=.[^'\"]*" | head -1 | sed 's/.*content=.//')"
		if [ "$1" = lanzado ]; then
			printf '%s' "$html" | grep -qiE "<meta name=.robots. content=.[^'\"]*noindex" && { echo "  FALLO  noindex en $u"; FALLOS=$((FALLOS + 1)); }
			printf '%s' "$html" | grep -q '<link rel="canonical"' || { echo "  FALLO  sin canonical en $u"; FALLOS=$((FALLOS + 1)); }
		fi
		printf '%s' "$html" | grep -oE '<link rel="(canonical|alternate)"[^>]+>' | sed 's/^/         /'
	done
}

cabeceras() {
	seccion "Cabeceras: gzip, caché de estáticos, WebP"
	ver "portada content-encoding" "$(peticion -o /dev/null -D - -H 'Accept-Encoding: gzip' "$B/" | tr -d '\r' | grep -i '^content-encoding:' | cut -d' ' -f2-)"
	ver "portada set-cookie" "$(cabecera "$B/" set-cookie) (con caché de página debe estar vacío: PLL_COOKIE=false)"
	[ -n "${FUENTE:-}" ] && ver "fuente cache-control" "$(cabecera "$B$FUENTE" cache-control)"
	local img
	img=$(peticion "$B/" | grep -oE "$B/wp-content/uploads/[^\"' ]+\.(jpe?g|png)" | head -1)
	if [ -n "$img" ]; then
		ver "imagen de prueba" "${img#"$B"}"
		ver "  con Accept: image/webp" "$(peticion -o /dev/null -D - -H 'Accept: image/webp,*/*' "$img" | tr -d '\r' | grep -iE '^(content-type|vary):' | tr '\n' ' ')"
	fi
}

case "$MODO" in
publico)
	seccion "Público sin llave (cortina: esperado 503)"
	esperar "/" 503 "$(codigo "$B/")"
	ver "Retry-After" "$(cabecera "$B/" retry-after)"
	ver "Cache-Control" "$(cabecera "$B/" cache-control)"
	ver "/robots.txt" "$(codigo "$B/robots.txt") (503 con cualquier cortina: que dure días, no semanas)"
	canonico; acme
	;;
llave)
	obtener_jar
	seccion "Con la llave (visitante sin sesión)"
	esperar "/" 200 "$(codigo "$B/")"
	rutas; especiales llave; mapa; contenido llave; cabeceras; acme
	;;
lanzado)
	seccion "Lanzado (público, sin cookie)"
	esperar "/" 200 "$(codigo "$B/")"
	[ -n "${IDIOMA_2:-}" ] && esperar "/$IDIOMA_2/" 200 "$(codigo "$B/$IDIOMA_2/")"
	canonico; rutas; especiales lanzado; mapa; contenido lanzado; cabeceras; acme
	;;
esac

printf '\nFallos: %s\n' "$FALLOS"
[ "$FALLOS" -eq 0 ]
