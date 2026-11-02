#!/usr/bin/env bash
# dns-correo.sh · Estado DNS del correo del dominio, preguntado al servidor autoritativo (solo lectura).
#
# Propósito: ver SPF, DKIM (selector default), DMARC, MX y el serial SOA tal como los sirven los NS
#   autoritativos, y compararlos con un resolver público. Detecta: más de un registro SPF, SPF que
#   no autoriza la IP del servidor nuevo y cambios hechos en el panel que no llegan al DNS público
#   (la trampa del Zone Editor de cPanel, references/correo-dns.md).
# Uso:
#   DOMINIO=<DOMINIO> [IP_SERVIDOR=<IP_SERVIDOR>] [NS=<NS_AUTORITATIVO>] ./dns-correo.sh           # foto
#   DOMINIO=<DOMINIO> IP_SERVIDOR=<IP_SERVIDOR> ./dns-correo.sh esperar [intentos]               # repite cada 25 s
#   ./dns-correo.sh … > dns-antes.txt     (guárdalo antes de cambiar nada: sirve para volver atrás)
# Variables: DOMINIO (obligatoria); IP_SERVIDOR (para comprobar el ip4: del SPF); NS (por defecto,
#   el primero de «dig +short NS»); RESOLVER (por defecto 1.1.1.1); SELECTOR (por defecto default).
# Estado:  generalizado, no probado fuera de HomeMaker (los dig y el bucle son los del caso de referencia).
# Riesgos: ninguno; solo consultas DNS. Compatible con el bash 3.2 de macOS.

[ -n "${DOMINIO:-}" ] || { sed -n '2,17p' "$0"; exit 2; }
NS=${NS:-$(dig +short NS "$DOMINIO" | head -1)}
RESOLVER=${RESOLVER:-1.1.1.1}
SELECTOR=${SELECTOR:-default}
[ -n "$NS" ] || { echo "No se pudo averiguar el NS autoritativo de $DOMINIO"; exit 2; }

serial() { dig +short SOA "$DOMINIO" @"$1" | awk '{print $3}'; }
spf()    { dig +short TXT "$DOMINIO" @"$1" | grep -i 'v=spf1'; }

foto() {
	echo "== $DOMINIO · autoritativo $NS · resolver $RESOLVER · $(date '+%Y-%m-%d %H:%M:%S')"
	echo "SOA serial      autoritativo=$(serial "$NS")  resolver=$(serial "$RESOLVER")"
	echo "MX              $(dig +short MX "$DOMINIO" @"$NS" | tr '\n' ' ')"
	local n
	n=$(spf "$NS" | wc -l | tr -d ' ')
	echo "SPF (${n} registro/s) $(spf "$NS")"
	[ "$n" -gt 1 ] && echo "  AVISO: más de un SPF en el mismo nombre invalida el SPF entero; se edita el existente"
	if [ -n "${IP_SERVIDOR:-}" ]; then
		spf "$NS" | grep -q "ip4:$IP_SERVIDOR" && echo "  SPF autoriza ip4:$IP_SERVIDOR: sí" || echo "  SPF autoriza ip4:$IP_SERVIDOR: NO (fallará SPF desde el servidor nuevo)"
	fi
	echo "DKIM $SELECTOR    $(dig +short TXT "$SELECTOR._domainkey.$DOMINIO" @"$NS" | tr -d '"' | tr -d ' \n' | cut -c1-90)…"
	echo "DMARC           $(dig +short TXT "_dmarc.$DOMINIO" @"$NS")"
}

case "${1:-}" in
esperar)
	[ -n "${IP_SERVIDOR:-}" ] || { echo "«esperar» necesita IP_SERVIDOR"; exit 2; }
	max=${2:-6}; i=1
	while [ "$i" -le "$max" ]; do
		t=$(spf "$NS" | grep -c "ip4:$IP_SERVIDOR")
		echo "$(date +%H:%M:%S) serial=$(serial "$NS") spf_con_ip=$t"
		[ "$t" -ge 1 ] && { echo "El SPF nuevo ya lo sirve $NS."; exit 0; }
		i=$((i + 1)); [ "$i" -le "$max" ] && sleep 25
	done
	echo "Sin cambios en el autoritativo: el panel donde se editó no es el que sirve la zona."
	echo "Prepara el archivo de registros para el usuario (references/correo-dns.md) y no des el correo por arreglado."
	exit 1
	;;
'') foto ;;
*) sed -n '2,17p' "$0"; exit 2 ;;
esac
