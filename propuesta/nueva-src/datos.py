"""Lectura de propuesta/nueva/data-ejemplo.json (FUENTE ÚNICA de datos) y utilidades
de formato. Ninguna página escribe a mano un resultado, una tabla o un fichaje:
todo se renderiza desde aquí para que ninguna página contradiga a otra.
"""
import datetime as dt
import json
import pathlib

RAIZ = pathlib.Path(__file__).resolve().parent.parent  # propuesta/
JSON = RAIZ / "nueva" / "data-ejemplo.json"

D = json.loads(JSON.read_text(encoding="utf-8"))
ALIADOS = json.loads((RAIZ / "nueva" / "aliados.json").read_text(encoding="utf-8"))["aliados"]
EQUIPOS = {e["clave"]: e for e in D["equipos"]}
HOY = dt.date.fromisoformat(D["_fecha_hoy"])

DIAS = ["lun", "mar", "mié", "jue", "vie", "sáb", "dom"]
DIAS_LARGO = ["lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"]
MESES = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"]
MESES_LARGO = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto",
               "septiembre", "octubre", "noviembre", "diciembre"]

# Etiqueta global de los módulos de datos. Sin proveedor contratado (06 D4).
FUENTE = "Datos de ejemplo · fuente por definir."


def equipo(clave):
    return EQUIPOS[clave]


def fecha(iso):
    return dt.date.fromisoformat(iso)


def dia_corto(iso, mayus=True):
    """'Vie 13 nov' / 'Hoy' / 'Mañana' / 'Ayer'."""
    d = fecha(iso)
    delta = (d - HOY).days
    rel = {0: "Hoy", 1: "Mañana", -1: "Ayer"}.get(delta)
    if rel:
        return rel
    s = f"{DIAS[d.weekday()]} {d.day} {MESES[d.month - 1]}"
    return s[0].upper() + s[1:] if mayus else s


def dia_largo(iso):
    """'viernes 13 de noviembre'."""
    d = fecha(iso)
    return f"{DIAS_LARGO[d.weekday()]} {d.day} de {MESES_LARGO[d.month - 1]}"


def hora(h):
    """'7:30 p.m.' -> '7:30 p. m.' (estilo de 03-contenido §0). '12:00 m.' se respeta."""
    return h.replace("p.m.", "p. m.").replace("a.m.", "a. m.")


def hora_iso(iso, h):
    """Fecha ISO con hora de Bogotá para <time datetime> y la cuenta regresiva."""
    hh, rest = h.split(":")
    mm = rest[:2]
    hh = int(hh)
    if "p.m." in rest and hh != 12:
        hh += 12
    return f"{iso}T{hh:02d}:{mm}:00-05:00"


def id_partido(p):
    return p.get("id") or f"{p['local']}-{p['visitante']}"


def todos_los_proximos():
    return [p for j in D["resultados"]["proximos"] for p in j["partidos"]]


def partido_por_id(pid):
    for p in todos_los_proximos():
        if id_partido(p) == pid:
            return p
    raise KeyError(pid)


def fila_tabla(clave):
    for f in D["tabla_posiciones"]["filas"]:
        if f["equipo"] == clave:
            return f
    raise KeyError(clave)


FORMA_TXT = {"G": "ganó", "E": "empató", "P": "perdió"}


def forma_texto(forma):
    return "Últimos cinco: " + ", ".join(FORMA_TXT[c] for c in forma)


def signo(n):
    return f"+{n}" if n > 0 else str(n)


def miles(n):
    return f"{n:,}".replace(",", ".")
