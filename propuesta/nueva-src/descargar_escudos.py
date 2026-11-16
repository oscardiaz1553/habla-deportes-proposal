"""Descarga los escudos de los equipos de la Liga BetPlay desde el CDN de Datafactory.

Uso:  python3 propuesta/nueva-src/descargar_escudos.py

Solo baja los equipos que todavía no tienen escudo propio en html/assets/img (los demás ya tienen
el original en alta). Datafactory los publica como GIF de 200 x 200; se guardan como PNG con
transparencia, sin recolorear ni recortar. Son escudos PROVISIONALES para la maqueta: el cliente
debe entregar los oficiales (en especial el del Deportivo Pasto).
"""
import io
import pathlib
import urllib.request

from PIL import Image

ORIGEN = pathlib.Path(__file__).resolve().parent.parent / "html" / "assets" / "img"
CDN = "https://1287719000.rsc.cdn77.org/escudos/{id}.gif"
# id de Datafactory -> clave (los mismos de importar_datafactory.py) para los equipos sin escudo propio
FALTAN = {111: "cali", 175: "bucaramanga", 183: "santa-fe", 88: "once-caldas", 266: "cucuta", 922: "aguilas",
          880: "llaneros", 597: "int-bogota", 177: "boyaca-chico", 921: "alianza", 181: "pereira",
          180: "pasto", 1655: "jaguares", 3209: "fortaleza"}


def main():
    for id_, clave in FALTAN.items():
        destino = ORIGEN / f"escudo-{clave}.png"
        if destino.exists():
            continue
        req = urllib.request.Request(CDN.format(id=id_), headers={"User-Agent": "Mozilla/5.0 (maqueta Habla Deportes)"})
        datos = urllib.request.urlopen(req, timeout=30).read()
        Image.open(io.BytesIO(datos)).convert("RGBA").save(destino)
        print("OK", destino.name)


if __name__ == "__main__":
    main()
