"""Importa partidos y posiciones de Datafactory tal como las publica Diario Deportes.

Uso:  python3 propuesta/nueva-src/importar_datafactory.py [archivo.html]

Sin argumento descarga https://diariodeportes.com.co/ y escribe la foto en
propuesta/nueva/data-datafactory.json. Es una solución PARA LA MAQUETA: lee el HTML que ya
publica Diario Deportes (tira de partidos y tabla). En producción se sustituye por la API
de Datafactory (credenciales en variable de entorno, nunca en el repo); el JSON de salida
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
URL = "https://diariodeportes.com.co/"

# sigla de Datafactory -> clave de data-ejemplo.json (None = equipo nuevo, se registra aparte)
SIGLAS = {"PAS": "pasto", "NAC": "nacional", "ONC": "once-caldas", "AGU": "aguilas", "DIM": "medellin",
          "CUC": "cucuta", "JAG": "jaguares", "BUC": "bucaramanga", "FOR": "fortaleza", "INT": "int-bogota",
          "SFE": "santa-fe", "MIL": "millonarios", "ALI": "alianza", "LLA": "llaneros", "CHI": "boyaca-chico",
          "CAL": "cali", "TOL": "tolima", "PER": "pereira", "AME": "america", "JUN": "junior"}
NOMBRES = {"At. Nacional": "nacional", "América de Cali": "america", "Deportivo Cali": "cali",
           "Bucaramanga": "bucaramanga", "Independiente Medellín": "medellin", "Santa Fe": "santa-fe",
           "Millonarios": "millonarios", "Águilas Doradas Rionegro": "aguilas", "Pasto": "pasto",
           "Junior": "junior", "Once Caldas": "once-caldas", "Tolima": "tolima"}
NUEVOS = {"cucuta": ("Cúcuta Deportivo", "Cúcuta", "CUC"),
          "int-bogota": ("Internacional de Bogotá", "Int. de Bogotá", "INT")}
MESES = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre",
         "octubre", "noviembre", "diciembre"]


def leer(origen):
    if origen:
        return pathlib.Path(origen).read_text(encoding="utf-8")
    req = urllib.request.Request(URL, headers={"User-Agent": "Mozilla/5.0"})
    return urllib.request.urlopen(req, timeout=30).read().decode("utf-8")


def txt(s):
    return html.unescape(re.sub(r"<[^>]+>", "", s)).replace("\xa0", " ").strip()


def partidos(h, anio):
    seg = h[h.index('class="tira__lista"'):]
    seg = seg[:seg.index("tira__cierre")]
    out = []
    for li in seg.split('<li class="partido')[1:]:
        sr = txt(re.search(r'<span class="sr">(.*?)</span>', li).group(1))
        sig = re.findall(r'title="[^"]*">([A-Z]{3})</abbr>', li)
        m = re.search(r"(\d{1,2}) de (\w+) a las (\d{1,2}):(\d{2}) ([ap])\. m\.", sr)
        d, mes, hh, mm, ap = m.groups()
        dia = dt.date(anio, MESES.index(mes) + 1, int(d)).isoformat()
        res = re.search(r'partido__resultado">(.*?)</span>\s*<abbr', li, re.S)
        marcador = re.findall(r"(\d+)", txt(res.group(1))) if res else []
        out.append({"local": SIGLAS[sig[0]], "visitante": SIGLAS[sig[1]], "dia": dia,
                    "hora": f"{int(hh)}:{mm} {ap}.m.",
                    "estado": "final" if len(marcador) == 2 else "proximo",
                    "gl": int(marcador[0]) if len(marcador) == 2 else None,
                    "gv": int(marcador[1]) if len(marcador) == 2 else None})
    return out


def tabla(h):
    t = h[h.index('<table class="tabla"'):]
    t = t[:t.index("</table>")]
    cap = txt(re.search(r"<caption>(.*?)</caption>", t).group(1))
    filas = []
    for tr in re.findall(r'<tr class="[^"]*">.*?</tr>', t):
        c = re.findall(r"<td[^>]*>([^<]*)</td>", tr)
        nombre = (re.findall(r"<span>([^<]*)</span>", tr) or ["Junior"])[0]
        filas.append({"pos": int(c[0]), "equipo": NOMBRES[html.unescape(nombre)], "nombre": html.unescape(nombre),
                      "pj": int(c[1]), "dg": int(c[2].replace("−", "-")), "pts": int(c[3])})
    return cap, filas


def main():
    h = leer(sys.argv[1] if len(sys.argv) > 1 else None)
    ahora = dt.datetime.now(dt.timezone(dt.timedelta(hours=-5)))
    cap, filas = tabla(h)
    ps = partidos(h, ahora.year)
    fecha = re.search(r"Fecha (\d+)", re.search(r'tabla--resultados"><caption>(.*?)</caption>', h).group(1))
    datos = {
        "_aviso": "Foto tomada del HTML de Diario Deportes (fuente: Datafactory). Solo para la maqueta; "
                  "en producción la sustituye la API de Datafactory con el mismo formato.",
        "fuente": "Datafactory", "capturado": ahora.isoformat(timespec="minutes"),
        "torneo": {"nombre": "Liga BetPlay", "detalle": cap, "fecha": int(fecha.group(1))},
        "equipos_nuevos": {k: {"nombre": v[0], "corto": v[1], "abrev": v[2]} for k, v in NUEVOS.items()},
        "tabla": filas, "partidos": ps,
    }
    SALIDA.write_text(json.dumps(datos, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"OK: {len(filas)} filas de tabla y {len(ps)} partidos (Fecha {fecha.group(1)}) -> {SALIDA}")


if __name__ == "__main__":
    main()
