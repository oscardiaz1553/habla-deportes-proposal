"""Componentes que las páginas invocan con {{ nombre(argumentos) }}.

Regla: cada función devuelve HTML de UN componente de 01-sistema-de-diseno.md
(o un bloque de datos de 02-arquitectura.md). Las clases son estables y legibles
para mapearlas luego a bloques/plantillas de WordPress.
Los datos salen SIEMPRE de datos.D (data-ejemplo.json).
"""
import datetime as dt

import nucleo
from datos import (D, FUENTE, HOY, dia_corto, dia_largo, equipo, fila_tabla, forma_texto, hora,
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


def carril_shorts(id, etiqueta, items):
    """Carril de Shorts con flechas (01 §6.5). items: [(foto, titulo, duracion)]."""
    contenido = "".join(short(*x) for x in items)
    return (
        f'<div class="rail" data-rail>'
        f'<div class="rail__controls">'
        f'<button class="icon-btn" type="button" data-rail-prev aria-controls="{id}">'
        f'{icono("caret-left")}<span class="visually-hidden">Shorts anteriores</span></button>'
        f'<button class="icon-btn" type="button" data-rail-next aria-controls="{id}">'
        f'{icono("caret-right")}<span class="visually-hidden">Shorts siguientes</span></button></div>'
        f'<ul class="rail__track" id="{id}" role="list" aria-label="{e(etiqueta)}">{contenido}</ul></div>')


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
        if s["estado"] in ("vivo", "final"):
            centro = (f'<p class="match-slide__score"><span aria-hidden="true">{s["gl"]}'
                      f'<span class="match-slide__dash">-</span>{s["gv"]}</span>'
                      f'<span class="visually-hidden">{e(loc["corto"])} {s["gl"]}, {e(vis["corto"])} {s["gv"]}</span></p>')
        else:
            hh, ampm = hora(s["hora"]).split(" ", 1)
            centro = (f'<p class="match-slide__time"><span class="visually-hidden">{e(loc["corto"])} contra '
                      f'{e(vis["corto"])}, a las </span>{e(hh)}<span class="match-slide__ampm">{e(ampm)}</span></p>')
        info = [f'{icono("calendar-blank", "icon icon--sm")}<span>{e(dia_corto(s["dia"]))}'
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
            f'<ul class="match-slide__info" role="list">{info_html}</ul>'
            f'<a class="match-slide__cta" href="partido.html">{e(s["cta"])}'
            f'<span class="visually-hidden">: {e(loc["corto"])} contra {e(vis["corto"])}</span>'
            f'{icono("caret-right", "icon icon--sm")}</a>'
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
        f'<aside class="match-center__sponsor" aria-label="Publicidad del Centro de Partidos">'
        f'<!-- Pieza del patrocinador (322×424). Fija: no rota con las diapositivas (04 §4). -->'
        f'{img("anuncio-transelca", clase="match-center__ad")}'
        f'<p class="sponsor-seal sponsor-seal--inline"><span class="sponsor-seal__text">Centro de Partidos presentado por</span> '
        f'<span class="sponsor-seal__logo" data-logo-pendiente="transelca">Transelca</span></p>'
        f'</aside></section>')


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
    lista = tp["filas"][:filas] if variante == "compacta" else tp["filas"]
    if variante == "compacta" and not any(f["equipo"] == "junior" for f in lista):
        lista = lista + [fila_tabla("junior")]
    completa = variante == "completa"
    cab = ['<th scope="col" class="num">#</th>', '<th scope="col" class="team-col">Equipo</th>',
           '<th scope="col" class="num"><abbr title="Partidos jugados">PJ</abbr></th>']
    if completa:
        cab += ['<th scope="col" class="num"><abbr title="Ganados">G</abbr></th>',
                '<th scope="col" class="num"><abbr title="Empatados">E</abbr></th>',
                '<th scope="col" class="num"><abbr title="Perdidos">P</abbr></th>',
                '<th scope="col" class="num"><abbr title="Goles a favor">GF</abbr></th>',
                '<th scope="col" class="num"><abbr title="Goles en contra">GC</abbr></th>']
    cab += ['<th scope="col" class="num"><abbr title="Diferencia de goles">DG</abbr></th>',
            '<th scope="col" class="num"><abbr title="Puntos">Pts</abbr></th>']
    if completa:
        cab.append('<th scope="col" class="form-col">Racha</th>')
    cuerpo = []
    for f in lista:
        eq = equipo(f["equipo"])
        zona = f["pos"] <= hasta
        marca = (f'<span class="zone-mark" aria-hidden="true"></span>'
                 f'<span class="visually-hidden">, zona de cuadrangulares</span>') if zona else '<span class="zone-mark zone-mark--none" aria-hidden="true"></span>'
        celdas = [f'<td class="num pos">{marca}{f["pos"]}</td>',
                  f'<th scope="row" class="team-col"><span class="team-cell">{escudo(f["equipo"], "crest crest--xs")}'
                  f'<span>{e(eq["corto"])}</span></span></th>',
                  f'<td class="num">{f["pj"]}</td>']
        if completa:
            celdas += [f'<td class="num">{f[k]}</td>' for k in ("g", "e", "p", "gf", "gc")]
        celdas += [f'<td class="num">{signo(f["dg"])}</td>', f'<td class="num pts">{f["pts"]}</td>']
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
# 6.11 · Radio («La escaleta») y episodios
# ==========================================================================
EPISODIOS = [
    ("Viernes: la previa de Junior vs. Nacional", "1:51:20", "2026-11-06", "Junior"),
    ("Jueves: la lista de la Selección y el mercado", "1:49:40", "2026-11-05", "Selección"),
    ("Miércoles: quién llega y quién se va del Tiburón", "1:42:15", "2026-11-04", "Mercado"),
    ("Martes: ¿es Junior favorito esta semana? El debate", "1:51:28", "2026-11-03", "Debate"),
]


def escaleta():
    pr = D["programa"]
    filas = "".join(
        f'<li class="schedule-row" data-slot-start="{p["hora"]}"><span class="schedule-row__time">{e(p["hora"])}</span>'
        f'<span class="schedule-row__topic">{e(p["bloque"])}'
        f'<span class="schedule-row__now only-aire" hidden> · Ahora</span></span></li>'
        for p in pr["parrilla"])
    return f'<ol class="schedule-list" role="list" data-schedule>{filas}</ol>'


def episodios(n=3):
    filas = []
    for titulo, dur, fecha, tema in EPISODIOS[:n]:
        filas.append(
            f'<li class="episode-row">'
            f'<button class="audio-play" type="button" data-radio-play="episodio" data-title="{e(titulo)}">'
            f'{icono("play-fill")}<span class="visually-hidden">Reproducir: {e(titulo)}</span></button>'
            f'<span class="episode-row__body"><a class="episode-row__title" href="video.html">{e(titulo)}</a>'
            f'<span class="meta"><time datetime="{fecha}">{e(dia_corto(fecha))}</time> · {e(dur)} · {e(tema)}</span></span></li>')
    return f'<ul class="episode-list" role="list">{"".join(filas)}</ul>'


def estado_radio():
    """Texto de estado; JS cambia html[data-radio] según la hora de Bogotá (L-V 10:00-12:00)."""
    return (f'<p class="radio-status"><span class="only-aire" hidden>{live_pill("Al aire")} hasta las 12:00 m.</span>'
            f'<span class="only-fuera" data-radio-next>Fuera del aire. El programa vuelve el lunes a las 10:00 a. m.</span></p>')


def boton_radio():
    return (f'<button class="btn btn--primary only-aire" type="button" data-radio-play="vivo" hidden>'
            f'{icono("play-fill")}Escuchar en vivo</button>'
            f'<button class="btn btn--secondary only-fuera" type="button" data-radio-play="episodio" '
            f'data-title="{e(EPISODIOS[0][0])}">{icono("play-fill")}Escuchar el último episodio</button>')


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


def pauta(slot):
    nombre, esc, mov = PAUTA[slot]
    medidas = (f'<span class="ad-slot__size ad-slot__size--lg">{esc}</span>'
               f'<span class="ad-slot__size ad-slot__size--sm">{mov}</span>') if esc != mov else f'<span class="ad-slot__size">{esc}</span>'
    return (f'<!-- slot pauta: {slot} -->'
            f'<aside class="ad-slot ad-slot--{slot}" aria-label="{nombre}" data-slot="{slot}">'
            f'<p class="ad-slot__label">Publicidad</p>'
            f'<a class="ad-slot__frame" href="contacto.html#publicidad">'
            f'<span class="ad-slot__placeholder">Espacio publicitario{medidas}'
            f'<span class="ad-slot__cta">Cómo anunciar aquí</span></span></a></aside>')


def sello(texto="Sección de datos presentada por"):
    return (f'<p class="sponsor-seal"><span class="sponsor-seal__text">{e(texto)}</span> '
            f'<!-- logo oficial de Transelca (SVG): pendiente del cliente -->'
            f'<span class="sponsor-seal__logo" data-logo-pendiente="transelca">Transelca</span></p>')


# ==========================================================================
# Artículo: migas, compartir, caja de datos, etiquetas
# ==========================================================================
def migas(*niveles):
    """niveles: ("Inicio", "index.html"), ("Noticias", "noticias.html"), ... Se renderiza en build (JSON-LD)."""
    items = "".join(f'<li><a href="{e(h)}">{e(t)}</a></li>' for t, h in niveles)
    padre_t, padre_h = niveles[-1]
    return (f'<nav class="breadcrumb" aria-label="Ruta de navegación"><ol class="breadcrumb__list" role="list">{items}</ol>'
            f'<a class="breadcrumb__back" href="{e(padre_h)}">{icono("caret-left", "icon icon--sm")}{e(padre_t)}</a></nav>')


def compartir():
    return (f'<div class="share" data-share><p class="share__label">Compartir</p><ul class="share__list" role="list">'
            f'<li><a class="icon-btn" href="https://wa.me/?text=Rivas%20y%20Castro%3A%20el%20eje%20que%20sostiene%20al%20l%C3%ADder" '
            f'target="_blank" rel="noopener">{icono("whatsapp-logo")}<span class="visually-hidden">Compartir por WhatsApp (se abre en una pestaña nueva)</span></a></li>'
            f'<li><a class="icon-btn" href="https://x.com/intent/post?text=Rivas%20y%20Castro%3A%20el%20eje%20que%20sostiene%20al%20l%C3%ADder" '
            f'target="_blank" rel="noopener">{icono("x-logo")}<span class="visually-hidden">Compartir en X (se abre en una pestaña nueva)</span></a></li>'
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
            f'{fuente("Datos de ejemplo hasta la Fecha 19. En producción: Data Factory.")}</aside>')


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
            f'En producción, cada torneo trae su tabla y sus resultados desde Data Factory.</p>')


