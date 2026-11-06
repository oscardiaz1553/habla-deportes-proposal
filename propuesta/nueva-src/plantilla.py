"""Plantilla común (equivale a header.php / footer.php de un tema WordPress).

Partes, en orden de documento:
  <head> · enlace de salto · cabecera (nivel 1 azul + nivel 2 menú + barra
  contextual blanca + buscador + menú móvil) · <main> (contenido de la página)
  · Publicidad C · pie (con el muro de aliados) · barra inferior móvil.
"""
import json

import nucleo
from componentes import aliados, pauta
from nucleo import e, icono, img
from sitio import CONTEXTO, INFERIOR, MAS, MENU_MOVIL, PAGINAS, PIE, PRIMARIO, REDES, SITIO

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
# Cabecera
# --------------------------------------------------------------------------
def _panel(items, clase=""):
    li = "".join(f'<li><a class="panel__link" href="{e(h)}">{e(t)}</a></li>' for t, h in items)
    return f'<div class="panel{clase}"><ul class="panel__list" role="list">{li}</ul></div>'


def _primario(cfg):
    out = []
    for clave, texto, destino in PRIMARIO:
        cur = ' aria-current="page"' if cfg["primario"] == clave else ""
        extra = ""
        if clave == "en-vivo":
            # Punto rojo solo cuando el programa está al aire (main.js, constante EN_VIVO)
            extra = ('<span class="live-dot live-dot--nav only-aire" hidden aria-hidden="true"></span>'
                     '<span class="visually-hidden only-aire" hidden>, al aire ahora</span>')
        out.append(f'<li class="primary-nav__item"><a class="primary-nav__link" href="{destino}"{cur}>'
                   f'{e(texto)}{extra}</a></li>')
    out.append(
        f'<li class="primary-nav__item has-panel"><a class="primary-nav__link" href="{MAS["destino"]}">'
        f'{MAS["texto"]}{icono("caret-down", "icon icon--sm chevron")}'
        f'<span class="visually-hidden">: más secciones en el pie de página</span></a>'
        f'{_panel(MAS["items"], " panel--end")}</li>')
    return "".join(out)


def _contexto(slug, cfg):
    sec = CONTEXTO[cfg["seccion"]]
    items = []
    for it in sec["items"]:
        texto, destino = it[0], it[1]
        cur = ' aria-current="page"' if cfg["activo"] == texto else ""
        punto = ""
        if texto == "Partido en vivo":
            punto = '<span class="live-dot" aria-hidden="true"></span>'
        if len(it) == 3:
            items.append(
                f'<li class="context-nav__item has-panel"><a class="context-nav__link" href="{e(destino)}"{cur}>'
                f'{e(texto)}{icono("caret-down", "icon icon--sm chevron")}</a>{_panel(it[2])}</li>')
        else:
            items.append(f'<li class="context-nav__item"><a class="context-nav__link" href="{e(destino)}"{cur}>'
                         f'{punto}{e(texto)}</a></li>')
    extra = ""
    if sec.get("extra"):
        t, u = sec["extra"]
        extra = (f'<a class="btn btn--secondary btn--sm context-bar__extra" href="{e(u)}" target="_blank" rel="noopener">'
                 f'{icono("youtube-logo")}{e(t)}<span class="visually-hidden"> (se abre en una pestaña nueva)</span></a>')
    return (
        f'<div class="context-bar">'
        f'<div class="container context-bar__inner">'
        f'<a class="context-bar__title" href="{sec["destino"]}">{e(sec["titulo"])}</a>'
        f'<nav class="context-nav" aria-label="{e(sec["titulo"])}: enlaces de la sección">'
        f'<ul class="context-nav__list" role="list">{"".join(items)}</ul></nav>{extra}</div></div>')


