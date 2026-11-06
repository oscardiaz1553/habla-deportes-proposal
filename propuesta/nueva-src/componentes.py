"""Componentes que las páginas invocan con {{ nombre(argumentos) }}.

Regla: cada función devuelve HTML de UN componente de 01-sistema-de-diseno.md
(o un bloque de datos de 02-arquitectura.md). Las clases son estables y legibles
para mapearlas luego a bloques/plantillas de WordPress.
Los datos salen SIEMPRE de datos.D (data-ejemplo.json).
"""
import datetime as dt

import imagenes
import nucleo
from datos import (D, DIAS, FUENTE, HOY, dia_corto, dia_largo, equipo, fecha, fila_tabla, forma_texto, hora,
                   hora_iso, id_partido, miles, partido_por_id, signo, todos_los_proximos)
from nucleo import e, enlace_externo, escudo, icono, img

CUR = ' aria-current="true"'
NOTAB = ' tabindex="-1"'
SEL = ' class="is-selected"'
AHORA = dt.datetime(2026, 11, 6, 20, 40, tzinfo=dt.timezone(dt.timedelta(hours=-5)))  # referencia del JSON


# ==========================================================================
# Utilidades de página
# ==========================================================================
def foto(clave, clase="", alt=None, prioridad=False):
    return img(clave, clase=clase, alt=alt, prioridad=prioridad)


def ico(nombre, clase="icon"):
    return icono(nombre, clase)


def rotulo(titulo, enlace=None, texto_enlace=None, id=None, nivel=2, oculto=False):
    """Rótulo de sección (01 §4: Archivo 800 cursiva sobre filete de 2 px)."""
    hid = id or nucleo.P.nuevo_id("s-" + _slug(titulo))
    if oculto:
        return f'<h{nivel} class="visually-hidden" id="{hid}">{e(titulo)}</h{nivel}>'
    link = ""
    if enlace:
        link = (f'<a class="link-action" href="{e(enlace)}">{e(texto_enlace)}'
                f'{icono("caret-right", "icon icon--sm")}</a>')
    return (f'<div class="section-head"><h{nivel} class="section-head__title" id="{hid}">{e(titulo)}</h{nivel}>'
            f'{link}</div>')


def _slug(t):
    import re
    import unicodedata
    t = unicodedata.normalize("NFKD", t).encode("ascii", "ignore").decode()
    return re.sub(r"[^a-z0-9]+", "-", t.lower()).strip("-")[:32]


def fuente(texto=FUENTE, actualizado=None):
    extra = f" {e(actualizado)}" if actualizado else ""
    return f'<p class="source-note">{e(texto)}{extra}</p>'


def ejemplo(texto="Contenido de ejemplo para la propuesta"):
    return f'<p class="example-flag">{e(texto)}</p>'


def live_pill(texto="En vivo", minuto=None):
    m = f" {minuto}'" if minuto is not None else ""
    return f'<span class="live-pill"><span class="live-dot" aria-hidden="true"></span>{e(texto)}{m}</span>'


# ==========================================================================
# 6.4 · Noticia pequeña / tarjeta de video
# ==========================================================================
def _media(clave, url, duracion=None, play=None, chip=None, prioridad=False, enlace=False):
    """Bloque de imagen 16:9 con play y chip. Solo controles y datos cortos sobre la foto."""
    partes = [img(clave, clase="media__img", prioridad=prioridad)]
    if play:
        partes.append(f'<span class="play play--{play}" aria-hidden="true">{icono("play-fill", "icon")}</span>')
    if chip:
        partes.append(f'<span class="chip-media chip-media--tl">{e(chip)}</span>')
    if duracion:
        partes.append(f'<span class="chip-media chip-media--br"><span class="visually-hidden">Duración: </span>'
                      f'{e(duracion)}</span>')
    return "".join(partes)


def tarjeta(url, foto, tema, titulo, meta, duracion=None, variante="", nivel=3, prioridad=False):
    """Noticia pequeña (01 §6.4). Un solo enlace: el titular, estirado sobre la tarjeta."""
    es_video = duracion is not None
    clases = "story-card" + (" story-card--video" if es_video else "") + (f" story-card--{variante}" if variante else "")
    tipo = '<span class="visually-hidden">Video: </span>' if es_video else ""
    return (
        f'<article class="{clases}">'
        f'<div class="story-card__media media">{_media(foto, url, duracion, "sm" if es_video else None, prioridad=prioridad)}</div>'
        f'<div class="story-card__body">'
        f'<p class="topic">{e(tema)}</p>'
        f'<h{nivel} class="story-card__title"><a class="stretched-link" href="{e(url)}">{tipo}{e(titulo)}</a></h{nivel}>'
        f'<p class="meta">{meta}</p>'
        f'</div></article>')


# ==========================================================================
# 6.5 · Carril de Shorts
# ==========================================================================
def short(foto, titulo, duracion):
    return (
        f'<li class="short-card">'
        f'<div class="short-card__media media media--9x16">{_media(foto, "shorts.html", chip="Short")}'
        f'<span class="chip-media chip-media--br"><span class="visually-hidden">Duración: </span>{e(duracion)}</span></div>'
        f'<h3 class="short-card__title"><a class="stretched-link" href="shorts.html">{e(titulo)}</a></h3>'
        f'<p class="meta">Vistas: cifra por definir</p>'
        f'</li>')


def carril_shorts(id, titulo, enlace, texto_enlace, items):
    """Rótulo + carril de Shorts con flechas a la derecha del rótulo (01 §6.5).
    items: [(foto, titulo, duracion)]."""
    contenido = "".join(short(*x) for x in items)
    hid = nucleo.P.nuevo_id("s-" + _slug(titulo))
    return (
        f'<div class="rail" data-rail>'
        f'<div class="section-head"><h2 class="section-head__title" id="{hid}">{e(titulo)}</h2>'
        f'<div class="section-head__tools">'
        f'<div class="rail__controls">'
        f'<button class="icon-btn" type="button" data-rail-prev aria-controls="{id}">'
        f'{icono("caret-left")}<span class="visually-hidden">Shorts anteriores</span></button>'
        f'<button class="icon-btn" type="button" data-rail-next aria-controls="{id}">'
        f'{icono("caret-right")}<span class="visually-hidden">Shorts siguientes</span></button></div>'
        f'<a class="link-action" href="{e(enlace)}">{e(texto_enlace)}{icono("caret-right", "icon icon--sm")}</a>'
        f'</div></div>'
        f'<ul class="rail__track" id="{id}" role="list" aria-labelledby="{hid}">{contenido}</ul></div>')


# ==========================================================================
# 6.6 · Programas
# ==========================================================================
def programa(url, foto, fecha, titulo, duracion, destacado=False, descripcion=None, prioridad=False):
    clases = "program-card" + (" program-card--featured" if destacado else "")
    desc = f'<p class="program-card__desc">{e(descripcion)}</p>' if descripcion else ""
    n = 3
    return (
        f'<article class="{clases}">'
        f'<div class="program-card__media media">{_media(foto, url, duracion, "sm", prioridad=prioridad)}</div>'
        f'<div class="program-card__body">'
        f'<p class="program-card__date"><time datetime="{fecha[0]}">{e(fecha[1])}</time></p>'
        f'<h{n} class="program-card__title"><a class="stretched-link" href="{e(url)}">'
        f'<span class="visually-hidden">Video: </span>{e(titulo)}</a></h{n}>{desc}'
        f'</div></article>')


# ==========================================================================
# Lista de titulares de solo texto (M5, M7, "Lo más leído")
# ==========================================================================
def titulares(items, numerada=False):
    """items: lista de (url, titular, meta, es_video)."""
    tag = "ol" if numerada else "ul"
    filas = []
    for url, titular, meta, es_video in items:
        marca = (f'<span class="headline-list__type">{icono("play-fill", "icon icon--sm")}'
                 f'<span class="visually-hidden">Video: </span></span>') if es_video else ""
        filas.append(
            f'<li class="headline-list__item"><a class="headline-list__link" href="{e(url)}">{marca}'
            f'<span class="headline-list__title">{e(titular)}</span></a>'
            f'<span class="meta">{e(meta)}</span></li>')
    return f'<{tag} class="headline-list{" headline-list--numbered" if numerada else ""}" role="list">{"".join(filas)}</{tag}>'


# ==========================================================================
# 6.7 · Banner de partidos (Centro de Partidos) + patrocinio
# ==========================================================================
def _diapositivas():
    pv = D["partido_en_vivo"]
    cj = D["calendario_junior"]
    cs = D["calendario_seleccion"]
    sig_junior = cj["proximos"][1]
    ult_junior = cj["pasados"][-1]
    destacado = partido_por_id("tolima-america")
    return [
        {"torneo": pv["torneo"], "estado": "vivo", "minuto": pv["minuto"], "local": pv["local"],
         "visitante": pv["visitante"], "gl": pv["marcador"]["local"], "gv": pv["marcador"]["visitante"],
         "dia": pv["dia"], "hora": pv["hora_inicio"], "estadio": pv["estadio"], "canal": pv["canal"],
         "cta": "Seguir el minuto a minuto"},
        {"torneo": f"Liga BetPlay · Fecha {sig_junior['fecha']}", "estado": "proximo", **_pick(sig_junior),
         "cta": "Ver la previa"},
        {"torneo": cs["proximos"][0]["torneo"], "estado": "proximo", **_pick(cs["proximos"][0]),
         "cta": "Ver la previa", "cuenta": True},
        {"torneo": f"Liga BetPlay · Fecha {ult_junior['fecha']}", "estado": "final", "local": ult_junior["local"],
         "visitante": ult_junior["visitante"], "gl": ult_junior["gl"], "gv": ult_junior["gv"],
         "dia": ult_junior["dia"], "hora": None, "estadio": equipo(ult_junior["local"]).get("estadio"),
         "canal": None, "cta": "Ver la crónica"},
        {"torneo": "Liga BetPlay · Fecha 20", "estado": "proximo", **_pick(destacado), "cta": "Ver la previa"},
    ]


def _pick(p):
    return {k: p.get(k) for k in ("local", "visitante", "dia", "hora", "estadio", "canal")}


def _equipo_banner(clave):
    eq = equipo(clave)
    return (f'<div class="match-slide__team">{escudo(clave, "crest crest--xl")}'
            f'<p class="match-slide__name">{e(eq["corto"])}</p></div>')


def _marcador(s, tag="p"):
    """Centro del banner: marcador (en juego o final) u hora (próximo). Lo comparten el
    banner de partidos y la tarjeta de partido del megamenú."""
    loc, vis = equipo(s["local"]), equipo(s["visitante"])
    if s["estado"] in ("vivo", "final"):
        return (f'<{tag} class="match-slide__score"><span aria-hidden="true">{s["gl"]}'
                f'<span class="match-slide__dash">-</span>{s["gv"]}</span>'
                f'<span class="visually-hidden">{e(loc["corto"])} {s["gl"]}, {e(vis["corto"])} {s["gv"]}</span></{tag}>')
    hh, ampm = hora(s["hora"]).split(" ", 1)
    return (f'<{tag} class="match-slide__time"><span class="visually-hidden">{e(loc["corto"])} contra '
            f'{e(vis["corto"])}, a las </span>{e(hh)}<span class="match-slide__ampm">{e(ampm)}</span></{tag}>')


