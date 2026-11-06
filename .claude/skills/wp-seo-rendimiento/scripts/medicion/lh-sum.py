#!/usr/bin/env python3
"""lh-sum.py — resume informes de Lighthouse o PSI (JSON del LHR).

Estado: generalizado del caso de referencia (HomeMaker); probado en local con sus informes.
Uso:
  python3 lh-sum.py <carpeta|informe.json ...> [--origen https://<STAGING_URL>] [--mediana] [--max-red 300]

Por informe imprime: P/A/BP/SEO, FCP/LCP/TBT/CLS/SI, rtt y srv (latencia de servidor) del origen,
formFactor y fetchTime. Marca «FF!» cuando el nombre del archivo dice mobile/desktop y el informe
dice lo contrario (así se detectó que una captura «de escritorio» era la de móvil).
--mediana agrupa por <nombre>-<mobile|desktop> (quita el sufijo -rN) y da la mediana de cada
columna con las corridas cuyo rtt y srv del origen no pasan de --max-red ms (ruido de red: la
simulación de Lighthouse traslada al resultado el RTT medido desde tu equipo).
--origen: origen de las columnas rtt/srv; por defecto, el de la URL final de cada informe.
"""
import glob
import json
import os
import re
import statistics
import sys
from urllib.parse import urlsplit

CATS = ['performance', 'accessibility', 'best-practices', 'seo']
METR = ['first-contentful-paint', 'largest-contentful-paint', 'total-blocking-time', 'cumulative-layout-shift', 'speed-index']
CORTO = {'first-contentful-paint': 'FCP', 'largest-contentful-paint': 'LCP', 'total-blocking-time': 'TBT',
         'cumulative-layout-shift': 'CLS', 'speed-index': 'SI'}


def args():
    a = sys.argv[1:]
    o = {'origen': None, 'mediana': False, 'max': 300.0, 'rutas': []}
    i = 0
    while i < len(a):
        if a[i] == '--origen':
            o['origen'] = a[i + 1].rstrip('/'); i += 2
        elif a[i] == '--max-red':
            o['max'] = float(a[i + 1]); i += 2
        elif a[i] == '--mediana':
            o['mediana'] = True; i += 1
        else:
            o['rutas'].append(a[i]); i += 1
    if not o['rutas']:
        sys.exit(__doc__)
    return o


def archivos(rutas):
    out = []
    for r in rutas:
        out += sorted(glob.glob(os.path.join(r, '*.json'))) if os.path.isdir(r) else [r]
    return out


def origen_de(d, forzado):
    if forzado:
        return forzado
    u = d.get('finalDisplayedUrl') or d.get('finalUrl') or d.get('requestedUrl') or ''
    s = urlsplit(u)
    return f'{s.scheme}://{s.netloc}' if s.netloc else None


def leer(f, forzado):
    d = json.load(open(f))
    if d.get('runtimeError') and not d.get('categories'):
        return {'f': f, 'error': d['runtimeError'].get('code')}
    c, a = d['categories'], d['audits']
    o = origen_de(d, forzado)
    rtt = {i.get('origin'): i.get('rtt') for i in a.get('network-rtt', {}).get('details', {}).get('items', [])}.get(o)
    srv = {i.get('origin'): i.get('serverResponseTime') for i in a.get('network-server-latency', {}).get('details', {}).get('items', [])}.get(o)
    ff = (d.get('configSettings') or {}).get('formFactor', '?')
    base = os.path.basename(f)[:-5]
    esperado = 'desktop' if 'desktop' in base else ('mobile' if 'mobile' in base else None)
    return {
        'f': f, 'nombre': base,
        's': {k: round((c[k]['score'] or 0) * 100) for k in CATS if k in c},
        'txt': {k: a.get(k, {}).get('displayValue', '') for k in METR},
        'num': {k: a.get(k, {}).get('numericValue') for k in METR},
        'rtt': rtt, 'srv': srv, 'ff': ff, 'fetch': d.get('fetchTime', ''),
        'ffmal': esperado is not None and ff != esperado,
    }


def red(x):
    return f"rtt {x['rtt']:.0f} srv {x['srv']:.0f}" if x['rtt'] is not None and x['srv'] is not None else 'rtt ? srv ?'


def por_informe(filas):
    for x in filas:
        if 'error' in x:
            print(f"{os.path.basename(x['f']):28} ERROR {x['error']}"); continue
        s, t = x['s'], x['txt']
        print(f"{x['nombre']:28} P{s.get('performance', '-'):>4} A{s.get('accessibility', '-'):>4} "
              f"BP{s.get('best-practices', '-'):>4} SEO{s.get('seo', '-'):>4} | "
              + ' '.join(f"{CORTO[k]} {t[k]:>7}" for k in METR)
              + f" | {red(x)} | {x['ff']} {x['fetch'][:19]}{'  FF!' if x['ffmal'] else ''}")


def mediana(filas, maxred):
    grupos = {}
    for x in filas:
        if 'error' in x:
            continue
        grupos.setdefault(re.sub(r'-r\d+$', '', x['nombre']), []).append(x)
    print(f"--- mediana (descarta rtt/srv del origen > {maxred:.0f} ms)")
    for g, xs in sorted(grupos.items()):
        ok = [x for x in xs if (x['rtt'] or 0) <= maxred and (x['srv'] or 0) <= maxred]
        malos = [x for x in xs if x['ffmal']]
        if not ok:
            print(f"{g:24} sin corridas válidas ({len(xs)} descartadas por red)"); continue
        m = lambda v: statistics.median(v) if v else None
        sc = {k: m([x['s'][k] for x in ok if k in x['s']]) for k in CATS}
        nu = {k: m([x['num'][k] for x in ok if x['num'][k] is not None]) for k in METR}
        fmt = lambda k, v: '-' if v is None else (f"{v:.3f}" if k == 'cumulative-layout-shift' else f"{v / 1000:.1f}s" if k != 'total-blocking-time' else f"{v:.0f}ms")
        n = lambda v: '-' if v is None else f"{v:g}"
        print(f"{g:24} P{n(sc['performance']):>5} A{n(sc['accessibility']):>5} BP{n(sc['best-practices']):>5} SEO{n(sc['seo']):>5} | "
              + ' '.join(f"{CORTO[k]} {fmt(k, nu[k]):>7}" for k in METR)
              + f" | {len(ok)}/{len(xs)} corridas" + (f" | FF! en {len(malos)}" if malos else ''))


if __name__ == '__main__':
    o = args()
    filas = []
    for f in archivos(o['rutas']):
        try:
            filas.append(leer(f, o['origen']))
        except Exception as e:  # informe corrupto o a medias
            filas.append({'f': f, 'error': str(e)[:80]})
    mediana(filas, o['max']) if o['mediana'] else por_informe(filas)
