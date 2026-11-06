#!/bin/bash
# checks-html.sh — las verificaciones «de una línea» de la sesión SEO/rendimiento, juntas, por URL.
# No sustituye a Lighthouse: comprueba que cada arreglo SIGUE en el HTML servido (tras un
# despliegue, una regeneración del segundo idioma, una migración o una actualización de Elementor).
#
# Estado: generalizado del caso de referencia (HomeMaker); no probado fuera de HomeMaker.
# Uso:   BASE=https://<STAGING_URL> ./checks-html.sh            (páginas de PAGINAS=./paginas.txt)
#        BASE=https://<STAGING_URL> ./checks-html.sh /ruta/ ...
# Esperado por URL (valores del caso; ajústalos a tu proyecto):
#   http 200 · set-cookie vacío y 2.ª petición HIT (si el hosting expone x-proxy-cache) ·
#   googleapis 0 · preload-font 2 · jquery-en-head 0 · ids-numericos-dup vacío · fetchpriority ≤ 1 ·
#   img-attr-dup 0 · robots/canonical/hreflang coherentes con blog_public · tipos del JSON-LD.
# Riesgos: solo lectura. Usa UA de navegador (algunos hostings retan al UA de curl).

[ -n "${BASE:-}" ] || { echo "Falta BASE=https://<STAGING_URL>" >&2; exit 1; }
BASE=${BASE%/}
UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'
if [ $# -gt 0 ]; then RUTAS=("$@"); else
  RUTAS=()
  while IFS= read -r l || [ -n "$l" ]; do
    case "$l" in ''|\#*) continue ;; esac
    l=$(printf '%s' "$l" | tr -d '[:space:]'); RUTAS+=("${l#*|}")
  done < "${PAGINAS:-./paginas.txt}"
fi
T=$(mktemp); trap 'rm -f "$T"' EXIT

for p in "${RUTAS[@]}"; do
  u="$BASE$p"
  echo "== $p"
  for i in 1 2; do
    printf '   cabeceras #%s: ' "$i"
    curl -s -A "$UA" -D - -o /dev/null "$u" | grep -iE '^(HTTP|set-cookie|x-proxy-cache:)' | tr -d '\r' | tr '\n' ' '; echo
  done
  curl -s -A "$UA" "$u" > "$T"
  printf '   googleapis=%s preload-font=%s jquery-en-head=%s fetchpriority-high=%s img-attr-dup=%s block-library=%s\n' \
    "$(grep -c 'fonts.googleapis' "$T")" \
    "$(grep -o 'as="font"' "$T" | wc -l | tr -d ' ')" \
    "$(sed -n '1,/<\/head>/p' "$T" | grep -c jquery)" \
    "$(grep -o 'fetchpriority="high"' "$T" | wc -l | tr -d ' ')" \
    "$(grep -o '<img[^>]*>' "$T" | grep -cE 'sizes=.*sizes=|loading=.*loading=|fetchpriority=.*fetchpriority=')" \
    "$(grep -c 'wp-block-library-css' "$T")"
  printf '   ids-numericos-dup: %s\n' "$(grep -o ' id="[0-9]*"' "$T" | sort | uniq -d | tr '\n' ' ')"
  sed -n '/<!-- This site is optimized/,/<!-- \/ Yoast SEO/p' "$T" \
    | grep -oE '<title>[^<]*|name="robots" content="[^"]*"|rel="canonical" href="[^"]*"|og:locale" content="[^"]*"' | sed 's/^/   /'
  grep -oE '<link rel="alternate" href="[^"]*" hreflang="[^"]*"' "$T" | sed 's/^/   /'
  python3 -c "import sys,re,json; h=open(sys.argv[1]).read(); m=re.search(r'yoast-schema-graph\">(.*?)</script>',h,re.S); print('   json-ld:', [n.get('@type') for n in json.loads(m.group(1))['@graph']] if m else 'sin grafo de Yoast')" "$T"
done

echo "== sitio"
printf '   sitemap_index.xml: '; curl -s -A "$UA" "$BASE/sitemap_index.xml" | grep -o '<loc>[^<]*' | sed 's/<loc>//' | tr '\n' ' '; echo
echo '   robots.txt:'; curl -s -A "$UA" "$BASE/robots.txt" | sed 's/^/     /'
