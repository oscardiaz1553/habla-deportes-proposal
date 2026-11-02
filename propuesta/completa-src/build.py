#!/usr/bin/env python3
"""Genera propuesta/html/completa/*.html a partir de completa-src/pages/*.html.
Cada página lleva en su primera línea un comentario con metadatos:
<!--title=Título|desc=Descripción|nav=inicio|og=juansalvador1.png-->
Atajos en el cuerpo: {{i:nombre}} icono SVG, @esc/clave escudo, @img/ ruta de imágenes.
Uso: python3 propuesta/completa-src/build.py
"""
import re, pathlib, sys
SRC = pathlib.Path(__file__).parent / "pages"
OUT = pathlib.Path(__file__).parent.parent / "html" / "completa"

ESC = {'junior': 'escudo-junior.png', 'nacional': 'escudo-nacional.png', 'millonarios': 'escudo-millonarios.png', 'america': 'escudo-america.png', 'tolima': 'escudo-tolima.png', 'medellin': 'escudo-medellin.png', 'colombia': 'escudo-colombia.png', 'fcf': 'escudo-fcf.png'}
NAV = [("inicio","Inicio","index.html"),("junior","Junior FC","equipo.html"),("seleccion","Selección","seccion.html"),
       ("datos","Datos","datos.html"),("videos","Videos y redes","redes.html"),("radio","Radio","radio.html"),
       ("opinion","Opinión","juan-salvador.html")]
MORE = [("Noticias","seccion.html"),("Partido en vivo","partido.html"),("Fichajes","seccion.html#fichajes"),
        ("Efemérides","seccion.html#efemerides"),("Programas","radio.html#programas"),("Publicidad","contacto.html")]

