#!/usr/bin/env python3
# qa-es-en.py — restos del idioma base (español) en el HTML de la versión EN: texto visible y atributos.
# M14 de ../references/mediciones.md.
# Propósito: en el segundo idioma quedan textos del base donde nadie mira: alt, aria-label, title,
#   placeholder, content de metas y data-<PREFIJO>-* que lee el JS del child.
# Uso: curl -s -A "$UA" "$BASE/<IDIOMA_2>/<ruta>/?nocache=$RANDOM" > en-page.html && python3 qa-es-en.py en-page.html [<PREFIJO>=pfx]
# Salida: lista ordenada de palabras sospechosas (con tilde, ñ, ¿, ¡ o de la lista de palabras
#   frecuentes del español). Revisa cada una a mano: nombres propios y marcas pueden ser correctos.
# Estado: probado en HomeMaker (revisión EN, con los data-* del prefijo del caso escritos a mano); aquí
#   el prefijo es un argumento. Las listas sirven para el par es→en: adáptalas a otro par de idiomas.
# Riesgos: ninguno (lee un archivo local). Solo biblioteca estándar.
import re, html, sys
s = open(sys.argv[1] if len(sys.argv) > 1 else 'en-page.html', encoding='utf-8', errors='replace').read()
body = re.sub(r'(?s)<(script|style|noscript)[^>]*>.*?</\1>', '', s)
pref = sys.argv[2] if len(sys.argv) > 2 else 'pfx'  # <PREFIJO> del proyecto
extra = '|data-' + re.escape(pref) + r'-[a-z-]+' if pref else ''
attrs = re.findall(r'(?:alt|aria-label|title|placeholder|content' + extra + r')="([^"]*)"', body)
pool = html.unescape(re.sub(r'<[^>]+>', ' ', body)) + ' ' + ' '.join(html.unescape(a) for a in attrs)
stop = {'de', 'del', 'la', 'las', 'los', 'el', 'y', 'con', 'para', 'por', 'recetas', 'productos', 'inicio', 'contacto', 'ver', 'más', 'idioma', 'enviar', 'nombre', 'mensaje'}
print(sorted({w for w in re.findall(r"[A-Za-zÁÉÍÓÚáéíóúÑñÜü¿¡]+", pool) if re.search(r'[áéíóúñ¿¡]', w, re.I) or w.lower() in stop}))