def centro_partidos():
    slides = _diapositivas()
    n = len(slides)
    out = []
    for i, s in enumerate(slides):
        loc, vis = equipo(s["local"]), equipo(s["visitante"])
        if s["estado"] == "vivo":
            estado = live_pill("En vivo", s["minuto"])
        elif s["estado"] == "final":
            estado = '<span class="status-text">Final</span>'
        else:
            estado = '<span class="status-text">Próximo</span>'
        centro = _marcador(s)
        info =[f'{icono("calendar-blank", "icon icon--sm")}<span>{e(dia_corto(s["dia"]))}'
                + (f', {e(hora(s["hora"]))}' if s.get("hora") else "") + "</span>"]
        if s.get("estadio"):
            info.append(f'{icono("map-pin", "icon icon--sm")}<span>{e(s["estadio"])}</span>')
        if s.get("canal"):
            info.append(f'{icono("television", "icon icon--sm")}<span>{e(s["canal"])}</span>')
        info_html = "".join(f'<li>{x}</li>' for x in info)
        cuenta = ""
        if s.get("cuenta"):
            iso = hora_iso(s["dia"], s["hora"])
            cuenta = (f'<p class="match-slide__countdown" data-countdown="{iso}">'
                      f'{e(_cuenta_texto(iso))}</p>')
        out.append(
            f'<div class="match-slide" role="group" aria-roledescription="diapositiva" '
            f'aria-label="{i + 1} de {n}: {e(loc["corto"])} contra {e(vis["corto"])}"'
            f'{" hidden" if i else ""} data-slide>'
            f'<p class="match-slide__top"><span>{e(s["torneo"])}</span>{estado}</p>'
            f'<div class="match-slide__teams">{_equipo_banner(s["local"])}'
            f'<div class="match-slide__center">{centro}{cuenta}</div>{_equipo_banner(s["visitante"])}</div>'
            f'<ul class="match-slide__info" role="list">{info_html}'
            f'<li><a class="match-slide__cta" href="partido.html">{e(s["cta"])}'
            f'<span class="visually-hidden">: {e(loc["corto"])} contra {e(vis["corto"])}</span>'
            f'{icono("caret-right", "icon icon--sm")}</a></li></ul>'
            f'</div>')
    dots = "".join(
        f'<li><button class="dots__btn" type="button" data-goto="{i}" aria-label="Ir al partido {i + 1} de {n}"'
        f'{CUR if i == 0 else ""}><span class="dots__mark" aria-hidden="true"></span></button></li>'
        for i in range(n))
    return (
        f'<section class="match-center" aria-labelledby="centro-partidos">'
        f'<h2 class="visually-hidden" id="centro-partidos">Centro de partidos</h2>'
        f'<div class="match-center__banner" role="region" aria-roledescription="carrusel" '
        f'aria-label="Partidos destacados" data-carousel>'
        f'<div class="match-center__slides" aria-live="off" data-slides>{"".join(out)}</div>'
        f'<div class="match-center__controls">'
        f'<button class="icon-btn icon-btn--on-dark" type="button" data-prev>{icono("caret-left")}'
        f'<span class="visually-hidden">Partido anterior</span></button>'
        f'<ol class="dots" role="list">{dots}</ol>'
        f'<button class="icon-btn icon-btn--on-dark" type="button" data-next>{icono("caret-right")}'
        f'<span class="visually-hidden">Partido siguiente</span></button>'
        f'<button class="icon-btn icon-btn--on-dark" type="button" data-pause aria-pressed="false">'
        f'{icono("pause-fill", "icon icon-pause")}{icono("play-fill", "icon icon-play")}'
        f'<span class="visually-hidden" data-pause-label>Pausar la rotación</span></button>'
        f'<p class="match-center__note">Datos de ejemplo</p>'
        f'<a class="match-center__all" href="datos.html#hoy">Todos los partidos{icono("caret-right", "icon icon--sm")}</a>'
        f'</div></div>'
        f'<div class="match-center__sponsor">'
        f'<!-- Pieza del patrocinador (322×424). Fija: no rota con las diapositivas (04 §4). -->'
        f'{img("anuncio-transelca", clase="match-center__ad")}'
        f'<p class="sponsor-seal sponsor-seal--inline"><span class="sponsor-seal__text">Centro de Partidos presentado por</span> '
        f'<span class="sponsor-seal__logo" data-logo-pendiente="transelca">Transelca</span></p>'
        f'</div></section>')


def _cuenta_texto(iso, ahora=AHORA):
    objetivo = dt.datetime.fromisoformat(iso)
    s = int((objetivo - ahora).total_seconds())
    if s <= 0:
        return "El partido ya empezó"
    d, r = divmod(s, 86400)
    h, r = divmod(r, 3600)
    m = r // 60
    return f"Faltan {d} días, {h} h y {m} min"


# ==========================================================================
# Fila de partido (franja por día, resultados, próximos): 01 §6.8
# ==========================================================================
def _fila_partido(p, enlace=True):
    loc, vis = equipo(p["local"]), equipo(p["visitante"])
    estado = p.get("estado", "jugada")
    gl, gv = p.get("gl"), p.get("gv")
    if estado == "en_vivo":
        izq = f'<span class="match-row__live"><span class="live-dot" aria-hidden="true"></span>{p["minuto"]}\'</span>'
        sr_estado = f"en vivo, minuto {p['minuto']}"
    elif gl is not None:
        izq = '<span class="match-row__state">Final</span>'
        sr_estado = "final"
    else:
        izq = f'<span class="match-row__state">{e(hora(p["hora"]).replace(" ", " "))}</span>'
        sr_estado = f"a las {hora(p['hora'])}"
    if gl is not None:
        marcador = f'<span aria-hidden="true">{gl}<span class="match-row__dash">-</span>{gv}</span>'
        gana_l, gana_v = gl > gv, gv > gl
    else:
        marcador = '<span class="match-row__vs" aria-hidden="true">vs</span>'
        gana_l = gana_v = False
    sr = f"{loc['corto']} {gl if gl is not None else ''} contra {vis['corto']} {gv if gv is not None else ''}, {sr_estado}"
    canal = f'<span class="match-row__channel">{e(p["canal"])}</span>' if p.get("canal") else '<span class="match-row__channel"></span>'
    cuerpo = (
        f'{izq}'
        f'<span class="match-row__team match-row__team--home{" is-winner" if gana_l else ""}">'
        f'<span class="match-row__name">{e(loc["corto"])}</span>{escudo(p["local"], "crest crest--sm")}</span>'
        f'<span class="match-row__score">{marcador}</span>'
        f'<span class="match-row__team{" is-winner" if gana_v else ""}">{escudo(p["visitante"], "crest crest--sm")}'
        f'<span class="match-row__name">{e(vis["corto"])}</span></span>'
        f'{canal}<span class="visually-hidden">{e(sr)}</span>')
    clase = "match-row" + (" match-row--live" if estado == "en_vivo" else "")
    if enlace:
        return f'<li><a class="{clase}" href="partido.html">{cuerpo}</a></li>'
    return f'<li><div class="{clase}">{cuerpo}</div></li>'


def _grupo_partidos(titulo, partidos):
    filas = "".join(_fila_partido(p) for p in partidos)
    return (f'<div class="match-group"><h3 class="match-group__title">{e(titulo)}</h3>'
            f'<ul class="match-list" role="list">{filas}</ul></div>')


def _tabs(id, etiqueta, pestanas, activa):
    """Pestañas accesibles (role=tablist). pestanas: [(clave, texto, contenido_html)]."""
    botones, paneles = [], []
    for clave, texto, contenido in pestanas:
        on = clave == activa
        botones.append(
            f'<button class="tabs__tab" type="button" role="tab" id="{id}-tab-{clave}" '
            f'aria-controls="{id}-{clave}" aria-selected="{"true" if on else "false"}"'
            f'{"" if on else NOTAB}>{e(texto)}</button>')
        paneles.append(
            f'<div class="tabs__panel" role="tabpanel" id="{id}-{clave}" aria-labelledby="{id}-tab-{clave}" '
            f'tabindex="0"{"" if on else " hidden"}>{contenido}</div>')
    return (f'<div class="tabs" data-tabs><div class="tabs__list" role="tablist" aria-label="{e(etiqueta)}">'
            f'{"".join(botones)}</div>{"".join(paneles)}</div>')


def franja_dias():
    """Franja Ayer / Hoy / Mañana (01 §6.8, 03 P2)."""
    fd = D["resultados"]["filtro_dias"]
    ayer = (f'<div class="empty-state"><p>Ayer no hubo partidos de Liga BetPlay. Mira los resultados de la Fecha 19.</p>'
            f'<a class="link-action" href="#resultados">Ver resultados de la Fecha 19{icono("caret-right", "icon icon--sm")}</a></div>')
    hoy = _grupo_partidos("Liga BetPlay · Fecha 20", [partido_por_id(i) for i in fd["hoy"]["partidos"]])
    manana = _grupo_partidos("Liga BetPlay · Fecha 20", [partido_por_id(i) for i in fd["manana"]["partidos"]])
    return _tabs("dias", "Partidos por día", [
        ("ayer", "Ayer", ayer), ("hoy", "Hoy", hoy), ("manana", "Mañana", manana)], "hoy")


def resultados():
    """Resultados por fecha con selector (pestañas)."""
    pestanas = []
    jornadas = D["resultados"]["jornadas"]
    proximos = D["resultados"]["proximos"][0]
    def por_dia(partidos):
        grupos, orden = {}, []
        for p in partidos:
            if p["dia"] not in grupos:
                grupos[p["dia"]] = []
                orden.append(p["dia"])
            grupos[p["dia"]].append(p)
        out = []
        for d in orden:
            t = dia_largo(d)
            out.append(_grupo_partidos(t[0].upper() + t[1:], grupos[d]))
        return "".join(out)
    for j in jornadas:
        pestanas.append((f"f{j['fecha']}", f"Fecha {j['fecha']}", por_dia(j["partidos"])))
    pestanas.append((f"f{proximos['fecha']}", f"Fecha {proximos['fecha']} (en curso)", por_dia(proximos["partidos"])))
    return _tabs("fechas", "Elige la fecha", pestanas, f"f{jornadas[0]['fecha']}")