SPRITE = """<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>
<symbol id="i-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></symbol>
<symbol id="i-pause" viewBox="0 0 24 24"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></symbol>
<symbol id="i-yt" viewBox="0 0 24 24"><path d="M23 7.2a3 3 0 0 0-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 0 0 1 7.2C.5 9.1.5 12 .5 12s0 2.9.5 4.8a3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1c.5-1.9.5-4.8.5-4.8s0-2.9-.5-4.8zM9.7 15.1V8.9l5.4 3.1z"/></symbol>
<symbol id="i-ig" viewBox="0 0 24 24"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm5 3.500a4.500 4.500 0 1 1 0 9 4.500 4.500 0 0 1 0-9zm0 2a2.500 2.500 0 1 0 0 5 2.500 2.500 0 0 0 0-5zM17.500 5.500a1.200 1.200 0 1 1 0 2.400 1.200 1.200 0 0 1 0-2.400z"/></symbol>
<symbol id="i-x" viewBox="0 0 24 24"><path d="M18.2 2.3h3.3l-7.2 8.3 8.5 11.2h-6.7l-5.2-6.8-6 6.8H1.600l7.700-8.800L1.200 2.300H8l4.700 6.200zm-1.200 17.500h1.800L7.100 4.200H5.100z"/></symbol>
<symbol id="i-wa" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.600 15L2 22l5.200-1.400A10 10 0 1 0 12 2zm5.800 14.200c-.2.700-1.300 1.300-1.800 1.300-.5.100-1 .2-3.300-.7-2.800-1.200-4.600-4-4.700-4.200-.1-.2-1.100-1.500-1.100-2.800 0-1.300.7-2 1-2.300.3-.3.600-.3.800-.3h.6c.2 0 .4 0 .6.500l.9 2.100c.1.200.1.400 0 .5l-.4.600-.4.500c-.1.100-.3.300-.1.600.2.300.8 1.300 1.700 2.100 1.200 1 2.100 1.300 2.400 1.400.3.100.5.100.6-.1l.9-1.100c.2-.3.400-.2.600-.1l2 1c.3.100.5.200.5.300.1.200.1.800-.1 1.500z"/></symbol>
<symbol id="i-tt" viewBox="0 0 24 24"><path d="M16.600 2h-3.100v13.400a2.700 2.700 0 1 1-2.700-2.700c.3 0 .5 0 .8.100V9.600a5.800 5.800 0 1 0 5 5.700V8.700a7 7 0 0 0 4.100 1.300V6.900a4 4 0 0 1-4.100-4.900z"/></symbol>
<symbol id="i-search" viewBox="0 0 24 24"><path d="M10 2a8 8 0 1 0 4.900 14.300l5.400 5.400 1.400-1.400-5.400-5.400A8 8 0 0 0 10 2zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12z"/></symbol>
<symbol id="i-menu" viewBox="0 0 24 24"><path d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z"/></symbol>
<symbol id="i-close" viewBox="0 0 24 24"><path d="M5.700 4.300 12 10.600l6.300-6.300 1.400 1.400-6.300 6.300 6.300 6.300-1.400 1.400-6.300-6.300-6.300 6.300-1.400-1.400 6.300-6.300-6.300-6.300z"/></symbol>
<symbol id="i-heart" viewBox="0 0 24 24"><path d="M12 21s-8-5.300-8-11a4.800 4.800 0 0 1 8-3.500A4.800 4.800 0 0 1 20 10c0 5.700-8 11-8 11z"/></symbol>
<symbol id="i-comment" viewBox="0 0 24 24"><path d="M4 4h16v12H8l-4 4z"/></symbol>
<symbol id="i-repeat" viewBox="0 0 24 24"><path d="M7 7h10v3l4-4-4-4v3H5v6h2zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2z"/></symbol>
<symbol id="i-share" viewBox="0 0 24 24"><path d="M18 16a3 3 0 0 0-2.200 1L8.900 13.100a3 3 0 0 0 0-2.200L15.800 7A3 3 0 1 0 15 5a3 3 0 0 0 .1.800L8.200 9.700a3 3 0 1 0 0 4.600l6.900 3.900A3 3 0 1 0 18 16z"/></symbol>
<symbol id="i-clock" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 5h-2v6l5 3 1-1.700-4-2.300z"/></symbol>
<symbol id="i-pin" viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.500A2.500 2.500 0 1 1 12 6.500a2.500 2.500 0 0 1 0 5z"/></symbol>
<symbol id="i-mic" viewBox="0 0 24 24"><path d="M12 14a3 3 0 0 0 3-3V5a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3zm5-3a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.900V21h2v-3.100A7 7 0 0 0 19 11z"/></symbol>
<symbol id="i-arrl" viewBox="0 0 24 24"><path d="M15.400 5.400 14 4l-8 8 8 8 1.400-1.400L8.800 12z"/></symbol>
<symbol id="i-arrr" viewBox="0 0 24 24"><path d="M8.600 5.400 10 4l8 8-8 8-1.400-1.400 6.600-6.600z"/></symbol>
<symbol id="i-ball" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 2 3 2.100-1.100 3.500h-3.800L9 6.100zM5.200 9.300l2.600-1.100 1.600 2.600-1 3-3 .2a8 8 0 0 1-.2-4.700zm6.800 10.700a8 8 0 0 1-4-1.100l.8-3 3.200-1 3.200 1 .8 3a8 8 0 0 1-4 1.100zm6.800-6.200-3-.2-1-3 1.600-2.600 2.600 1.100c.4 1.600.4 3.200-.2 4.700z"/></symbol>
<symbol id="i-radio" viewBox="0 0 24 24"><path d="M3.500 7.500 17 2l.8 1.900L8 8h12v13H3zM7 12v2h6v-2zm0 4v2h10v-2z"/></symbol>
<symbol id="i-chart" viewBox="0 0 24 24"><path d="M4 20V10h4v10zm6 0V4h4v16zm6 0v-7h4v7z"/></symbol>
<symbol id="i-sun" viewBox="0 0 24 24"><path d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm0-5h-1v3h2V2zm0 17h-1v3h2v-3zM2 11v2h3v-2zm17 0v2h3v-2zM5.600 4.200 4.200 5.600l2.100 2.100 1.400-1.400zm12.700 12.700-1.400 1.400 2.100 2.100 1.400-1.400zM4.200 18.400l1.400 1.400 2.100-2.100-1.400-1.400zM18.400 5.600l-1.400-1.400-2.100 2.100 1.400 1.400z"/></symbol>
<symbol id="i-mail" viewBox="0 0 24 24"><path d="M3 5h18v14H3zm9 7L5 7v1.500l7 5 7-5V7z"/></symbol>
<symbol id="i-down" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></symbol>
<symbol id="i-news" viewBox="0 0 24 24"><path d="M4 4h13v16H4zm15 4h1v10a2 2 0 0 1-2 2h-1V8zM7 8h7v2H7zm0 4h7v2H7zm0 4h5v2H7z"/></symbol>
<symbol id="i-cam" viewBox="0 0 24 24"><path d="M4 7h3l2-2h6l2 2h3v12H4zm8 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"/></symbol>
</defs></svg>"""

