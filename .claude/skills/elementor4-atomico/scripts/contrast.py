#!/usr/bin/env python3
"""contrast.py — Contraste WCAG de bandas de texto sobre FOTO o VÍDEO con velo degradado,
fotograma a fotograma (o imagen a imagen), con el velo compuesto por píxel.

PROPÓSITO (síntoma → causa)
  El texto blanco o de acento de un hero con vídeo debe ser legible sobre CUALQUIER fotograma,
  y el contraste cambia con cada fotograma y con el degradado del velo. Un color picker sobre una
  captura no sirve. Se recorta cada fotograma como object-fit:cover al tamaño real de la caja,
  se compone el velo (vertical y horizontal: alfa combinado 1-(1-a)(1-b)) sobre cada píxel y se
  muestrea la banda que ocupa cada texto según su geometría MEDIDA en el navegador.
  Sirve igual para banners estáticos (una sola imagen). El contraste estático de HTML + CSS
  (texto sobre colores planos) es otra herramienta: skill wp-seo-rendimiento.

USO
  pip install Pillow
  Fotogramas (orientativo, el comando exacto no quedó registrado en el caso):
      ffmpeg -i hero.mp4 -vf fps=1 frames/f%03d.png
  python3 contrast.py --ejemplo > contraste.json      # plantilla de configuración
  python3 contrast.py contraste.json 'frames/*.png'   # comillas: que el glob lo expanda Python
  python3 contrast.py contraste.json banner.jpg
  Salida por banda: peor caso, mediana y cuántos fotogramas quedan por debajo de 4.5:1 y 3:1
  (4.5:1 texto normal; 3:1 texto grande: ≥24 px, o ≥18.66 px en negrita), más el peor fotograma.

QUÉ EDITAR (en el JSON)
  ancho, alto          caja real del hero/banner en px al viewport medido (p. ej. 1440 × 774).
  velo.color           RGB del velo.  velo.vertical / velo.horizontal: paradas [posición %, alfa],
                       vertical medida DESDE ABAJO, horizontal desde la izquierda; null = sin velo.
  modo                 "media" (media de la banda por fotograma) o "minimo" (peor píxel: más estricto).
  paso_x, paso_y       muestreo en px.
  bandas[]             nombre, y_desde_abajo (px desde el borde inferior de la caja al borde
                       inferior de la banda) o y_desde_arriba, alto, x0, x1, color [r,g,b], alfa del texto.

ESTADO
  Núcleo probado en HomeMaker (contrast.py, c2.py, c2.py.bak, c3.py: luminancia WCAG, composición
  del velo por paradas lineales, recorte cover, bandas por geometría medida, peor/mediana/fallos).
  Generalizado, no probado fuera de HomeMaker: configuración por JSON, paradas arbitrarias,
  y_desde_arriba y el modo elegible (en el caso eran scripts separados con valores fijos).

RIESGOS
  Solo lee imágenes. La geometría de las bandas debe salir de medir la página (medir-vs-prototipo.js),
  no del diseño: una banda mal situada da un resultado falso.
"""
import glob
import json
import os
import statistics
import sys

EJEMPLO = {
    "_nota": "Geometría y velo del caso de referencia (hero 1440x774, contenido abajo). Sustituye por lo medido.",
    "ancho": 1440,
    "alto": 774,
    "modo": "media",
    "paso_x": 5,
    "paso_y": 3,
    "velo": {
        "color": [10, 22, 14],
        "vertical": [[0, 0.78], [42, 0.34], [68, 0.12], [100, 0.0]],
        "horizontal": [[0, 0.5], [62, 0.0], [100, 0.0]],
    },
    "bandas": [
        {"nombre": "kicker blanco/80", "y_desde_abajo": 472, "alto": 14, "x0": 100, "x1": 400, "color": [255, 255, 255], "alfa": 0.8},
        {"nombre": "h1 línea 1 blanco", "y_desde_abajo": 381, "alto": 70, "x0": 100, "x1": 950, "color": [255, 255, 255], "alfa": 1.0},
        {"nombre": "h1 acento", "y_desde_abajo": 311, "alto": 70, "x0": 574, "x1": 800, "color": [243, 116, 33], "alfa": 1.0},
        {"nombre": "sellos blanco/75", "y_desde_abajo": 174, "alto": 20, "x0": 100, "x1": 800, "color": [255, 255, 255], "alfa": 0.75},
    ],
}


def lin(c):
    c = c / 255.0
    return c / 12.92 if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4