def proximos_partidos(cuales="portada"):
    """Lista de consulta (L2). Si el banner ya muestra el próximo de Junior, aquí van los siguientes (02 L2)."""
    cj, cs = D["calendario_junior"]["proximos"], D["calendario_seleccion"]["proximos"]
    if cuales == "portada":
        lista = [cj[2], cj[3], cs[1]]
    elif cuales == "junior":
        lista = cj[1:4]
    else:  # articulo / general: lo siguiente de Junior y de la Selección
        lista = [cj[1], cj[2], cs[0]]
    filas = []
    for p in lista:
        loc, vis = equipo(p["local"]), equipo(p["visitante"])
        torneo = p.get("torneo") or f"Liga BetPlay · Fecha {p['fecha']}"
        filas.append(
            f'<li><a class="fixture" href="partido.html">'
            f'<span class="fixture__when"><time datetime="{hora_iso(p["dia"], p["hora"])}">{e(dia_corto(p["dia"]))}</time>'
            f'<span>{e(hora(p["hora"]))}</span></span>'
            f'<span class="fixture__teams"><span class="fixture__team">{escudo(p["local"], "crest crest--sm")}{e(loc["corto"])}</span>'
            f'<span class="fixture__team">{escudo(p["visitante"], "crest crest--sm")}{e(vis["corto"])}</span></span>'
            f'<span class="fixture__meta">{e(torneo.split(" · ")[0])}<br>{e(p.get("canal", ""))}</span></a></li>')
    return f'<ul class="fixture-list" role="list">{"".join(filas)}</ul>'


# ==========================================================================
# 6.9 · Tabla de posiciones
# ==========================================================================
def _forma(forma):
    cuadros = "".join(f'<span class="form-badge form-badge--{c.lower()}">{c}</span>' for c in forma)
    return f'<span class="form-list" role="img" aria-label="{e(forma_texto(forma))}">{cuadros}</span>'


def tabla_posiciones(variante="compacta", filas=5, sello=False):
    tp = D["tabla_posiciones"]
    hasta = tp["leyenda"]["cuadrangulares"][1]
    lista = tp["filas"][:filas] if variante in ("compacta", "mini") else tp["filas"]
    if variante == "compacta" and not any(f["equipo"] == "junior" for f in lista):
        lista = lista + [fila_tabla("junior")]
    completa = variante == "completa"
    mini = variante == "mini"  # megamenú de Datos (08 §4.5): #, equipo, PJ y Pts; sin zonas ni leyenda
    cab = ['<th scope="col" class="num">#</th>', '<th scope="col" class="team-col">Equipo</th>',
           '<th scope="col" class="num"><abbr title="Partidos jugados">PJ</abbr></th>']
    if completa:
        cab += ['<th scope="col" class="num"><abbr title="Ganados">G</abbr></th>',
                '<th scope="col" class="num"><abbr title="Empatados">E</abbr></th>',
                '<th scope="col" class="num"><abbr title="Perdidos">P</abbr></th>',
                '<th scope="col" class="num"><abbr title="Goles a favor">GF</abbr></th>',
                '<th scope="col" class="num"><abbr title="Goles en contra">GC</abbr></th>']
    if not mini:
        cab.append('<th scope="col" class="num"><abbr title="Diferencia de goles">DG</abbr></th>')
    cab.append('<th scope="col" class="num"><abbr title="Puntos">Pts</abbr></th>')
    if completa:
        cab.append('<th scope="col" class="form-col">Racha</th>')
    cuerpo = []
    for f in lista:
        eq = equipo(f["equipo"])
        zona = f["pos"] <= hasta and not mini
        marca = "" if mini else (f'<span class="zone-mark" aria-hidden="true"></span>'
                 f'<span class="visually-hidden">, zona de cuadrangulares</span>') if zona else '<span class="zone-mark zone-mark--none" aria-hidden="true"></span>'
        celdas = [f'<td class="num pos">{marca}{f["pos"]}</td>',
                  f'<th scope="row" class="team-col"><span class="team-cell">{escudo(f["equipo"], "crest crest--xs")}'
                  f'<span>{e(eq["corto"])}</span></span></th>',
                  f'<td class="num">{f["pj"]}</td>']
        if completa:
            celdas += [f'<td class="num">{f[k]}</td>' for k in ("g", "e", "p", "gf", "gc")]
        if not mini:
            celdas.append(f'<td class="num">{signo(f["dg"])}</td>')
        celdas.append(f'<td class="num pts">{f["pts"]}</td>')
        if completa:
            celdas.append(f'<td class="form-col">{_forma(f["forma"])}</td>')
        sel = ' class="is-selected"' if f["equipo"] == "junior" else ""
        cuerpo.append(f'<tr{sel}>{"".join(celdas)}</tr>')
    caption = (f'Liga BetPlay, tabla de posiciones hasta la Fecha 19'
               + ("" if completa else f" (primeros {filas})"))
    tabla = (f'<table class="standings standings--{variante}"><caption class="visually-hidden">{e(caption)}</caption>'
             f'<thead><tr>{"".join(cab)}</tr></thead><tbody>{"".join(cuerpo)}</tbody></table>')
    if completa:
        tabla = (f'<div class="table-scroll" role="region" aria-label="Tabla de posiciones, se desplaza de lado" tabindex="0">'
                 f'{tabla}</div>')
    if mini:
        return tabla
    leyenda = (f'<p class="standings-legend"><span class="zone-mark" aria-hidden="true"></span>'
               f'Cuadrangulares (1.º a {hasta}.º)</p>')
    return tabla + leyenda


# ==========================================================================
# Goleadores, asistencias y estadísticas (datos.html)
# ==========================================================================
def _ranking(titulo, filas, col, id):
    trs = "".join(
        f'<tr{SEL if f["equipo"] == "junior" else ""}><td class="num pos">{f["pos"]}</td>'
        f'<th scope="row"><span class="player-cell"><span class="player-cell__name">{e(f["jugador"])}</span>'
        f'<span class="player-cell__team">{escudo(f["equipo"], "crest crest--xs")}{e(equipo(f["equipo"])["corto"])}</span></span></th>'
        f'<td class="num pts">{f[col]}</td></tr>' for f in filas)
    etiqueta = "Goles" if col == "goles" else "Asistencias"
    return (f'<div class="ranking"><h3 class="ranking__title" id="{id}">{e(titulo)}</h3>'
            f'<table class="standings standings--ranking" aria-labelledby="{id}"><thead><tr><th scope="col" class="num">#</th>'
            f'<th scope="col">Jugador</th><th scope="col" class="num">{etiqueta}</th></tr></thead>'
            f'<tbody>{trs}</tbody></table></div>')


def goleadores():
    g = D["goleadores"]
    return (f'<div class="ranking-pair">{_ranking("Goleadores", g["goles"], "goles", "rk-goles")}'
            f'{_ranking("Asistencias", g["asistencias"], "asistencias", "rk-asist")}</div>')


def estadisticas():
    eqs = D["goleadores"]["equipos"]
    prom = str(D["goleadores"]["promedio_goles_por_partido"]).replace(".", ",")
    bloques = [("Más goles a favor", eqs["mas_goles_a_favor"], "goles"),
               ("Menos goles en contra", eqs["menos_goles_en_contra"], "goles"),
               ("Más tiros de esquina", eqs["mas_esquinas"], "córners")]
    cols = []
    for titulo, lista, unidad in bloques:
        filas = "".join(
            f'<li class="stat-list__row{" is-selected" if c == "junior" else ""}"><span class="stat-list__pos">{i + 1}</span>'
            f'<span class="stat-list__team">{escudo(c, "crest crest--xs")}{e(equipo(c)["corto"])}</span>'
            f'<span class="stat-list__value">{v}<span class="visually-hidden"> {unidad}</span></span></li>'
            for i, (c, v) in enumerate(lista))
        cols.append(f'<div class="stat-block"><h3 class="stat-block__title">{e(titulo)}</h3>'
                    f'<ol class="stat-list" role="list">{filas}</ol></div>')
    return (f'<p class="stat-lead"><span class="stat-lead__value">{prom}</span> goles por partido en la Liga BetPlay, '
            f'hasta la Fecha 19.</p><div class="stat-grid">{"".join(cols)}</div>')


# ==========================================================================
# 6.10 · Tarjeta de partido en vivo
# ==========================================================================
def tarjeta_en_vivo():
    pv = D["partido_en_vivo"]
    ult_gol = [c for c in pv["cronologia"] if c["tipo"] == "gol"][-1]
    filas = ""
    for lado, clave in (("local", pv["local"]), ("visitante", pv["visitante"])):
        eq = equipo(clave)
        filas += (f'<li class="live-card__row">{escudo(clave, "crest crest--md")}'
                  f'<span class="live-card__team">{e(eq["corto"])}</span>'
                  f'<span class="live-card__goals">{pv["marcador"][lado]}</span></li>')
    return (
        f'<article class="live-card" aria-labelledby="live-card-title">'
        f'<header class="live-card__head"><h3 class="live-card__league" id="live-card-title">{e(pv["torneo"])}</h3>'
        f'{live_pill("En vivo", pv["minuto"])}</header>'
        f'<ul class="live-card__score" role="list">{filas}</ul>'
        f'<p class="live-card__event">Gol de {e(ult_gol["jugador"])} {ult_gol["min"]}\'. {e(ult_gol["texto"])}.</p>'
        f'<a class="link-action" href="partido.html">Minuto a minuto{icono("caret-right", "icon icon--sm")}</a>'
        f'</article>')


def racha(clave="junior"):
    f = fila_tabla(clave)
    return (f'<div class="streak">{_forma(f["forma"])}'
            f'<p class="streak__text">Cinco partidos sin perder. Líder con {f["pts"]} puntos y {signo(f["dg"])} de diferencia de gol.</p>'
            f'<a class="link-action" href="junior.html">Ver Junior{icono("caret-right", "icon icon--sm")}</a></div>')


# ==========================================================================
# Mercado de fichajes (L4)
# ==========================================================================
ESTADOS = {"Cerrado": "closed", "Negociando": "talks", "Rumor": "rumor"}


def mercado(ids=("m1", "m2", "m4", "m5", "m6")):
    movs = {m["id"]: m for m in D["mercado_fichajes"]["movimientos"]}
    filas = []
    for i in ids:
        m = movs[i]
        origen = equipo(m["origen"])["corto"]
        destino = equipo(m["destino"])["corto"] if m["destino"] else "Por definir"
        filas.append(
            f'<li class="market-row"><span class="market-row__date">'
            f'<time datetime="{m["fecha"]}">{e(dia_corto(m["fecha"]))}</time></span>'
            f'<span class="market-row__body"><span class="market-row__player">{e(m["jugador"])}</span>'
            f'<span class="market-row__route">{e(origen)}<span aria-hidden="true"> → </span>'
            f'<span class="visually-hidden"> a </span>{e(destino)}</span></span>'
            f'<span class="status-chip status-chip--{ESTADOS[m["estado"]]}">{e(m["estado"])}</span></li>')
    return (f'<ul class="market-list" role="list">{"".join(filas)}</ul>'
            f'<p class="source-note">Estados de ejemplo, definidos por la redacción.</p>')