def icons(s): return re.sub(r"\{\{i:([a-z]+)\}\}", r'<svg class="ico" aria-hidden="true"><use href="#i-\1"/></svg>', s)
def esc(s): return re.sub(r"@esc/([a-z]+)", lambda m: "../assets/img/"+ESC[m.group(1)], s)
def imgs(s): return s.replace("@img/", "../assets/img/")

def header(cur):
    items = "".join(
      '<li class="nav__item"><a class="nav__link" href="%s"%s>%s</a></li>' % (h, ' aria-current="page"' if k==cur else '', t) for k,t,h in NAV)
    more = "".join('<a href="%s">%s</a>' % (h,t) for t,h in MORE)
    items += '<li class="nav__item"><button class="nav__link" aria-haspopup="true" aria-expanded="false" data-more>Más {{i:down}}</button><div class="nav__drop">%s</div></li>' % more
    mob = "".join('<a href="%s"%s>%s</a>' % (h, ' aria-current="page"' if k==cur else '', t) for k,t,h in NAV) + "".join('<a href="%s">%s</a>' % (h,t) for t,h in MORE)
    return """
<a class="skip" href="#main">Saltar al contenido</a>
<header class="hdr">
 <div class="wrap hdr1">
  <a class="hdr__logo" href="index.html" aria-label="Habla Deportes — inicio"><img src="../assets/img/LogoHablaDeportes.webp" width="1600" height="545" alt="Habla Deportes"></a>
  <div class="hdr1__info"><span>{{i:pin}} Barranquilla · 31°C</span><span data-fecha>Lunes, 2 de noviembre de 2026</span></div>
  <div class="hdr1__tools">
   <div class="hdr1__soc" aria-label="Redes de Habla Deportes"><a href="redes.html#youtube" aria-label="YouTube">{{i:yt}}</a><a href="redes.html#instagram" aria-label="Instagram">{{i:ig}}</a><a href="redes.html#x" aria-label="X">{{i:x}}</a><a href="redes.html#whatsapp" aria-label="Canal de WhatsApp">{{i:wa}}</a></div>
   <a class="btn hdr__radio" href="radio.html"><span class="badge badge--live">Al aire</span> Escuchar</a>
   <button class="iconbtn" aria-label="Buscar" data-search>{{i:search}}</button>
   <button class="iconbtn burger" aria-label="Abrir menú" aria-expanded="false" aria-controls="mnav" data-burger>{{i:menu}}</button>
  </div>
 </div>
 <nav class="mnav" id="mnav" aria-label="Menú móvil">""" + mob + """</nav>
</header>
<div class="hdr2"><div class="wrap hdr2__in"><nav class="nav" aria-label="Principal"><ul class="nav__list">""" + items + """</ul></nav><a class="hdr2__radio" href="radio.html">Radio · L–V 10:00 a.m.–12:00 m.</a></div></div>
{SCOREBAR}"""

