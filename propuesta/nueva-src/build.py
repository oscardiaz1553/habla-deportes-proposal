#!/usr/bin/env python3
"""Generador de la propuesta nueva de Habla Deportes.

    python3 propuesta/nueva-src/build.py

Lee paginas/*.html (cabecera de metadatos + contenido), sustituye las llamadas
{{ componente(argumentos) }} por HTML (componentes.py), envuelve el resultado
en la plantilla común (plantilla.py) y escribe propuesta/html/nueva/*.html.
Copia y convierte a WebP solo las imágenes usadas. Falla si una página repite
una foto, usa un componente desconocido o enlaza a un archivo que no existe.
Requiere Python 3.8+ y Pillow. Funciona en Windows, Linux y macOS.
"""
import ast
import pathlib
import re
import sys

AQUI = pathlib.Path(__file__).resolve().parent
sys.path.insert(0, str(AQUI))

import componentes  # noqa: E402
import imagenes  # noqa: E402
import nucleo  # noqa: E402
import plantilla  # noqa: E402
from sitio import PAGINAS, SITIO  # noqa: E402

FUENTES = AQUI / "paginas"
SALIDA = AQUI.parent / "html" / "nueva"
LLAMADA = re.compile(r"\{\{\s*([a-z_0-9]+)\((.*?)\)\s*\}\}", re.S)


def leer_pagina(ruta):
    """Separa la cabecera de metadatos (entre líneas '---') del contenido."""
    texto = ruta.read_text(encoding="utf-8")
    m = re.match(r"---\n(.*?)\n---\n", texto, re.S)
    if not m:
        raise nucleo.ErrorDeContenido(f"{ruta.name}: falta la cabecera de metadatos entre '---'")
    meta = {}
    for linea in m.group(1).splitlines():
        if linea.strip() and not linea.lstrip().startswith("#"):
            k, _, v = linea.partition(":")
            meta[k.strip()] = v.strip()
    return meta, texto[m.end():]


def _argumentos(texto):
    arbol = ast.parse(f"f({texto})", mode="eval").body
    args = [ast.literal_eval(a) for a in arbol.args]
    kwargs = {k.arg: ast.literal_eval(k.value) for k in arbol.keywords}
    return args, kwargs


def renderizar(cuerpo, slug):
    def sustituir(m):
        nombre, texto = m.group(1), m.group(2)
        if nombre not in componentes.REGISTRO:
            raise nucleo.ErrorDeContenido(f"{slug}.html: componente desconocido '{nombre}'")
        args, kwargs = _argumentos(texto)
        return componentes.REGISTRO[nombre](*args, **kwargs)
    return LLAMADA.sub(sustituir, cuerpo)


def jsonld(slug, meta):
    org = {"@type": "NewsMediaOrganization", "name": "Habla Deportes", "url": SITIO["url"],
           "logo": SITIO["url"] + "assets/img/logo-habla-blanco.webp",
           "sameAs": ["https://www.youtube.com/", "https://www.instagram.com/", "https://x.com/"]}
    url = SITIO["url"] + meta.get("url_wp", "")
    grafo = []
    tipo = meta.get("tipo", "pagina")
    if tipo == "portada":
        grafo += [dict(org, **{"@id": SITIO["url"] + "#org"}),
                  {"@type": "WebSite", "name": "Habla Deportes", "url": SITIO["url"], "inLanguage": "es-CO",
                   "publisher": {"@id": SITIO["url"] + "#org"},
                   "potentialAction": {"@type": "SearchAction", "target": SITIO["url"] + "?s={consulta}",
                                       "query-input": "required name=consulta"}}]
    elif tipo == "articulo":
        grafo.append({"@type": "NewsArticle", "headline": meta["titular"], "description": meta["descripcion"],
                      "datePublished": meta["publicado"], "inLanguage": "es-CO",
                      "image": [SITIO["url"] + meta.get("og_imagen", "")],
                      "author": {"@type": "Person", "name": meta["autor"]},
                      "publisher": org, "mainEntityOfPage": url})
    else:
        grafo.append({"@type": "WebPage", "name": meta["titulo"], "description": meta["descripcion"],
                      "url": url, "inLanguage": "es-CO", "isPartOf": {"@type": "WebSite", "name": "Habla Deportes"}})
    if meta.get("migas"):
        niveles = [x.split(">") for x in meta["migas"].split("|")]
        grafo.append({"@type": "BreadcrumbList", "itemListElement": [
            {"@type": "ListItem", "position": i + 1, "name": n.strip(), "item": SITIO["url"] + u.strip()}
            for i, (n, u) in enumerate(niveles)]})
    return {"@context": "https://schema.org", "@graph": grafo}


def comprobar(slug, html, existentes):
    """Comprobaciones de contenido que no dependen del navegador."""
    errores = []
    h1 = len(re.findall(r"<h1[\s>]", html))
    if h1 != 1:
        errores.append(f"{slug}.html: tiene {h1} h1 (debe tener 1)")
    for href in re.findall(r'href="([^"]+)"', html):
        if href.startswith(("http", "mailto:", "tel:", "#", "data:")):
            continue
        archivo = href.split("#")[0].split("?")[0]
        if archivo and archivo not in existentes and not (SALIDA / archivo).exists():
            errores.append(f"{slug}.html: enlace a un archivo que no existe: {href}")
    if 'href="#"' in html:
        errores.append(f"{slug}.html: hay un href=\"#\"")
    if re.search(r"[—–]", re.sub(r"<script.*?</script>|<!--.*?-->", "", html, flags=re.S)):
        errores.append(f"{slug}.html: contiene raya (— o –); usar guion o reescribir")
    return errores


def main():
    fuentes = sorted(FUENTES.glob("*.html"))
    slugs = [f.stem for f in fuentes]
    faltan = set(PAGINAS) - set(slugs)
    if faltan:
        sys.exit(f"Faltan fuentes en paginas/: {', '.join(sorted(faltan))}")
    existentes = {f"{s}.html" for s in slugs} | {"assets/css/" + c + ".css" for c in plantilla.CSS}
    SALIDA.mkdir(parents=True, exist_ok=True)
    usadas, errores = set(), []
    for ruta in fuentes:
        slug = ruta.stem
        meta, cuerpo = leer_pagina(ruta)
        nucleo.P = nucleo.Pagina(slug, meta)
        try:
            contenido = renderizar(cuerpo, slug)
            html = plantilla.documento(slug, meta, contenido.strip(), jsonld(slug, meta))
        except nucleo.ErrorDeContenido as err:
            errores.append(str(err))
            continue
        errores += comprobar(slug, html, existentes)
        usadas |= nucleo.P.imagenes
        (SALIDA / f"{slug}.html").write_text(html, encoding="utf-8")
        fotos = ", ".join(nucleo.P.fotos) or "sin fotos"
        print(f"  {slug + '.html':20} {meta.get('estado', 'completa'):12} fotos: {fotos}")
    nuevas = imagenes.convertir(usadas, SALIDA / "assets" / "img")
    print(f"Imágenes: {len(usadas)} usadas, {len(nuevas)} convertidas en esta pasada.")
    if errores:
        print("\nERRORES:")
        for x in errores:
            print("  - " + x)
        sys.exit(1)
    print(f"OK: {len(slugs)} páginas en {SALIDA}")


if __name__ == "__main__":
    main()