# ==========================================================================
# 6.11 · En vivo (transmisión de YouTube dentro de la web, 06 D1)
# Sin iframe real en el prototipo: portada con play + comentario del embed.
# El estado «al aire» lo decide main.js (constante EN_VIVO, un solo sitio).
# ==========================================================================
CANAL = D["en_vivo"]["canal_youtube"]
EPISODIOS = [  # 03 Banco 5: programas anteriores (videos del canal)
    ("Viernes: la previa de Junior vs. Nacional", "1:51:20", "2026-11-06", "Junior"),
    ("Jueves: la lista de la Selección y el mercado", "1:49:40", "2026-11-05", "Selección"),
    ("Miércoles: quién llega y quién se va del Tiburón", "1:42:15", "2026-11-04", "Mercado"),
    ("Martes: ¿es Junior favorito esta semana? El debate", "1:51:28", "2026-11-03", "Debate"),
    ("Lunes: balance de la Fecha 19", "1:46:05", "2026-11-02", "Junior"),
    ("Viernes: la gira de octubre, tres lecciones", "1:44:50", "2026-10-30", "Selección"),
    ("Jueves: el clásico costeño, sin filtros", "1:53:11", "2026-10-29", "Junior"),
    ("Miércoles: cierre de la ventana y salidas", "1:40:02", "2026-10-28", "Mercado"),
]
PARRILLA_SEMANA = [  # 03 Banco 5
    ("10:00", ["Apertura: balance del fin de semana", "Apertura y titulares", "Apertura y titulares",
               "Apertura y titulares", "Apertura: lo que se juega esta noche"]),
    ("10:30", ["Junior en primer plano: la Fecha", "Junior: análisis y plantel", "Junior: el mercado",
               "Junior: el rival y la previa", "Junior: la previa del partido"]),
    ("11:00", ["Selección: la semana", "Selección: rivales", "Selección y mercado",
               "Selección: la convocatoria", "Selección: lista y cifras"]),
    ("11:30", ["Debate de los oyentes", "Debate de los oyentes", "Debate: el tema de la semana",
               "Debate de los oyentes", "Debate de los oyentes y encuesta"]),
]


TEMAS_HOY = e("|".join(p["bloque"] for p in D["programa"]["parrilla"]))


def estado_aire():
    """Estado «Al aire / Próximo programa». main.js muestra uno u otro."""
    return (f'<p class="air-status"><span class="only-aire" hidden>{live_pill("Al aire")} hasta las 12:00 m.</span>'
            f'<span class="only-fuera" data-next-show>Próximo programa · lunes a las 10:00 a. m.</span></p>')


def _portada_video(url, externo=False, prioridad=False, nivel=None):
    """Portada del video de YouTube con play. El iframe real va donde indica el comentario."""
    destino = f' href="{e(url)}"' + (' target="_blank" rel="noopener"' if externo else "")
    sr = "Ver el programa en vivo en YouTube (se abre en una pestaña nueva)" if externo else "Ver el programa en vivo"
    return (
        f'<!-- iframe youtube-nocookie.com/embed/live_stream?channel=ID_DEL_CANAL irá aquí '
        f'(title="El show de las mañanas, en vivo", loading="lazy", allow="autoplay; encrypted-media; picture-in-picture") -->'
        f'<a class="video-cover media"{destino}>'
        f'{img("js-1", clase="media__img", alt="Portada de la transmisión en vivo de El show de las mañanas: Juan Salvador Bárcena ante el micrófono azul de Habla Deportes", prioridad=prioridad)}'
        f'<span class="play play--lg">{icono("play-fill")}</span>'
        f'<span class="chip-media chip-media--tl only-aire" hidden>En directo</span>'
        f'<span class="visually-hidden">{sr}</span></a>')


def en_vivo_ahora():
    """Portada · «En vivo ahora» (03 §1.9): portada del video + estado + próximo programa."""
    return (
        f'<div class="live-now">'
        f'<div class="live-now__media">{_portada_video("en-vivo.html")}</div>'
        f'<div class="live-now__body">'
        f'{estado_aire()}'
        f'<h3 class="live-now__title">El show de las mañanas, en vivo desde Barranquilla</h3>'
        f'<p class="live-now__text">Míralo aquí, sin salir de la web, o ponte al día con los programas anteriores. '
        f'Lunes a viernes, 10:00 a. m. a 12:00 m.</p>'
        f'<p class="live-now__line only-aire" hidden data-now-line="{TEMAS_HOY}">Ahora: {e(D["programa"]["parrilla"][0]["bloque"])} · '
        f'Después: {e(D["programa"]["parrilla"][1]["bloque"])}</p>'
        f'<div class="live-now__actions"><a class="btn btn--primary" href="en-vivo.html">{icono("play-fill")}Ver en vivo</a>'
        f'<a class="btn btn--secondary" href="en-vivo.html#parrilla">Ver parrilla</a></div>'
        f'</div></div>')


def reproductor_youtube():
    """Página En vivo: reproductor principal (portada + play) y enlaces del canal."""
    audio = D["en_vivo"]["audio_solo"]
    return (
        f'<div class="player">'
        f'{_portada_video(CANAL, externo=True, prioridad=True)}'
        f'<div class="player__bar">'
        f'<div><p class="player__title">El show de las mañanas · En directo</p>'
        f'<p class="meta only-aire" hidden data-now-line="{TEMAS_HOY}">Ahora: {e(D["programa"]["parrilla"][0]["bloque"])}</p></div>'
        f'{enlace_externo("Abrir en YouTube", CANAL, clase="btn btn--secondary btn--sm")}'
        f'</div>'
        f'<p class="player__audio"><span class="player__audio-label">{e(audio["texto"])}</span> '
        f'Aún no confirmamos que exista una transmisión solo en audio.</p>'
        f'</div>')


def escaleta():
    pr = D["programa"]
    filas = "".join(
        f'<li class="schedule-row" data-slot-start="{p["hora"]}"><span class="schedule-row__time">{e(p["hora"])}</span>'
        f'<span class="schedule-row__topic">{e(p["bloque"])}'
        f'<span class="schedule-row__now" hidden> · Ahora</span></span></li>'
        for p in pr["parrilla"])
    return f'<ol class="schedule-list" role="list" data-schedule>{filas}</ol>'


def parrilla_semanal():
    dias = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes"]
    cab = "".join(f'<th scope="col">{d}</th>' for d in dias)
    filas = "".join(
        f'<tr><th scope="row" class="week-grid__time">{h}</th>' + "".join(f'<td data-day="{i + 1}">{e(t)}</td>' for i, t in enumerate(c)) + "</tr>"
        for h, c in PARRILLA_SEMANA)
    return (f'<div class="table-scroll" role="region" aria-label="Parrilla semanal, se desplaza de lado" tabindex="0">'
            f'<table class="week-grid" data-week-grid><caption class="visually-hidden">Parrilla de lunes a viernes, de 10:00 a. m. a 12:00 m.</caption>'
            f'<thead><tr><th scope="col">Hora</th>{cab}</tr></thead><tbody>{filas}</tbody></table></div>'
            f'<p class="note">Cada bloque dura 30 minutos; el último cierra a las 12:00 m. El bloque resaltado es el que está al aire ahora.</p>')


def programas_anteriores(n=8):
    """Programas anteriores = videos del canal (03 Banco 5), con filtro por tema."""
    temas = ["Todos"] + sorted({t for *_, t in EPISODIOS[:n]}, key=["Junior", "Selección", "Mercado", "Debate"].index)
    filas = []
    for titulo, dur, fecha, tema in EPISODIOS[:n]:
        filas.append(
            f'<li class="episode-row" data-tema="{_slug(tema)}">'
            f'<a class="audio-play" href="video.html">{icono("play-fill")}<span class="visually-hidden">Ver: {e(titulo)}</span></a>'
            f'<span class="episode-row__body"><a class="episode-row__title" href="video.html">{e(titulo)}</a>'
            f'<span class="meta"><time datetime="{fecha}">{e(dia_corto(fecha))}</time> · {e(dur)} · {e(tema)}</span></span></li>')
    return _filtros("programas", "Filtrar programas por tema",
                    [(t, _slug(t) if t != "Todos" else "todas") for t in temas],
                    f'<ul class="episode-list" role="list" data-filter-items>{"".join(filas)}</ul>',
                    "No hay programas de este tema esta semana. Prueba con otro filtro.")


def participa():
    return (
        f'<form class="contact-form" data-validate novalidate>'
        f'<div class="field"><label class="field__label" for="p-nombre">Tu nombre y ciudad</label>'
        f'<input class="field__input" id="p-nombre" name="nombre" autocomplete="name" required aria-describedby="p-nombre-error">'
        f'<p class="form-error" id="p-nombre-error" data-error hidden>{icono("warning-circle")}<span>Escribe tu nombre y tu ciudad.</span></p></div>'
        f'<fieldset class="field"><legend class="field__label">Tipo de mensaje</legend><div class="choice-row">'
        + "".join(f'<label class="choice"><input type="radio" name="tipo" value="{t}"{" checked" if i == 0 else ""}><span>{t}</span></label>'
                  for i, t in enumerate(["Pregunta", "Opinión", "Saludo"])) +
        f'</div></fieldset>'
        f'<div class="field"><label class="field__label" for="p-mensaje">Tu mensaje</label>'
        f'<textarea class="field__input field__input--area" id="p-mensaje" name="mensaje" rows="4" required aria-describedby="p-mensaje-error"></textarea>'
        f'<p class="form-error" id="p-mensaje-error" data-error hidden>{icono("warning-circle")}<span>Escribe tu mensaje antes de enviarlo.</span></p></div>'
        f'<button class="btn btn--primary" type="submit">Enviar al programa</button>'
        f'<p class="form-success" data-success role="status" hidden>{icono("check")}<span>Recibimos tu mensaje. Gracias por escribir. (Demostración: no se envió nada.)</span></p>'
        f'</form>')


# ==========================================================================
# Comunidad: encuesta, boletín, efemérides
# ==========================================================================
def encuesta():
    en = D["encuesta"]
    ops = "".join(
        f'<li class="poll__option"><label class="choice"><input type="radio" name="encuesta" value="{i}" required>'
        f'<span>{e(o["texto"])}</span></label>'
        f'<span class="poll__result" hidden><span class="poll__bar" data-pct="{o["porcentaje"]}"></span>'
        f'<span class="poll__pct">{o["porcentaje"]} %</span></span></li>'
        for i, o in enumerate(en["opciones"]))
    return (f'<form class="poll" data-poll novalidate><fieldset><legend class="poll__q">{e(en["pregunta"])}</legend>'
            f'<ul class="poll__options" role="list">{ops}</ul></fieldset>'
            f'<p class="form-error" data-error hidden>{icono("warning-circle")}<span>Elige una opción antes de votar.</span></p>'
            f'<div class="poll__foot"><button class="btn btn--secondary" type="submit">Votar</button>'
            f'<p class="meta" data-poll-msg aria-live="polite">{miles(en["votos"])} votos (ejemplo)</p></div></form>')