def _buscador():
    sugerencias = "".join(f'<li><a class="chip" href="buscar.html?q={q}">{t}</a></li>'
                          for t, q in (("Junior", "junior"), ("Selección", "seleccion"), ("Mercado", "mercado"), ("Europa", "europa")))
    return (
        f'<div class="search-bar" id="buscador" hidden data-search>'
        f'<div class="container search-bar__inner">'
        f'<form class="search-bar__form" action="buscar.html" method="get" role="search" aria-label="Buscar en el sitio">'
        f'<label class="field__label" for="q">Buscar en Habla Deportes</label>'
        f'<div class="search-bar__row"><input class="field__input" id="q" name="q" type="search" '
        f'placeholder="Equipo, jugador o tema" autocomplete="off" enterkeyhint="search">'
        f'<button class="btn btn--primary" type="submit">Buscar</button></div></form>'
        f'<ul class="chip-list search-bar__suggest" role="list" aria-label="Búsquedas sugeridas">{sugerencias}</ul>'
        f'<button class="icon-btn search-bar__close" type="button" data-search-close>{icono("x")}'
        f'<span class="visually-hidden">Cerrar el buscador</span></button></div></div>')


def _menu_movil():
    grupos = "".join(
        f'<ul class="mobile-menu__list" role="list">'
        + "".join(f'<li><a class="mobile-menu__link" href="{e(h)}">{e(t)}</a></li>' for t, h in g)
        + "</ul>" for g in MENU_MOVIL)
    return (f'<nav class="mobile-menu" id="menu-movil" aria-label="Menú" hidden data-menu>'
            f'<div class="container">{grupos}</div></nav>')


def cabecera(slug):
    cfg = PAGINAS[slug]
    redes_cab = "".join(
        f'<a class="icon-btn icon-btn--on-brand masthead__social" href="{r["url"]}" target="_blank" rel="noopener">'
        f'{icono(r["icono"])}<span class="visually-hidden">{r["nombre"]} (se abre en una pestaña nueva)</span></a>'
        for r in REDES[:3])
    return f"""<a class="skip-link" href="#contenido">Saltar al contenido</a>
<header class="site-header" data-header>
  <div class="masthead">
    <div class="container masthead__inner">
      <a class="masthead__logo" href="index.html">{img("logo-blanco", clase="masthead__logo-img", alt="Habla Deportes, ir a la portada", prioridad=True)}</a>
      <div class="masthead__tools">
        {redes_cab}
        <button class="icon-btn icon-btn--on-brand" type="button" aria-expanded="false" aria-controls="buscador" data-search-toggle>{icono("magnifying-glass")}<span class="visually-hidden">Buscar</span></button>
        <button class="icon-btn icon-btn--on-brand masthead__menu" type="button" aria-expanded="false" aria-controls="menu-movil" data-menu-toggle>{icono("list", "icon icon-open")}{icono("x", "icon icon-close")}<span class="visually-hidden" data-menu-label>Abrir el menú</span></button>
      </div>
    </div>
  </div>
  <nav class="primary-nav" aria-label="Secciones">
    <div class="container primary-nav__inner">
      <a class="primary-nav__logo" href="index.html">{img("logo-blanco", clase="primary-nav__logo-img", alt="Habla Deportes, ir a la portada", prioridad=True)}</a>
      <ul class="primary-nav__list" role="list">{_primario(cfg)}</ul>
    </div>
  </nav>
  {_contexto(slug, cfg)}
  {_buscador()}
  {_menu_movil()}
</header>"""


# --------------------------------------------------------------------------
# Pie, barra inferior, reproductor
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


def barra_inferior(slug):
    activo = PAGINAS[slug]["inferior"]
    items = []
    for clave, texto, destino, ic in INFERIOR:
        on = activo == clave
        cur = ' aria-current="page"' if on else ""
        punto = '<span class="bottom-nav__dot only-aire" hidden aria-hidden="true"></span>' if clave == "en-vivo" else ""
        sr = '<span class="visually-hidden only-aire" hidden>, al aire ahora</span>' if clave == "en-vivo" else ""
        items.append(
            f'<li><a class="bottom-nav__link" href="{destino}"{cur}>'
            f'<span class="bottom-nav__icon">{icono(ic, "icon icon-line")}{icono(ic + "-fill", "icon icon-fill")}{punto}</span>'
            f'<span class="bottom-nav__label">{texto}</span>{sr}</a></li>')
    return (f'<nav class="bottom-nav" aria-label="Accesos rápidos">'
            f'<ul class="bottom-nav__list" role="list">{"".join(items)}</ul></nav>')


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
