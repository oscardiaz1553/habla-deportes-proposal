"""Importa fixture y posiciones de la Liga BetPlay desde las páginas públicas del HTML Center de Datafactory.

Uso:  python3 propuesta/nueva-src/importar_datafactory.py [fixture.html posiciones.html]

Sin argumentos descarga las dos páginas que publica el «publisher» de Datafactory
(df.wplay.co/media/html/v3/htmlCenter/data/deportes/futbol/colombia/pages/es/) y escribe la foto en
propuesta/nueva/data-datafactory.json. Es una solución PARA LA MAQUETA: lee HTML que Datafactory
ya publica (equipos por id, hora local de Colombia, marcadores y tabla). En producción se sustituye
por la API de Datafactory (credenciales en variable de entorno, nunca en el repo); el JSON de salida
conserva el mismo formato para que el sitio no cambie.
"""
import datetime as dt
import html
import json
import pathlib
import re
import sys
import urllib.request

SALIDA = pathlib.Path(__file__).resolve().parent.parent / "nueva" / "data-datafactory.json"
BASE = "https://df.wplay.co/media/html/v3/htmlCenter/data/deportes/futbol/colombia/pages/es/"
URL_FIXTURE = BASE + "fixture_publisher.html"
URL_POSICIONES = BASE + "posiciones.html"
TZ = dt.timezone(dt.timedelta(hours=-5))

# id de equipo en Datafactory -> clave de data-ejemplo.json
IDS = {105: "nacional", 66: "america", 111: "cali", 175: "bucaramanga", 183: "santa-fe", 67: "medellin",
       184: "millonarios", 88: "once-caldas", 179: "tolima", 266: "cucuta", 922: "aguilas", 880: "llaneros",
       597: "int-bogota", 81: "junior", 177: "boyaca-chico", 921: "alianza", 181: "pereira", 180: "pasto",
       1655: "jaguares", 3209: "fortaleza"}
NUEVOS = {"cucuta": ("Cúcuta Deportivo", "Cúcuta", "CUC"),
          "int-bogota": ("Internacional de Bogotá", "Int. de Bogotá", "INT")}
DETALLE = "Liga BetPlay 2026-II · Todos contra todos. Los 8 primeros clasifican."


def leer(url, archivo=None):
    if archivo:
        return pathlib.Path(archivo).read_text(encoding="utf-8")
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 (maqueta Habla Deportes)"})
    return urllib.request.urlopen(req, timeout=30).read().decode("utf-8")


def txt(s):
    return html.unescape(re.sub(r"<[^>]+>", "", s)).replace("\xa0", " ").strip()


def hora_texto(d):
    h = d.hour % 12 or 12
    return f"{h}:{d.minute:02d} {'a' if d.hour < 12 else 'p'}.m."


def fixture(h):
    """Partidos por rótulo de fase/fecha, en el orden de la página."""
    rotulos = [(m.start(), txt(m.group(1))) for m in re.finditer(r"<button[^>]*dropdown-toggle[^>]*>(.*?)</button>", h, re.S)]
    pos = [p for p, _ in rotulos]
    import bisect
    por = {}
    patron = re.compile(r"mc-matchContainer.*?data-teams='\{\"home\":(\d*), \"away\":(\d*)\}'[^>]*"
                        r'data-originaldate="([^"]*)"(.*?)(?=mc-matchContainer|\Z)', re.S)
    for m in patron.finditer(h):
        loc, vis, fecha, resto = m.groups()
        if not (loc and vis and fecha):
            continue
        i = bisect.bisect(pos, m.start()) - 1
        goles = re.findall(r'class="resultado[^"]*"><div class="badge">(\d+)</div>', resto)
        d = dt.datetime.strptime(fecha, "%Y/%m/%d %H:%M").replace(tzinfo=TZ)
        por.setdefault(rotulos[i][1], []).append({
            "local": IDS[int(loc)], "visitante": IDS[int(vis)], "dia": d.date().isoformat(), "hora": hora_texto(d),
            "estado": "final" if len(goles) == 2 else "proximo",
            "gl": int(goles[0]) if len(goles) == 2 else None, "gv": int(goles[1]) if len(goles) == 2 else None})
    return por


def fecha_actual(por, ahora):
    """Última fecha de la temporada regular con partidos jugados; si hay una en curso, esa."""
    fechas = sorted((int(k.split()[1]), k) for k in por if re.fullmatch(r"Fecha \d+", k))
    elegida = fechas[0]
    for n, k in fechas:
        ps = por[k]
        if any(p["estado"] == "final" for p in ps):
            elegida = (n, k)
            if any(p["estado"] == "proximo" for p in ps):
                break
        elif elegida[0] == n:
            break
    return elegida


def tabla(h):
    cuerpo = h[h.index('id="pos_n1"'):]
    cuerpo = cuerpo[:cuerpo.index("</table>")]
    filas = []
    for id_, tr in re.findall(r'<tr class="linea e_(\d+)">(.*?)</tr>', cuerpo, re.S):
        c = [txt(x) for x in re.findall(r"<td[^>]*>(.*?)</td>", tr, re.S)]
        filas.append({"pos": int(c[0]), "equipo": IDS[int(id_)], "nombre": c[2], "pj": int(c[4]),
                      "dg": int(c[10].replace("−", "-")), "pts": int(c[3])})
    return filas


def main():
    arg = sys.argv[1:]
    hf = leer(URL_FIXTURE, arg[0] if len(arg) > 1 else None)
    hp = leer(URL_POSICIONES, arg[1] if len(arg) > 1 else None)
    ahora = dt.datetime.now(TZ)
    por = fixture(hf)
    n, rotulo = fecha_actual(por, ahora)
    filas = tabla(hp)
    # la clasificación compacta muestra los 8 primeros y a Junior
    filas = [f for f in filas if f["pos"] <= 8 or f["equipo"] == "junior"]
    datos = {
        "_aviso": "Foto tomada de las páginas públicas del HTML Center de Datafactory (publisher). Solo para la maqueta; "
                  "en producción la sustituye la API de Datafactory con el mismo formato.",
        "fuente": "Datafactory", "capturado": ahora.isoformat(timespec="minutes"),
        "torneo": {"nombre": "Liga BetPlay", "detalle": DETALLE, "fecha": n},
        "equipos_nuevos": {k: {"nombre": v[0], "corto": v[1], "abrev": v[2]} for k, v in NUEVOS.items()},
        "tabla": filas, "partidos": por[rotulo],
    }
    SALIDA.write_text(json.dumps(datos, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"OK: {len(filas)} filas de tabla y {len(por[rotulo])} partidos ({rotulo}) -> {SALIDA}")


if __name__ == "__main__":
    main()