def boletin():
    return (
        f'<form class="newsletter" data-newsletter novalidate>'
        f'<h3 class="community__title">Todo el fútbol en tu correo</h3>'
        f'<p class="community__text">Cada mañana a las 6:00 a. m.: titulares, datos y el tema que abre el programa.</p>'
        f'<div class="field"><label class="field__label" for="boletin-correo">Correo electrónico</label>'
        f'<p class="field__hint" id="boletin-ayuda">Ejemplo: nombre@correo.com. Un correo al día; te das de baja con un clic.</p>'
        f'<input class="field__input" id="boletin-correo" name="correo" type="email" autocomplete="email" '
        f'inputmode="email" aria-describedby="boletin-ayuda boletin-error" required>'
        f'<p class="form-error" id="boletin-error" data-error hidden>{icono("warning-circle")}'
        f'<span>Revisa el correo: falta el @ o el dominio. Ejemplo: nombre@correo.com</span></p></div>'
        f'<button class="btn btn--primary" type="submit">Recibir el resumen</button>'
        f'<p class="form-success" data-success role="status" hidden>{icono("check")}'
        f'<span>Listo. Mañana a las 6:00 a. m. te llega el primer resumen. (Demostración: no se envió nada.)</span></p>'
        f'</form>')


def efemeride():
    ef = D["efemerides"][0]
    return (f'<p class="ephemeris__date"><time datetime="2026-11-06">6 de noviembre</time></p>'
            f'<p class="ephemeris__title">{e(ef["titulo"])}</p>'
            f'<p class="community__text">{e(ef["texto"])}</p>')


# ==========================================================================
# 6.13 · Pauta y sello de patrocinio
# ==========================================================================
PAUTA = {
    # 02 §5: A superior · B lateral · C cierre
    "superior": ("Publicidad superior", "970 × 90", "320 × 100"),
    "lateral": ("Publicidad lateral", "300 × 250", "300 × 250"),
    "cierre": ("Publicidad, cierre de página", "970 × 90", "320 × 100"),
}


def pauta(slot, pieza=None, fija=None, alt="", destino=None, ancho=None, alto=None):
    """Hueco fijo de pauta (A superior · B lateral · C cierre).

    USO CON PIEZA REAL (GIF/WebP animado de un patrocinador), reglas de 06 D3:
      - peso máximo 250 KB; máximo 3 repeticiones u 8 s de animación; sin sonido;
      - siempre con imagen FIJA de respaldo (primer cuadro): `fija`;
      - con prefers-reduced-motion se muestra solo la imagen fija (el <source> animado
        solo aplica con movimiento permitido);
      - el build no valida el peso: se revisa al cargar la pieza (campo simple en WP).
      pauta("lateral", pieza="assets/img/pauta/marca.gif", fija="assets/img/pauta/marca.webp",
            alt="Anuncio de Marca: mensaje", destino="https://…", ancho=300, alto=250)
    Sin pieza: marcador de posición rotulado (prototipo) que en producción se colapsa.
    """
    nombre, esc, mov = PAUTA[slot]
    reglas = 'data-max-kb="250" data-max-loops="3" data-max-seconds="8"'
    if pieza:
        cuerpo = (f'<a class="ad-slot__frame ad-slot__frame--piece" href="{e(destino)}" target="_blank" rel="sponsored noopener">'
                  f'<picture><source srcset="{e(pieza)}" media="(prefers-reduced-motion: no-preference)">'
                  f'<img src="{e(fija)}" alt="{e(alt)}" width="{ancho}" height="{alto}" loading="lazy" decoding="async"></picture></a>')
    else:
        medidas = (f'<span class="ad-slot__size ad-slot__size--lg">{esc}</span>'
                   f'<span class="ad-slot__size ad-slot__size--sm">{mov}</span>') if esc != mov else f'<span class="ad-slot__size">{esc}</span>'
        cuerpo = (f'<a class="ad-slot__frame" href="contacto.html#publicidad">'
                  f'<span class="ad-slot__placeholder">Espacio publicitario{medidas}'
                  f'<span class="ad-slot__cta">Cómo anunciar aquí</span></span></a>')
    return (f'<!-- slot pauta: {slot}. Piezas animadas: máx. 250 KB, 3 repeticiones u 8 s, sin sonido, '
            f'con imagen fija de respaldo; con movimiento reducido solo el primer cuadro. -->'
            f'<aside class="ad-slot ad-slot--{slot}" aria-label="{nombre}" data-slot="{slot}" {reglas}>'
            f'<p class="ad-slot__label">Publicidad</p>{cuerpo}</aside>')


def sello(texto="Sección de datos presentada por"):
    return (f'<p class="sponsor-seal"><span class="sponsor-seal__text">{e(texto)}</span> '
            f'<!-- logo oficial de Transelca (SVG): pendiente del cliente; su rol de aliado principal está por confirmar -->'
            f'<span class="sponsor-seal__logo" data-logo-pendiente="transelca">Transelca</span>'
            f'<span class="sponsor-seal__note">(rol por confirmar)</span></p>')


def migas(*niveles):
    """niveles: ("Inicio", "index.html"), ("Noticias", "noticias.html"), ... Se renderiza en build (JSON-LD)."""
    items = "".join(f'<li><a href="{e(h)}">{e(t)}</a></li>' for t, h in niveles)
    padre_t, padre_h = niveles[-1]
    return (f'<nav class="breadcrumb" aria-label="Ruta de navegación"><ol class="breadcrumb__list" role="list">{items}</ol>'
            f'<a class="breadcrumb__back" href="{e(padre_h)}">{icono("caret-left", "icon icon--sm")}{e(padre_t)}</a></nav>')


def compartir(texto="Rivas y Castro: el eje que sostiene al líder"):
    from urllib.parse import quote
    q = quote(texto)
    return (f'<div class="share" data-share><p class="share__label">Compartir</p><ul class="share__list" role="list">'
            f'<li><a class="icon-btn" href="https://x.com/intent/post?text={q}" target="_blank" rel="noopener">{icono("x-logo")}'
            f'<span class="visually-hidden">Compartir en X (se abre en una pestaña nueva)</span></a></li>'
            f'<li><a class="icon-btn" href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fhabladeportes.co%2F" '
            f'target="_blank" rel="noopener">{icono("facebook-logo")}<span class="visually-hidden">Compartir en Facebook (se abre en una pestaña nueva)</span></a></li>'
            f'<li><button class="icon-btn" type="button" data-copy>{icono("link")}<span class="visually-hidden">Copiar enlace</span></button></li>'
            f'</ul><p class="share__status" data-copy-status role="status"></p></div>')


def caja_datos_junior():
    f = fila_tabla("junior")
    asist = D["goleadores"]["asistencias"][0]
    gol = D["goleadores"]["goles"][0]
    items = [("Puesto", f"{f['pos']}.º"), ("Puntos", f["pts"]), ("Goles a favor", f["gf"]),
             ("Goles en contra", f["gc"]), (f"Asistencias de {asist['jugador'].split()[1]}", asist["asistencias"]),
             (f"Goles de {gol['jugador'].split()[1]}", gol["goles"])]
    dl = "".join(f'<div class="data-box__item"><dt>{e(k)}</dt><dd>{e(v)}</dd></div>' for k, v in items)
    return (f'<aside class="data-box" aria-labelledby="caja-datos"><h2 class="data-box__title" id="caja-datos">Junior en la Liga, en seis cifras</h2>'
            f'<dl class="data-box__grid">{dl}</dl>'
            f'<div class="data-box__form"><span class="meta">Racha</span>{_forma(f["forma"])}</div>'
            f'{fuente("Datos de ejemplo hasta la Fecha 19 · fuente por definir.")}</aside>')


def etiquetas(*items):
    li = "".join(f'<li><a class="chip" href="{e(h)}">{e(t)}</a></li>' for t, h in items)
    return f'<ul class="chip-list" role="list">{li}</ul>'


def chips_torneos():
    items = [("Liga BetPlay", "liga"), ("Copa Colombia", "copa"), ("Eliminatorias", "eliminatorias"),
             ("Copa Libertadores", "libertadores"), ("Copa Sudamericana", "sudamericana")]
    li = "".join(
        f'<li><a class="chip" href="datos.html?t={k}" data-torneo="{k}"{CUR if k == "liga" else ""}>{e(t)}</a></li>'
        for t, k in items)
    return (f'<ul class="chip-list" role="list" data-torneos>{li}</ul>'
            f'<p class="source-note" data-torneo-aviso hidden>En el prototipo solo hay datos de ejemplo de la Liga BetPlay. '
            f'La fuente de los datos de cada torneo está por definir.</p>')


# ==========================================================================
# Menú: megamenús (escritorio) y hoja del menú móvil · 08-menu-diseno, 09 §3–§4
# Contenido en sitio.PANELES; cifras SIEMPRE de data-ejemplo.json. Las fotos se
# piden con menu=True: exentas de la regla de foto única, pero solo del catálogo.
# Sin h2 dentro del menú (el h1 de la página es único): rótulos en <p>.
# ==========================================================================
EQUIPOS_FOCO = ("junior", "colombia")
_PV = D["partido_en_vivo"]
# Punto rojo de «Datos»: solo con un partido de Junior o de la Selección en juego (09 §6.1)
PARTIDO_EN_JUEGO = _PV["estado"] == "en_vivo" and (_PV["local"] in EQUIPOS_FOCO or _PV["visitante"] in EQUIPOS_FOCO)


def punto_vivo(tipo, clase="live-dot live-dot--ring"):
    """Punto rojo de la barra azul, la hoja y la barra inferior.
    tipo 'aire': lo muestra main.js (constante EN_VIVO). tipo 'partido': lo decide el JSON."""
    if tipo == "aire":
        return (f'<span class="{clase} only-aire" hidden aria-hidden="true"></span>'
                f'<span class="visually-hidden only-aire" hidden>, al aire ahora</span>')
    if tipo == "partido" and PARTIDO_EN_JUEGO:
        return f'<span class="{clase}" aria-hidden="true"></span><span class="visually-hidden">, partido en vivo</span>'
    return ""


def _nota_base(tema):
    return next(n for n in NOTAS_BASE if n["tema"] == tema)


def _menu_enlace(texto, href, slug, clase="mega__link"):
    cur = ' aria-current="page"' if href == f"{slug}.html" else ""
    punto = ""
    if href == "partido.html" and texto == "Partido en vivo":
        if PARTIDO_EN_JUEGO:
            punto = punto_vivo("partido", "live-dot")
        else:
            texto = "Último partido"  # 09 §3.5: sin partido en juego
    return f'<li><a class="{clase}" href="{e(href)}"{cur}>{punto}<span>{e(texto)}</span></a></li>'