def lum(p):
    return 0.2126 * lin(p[0]) + 0.7152 * lin(p[1]) + 0.0722 * lin(p[2])


def ratio(a, b):
    l1, l2 = max(lum(a), lum(b)), min(lum(a), lum(b))
    return (l1 + 0.05) / (l2 + 0.05)


def over(fg, a, bg):
    """fg con opacidad a sobre bg"""
    return tuple(a * f + (1 - a) * b for f, b in zip(fg, bg))


def tramo(paradas, t):
    """alfa en la posición t (0-100 %) interpolando linealmente entre paradas [pos, alfa]"""
    if not paradas:
        return 0.0
    if t <= paradas[0][0]:
        return paradas[0][1]
    for (p0, a0), (p1, a1) in zip(paradas, paradas[1:]):
        if t <= p1:
            return a0 + (a1 - a0) * ((t - p0) / (p1 - p0)) if p1 != p0 else a1
    return paradas[-1][1]


def cover(im, w, h):
    """recorte equivalente a object-fit:cover centrado"""
    iw, ih = im.size
    s = max(w / iw, h / ih)
    im = im.resize((int(iw * s), int(ih * s)))
    nw, nh = im.size
    left, top = (nw - w) // 2, (nh - h) // 2
    return im.crop((left, top, left + w, top + h))


def main():
    if len(sys.argv) >= 2 and sys.argv[1] == "--ejemplo":
        print(json.dumps(EJEMPLO, ensure_ascii=False, indent=1))
        return 0
    if len(sys.argv) < 3:
        print(__doc__)
        return 2
    try:
        from PIL import Image
    except ImportError:
        print("Falta Pillow: pip install Pillow")
        return 2

    cfg = json.load(open(sys.argv[1], encoding="utf-8"))
    archivos = sorted(glob.glob(sys.argv[2]))
    if not archivos:
        print("No hay imágenes que coincidan con " + sys.argv[2])
        return 2
    W, H = int(cfg["ancho"]), int(cfg["alto"])
    velo = cfg.get("velo") or {}
    color_velo = tuple(velo.get("color") or (0, 0, 0))
    vert, horiz = velo.get("vertical"), velo.get("horizontal")
    modo = cfg.get("modo", "media")
    px_, py_ = int(cfg.get("paso_x", 5)), int(cfg.get("paso_y", 3))

    def alfa_velo(xx, yy):
        a = tramo(vert, (H - yy) / H * 100) if vert else 0.0
        b = tramo(horiz, xx / W * 100) if horiz else 0.0
        return 1 - (1 - a) * (1 - b)

    bandas = []
    for b in cfg["bandas"]:
        alto = float(b["alto"])
        ybot = float(b["y_desde_abajo"]) if "y_desde_abajo" in b else H - float(b["y_desde_arriba"]) - alto
        bandas.append((b["nombre"], ybot, alto, float(b["x0"]), float(b["x1"]), tuple(b["color"]), float(b.get("alfa", 1.0))))

    res = {b[0]: [] for b in bandas}
    for f in archivos:
        im = cover(Image.open(f).convert("RGB"), W, H)
        px = im.load()
        for nombre, ybot, alto, x0, x1, fg, al in bandas:
            acc = []
            for yy in range(int(H - ybot - alto), int(H - ybot), py_):
                for xx in range(int(x0), int(x1), px_):
                    if not (0 <= yy < H and 0 <= xx < W):
                        continue
                    a = alfa_velo(xx, yy)
                    bg = over(color_velo, a, px[xx, yy]) if a > 0 else px[xx, yy]
                    txt = over(fg, al, bg) if al < 1 else fg
                    acc.append(ratio(txt, bg))
            if not acc:
                continue
            valor = min(acc) if modo == "minimo" else sum(acc) / len(acc)
            res[nombre].append((valor, os.path.basename(f)))

    print(f"{len(archivos)} imagen(es) · {W}x{H} · modo {modo}")
    print("banda                        peor   mediana  <4.5:1   <3:1   peor imagen")
    for nombre, vals in res.items():
        if not vals:
            print(f"{nombre:26s}   (banda fuera de la caja)")
            continue
        v = [x[0] for x in vals]
        peor = min(vals)
        f45 = sum(1 for x in v if x < 4.5)
        f3 = sum(1 for x in v if x < 3.0)
        print(f"{nombre:26s} {peor[0]:6.2f}  {statistics.median(v):6.2f}   {f45:3d}/{len(v):<3d} {f3:3d}/{len(v):<3d} {peor[1]}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
