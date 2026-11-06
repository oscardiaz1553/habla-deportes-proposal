#!/usr/bin/env python3
"""cmp.py — compara dos fotos de snap.mjs y marca cada diferencia de geometría mayor que la tolerancia.

Estado: copiado del caso de referencia (HomeMaker) sin cambios de lógica; probado en local.
Uso: python3 cmp.py antes.json despues.json [tolerancia_px=1]
Salida: una línea por <ancho><ruta> con OK o «N diffs» y hasta 8 diferencias (alto del documento,
scrollWidth, número de encabezados/imágenes, caja de cada uno). Un cambio «invisible» debe dar OK
en todas las páginas y anchos.
"""
import json
import sys

if len(sys.argv) < 3:
    sys.exit(__doc__)
a, b = json.load(open(sys.argv[1])), json.load(open(sys.argv[2]))
tol = float(sys.argv[3]) if len(sys.argv) > 3 else 1.0
for k in a:
    A, B = a[k], b.get(k, {})
    if 'error' in A or 'error' in B:
        print(k, 'ERROR', A.get('error'), B.get('error')); continue
    if not B:
        print(k, 'FALTA en', sys.argv[2]); continue
    diffs = []
    if abs(A['doc'] - B['doc']) > tol: diffs.append(f"doc {A['doc']}→{B['doc']}")
    if A['sw'] != B['sw']: diffs.append(f"scrollWidth {A['sw']}→{B['sw']}")
    for kind in ('heads', 'imgs'):
        la, lb = A[kind], B[kind]
        if len(la) != len(lb): diffs.append(f"{kind} count {len(la)}→{len(lb)}")
        for x, y in zip(la, lb):
            ia = 2 if kind == 'heads' else 1
            if any(abs(float(x[ia + j]) - float(y[ia + j])) > tol for j in range(4)):
                diffs.append(f"{kind} {x[:2] if kind == 'heads' else x[0]} {x[ia:ia + 4]}→{y[ia:ia + 4]}")
    print(f"{k:55} {'OK' if not diffs else str(len(diffs)) + ' diffs'}")
    for d in diffs[:8]: print('    ', d)