def _grupo(rotulo, enlaces, slug, base="mega"):
    gid = nucleo.P.nuevo_id(f"{base}-g-" + _slug(rotulo))
    lis = "".join(_menu_enlace(t, h, slug, f"{base}__link") for t, h in enlaces)
    return (f'<div class="{base}__group"><p class="{base}__label" id="{gid}">{e(rotulo)}</p>'
            f'<ul class="{base}__links" role="list" aria-labelledby="{gid}">{lis}</ul></div>')


def _boton_youtube(texto, url):
    return (f'<a class="btn btn--secondary btn--sm mega__cta" href="{e(url)}" target="_blank" rel="noopener">'
            f'{icono("youtube-logo")}{e(texto)}{icono("arrow-up-right", "icon icon--sm")}'
            f'<span class="visually-hidden"> (se abre en una pestaña nueva)</span></a>')


def _z1(clave, panel, slug):
    visible, sr, href = panel["z0"]
    grupos = panel["z1"]
    corte = len(grupos) - 1 if len(grupos) > 2 else 1   # 3 grupos: 2 + 1 (Noticias); 2 grupos: 1 + 1
    cols = (f'<div class="mega__col">{"".join(_grupo(r, l, slug) for r, l in grupos[:corte])}</div>'
            f'<div class="mega__col">{"".join(_grupo(r, l, slug) for r, l in grupos[corte:])}</div>')
    boton = _boton_youtube(*panel["boton"]) if panel.get("boton") else ""
    return (f'<div class="mega__z1">'
            f'<a class="mega__title" href="{e(href)}"><span class="visually-hidden">{e(sr)}</span>{e(visible)}'
            f'{icono("arrow-right", "icon mega__arrow")}</a>'
            f'<div class="mega__cols">{cols}</div>{boton}</div>')


def _media_menu(clave, play=None, chip=None, duracion=None, chip_vivo=False, clase="mega-feature__media"):
    partes = [img(clave, clase="media__img", alt="", menu=True)]
    if play:
        partes.append(f'<span class="play play--{play}" aria-hidden="true">{icono("play-fill")}</span>')
    if chip_vivo:
        partes.append(f'<span class="mega-feature__badge only-aire" hidden>{live_pill("Al aire")}</span>'
                      f'<span class="chip-media chip-media--tl only-fuera">Próximo programa</span>')
    if chip:
        partes.append(f'<span class="chip-media chip-media--tl">{e(chip)}</span>')
    if duracion:
        partes.append(f'<span class="chip-media chip-media--br"><span class="visually-hidden">Duración: </span>{e(duracion)}</span>')
    return f'<span class="{clase} media">{"".join(partes)}</span>'


def _z2(panel):
    z = panel["z2"]
    if z["tipo"] == "tabla":
        tit, href = z["enlace"]
        return (f'<div class="mega__z2 mega-table">'
                f'<p class="mega__module">{e(z["titulo"])}</p>'
                f'{tabla_posiciones("mini", filas=z["filas"])}'
                f'<p class="mega-table__foot"><span>Hasta la Fecha 19 · {e(FUENTE.rstrip("."))}</span>'
                f'<a class="mega__more" href="{e(href)}">{e(tit)}{icono("caret-right", "icon icon--sm")}</a></p></div>')
    if z["tipo"] == "programa":
        pr = D["programa"]
        return (f'<div class="mega__z2"><a class="mega-feature" href="{e(z["href"])}">'
                f'{_media_menu(z["imagen"], play="lg", chip_vivo=True)}'
                f'<span class="mega-feature__title">{e(z["titular"])}</span>'
                f'<span class="mega-feature__actions"><span class="btn btn--primary btn--sm mega-feature__btn">{icono("play-fill")}'
                f'<span class="only-aire" hidden>Ver en vivo</span><span class="only-fuera">Ver el último programa</span></span>'
                f'<span class="mega-feature__when"><span class="only-aire" hidden>Al aire hasta las 12:00 m.</span>'
                f'<span class="only-fuera">Lunes a viernes, 10:00 a. m.</span></span></span>'
                f'</a></div>')
    if z["tipo"] == "video":
        meta = f'{z["duracion"]} · {dia_corto(z["fecha"]).lower()}'
        etiqueta, titular = z["etiqueta"], z["titular"]
        media = _media_menu(z["imagen"], play="sm", duracion=z["duracion"])
        tipo = '<span class="visually-hidden">Video: </span>'
    else:  # nota
        n = _nota_base(z["nota"]) if z.get("nota") else None
        titular = n["titular"] if n else z["titular"]
        meta = f'{n["lectura_min"]} min de lectura' if n else z.get("meta", "")
        etiqueta = z["etiqueta"]
        media = _media_menu(z["imagen"])
        tipo = ""
    return (f'<div class="mega__z2"><a class="mega-feature" href="{e(z["href"])}">{media}'
            f'<span class="mega-feature__label">{e(etiqueta)}</span>'
            f'<span class="mega-feature__title">{tipo}{e(titular)}</span>'
            f'<span class="mega-feature__meta">{e(meta)}</span></a></div>')


def _ficha_partido(equipo_foco):
    """Datos de la tarjeta de partido del panel (mismo formato que el banner de partidos)."""
    if equipo_foco == "junior":
        if _PV["estado"] == "en_vivo":
            return {"torneo": _PV["torneo"], "estado": "vivo", "minuto": _PV["minuto"], "local": _PV["local"],
                    "visitante": _PV["visitante"], "gl": _PV["marcador"]["local"], "gv": _PV["marcador"]["visitante"],
                    "linea": f'{_PV["estadio"].replace("Estadio ", "", 1)} · {_PV["canal"]}', "href": "partido.html",
                    "cta": "Seguir el partido"}
        p = D["calendario_junior"]["proximos"][1]
        return {"torneo": f"Liga BetPlay · Fecha {p['fecha']}", "estado": "proximo", **_pick(p),
                "linea": f'{dia_corto(p["dia"])} · {p["canal"]}', "href": "junior.html#partidos", "cta": "Ver el partido"}
    p = D["calendario_seleccion"]["proximos"][0]
    return {"torneo": p["torneo"], "estado": "proximo", **_pick(p),
            "linea": f'{dia_corto(p["dia"])} · {p["canal"]}', "href": "seleccion.html#partidos",
            "cta": "Ver los partidos"}


def tarjeta_partido_menu(equipo_foco):
    """Tarjeta oscura de partido (Z3 de Junior y Selección). Reutiliza las piezas del banner
    de partidos (píldora en vivo, marcador u hora con su texto para lector de pantalla y
    escudos) en tamaño compacto: escudos de 48 a los lados del marcador, nombres debajo."""
    s = _ficha_partido(equipo_foco)
    if s["estado"] == "vivo":
        estado = live_pill("En vivo", s["minuto"])
    elif s["estado"] == "final":
        estado = '<span class="status-text">Final</span>'
    else:
        estado = '<span class="mega-match__chip">Próximo partido</span>'
    return (f'<a class="mega-match" href="{e(s["href"])}">'
            f'<span class="mega-match__top"><span>{e(s["torneo"])}</span>{estado}</span>'
            f'<span class="mega-match__teams">{escudo(s["local"], "crest crest--lg")}'
            f'{_marcador(s, "span")}{escudo(s["visitante"], "crest crest--lg")}</span>'
            f'<span class="mega-match__names" aria-hidden="true"><span>{e(equipo(s["local"])["corto"])}</span>'
            f'<span>{e(equipo(s["visitante"])["corto"])}</span></span>'
            f'<span class="mega-match__line">{e(s["linea"])}</span>'
            f'<span class="mega-match__cta">{e(s["cta"])}{icono("caret-right", "icon icon--sm")}</span></a>')


