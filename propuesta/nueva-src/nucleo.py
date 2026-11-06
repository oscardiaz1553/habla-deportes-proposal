"""Utilidades compartidas por la plantilla y los componentes:
contexto de la página en construcción, iconos, imágenes y escudos.
"""
import html
import pathlib
import re

import imagenes
from datos import equipo

ICONOS = pathlib.Path(__file__).resolve().parent / "iconos"


class ErrorDeContenido(Exception):
    pass


class Pagina:
    """Estado de la página que se está generando (una a la vez)."""

    def __init__(self, slug, meta):
        self.slug = slug
        self.meta = meta
        self.iconos = set()
        self.imagenes = set()   # todas las claves de imagen usadas (para copiar)
        self.fotos = []         # inventario de fotos (regla: una vez por página)
        self.primera_foto = True
        self.ids = {}

    def registrar_foto(self, clave):
        if clave in self.fotos:
            raise ErrorDeContenido(
                f"{self.slug}.html: la foto '{clave}' se repite en la página (01 §7: una foto, una vez por página)")
        self.fotos.append(clave)

    def nuevo_id(self, base):
        n = self.ids.get(base, 0) + 1
        self.ids[base] = n
        return base if n == 1 else f"{base}-{n}"


P = None  # página actual; la fija build.py


def e(texto):
    return html.escape(str(texto), quote=True)


# --- Iconos (Phosphor Regular / Fill, sprite inline por página) -------------
def icono(nombre, clase="icon"):
    P.iconos.add(nombre)
    return f'<svg class="{clase}" aria-hidden="true" focusable="false"><use href="#i-{nombre}"></use></svg>'


def sprite(nombres):
    simbolos = []
    for n in sorted(nombres):
        svg = (ICONOS / f"{n}.svg").read_text(encoding="utf-8")
        cuerpo = re.sub(r"^<svg[^>]*>|</svg>\s*$", "", svg.strip())
        simbolos.append(f'<symbol id="i-{n}" viewBox="0 0 256 256" fill="currentColor">{cuerpo}</symbol>')
    return ('<svg class="icon-sprite" width="0" height="0" aria-hidden="true" focusable="false">'
            + "".join(simbolos) + "</svg>")


# --- Imágenes -------------------------------------------------------------
def img(clave, clase="", alt=None, prioridad=False, decorativa=False, sizes=None, menu=False):
    """<img> con width/height reales, lazy salvo la primera foto de la página.
    menu=True: foto del megamenú o de la hoja móvil. Queda fuera de la regla «una foto,
    una vez por página» (07: el panel se superpone y no se ve a la vez que el contenido),
    pero sigue saliendo SOLO del catálogo."""
    if clave not in imagenes.CATALOGO:
        raise ErrorDeContenido(f"{P.slug}.html: imagen desconocida '{clave}'")
    src, out, tipo, alt_cat, pos = imagenes.CATALOGO[clave]
    P.imagenes.add(clave)
    if tipo == "foto" and not menu:
        P.registrar_foto(clave)
    w, h = imagenes.dims(clave)
    texto_alt = "" if decorativa else (alt if alt is not None else alt_cat)
    atributos = [f'src="{imagenes.ruta(clave)}"', f'alt="{e(texto_alt)}"', f'width="{w}"', f'height="{h}"']
    if clase:
        atributos.insert(0, f'class="{clase}"')
    if pos:
        # Recorte por foto (01 §7). Es dato de contenido, no de estilo: se pasa como
        # variable CSS para que el tema WP lo pueda guardar por imagen.
        atributos.append(f'style="--focus: {pos}"')
    if prioridad:
        atributos.append('fetchpriority="high"')
    else:
        atributos.append('loading="lazy"')
    atributos.append('decoding="async"')
    return "<img " + " ".join(atributos) + ">"


def escudo(clave_equipo, clase="crest", con_alt=False):
    """Escudo de un equipo; si no hay archivo, monograma con la abreviatura."""
    eq = equipo(clave_equipo)
    if eq.get("escudo"):
        clave_img = eq["escudo"].rsplit(".", 1)[0]
        return img(clave_img, clase=clase, decorativa=not con_alt)
    return f'<span class="{clase} crest--mono" aria-hidden="true">{e(eq["abrev"])}</span>'


def enlace_externo(texto, url, clase="", icono_ext=True):
    flecha = icono("arrow-up-right", "icon icon--sm") if icono_ext else ""
    c = f' class="{clase}"' if clase else ""
    return (f'<a{c} href="{e(url)}" target="_blank" rel="noopener">{texto}{flecha}'
            f'<span class="visually-hidden"> (se abre en una pestaña nueva)</span></a>')
