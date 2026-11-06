#!/usr/bin/env python3
# qa-cmp.py — compara dos salidas de qa-medir.mjs con tolerancia en px (M6 de ../references/mediciones.md).
# Propósito: decir, por página y ancho, qué sección se sale de la tolerancia (x, y, ancho, alto), si
#   cambió el alto del documento o el número de secciones, y si el segundo archivo tiene scroll
#   horizontal, contenido visible con opacidad 0 o errores de consola. Con --todo compara además
#   encabezados, textos, botones e imágenes (geometría y tipografía computada).
# Uso: python3 qa-cmp.py prototipo.json wp.json 1        (fidelidad en 1440, 1280, 768 y 375; idioma 2)
#      python3 qa-cmp.py prototipo.json wp.json 3        (fidelidad solo en anchos intermedios: 1100, 900, bordes de breakpoint)
#      python3 qa-cmp.py antes.json despues.json 1 --todo (regresión: además encabezados, textos, botones e imágenes)
# Tolerancia canónica (wp-elementor-replica/references/verificacion.md): objetivo ≤1 px; se aceptan
#   ≤3 px por sección solo en anchos intermedios; idioma 2 frente al idioma base, ≤1 px; regresión, ±1 px.
# Formato: solo el de qa-medir.mjs (no el de snap.mjs, que se compara con cmp.py de wp-seo-rendimiento).
#   Recorre las claves del primer archivo: lo que solo esté en el segundo no se compara.
# Estado: generalizado a partir de cmp.py (regresión visual de la sesión SEO de HomeMaker); probado
#   solo en local con salidas de muestra; no probado fuera de HomeMaker.
# Riesgos: ninguno (lee dos JSON locales). Solo biblioteca estándar.
import json, sys
pos = [x for x in sys.argv[1:] if not x.startswith('--')]
if len(pos) < 2: sys.exit('uso: python3 qa-cmp.py a.json b.json [tolerancia_px=1] [--todo]')
a, b = json.load(open(pos[0], encoding='utf-8')), json.load(open(pos[1], encoding='utf-8'))
tol = float(pos[2]) if len(pos) > 2 else 1.0
todo = '--todo' in sys.argv
for k in a:
    A, B = a[k], b.get(k, {'error': 'no está en el segundo archivo'})
    if 'error' in A or 'error' in B:
        print(k, 'ERROR', A.get('error'), B.get('error')); continue
    d = []
    if abs(A['doc'] - B['doc']) > tol: d.append(f"alto del documento {A['doc']}→{B['doc']}")
    if B.get('desborde'): d.append(f"scroll horizontal: scrollWidth {B['sw']} > {B['util']}")
    if len(A['secs']) != len(B['secs']): d.append(f"secciones {len(A['secs'])}→{len(B['secs'])}")
    for i, (x, y) in enumerate(zip(A['secs'], B['secs'])):
        if any(abs(float(x[j]) - float(y[j])) > tol for j in range(4)):
            d.append(f"sección {i + 1} [x,y,w,h] {x}→{y}")
    if todo:
        for kind, ia in (('heads', 2), ('imgs', 1)):
            la, lb = A[kind], B[kind]
            if len(la) != len(lb): d.append(f"{kind}: {len(la)}→{len(lb)} elementos")
            for x, y in zip(la, lb):
                if any(abs(float(x[ia + j]) - float(y[ia + j])) > tol for j in range(4)):
                    d.append(f"{kind} {x[:2] if kind == 'heads' else x[0]} {x[ia:ia + 4]}→{y[ia:ia + 4]}")
                if kind == 'heads' and x[ia + 4:] != y[ia + 4:]:
                    d.append(f"tipografía {x[:2]} {x[ia + 4:]}→{y[ia + 4:]}")
    if B.get('ocultos'): d.append(f"{B['ocultos']} elementos visibles con opacidad 0")
    if B.get('consola'): d.append(f"consola: {B['consola'][:3]}")
    print(f"{k:50} {'OK' if not d else str(len(d)) + ' diferencias'}")
    for x in d[:10]: print('    ', x)