def _z3(panel):
    z = panel["z3"]
    tipo = z["tipo"]
    if tipo == "shorts":
        piezas = "".join(
            f'<li><a class="mega-short" href="{e(z["href"])}">'
            f'{_media_menu(foto, chip="Short", duracion=d, clase="mega-short__media media--9x16")}'
            f'<span class="mega-short__title"><span class="visually-hidden">Short: </span>{e(t)}</span></a></li>'
            for t, d, foto in z["piezas"])
        return (f'<div class="mega__z3"><div class="mega__module-head"><p class="mega__module">{e(z["titulo"])}</p>'
                f'<a class="mega__more" href="{e(z["href"])}">{e(z["enlace"])}<span class="visually-hidden"> los Shorts</span>{icono("caret-right", "icon icon--sm")}</a></div>'
                f'<ul class="mega-shorts" role="list">{piezas}</ul></div>')
    if tipo == "titulares":
        notas = {n["id"]: n for n in D["noticias_por_tema"]["notas"]}
        temas = {t["tema"]: t["etiqueta"] for t in D["noticias_por_tema"]["temas"]}
        filas = "".join(
            f'<li><a class="mega-headline" href="{e(h)}"><span class="mega-headline__topic">{e(temas[notas[i]["tema"]])}</span>'
            f'<span class="mega-headline__title">{e(notas[i]["titular"])}</span></a></li>' for i, h in z["filas"])
        return (f'<div class="mega__z3"><p class="mega__module">{e(z["titulo"])}</p>'
                f'<ul class="mega-headlines" role="list">{filas}</ul></div>')
    if tipo == "partido":
        extra = ""
        if z["equipo"] == "junior":
            f = fila_tabla("junior")
            extra = (f'<div class="mega-match__after">{_forma(f["forma"])}'
                     f'<span class="mega-match__note">{"Líder" if f["pos"] == 1 else str(f["pos"]) + ".º"} · {f["pts"]} pts</span></div>')
        else:
            p = D["calendario_seleccion"]["proximos"][1]
            extra = (f'<p class="mega-match__after mega-match__note">Después: {e(equipo(p["local"])["corto"])} vs '
                     f'{e(equipo(p["visitante"])["corto"])} · {e(dia_corto(p["dia"]).lower())} · {e(hora(p["hora"]))}</p>')
        return (f'<div class="mega__z3"><p class="mega__module">{e(z["titulo"])}</p>'
                f'{tarjeta_partido_menu(z["equipo"])}{extra}</div>')
    if tipo == "agenda":
        fd = D["resultados"]["filtro_dias"]
        filas = []
        for cuando in ("hoy", "manana"):
            for pid in fd[cuando]["partidos"]:
                p = partido_por_id(pid)
                vivo = p.get("estado") == "en_vivo"
                d = fecha(p["dia"])
                dia = "Hoy" if cuando == "hoy" else DIAS[d.weekday()].capitalize()
                loc, vis = equipo(p["local"])["corto"], equipo(p["visitante"])["corto"]
                if vivo:
                    partido = f'{e(loc)} {p["gl"]}-{p["gv"]} {e(vis)}'
                    estado = live_pill("En vivo", p["minuto"])
                else:
                    partido = f'{e(loc)} - {e(vis)}'
                    estado = f'<span class="mega-fixture__time">{e(hora(p["hora"]))}</span>'
                filas.append(
                    f'<li><a class="mega-fixture{" is-today" if cuando == "hoy" else ""}" href="{"partido.html" if vivo else "datos.html#hoy"}">'
                    f'<span class="mega-fixture__day"><time datetime="{p["dia"]}">{e(dia)}</time></span>'
                    f'<span class="mega-fixture__teams">{partido}</span>{estado}</a></li>')
            if cuando == "hoy" and not fd["hoy"]["partidos"]:
                filas.append('<li class="mega-fixture mega-fixture--empty">Sin partidos de Liga hoy</li>')
        return (f'<div class="mega__z3"><p class="mega__module">{e(z["titulo"])}</p>'
                f'<ul class="mega-fixtures" role="list">{"".join(filas)}</ul>'
                f'<p class="mega__source">{e(FUENTE.rstrip("."))}</p></div>')
    # tipo == "parrilla": Ahora y después (programa.parrilla) + partido en juego
    pr = D["programa"]["parrilla"]
    temas = e("|".join(p["bloque"] for p in pr))
    horas = e("|".join(p["hora"] for p in pr))
    filas = (
        f'<li class="mega-slot mega-slot--now only-aire" hidden data-slot="ahora">'
        f'<span class="mega-slot__time" data-slot-time>{e(pr[0]["hora"])}</span>'
        f'<span class="mega-slot__body"><span class="mega-slot__state"><span class="live-dot" aria-hidden="true"></span>Ahora</span>'
        f'<a class="mega-slot__title" href="en-vivo.html#parrilla" data-slot-topic>{e(pr[0]["bloque"])}</a></span></li>'
        f'<li class="mega-slot only-fuera"><span class="mega-slot__time">10:00</span>'
        f'<span class="mega-slot__body"><span class="mega-slot__state">Próximo programa</span>'
        f'<a class="mega-slot__title" href="en-vivo.html#parrilla">Lunes a viernes, 10:00 a. m.</a></span></li>'
        f'<li class="mega-slot only-aire" hidden data-slot="despues">'
        f'<span class="mega-slot__time" data-slot-time>{e(pr[1]["hora"])}</span>'
        f'<span class="mega-slot__body"><span class="mega-slot__state">Después</span>'
        f'<a class="mega-slot__title" href="en-vivo.html#parrilla" data-slot-topic>{e(pr[1]["bloque"])}</a></span></li>')
    if PARTIDO_EN_JUEGO:
        loc, vis = equipo(_PV["local"])["corto"], equipo(_PV["visitante"])["corto"]
        filas += (f'<li class="mega-slot mega-slot--match"><span class="mega-slot__time mega-slot__time--live">{_PV["minuto"]}\'</span>'
                  f'<span class="mega-slot__body"><span class="mega-slot__state"><span class="live-dot" aria-hidden="true"></span>Partido en juego</span>'
                  f'<a class="mega-slot__title" href="partido.html">{e(loc)} {_PV["marcador"]["local"]}-{_PV["marcador"]["visitante"]} {e(vis)}'
                  f'<span class="visually-hidden">, minuto {_PV["minuto"]}</span></a></span></li>')
    return (f'<div class="mega__z3"><p class="mega__module">{e(panel["z3"]["titulo"])}</p>'
            f'<ul class="mega-slots" role="list" data-slots="{temas}" data-slot-hours="{horas}">{filas}</ul></div>')


def _panel_mas(panel, slug):
    from sitio import REDES
    (rotulo, enlaces), = panel["z1"]
    redes = "".join(
        f'<li><a class="icon-btn" href="{r["url"]}" target="_blank" rel="noopener">{icono(r["icono"])}'
        f'<span class="visually-hidden">{r["nombre"]} (se abre en una pestaña nueva)</span></a></li>' for r in REDES)
    pf = panel["perfil"]
    links = "".join(_menu_enlace(t, h, slug) for t, h in pf["enlaces"])
    return (f'<div class="mega__inner mega__inner--compact">'
            f'<div class="mega__z1">{_grupo(rotulo, enlaces, slug)}'
            f'<ul class="mega__social" role="list" aria-label="Habla Deportes en redes">{redes}</ul></div>'
            f'<div class="mega__z2"><p class="mega__label">{e(pf["etiqueta"])}</p>'
            f'<a class="mega-profile" href="{e(pf["href"])}">'
            f'<span class="mega-profile__media">{img(pf["imagen"], clase="mega-profile__img", alt="", menu=True)}</span>'
            f'<span class="mega-profile__name">{e(pf["nombre"])}</span>'
            f'<span class="mega-profile__role">{e(pf["cargo"])}</span></a>'
            f'<ul class="mega__links" role="list">{links}</ul></div></div>')


def megamenu(clave, slug):
    """Panel de un ítem de la barra azul. Hermano del enlace (no hijo): el enlace conserva
    un solo nombre accesible. Cerrado = visibility: hidden (sin foco ni lectura)."""
    from sitio import PANELES
    panel = PANELES[clave]
    if clave == "mas":
        cuerpo = _panel_mas(panel, slug)
    else:
        cuerpo = f'<div class="container mega__inner">{_z1(clave, panel, slug)}{_z2(panel)}{_z3(panel)}</div>'
    variante = " mega--compact" if clave == "mas" else ""
    return f'<div class="mega{variante}" id="mega-{clave}" data-mega-panel>{cuerpo}</div>'


def hoja_menu(slug):
    """Hoja del menú móvil (< 1024 y pantallas táctiles): buscador, «Ahora», seis filas con
    miniatura y acordeón, «Más» y redes (08 §6, 09 §4)."""
    from sitio import MAS_MOVIL, PAGINAS, PANELES, PRIMARIO, REDES, SUGERENCIAS
    actual = PAGINAS[slug]["primario"]
    chips = "".join(f'<li><a class="chip" href="buscar.html?q={q}">{e(t)}</a></li>' for t, q in SUGERENCIAS)
    # Ahora: programa (al aire / próximo) y partido de Junior o la Selección en juego. Sin foto (08 §6.3).
    ahora = (f'<li><a class="sheet-now" href="en-vivo.html">'
             f'<span class="sheet-now__state"><span class="only-aire" hidden>{live_pill("Al aire")}</span>'
             f'<span class="sheet-now__chip only-fuera">Próximo programa</span></span>'
             f'<span class="sheet-now__body"><span class="sheet-now__title">El show de las mañanas</span>'
             f'<span class="sheet-now__cta"><span class="only-aire" hidden>Ver en vivo</span>'
             f'<span class="only-fuera">Lunes a viernes, 10:00 a. m.</span>{icono("caret-right", "icon icon--sm")}</span></span></a></li>')
    if PARTIDO_EN_JUEGO:
        loc, vis = equipo(_PV["local"]), equipo(_PV["visitante"])
        ahora += (f'<li><a class="sheet-now" href="partido.html">'
                  f'<span class="sheet-now__state">{live_pill("En vivo", _PV["minuto"])}</span>'
                  f'<span class="sheet-now__body"><span class="sheet-now__score">{escudo(_PV["local"], "crest crest--sm")}'
                  f'<span aria-hidden="true">{e(loc["corto"])} {_PV["marcador"]["local"]}-{_PV["marcador"]["visitante"]} {e(vis["corto"])}</span>'
                  f'<span class="visually-hidden">{e(loc["corto"])} {_PV["marcador"]["local"]}, {e(vis["corto"])} {_PV["marcador"]["visitante"]}</span>'
                  f'{escudo(_PV["visitante"], "crest crest--sm")}</span>'
                  f'<span class="sheet-now__cta">Seguir el partido{icono("caret-right", "icon icon--sm")}</span></span></a></li>')
    filas = []
    for it in PRIMARIO:
        clave = it["clave"]
        if clave == "mas":
            continue
        panel = PANELES[clave]
        desc, foto = panel["movil"]
        abierto = clave == actual
        cur = ' aria-current="page"' if it["href"] == f"{slug}.html" else ""
        pid = f"hoja-{clave}"
        grupos = "".join(_grupo(r, l, slug, "sheet-sub") for r, l in panel["z1"])
        filas.append(
            f'<li class="sheet-section">'
            f'<div class="sheet-section__row">'
            f'<a class="sheet-section__link" href="{e(it["href"])}"{cur}>'
            f'<span class="sheet-section__thumb media">{img(foto, clase="media__img", alt="", menu=True)}</span>'
            f'<span class="sheet-section__text"><span class="sheet-section__name">{punto_vivo(it.get("punto"), "live-dot")}'
            f'<span>{e(it["texto"])}</span></span>'
            f'<span class="sheet-section__desc">{e(desc)}</span></span></a>'
            f'<button class="sheet-section__toggle" type="button" aria-expanded="{"true" if abierto else "false"}" '
            f'aria-controls="{pid}" data-accordion>{icono("caret-down", "icon chevron")}'
            f'<span class="visually-hidden">Enlaces de {e(it["texto"])}</span></button></div>'
            f'<div class="sheet-section__panel" id="{pid}"{"" if abierto else " hidden"}>{grupos}</div></li>')
    mas = "".join(f'<li><a class="sheet-more__link" href="{e(h)}">{e(t)}</a></li>' for t, h in MAS_MOVIL)
    redes = "".join(
        f'<li><a class="icon-btn" href="{r["url"]}" target="_blank" rel="noopener">{icono(r["icono"])}'
        f'<span class="visually-hidden">{r["nombre"]} (se abre en una pestaña nueva)</span></a></li>' for r in REDES)
    return (
        f'<div class="menu-sheet" id="menu-movil" hidden data-menu>'
        f'<div class="menu-sheet__inner">'
        f'<form class="menu-sheet__search" action="buscar.html" method="get" role="search" aria-label="Buscar desde el menú">'
        f'<label class="field__label" for="q-menu">Buscar en Habla Deportes</label>'
        f'<div class="search-bar__row"><input class="field__input" id="q-menu" name="q" type="search" '
        f'placeholder="Equipo, jugador o tema" autocomplete="off" enterkeyhint="search">'
        f'<button class="btn btn--primary" type="submit">Buscar</button></div></form>'
        f'<ul class="chip-list menu-sheet__suggest" role="list" aria-label="Búsquedas sugeridas">{chips}</ul>'
        f'<p class="menu-sheet__heading" id="hoja-ahora">Ahora</p>'
        f'<ul class="sheet-now-list" role="list" aria-labelledby="hoja-ahora">{ahora}</ul>'
        f'<nav class="sheet-sections" aria-label="Menú: secciones"><ul role="list">{"".join(filas)}</ul></nav>'
        f'<p class="menu-sheet__heading" id="hoja-mas">Más</p>'
        f'<ul class="sheet-more" role="list" aria-labelledby="hoja-mas">{mas}</ul>'
        f'<ul class="sheet-social" role="list" aria-label="Habla Deportes en redes">{redes}</ul>'
        f'</div></div>')


