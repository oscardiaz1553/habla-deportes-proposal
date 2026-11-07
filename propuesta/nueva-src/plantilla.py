"""Plantilla común (equivale a header.php / footer.php de un tema WordPress).

Partes, en orden de documento:
  <head> · enlace de salto · cabecera (nivel 1 azul + nivel 2 con megamenús +
  buscador + hoja del menú móvil) · velo del megamenú · <main> (contenido de la
  página) · Publicidad C · pie (con el muro de aliados) · barra inferior móvil.
"""
import json

import nucleo
from componentes import (aliados, barra_inferior, etiqueta_nav, hoja_menu, megamenu, nav_label, pauta,
                         punto_vivo)
from nucleo import e, icono, img
from sitio import PAGINAS, PANELES, PIE, PRIMARIO, REDES, SITIO, SUGERENCIAS

CSS = ["tokens", "base", "components", "pages", "pages-a", "pages-b"]


# --------------------------------------------------------------------------
# <head>
# --------------------------------------------------------------------------
def head(meta, jsonld):
    titulo = meta["titulo"]
    desc = meta["descripcion"]
    og_img = meta.get("og_imagen", SITIO["og_imagen"])
    tipo = "article" if meta.get("tipo") == "articulo" else "website"
    url = SITIO["url"] + meta.get("url_wp", "")
    hojas = "\n".join(f'  <link rel="stylesheet" href="assets/css/{c}.css">' for c in CSS)
    ld = json.dumps(jsonld, ensure_ascii=False, indent=1)
    return f"""<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>{e(titulo)}</title>
  <meta name="description" content="{e(desc)}">
  <link rel="canonical" href="{e(url)}">
  <meta name="theme-color" content="#294D6E">
  <meta property="og:type" content="{tipo}">
  <meta property="og:site_name" content="Habla Deportes">
  <meta property="og:locale" content="es_CO">
  <meta property="og:title" content="{e(meta.get('og_titulo', titulo))}">
  <meta property="og:description" content="{e(desc)}">
  <meta property="og:url" content="{e(url)}">
  <meta property="og:image" content="{e(og_img)}">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="icon" href="data:,">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{hojas}
  <script src="assets/js/main.js" defer></script>
  <script type="application/ld+json">
{ld}
  </script>
</head>"""


# --------------------------------------------------------------------------
# Cabecera (08 §2): nivel 1 azul (logo, buscador, redes, «Menú») + nivel 2
# azul (secciones con megamenú). Sin barra blanca contextual.
# --------------------------------------------------------------------------
def _primario(cfg, slug):
    """Barra azul. Activo: aria-current="page" (o "true" si el destino no es esta misma
    página) + bold + barra de 3 px. Inicio no tiene panel ni chevron; los demás, sí."""
    out = []
    for it in PRIMARIO:
        clave = it["clave"]
        cur = ""
        if cfg["primario"] == clave:
            cur = ' aria-current="page"' if it["href"] == f"{slug}.html" else ' aria-current="true"'
        punto = punto_vivo(it["punto"], "live-dot live-dot--ring") if it.get("punto") else ""
        temas = f' data-temas="{e(it["temas"])}"' if it.get("temas") else ""
        if clave not in PANELES:
            out.append(f'<li class="primary-nav__item" data-nav="{clave}"{temas}><a class="primary-nav__link" href="{e(it["href"])}"{cur}>'
                       f'{nav_label(it["texto"])}</a></li>')
            continue
        extra = '<span class="visually-hidden">: más secciones en el pie de página</span>' if clave == "mas" else ""
        out.append(f'<li class="primary-nav__item" data-mega-item data-nav="{clave}"{temas}>'
                   f'<a class="primary-nav__link" href="{e(it["href"])}"{cur}>{punto}{etiqueta_nav(it)}{extra}'
                   f'{icono("caret-down", "icon icon--sm chevron")}</a>'
                   f'{megamenu(clave, slug)}</li>')
    return "".join(out)


def _buscador():
    """Opción B del dueño: campo con línea inferior, sin caja. En escritorio siempre visible;
    en móvil se abre con la lupa y ocupa la cabecera (reemplaza al logo mientras se busca).
    Un solo formulario para los dos tamaños. El atajo «/» enfoca el campo (main.js)."""
    return (
        f'<form class="header-search" action="buscar.html" method="get" role="search" aria-label="Buscar en la cabecera" data-search>'
        f'<button class="icon-btn icon-btn--on-brand header-search__back" type="button" data-search-close>'
        f'{icono("arrow-left")}<span class="visually-hidden">Cerrar la búsqueda</span></button>'
        f'<label class="visually-hidden" for="q">Buscar en Habla Deportes</label>'
        f'<span class="header-search__field">{icono("magnifying-glass", "icon icon--md header-search__icon")}'
        f'<input class="header-search__input" id="q" name="q" type="search" placeholder="Buscar" '
        f'autocomplete="off" enterkeyhint="search">'
        f'<kbd class="header-search__hint" aria-hidden="true">/</kbd></span></form>')


