#!/bin/bash
# lh-run.sh — Lighthouse local en móvil y escritorio, N corridas en serie, con la caché calentada antes.
#
# Estado: generalizado del caso de referencia (HomeMaker); no probado fuera de HomeMaker.
# Uso:
#   BASE=https://<STAGING_URL> ./lh-run.sh <etiqueta> [n=3] [nombre|/ruta/ ...]
#   Sin pares en la línea de órdenes lee PAGINAS (por defecto ./paginas.txt, formato nombre|/ruta/).
# Salida: $OUT/<etiqueta>/<nombre>-<mobile|desktop>-r<n>.json (OUT=./psi por defecto) y la tabla
#   de lh-sum.py con la mediana (descarta corridas con RTT o latencia del origen > 300 ms).
# Variables:
#   LH_VERSION  versión de Lighthouse; usa la misma que PSI (campo lighthouseVersion de un informe PSI).
#   LH_HEADERS  ruta a un JSON {"Cookie":"nombre=valor"} para medir detrás de una cortina por cookie.
#               Créalo con permisos 600 y bórralo al terminar: contiene la llave.
#   LH_LOCALE   idioma del informe (es).
# Riesgos: cada corrida abre Chrome headless (20-40 s): 7 URL x 2 x 3 son unos 25 min. Con la cortina
#   activa y sin LH_HEADERS mides la cortina. No lances dos tandas a la vez: se contaminan.

ETQ=${1:?"uso: BASE=https://<STAGING_URL> $0 <etiqueta> [n] [nombre|/ruta/ ...]"}
N=${2:-3}
[ -n "${BASE:-}" ] || { echo "Falta BASE=https://<STAGING_URL>" >&2; exit 1; }
BASE=${BASE%/}
DIR=$(cd "$(dirname "$0")" && pwd)
OUT=${OUT:-./psi}/$ETQ
LHV=${LH_VERSION:-13.5.0}
LOC=${LH_LOCALE:-es}
mkdir -p "$OUT"

if [ $# -ge 3 ]; then
  PARES=("${@:3}")
else
  PAGINAS=${PAGINAS:-./paginas.txt}
  [ -f "$PAGINAS" ] || { echo "No existe $PAGINAS (copia paginas.ejemplo.txt)" >&2; exit 1; }
  PARES=()
  while IFS= read -r l || [ -n "$l" ]; do
    case "$l" in ''|\#*) continue ;; esac
    PARES+=("$(printf '%s' "$l" | tr -d '[:space:]')")
  done < "$PAGINAS"
fi

# 1. calentar la caché de página: dos peticiones por URL
for par in "${PARES[@]}"; do
  p=${par#*|}
  curl -s -o /dev/null "$BASE$p"; curl -s -o /dev/null "$BASE$p"
done

# 2. corridas en serie (nunca en paralelo: compiten por CPU y red)
for r in $(seq 1 "$N"); do
  for par in "${PARES[@]}"; do
    n=${par%%|*}; p=${par#*|}
    for f in mobile desktop; do
      args=(--quiet --output=json --output-path="$OUT/$n-$f-r$r.json" --chrome-flags="--headless=new --no-sandbox" --locale="$LOC")
      [ "$f" = desktop ] && args+=(--preset=desktop)
      [ -n "${LH_HEADERS:-}" ] && args+=(--extra-headers="$LH_HEADERS")
      npx -y "lighthouse@$LHV" "$BASE$p" "${args[@]}" >/dev/null 2>&1 || echo "fallo: $n $f r$r" >&2
    done
  done
done

python3 "$DIR/lh-sum.py" "$OUT" --origen "$BASE"
python3 "$DIR/lh-sum.py" "$OUT" --origen "$BASE" --mediana