# ==========================================================================
# Página provisional («En construcción»)
# ==========================================================================
def en_construccion(titulo, texto, anclas=(), enlaces=()):
    secciones = "".join(
        f'<section class="stub__section" id="{e(i)}" aria-labelledby="{e(i)}-t"><h2 class="stub__h2" id="{e(i)}-t">{e(t)}</h2>'
        f'<p>Este bloque se construye en la siguiente etapa.</p></section>' for i, t in anclas)
    links = "".join(f'<li><a class="link-action" href="{e(h)}">{e(t)}{icono("caret-right", "icon icon--sm")}</a></li>'
                    for t, h in enlaces)
    nav = (f'<nav class="stub__links" aria-label="Mientras tanto"><h2 class="stub__h2">Mientras tanto</h2>'
           f'<ul role="list">{links}</ul></nav>') if links else ""
    return (f'<div class="container stub"><p class="stub__badge">Página en construcción</p>'
            f'<h1 class="page-title">{e(titulo)}</h1><p class="standfirst">{e(texto)}</p>{nav}{secciones}</div>')


def buscador_404():
    return (f'<form class="search-inline" action="buscar.html" method="get" role="search" aria-label="Buscar en el sitio">'
            f'<label class="field__label" for="q404">Buscar en Habla Deportes</label>'
            f'<div class="search-inline__row"><input class="field__input" id="q404" name="q" type="search" '
            f'placeholder="Equipo, jugador o tema" enterkeyhint="search">'
            f'<button class="btn btn--primary" type="submit">Buscar</button></div></form>')


REGISTRO = {f.__name__: f for f in [
    foto, ico, rotulo, fuente, ejemplo, live_pill, tarjeta, short, carril_shorts, programa, titulares,
    centro_partidos, franja_dias, resultados, proximos_partidos, tabla_posiciones, goleadores, estadisticas,
    tarjeta_en_vivo, racha, mercado, escaleta, episodios, estado_radio, boton_radio, encuesta, boletin,
    efemeride, pauta, sello, migas, compartir, caja_datos_junior, etiquetas, chips_torneos, en_construccion,
    buscador_404]}
REGISTRO["escudo"] = escudo
