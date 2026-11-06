#!/usr/bin/env python3
"""probar-301.py — simula un mapa de redirecciones (líneas RewriteRule de .htaccess) ANTES de subirlo.

Comprueba dos cosas: que cada URL vieja llega a su destino esperado (o a 410) y que NINGUNA URL del
sitio nuevo queda capturada por una regla (en el caso de referencia, la primera versión del mapa se
comía la nueva /store-locator/).

Estado: generalizado del probador del caso de referencia (HomeMaker); probado en local con su mapa.
Uso: python3 probar-301.py <mapa.conf> <viejas.tsv> <nuevas.txt>
  viejas.tsv  «/url-vieja/<TAB>/destino-esperado» o «/url-vieja/<TAB>410», una por línea
  nuevas.txt  una ruta del sitio nuevo por línea: TODAS las páginas y singles de ambos idiomas,
              /wp-login.php, /<LOGIN_SLUG>, /wp-json/, /sitemap_index.xml, /robots.txt…
Imita .htaccess: se compara la ruta sin la barra inicial y sin la query, gana la primera regla que
casa (todas llevan L), NC = sin distinguir mayúsculas y [G] = 410.
Limitaciones: ignora RewriteCond y variables de servidor; no sigue cadenas de redirección. El
resultado real se comprueba después en vivo con curl -o /dev/null -w "%{http_code} %{redirect_url}".
"""
import re
import sys

if len(sys.argv) < 4:
    sys.exit(__doc__)

reglas = []
for linea in open(sys.argv[1]):
    t = linea.strip()
    if not t.startswith('RewriteRule'):
        continue
    partes = t.split()
    patron, destino = partes[1], partes[2]
    flags = partes[3].strip('[]').upper().split(',') if len(partes) > 3 else []
    reglas.append((re.compile(patron, re.I if 'NC' in flags else 0), destino, 'G' in flags, t))


def aplicar(url):
    s = url.split('?', 1)[0].lstrip('/')
    for rx, destino, gone, _ in reglas:
        if rx.search(s):
            return '410' if gone else (None if destino == '-' else destino)
    return None


fallos = []
viejas = [l.rstrip('\n').split('\t') for l in open(sys.argv[2]) if l.strip() and not l.startswith('#')]
for v in viejas:
    if len(v) < 2:
        fallos.append((v[0], 'línea sin destino esperado')); continue
    r = aplicar(v[0])
    if r != v[1].strip():
        fallos.append((v[0], f'da {r}, se esperaba {v[1].strip()}'))
nuevas = [l.strip() for l in open(sys.argv[3]) if l.strip() and not l.startswith('#')]
for n in nuevas:
    r = aplicar(n)
    if r:
        fallos.append((n, f'URL NUEVA CAPTURADA → {r}'))

print(f'reglas {len(reglas)} | viejas {len(viejas)} | nuevas {len(nuevas)}')
print('fallos:', 'ninguno' if not fallos else '')
for u, m in fallos:
    print(f'  {u}  {m}')
sys.exit(1 if fallos else 0)