def cabecera(slug):
    cfg = PAGINAS[slug]
    redes_cab = "".join(
        f'<a class="icon-btn icon-btn--on-brand masthead__social" href="{r["url"]}" target="_blank" rel="noopener">'
        f'{icono(r["icono"])}<span class="visually-hidden">{r["nombre"]} (se abre en una pestaña nueva)</span></a>'
        for r in REDES[:3])
    return f"""<a class="skip-link" href="#contenido">Saltar al contenido</a>
<!-- CABECERA (header.php): nivel 1 + nivel 2 con megamenús + buscador + hoja móvil -->
<header class="site-header" data-header>
  <div class="masthead" data-masthead>
    <div class="container masthead__inner">
      <a class="masthead__logo" href="index.html">{img("logo-blanco", clase="masthead__logo-img", alt="Habla Deportes, ir a la portada", prioridad=True)}</a>
      <div class="masthead__tools">
        {_buscador()}
        <button class="search-trigger" type="button" aria-expanded="false" aria-controls="q" data-search-toggle>{icono("magnifying-glass", "icon icon--md")}<span class="visually-hidden">Buscar</span></button>
        {redes_cab}
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu-movil" data-menu-toggle>{icono("list", "icon icon-open")}{icono("x", "icon icon-close")}<span class="menu-toggle__text" aria-hidden="true" data-menu-word>Menú</span><span class="visually-hidden" data-menu-label>Abrir el menú</span></button>
      </div>
    </div>
  </div>
  <nav class="primary-nav" aria-label="Secciones" data-mega>
    <div class="container primary-nav__inner">
      <a class="primary-nav__logo" href="index.html">{img("logo-blanco", clase="primary-nav__logo-img", alt="Habla Deportes, ir a la portada", prioridad=True)}</a>
      <ul class="primary-nav__list" role="list">{_primario(cfg, slug)}</ul>
    </div>
  </nav>
  {hoja_menu(slug)}
</header>
<div class="mega-scrim" data-mega-scrim></div>"""


# --------------------------------------------------------------------------
# Pie y documento (la barra inferior se genera en componentes.barra_inferior)
# --------------------------------------------------------------------------
def pie():
    cols = "".join(
        f'<div class="site-footer__col"><h2 class="site-footer__heading">{e(t)}</h2><ul role="list">'
        + "".join(f'<li><a href="{e(h)}">{e(x)}</a></li>' for x, h in links) + "</ul></div>"
        for t, links in PIE)
    redes = "".join(
        f'<li><a class="icon-btn icon-btn--on-dark" href="{r["url"]}" target="_blank" rel="noopener">{icono(r["icono"])}'
        f'<span class="visually-hidden">{r["nombre"]} (se abre en una pestaña nueva)</span></a></li>' for r in REDES)
    return f"""<footer class="site-footer" id="pie">
  <div class="container">
    <div class="site-footer__brand">
      <a class="site-footer__logo" href="index.html">{img("logo-blanco", clase="site-footer__logo-img", alt="Habla Deportes, ir a la portada")}</a>
      <p class="site-footer__about">Fútbol colombiano desde Barranquilla, con Junior y la Selección en el centro. El show de las mañanas, de lunes a viernes de 10:00 a. m. a 12:00 m.</p>
      <ul class="site-footer__social" role="list" aria-label="Habla Deportes en redes">{redes}</ul>
    </div>
    <nav class="site-footer__nav" id="pie-enlaces" aria-label="Pie de página">{cols}</nav>
    <div class="site-footer__allies">
      <h2 class="site-footer__heading" id="pie-aliados">Nuestros aliados</h2>
      {aliados("pie")}
    </div>
    <div class="site-footer__base">
      <p class="site-footer__legal">© 2026 Habla Deportes · Barranquilla, Colombia. Sitio de ejemplo para la propuesta de rediseño: datos, nombres y cifras son ilustrativos.</p>
    </div>
  </div>
</footer>"""


def documento(slug, meta, cuerpo, jsonld):
    cierre = meta.get("pauta_cierre", "si") == "si"
    c = f'\n{pauta("cierre")}' if cierre else ""
    partes = [
        "<!doctype html>",
        f'<html lang="{SITIO["idioma"]}" data-aire="no" data-page="{slug}">',
        head(meta, jsonld),
        "<body>",
        cabecera(slug),
        f'<main id="contenido" class="site-main" tabindex="-1">\n{cuerpo}\n</main>{c}',
        pie(),
        barra_inferior(slug),
        nucleo.sprite(nucleo.P.iconos),
        "</body>",
        "</html>",
    ]
    return "\n".join(partes) + "\n"
