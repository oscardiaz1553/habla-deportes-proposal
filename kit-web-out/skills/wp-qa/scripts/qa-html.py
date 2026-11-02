#!/usr/bin/env python3
# qa-html.py — revisión estática del HTML servido: landmarks, encabezados, nombres accesibles, alt,
# SVG decorativos, nav y formularios, interactivos anidados, ids duplicados y lang. M13 de ../references/mediciones.md.
# Propósito: lo que axe no ve o ve tarde (dos <main> con «Elementor ancho completo», títulos de columna
#   del pie en h4 tras un h2, <nav> sin aria-label propio, botones sin nombre) se caza leyendo el HTML
#   que sirve el servidor, sin navegador: sirve para subagentes.
# Uso: curl -s -A "$UA" "$BASE/<ruta>/?nocache=$RANDOM" > p.html && python3 qa-html.py p.html
#      (detrás de la cortina: el HTML de render.php de wp-seo-rendimiento, M17)
# Salida: lang, <main> (debe haber exactamente uno), skip-link y si su destino existe, h1 y saltos de
#   nivel, aria-label de cada <nav>, nombre de cada <form> y campos con autocomplete, problemas con su
#   línea (máx. 40) e ids duplicados.
# Estado: generalizado a partir de a11y.py, nest.py y dom.py (revisiones de HomeMaker); probado solo
#   en local con una página de muestra; no probado fuera de HomeMaker.
# Riesgos: ninguno (lee un archivo local). Solo biblioteca estándar.
import sys, re
from html.parser import HTMLParser
VOID = {'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'}
INTERACTIVO = {'a', 'button', 'input', 'select', 'textarea', 'details', 'iframe'}
class P(HTMLParser):
    def __init__(s):
        super().__init__(convert_charrefs=True)
        s.pila, s.ids, s.problemas = [], {}, []
        s.mains, s.heads, s.navs, s.forms, s.skip, s.lang = [], [], [], [], [], None
        s.autocomplete = 0
    def oculto(s):
        return any(n['a'].get('aria-hidden') == 'true' for n in s.pila)
    def handle_starttag(s, t, attrs):
        a = {k: (v or '') for k, v in attrs}; linea = s.getpos()[0]
        if t == 'html': s.lang = a.get('lang')
        if 'id' in a: s.ids.setdefault(a['id'], []).append(linea)
        if any(n['t'] == 'svg' for n in s.pila):
            if t not in VOID: s.pila.append({'t': t, 'a': a, 'txt': [], 'l': linea})
            return
        es_int = (t in INTERACTIVO and not (t == 'a' and 'href' not in a)) or a.get('role') in ('button', 'link') \
            or ('tabindex' in a and a['tabindex'] != '-1' and t not in INTERACTIVO)
        if es_int:
            for n in s.pila:
                if (n['t'] == 'a' and 'href' in n['a']) or n['t'] == 'button' or n['a'].get('role') in ('button', 'link'):
                    s.problemas.append(f"L{linea}: interactivo <{t}> dentro de <{n['t']}> (L{n['l']})"); break
        if t == 'main': s.mains.append((a.get('id'), linea))
        if t == 'a' and 'skip-link' in a.get('class', ''): s.skip.append(a.get('href', ''))
        if t == 'nav': s.navs.append(a.get('aria-label', '(sin aria-label)'))
        if t == 'form': s.forms.append(a.get('aria-label') or a.get('name') or '(sin nombre)')
        if t in ('input', 'select', 'textarea') and a.get('autocomplete'): s.autocomplete += 1
        if t == 'img':
            if 'alt' not in a and not s.oculto(): s.problemas.append(f"L{linea}: <img> sin atributo alt ({a.get('src', '')[-40:]})")
            for n in s.pila: n['txt'].append(a.get('alt', ''))
        if t == 'svg' and a.get('aria-hidden') != 'true' and a.get('role') != 'img' and not s.oculto():
            s.problemas.append(f"L{linea}: <svg> sin aria-hidden ni role=img")
        if t not in VOID: s.pila.append({'t': t, 'a': a, 'txt': [], 'l': linea})
    def handle_startendtag(s, t, attrs):
        s.handle_starttag(t, attrs)
        if t not in VOID and s.pila and s.pila[-1]['t'] == t: s.pila.pop()
    def handle_data(s, d):
        if s.pila and s.pila[-1]['t'] in ('script', 'style', 'noscript', 'template'): return
        for n in s.pila: n['txt'].append(d)
    def handle_endtag(s, t):
        if t in VOID: return
        for i in range(len(s.pila) - 1, -1, -1):
            if s.pila[i]['t'] == t:
                n = s.pila[i]; oculto = any(x['a'].get('aria-hidden') == 'true' for x in s.pila[:i + 1])
                del s.pila[i:]
                texto = ' '.join(' '.join(n['txt']).split())
                if re.fullmatch(r'h[1-6]', t): s.heads.append((int(t[1]), texto[:50], n['l']))
                if (t == 'button' or (t == 'a' and 'href' in n['a'])) and not oculto:
                    if not (n['a'].get('aria-label') or n['a'].get('aria-labelledby') or len(texto) > 1):
                        s.problemas.append(f"L{n['l']}: <{t}> sin nombre accesible ({n['a'].get('class', '')[:50]})")
                return
if len(sys.argv) < 2: sys.exit('uso: python3 qa-html.py pagina.html')
p = P(); p.feed(open(sys.argv[1], encoding='utf-8', errors='replace').read())
print('lang:', p.lang)
print('main:', p.mains, '→ debe haber exactamente uno' if len(p.mains) != 1 else '')
print('skip-link →', p.skip, '| destino existe:', all(h.lstrip('#') in p.ids for h in p.skip if h.startswith('#')))
h1 = [h for h in p.heads if h[0] == 1]
print('h1:', len(h1), [h[1] for h in h1])
prev = 0
for nivel, texto, linea in p.heads:
    if prev and nivel > prev + 1: print(f"  salto de encabezado h{prev}→h{nivel} en L{linea} «{texto}»")
    prev = nivel
print('nav aria-label:', p.navs, '(repetidos)' if len(set(p.navs)) != len(p.navs) else '')
print('formularios:', p.forms, '| campos con autocomplete:', p.autocomplete)
for x in p.problemas[:40]: print(' ', x)
dups = {k: v for k, v in p.ids.items() if len(v) > 1}
print('ids duplicados:', {k: v[:3] for k, v in list(dups.items())[:15]} or 'ninguno')