SCOREBAR = """<div class="sbd" data-sbd role="region" aria-label="Marcadores"><div class="wrap sbd__in">
  <span class="sbd__tag">Marcadores</span>
  <div class="sbd__list">
   <a class="sbd__m is-on" href="partido.html" data-live><span class="sbd__b sbd__b--live"><i class="dot"></i>En vivo</span><span class="sbd__t"><img src="@esc/junior" alt="" width="22" height="22">Junior</span><strong>2 – 1</strong><span class="sbd__t">Nacional<img src="@esc/nacional" alt="" width="22" height="22"></span><em><span data-minuto="67">67'</span> · Liga BetPlay · Final ida</em></a>
   <a class="sbd__m" href="datos.html#resultados"><span class="sbd__b">Final</span><span class="sbd__t"><img src="@esc/millonarios" alt="" width="22" height="22">Millonarios</span><strong>1 – 1</strong><span class="sbd__t">Tolima<img src="@esc/tolima" alt="" width="22" height="22"></span><em>Ayer · Liga BetPlay</em></a>
   <a class="sbd__m" href="datos.html#resultados"><span class="sbd__b">Final</span><span class="sbd__t"><img src="@esc/america" alt="" width="22" height="22">América</span><strong>0 – 2</strong><span class="sbd__t">Medellín<img src="@esc/medellin" alt="" width="22" height="22"></span><em>Ayer · Liga BetPlay</em></a>
   <a class="sbd__m" href="partido.html"><span class="sbd__b sbd__b--next">Próximo</span><span class="sbd__t"><img src="@esc/colombia" alt="" width="22" height="22">Colombia</span><strong>vs</strong><span class="sbd__t">Perú<img src="@esc/fcf" alt="" width="22" height="22"></span><em>Jue 13 nov · 7:30 p.m. · Amistoso</em></a>
   <a class="sbd__m" href="datos.html#resultados"><span class="sbd__b sbd__b--next">Próximo</span><span class="sbd__t"><img src="@esc/tolima" alt="" width="22" height="22">Tolima</span><strong>vs</strong><span class="sbd__t">América<img src="@esc/america" alt="" width="22" height="22"></span><em>Sáb 8 nov · 4:00 p.m. · Liga BetPlay</em></a>
  </div>
  <div class="sbd__ctl"><button aria-label="Partido anterior" data-sbd-prev>{{i:arrl}}</button><span class="sbd__pg" data-sbd-pg><i></i><i></i><i></i><i></i><i></i></span><button aria-label="Partido siguiente" data-sbd-next>{{i:arrr}}</button></div>
  <a class="sbd__all" href="datos.html">Todos los marcadores ›</a>
</div></div>"""

def prefooter(meta):
    if meta.get("ads")=="0": return ""
    return '<div class="wrap prefooter"><div class="ad ad--lb"><span>970 × 90 · Espacio publicitario · Publicidad</span></div></div>'

def tabbar(cur):
    act = {"inicio":"noticias","junior":"noticias","seleccion":"noticias","opinion":"noticias","videos":"videos","datos":"datos","radio":"radio"}.get(cur,"noticias")
    items=[("noticias","Noticias","index.html","news"),("videos","Videos","redes.html","play"),("datos","Datos","datos.html","chart"),("radio","Radio","radio.html","radio")]
    return '<nav class="tabbar" aria-label="Navegación inferior">'+"".join('<a href="%s"%s>{{i:%s}}<span>%s</span></a>' % (h,' aria-current="page"' if k==act else '',ic,t) for k,t,h,ic in items)+'</nav>'