# ==========================================================================
# Página provisional («En construcción»)
# ==========================================================================
def en_construccion(titulo, texto, anclas=(), enlaces=(), filtro_pendiente=None):
    """filtro_pendiente=(parámetro, aviso): el menú ya enlaza ese filtro (p. ej. ?tema=) pero la
    página aún no filtra. main.js muestra el aviso solo si la URL lo trae: degrada a la lista completa."""
    secciones = "".join(
        f'<section class="stub__section" id="{e(i)}" aria-labelledby="{e(i)}-t"><h2 class="stub__h2" id="{e(i)}-t">{e(t)}</h2>'
        f'<p>Este bloque se construye en la siguiente etapa.</p></section>' for i, t in anclas)
    links = "".join(f'<li><a class="link-action" href="{e(h)}">{e(t)}{icono("caret-right", "icon icon--sm")}</a></li>'
                    for t, h in enlaces)
    nav = (f'<nav class="stub__links" aria-label="Mientras tanto"><h2 class="stub__h2">Mientras tanto</h2>'
           f'<ul role="list">{links}</ul></nav>') if links else ""
    aviso = ""
    if filtro_pendiente:
        param, msg = filtro_pendiente
        aviso = f'<p class="note" data-filter-pending="{e(param)}" role="status" hidden>{e(msg)}</p>'
    return (f'<div class="container stub"><p class="stub__badge">Página en construcción</p>'
            f'<h1 class="page-title">{e(titulo)}</h1><p class="standfirst">{e(texto)}</p>{aviso}{nav}{secciones}</div>')


def buscador_404():
    return (f'<form class="search-inline" action="buscar.html" method="get" role="search" aria-label="Buscar en el sitio">'
            f'<label class="field__label" for="q404">Buscar en Habla Deportes</label>'
            f'<div class="search-inline__row"><input class="field__input" id="q404" name="q" type="search" '
            f'placeholder="Equipo, jugador o tema" enterkeyhint="search">'
            f'<button class="btn btn--primary" type="submit">Buscar</button></div></form>')


# ==========================================================================
# Filtros por tema (enlaces reales ?tema=; main.js filtra en el cliente)
# ==========================================================================
def _filtros(id, etiqueta, opciones, contenido, vacio, pagina=None):
    enlaces = "".join(
        f'<li><a class="chip" href="{e(pagina + ("" if clave == "todas" else "?tema=" + clave)) if pagina else "?tema=" + clave}" '
        f'data-filter="{clave}"{CUR if clave == "todas" else ""}>{e(t)}</a></li>'
        for t, clave in opciones)
    return (f'<div class="filterable" data-filterable="{id}"{" data-filter-url" if pagina else ""}>'
            f'<nav class="filter-bar" aria-label="{e(etiqueta)}"><ul class="chip-list" role="list">{enlaces}</ul></nav>'
            f'<p class="visually-hidden" data-filter-status role="status"></p>'
            f'{contenido}<p class="empty-state" data-filter-empty hidden>{e(vacio)}</p></div>')


NOTAS_BASE = [  # Notas de Junior, Selección, Mercado y Opinión ya presentes en el sitio (03 Banco 2 y 6)
    {"tema": "junior", "etiqueta": "Junior", "titular": "Rivas y Castro: el eje que sostiene al líder",
     "bajada": "Junior suma cinco partidos sin perder y lidera la Liga con 39 puntos antes de recibir a Nacional.",
     "lectura_min": 5, "fecha": "2026-11-06", "url": "articulo.html"},
    {"tema": "seleccion", "etiqueta": "Selección", "titular": "La lista para Perú y Chile: cinco novedades y una ausencia que se discute",
     "bajada": "El técnico llamó a 26 jugadores para la fecha FIFA de noviembre. El medio campo es la línea con más cambios.",
     "lectura_min": 6, "fecha": "2026-11-06", "url": "articulo.html"},
    {"tema": "mercado", "etiqueta": "Mercado", "titular": "Junior negocia un extremo con pasado europeo: qué está firmado y qué no",
     "bajada": "Conversaciones avanzadas con Medellín; falta acordar la duración del contrato (dato de ejemplo).",
     "lectura_min": 4, "fecha": "2026-11-05", "url": "mercado.html"},
    {"tema": "opinion", "etiqueta": "Opinión", "titular": "Equilibrio o creatividad: el dilema del medio campo",
     "bajada": "Dos formas de armar el centro de la cancha y lo que cada una le cuesta al equipo. Columna de ejemplo.",
     "lectura_min": 5, "fecha": "2026-11-04", "url": "juan-salvador.html#columnas"},
]


def _nota_texto(n, nivel=3, con_tema=True):
    url = n.get("url", "articulo.html")
    tema = f'<p class="topic">{e(n["etiqueta"])}</p>' if con_tema else ""
    return (f'<article class="text-card" data-tema="{n["tema"]}">{tema}'
            f'<h{nivel} class="text-card__title"><a class="stretched-link" href="{e(url)}">{e(n["titular"])}</a></h{nivel}>'
            f'<p class="text-card__dek">{e(n["bajada"])}</p>'
            f'<p class="meta"><time datetime="{n["fecha"]}">{e(dia_corto(n["fecha"]))}</time> · {n["lectura_min"]} min de lectura</p></article>')


def noticias_hub():
    """noticias.html: barra de filtros por tema + lista de notas (Banco 10)."""
    npt = D["noticias_por_tema"]
    notas = NOTAS_BASE[:2] + npt["notas"] + NOTAS_BASE[2:]
    opciones = [(t["etiqueta"], t["tema"]) for t in npt["temas"]]
    lista = "".join(f'<li>{_nota_texto(n)}</li>' for n in notas)
    return _filtros("noticias", "Filtrar noticias por tema", opciones,
                    f'<ul class="news-list" role="list" data-filter-items>{lista}</ul>',
                    "Todavía no hay notas de este tema. Mira las últimas de otros temas.", pagina="noticias.html")


def noticias_por_tema():
    """Portada · carril «Noticias por tema» (03 §1.9b): una nota por tema, sin foto."""
    npt = D["noticias_por_tema"]
    cols = []
    for clave in ("futbol-colombiano", "europa", "colombianos-exterior"):
        tema = next(t for t in npt["temas"] if t["tema"] == clave)
        nota = next(n for n in npt["notas"] if n["tema"] == clave)
        cols.append(f'<div class="topic-col"><a class="topic-col__label" href="noticias.html?tema={clave}">{e(tema["etiqueta"])}'
                    f'{icono("caret-right", "icon icon--sm")}</a>{_nota_texto(nota, con_tema=False)}</div>')
    return f'<div class="topic-cols">{"".join(cols)}</div>'


# ==========================================================================
# Aliados (06 D3): muro monocromo, mismo alto visual. No es publicidad.
# ==========================================================================
ALTO_LOGO = 32  # px de alto visual común


def _logo_aliado(a, alto):
    if a["tono"] == "texto" or not a.get("archivo"):
        return f'<span class="ally__wordmark" data-logo-pendiente="{a["id"]}">{e(a["nombre"])}</span>'
    w, h = a.get("ancho"), a.get("alto")
    if not w:
        w, h = imagenes.dims_salida(a["archivo"])
    ancho = round(w * alto / h)
    tope = w // 2  # pantallas 2x: no mostrar más ancho que la mitad de los píxeles reales (evita el pixelado)
    if ancho > tope:
        ancho, alto = tope, round(h * tope / w)
    return (f'<img class="ally__logo" src="{e(a["archivo"])}" alt="{e(a["nombre"])}" width="{ancho}" height="{alto}" '
            f'loading="lazy" decoding="async">')


def aliados(variante="muro"):
    from datos import ALIADOS
    orden = sorted(ALIADOS, key=lambda a: a["rol"] != "principal")  # Transelca primero; Stake nunca primero
    if orden[0]["id"] == "stake":
        raise nucleo.ErrorDeContenido("aliados: Stake no puede ir primero")
    alto = ALTO_LOGO if variante == "muro" else 24
    items = []
    for a in orden:
        cuerpo = _logo_aliado(a, alto)
        rol = '<span class="ally__role">Aliado principal (por confirmar)</span>' if a["rol"] == "principal" else ""
        if a.get("web") and a.get("sitio_confirmado"):
            cuerpo = (f'<a class="ally__link" href="{e(a["web"])}" target="_blank" rel="noopener">{cuerpo}'
                      f'<span class="visually-hidden">: ir al sitio (se abre en una pestaña nueva)</span></a>')
        principal = " ally--principal" if a["rol"] == "principal" else ""
        items.append(f'<li class="ally ally--{a["tono"]}{principal}">{cuerpo}{rol}</li>')
    return (f'<!-- Muro de aliados: datos en propuesta/nueva/aliados.json. Logos provisionales, no oficiales. '
            f'No es publicidad: sin rótulo «Publicidad». -->'
            f'<ul class="allies allies--{variante}" role="list">{"".join(items)}</ul>'
            f'<p class="allies__legal">Juega con responsabilidad. Solo mayores de 18 años.</p>')


def siguenos():
    from sitio import REDES
    li = "".join(f'<li><a class="icon-btn" href="{r["url"]}" target="_blank" rel="noopener">{icono(r["icono"])}'
                 f'<span class="visually-hidden">{r["nombre"]} (se abre en una pestaña nueva)</span></a></li>' for r in REDES)
    return f'<p class="community__text">Síguenos donde prefieras.</p><ul class="share__list" role="list">{li}</ul>'


REGISTRO = {f.__name__: f for f in [
    foto, ico, rotulo, fuente, ejemplo, live_pill, tarjeta, short, carril_shorts, programa, titulares,
    centro_partidos, franja_dias, resultados, proximos_partidos, tabla_posiciones, goleadores, estadisticas,
    tarjeta_en_vivo, racha, mercado, escaleta, estado_aire, en_vivo_ahora, reproductor_youtube, parrilla_semanal,
    programas_anteriores, participa, noticias_hub, noticias_por_tema, aliados, siguenos, encuesta, boletin,
    efemeride, pauta, sello, migas, compartir, caja_datos_junior, etiquetas, chips_torneos, en_construccion,
    buscador_404]}
REGISTRO["escudo"] = escudo


# --- Registro de los grupos de páginas (cada grupo vive en su propio módulo) ---
def _unir_registros():
    import importlib
    for mod in ("componentes_a", "componentes_b"):
        try:
            m = importlib.import_module(mod)
        except ImportError:
            continue
        REGISTRO.update(getattr(m, "REGISTRO_" + mod[-1].upper(), {}))
_unir_registros()