def footer():
    return """
<footer class="ftr"><div class="wrap">
 <div class="ftr__top">
  <div><img class="ftr__logo" src="../assets/img/LogoHablaDeportes-white.png" width="800" height="272" alt="Habla Deportes">
   <p style="margin-top:14px;max-width:34ch;font-size:14.5px">El show de las mañanas en Barranquilla. Fútbol colombiano, Junior y Selección, de lunes a viernes de 10:00 a.m. a 12:00 m.</p>
   <div class="ftr__soc"><a href="redes.html#youtube" aria-label="YouTube">{{i:yt}}</a><a href="redes.html#instagram" aria-label="Instagram">{{i:ig}}</a><a href="redes.html#x" aria-label="X">{{i:x}}</a><a href="redes.html#tiktok" aria-label="TikTok">{{i:tt}}</a><a href="redes.html#whatsapp" aria-label="WhatsApp">{{i:wa}}</a></div></div>
  <div class="ftr__cols">
   <div><h2>Noticias</h2><ul><li><a href="equipo.html">Junior FC</a></li><li><a href="seccion.html">Selección Colombia</a></li><li><a href="seccion.html#fichajes">Fichajes</a></li><li><a href="seccion.html#efemerides">Efemérides</a></li></ul></div>
   <div><h2>Datos</h2><ul><li><a href="datos.html">Tabla de posiciones</a></li><li><a href="datos.html#resultados">Resultados y fixture</a></li><li><a href="datos.html#goleadores">Goleadores</a></li><li><a href="partido.html">Partido en vivo</a></li></ul></div>
   <div><h2>Multimedia</h2><ul><li><a href="redes.html#youtube">Canal de YouTube</a></li><li><a href="redes.html#shorts">Shorts</a></li><li><a href="redes.html#instagram">Instagram</a></li><li><a href="radio.html">Radio en vivo</a></li></ul></div>
   <div><h2>Habla Deportes</h2><ul><li><a href="juan-salvador.html">Juan Salvador</a></li><li><a href="radio.html#programas">El programa</a></li><li><a href="contacto.html">Contacto y publicidad</a></li><li><a href="#">Política de privacidad</a></li></ul></div>
  </div></div>
 <div class="spons">Con el respaldo de <span class="spons__logo" aria-label="Transelca">TRANSELCA</span></div>
 <div class="ftr__bot"><span>© 2026 Habla Deportes · Barranquilla, Colombia · info@habladeportes.co</span><span>Propuesta de rediseño — edición completa (prototipo HTML)</span></div>
</div></footer>"""

def build():
    OUT.mkdir(parents=True, exist_ok=True)
    for f in sorted(SRC.glob("*.html")):
        raw = f.read_text(encoding="utf-8")
        m = re.match(r"<!--(.*?)-->\s*", raw, re.S)
        meta = dict(p.split("=",1) for p in m.group(1).strip().split("|")) if m else {}
        body = raw[m.end():] if m else raw
        cur = meta.get("nav","")
        og = meta.get("og","juansalvador1.png")
        title = meta.get("title","Habla Deportes")
        desc = meta.get("desc","Habla Deportes — fútbol colombiano, Junior y Selección.")
        html = f"""<!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{title} — Habla Deportes</title>
<meta name="description" content="{desc}">
<meta property="og:type" content="website"><meta property="og:title" content="{title} — Habla Deportes"><meta property="og:description" content="{desc}"><meta property="og:image" content="../assets/img/{og}">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#122338">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/css/completa.css?v=4">
<link rel="icon" href="../assets/img/LogoHablaDeportes.webp" type="image/webp">
</head>
<body>
{SPRITE}
{header(cur).replace("{SCOREBAR}", SCOREBAR)}
<main id="main">
{body}
</main>
{prefooter(meta)}
{footer()}
{tabbar(cur)}
<script src="assets/js/completa.js?v=1" defer></script>
</body>
</html>"""
        html = imgs(esc(icons(html)))
        (OUT/f.name).write_text(html, encoding="utf-8")
        print("ok", f.name)
if __name__=="__main__": build()
