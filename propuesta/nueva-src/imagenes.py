"""Catálogo de imágenes (alt de 03-contenido.md, Banco 1) y conversión a WebP.

- Solo se copian a html/nueva/assets/img/ las imágenes que alguna página usa.
- PNG/JPG -> WebP calidad 80, MISMAS dimensiones (width/height van en el <img>).
- tipo "foto": cuenta para la regla "cada foto una sola vez por página"
  (01-sistema-de-diseno §7). Escudos, logos y anuncios no cuentan.
- pos: object-position del recorte (01 §7: Juan Salvador a 60 % 35 %).
- No están (a propósito): img-01.png (logo de Diario Deportes), img-07/img-08
  (Messi y Mbappé) y efemerides.png (no ilustra noticias).
"""
import pathlib

from PIL import Image

ORIGEN = pathlib.Path(__file__).resolve().parent.parent / "html" / "assets" / "img"

# clave: (archivo de origen, archivo de salida, tipo, alt, object-position)
CATALOGO = {
    # Fotos de Juan Salvador: reservadas para programa, radio y su perfil (01 §7.4)
    "js-1": ("juansalvador1.png", "js-1.webp", "foto",
             "Juan Salvador Bárcena se cubre la boca con las manos, con gesto de sorpresa, frente al "
             "micrófono azul de Habla Deportes; detrás, repisas con recuerdos de fútbol y el escudo de Junior",
             "60% 35%"),
    "js-2": ("juansalvador2.png", "js-2.webp", "foto",
             "Juan Salvador Bárcena, de camiseta negra y gafas, pensativo con la mano en la barbilla, "
             "sentado ante el micrófono de Habla Deportes",
             "60% 35%"),
    "seleccion-tecnico-retrato": ("img-02.jpg", "seleccion-tecnico-retrato.webp", "foto",
             "Retrato del técnico de la Selección: hombre de cabello castaño y chaqueta oscura, "
             "con las luces del estadio desenfocadas", "50% 30%"),
    "junior-trofeo-bandera": ("img-03.jpg", "junior-trofeo-bandera.webp", "foto",
             "Jugadores de Junior alzan el trofeo y una bandera con un mensaje religioso entre "
             "serpentinas azules", "50% 40%"),
    "seleccion-once": ("img-04.jpg", "seleccion-once.webp", "foto",
             "Once de la Selección Colombia en fila antes del partido, con camiseta amarilla y la "
             "tribuna detrás", "50% 45%"),
    "junior-escudo-bandera": ("img-05.jpg", "junior-escudo-bandera.webp", "foto",
             "Escudo de Junior sobre una bandera rojiblanca, en tonos oscuros", "30% 50%"),
    "invitado-estudio": ("img-06.png", "invitado-estudio.webp", "foto",
             "Hombre mayor con gafas de pasta y camisa rosada, sonriente, con micrófono de solapa, "
             "sobre fondo oscuro", "50% 30%"),
    "seleccion-tecnico-zona": ("img-09.jpg", "seleccion-tecnico-zona.webp", "foto",
             "El técnico de la Selección en la zona técnica, de traje oscuro y camisa de cuadros, ante "
             "un cartel de la Copa América 2024", "62% 40%"),
    "nacional-celebracion": ("img-10.jpg", "nacional-celebracion.webp", "foto",
             "Dos jugadores de Atlético Nacional con camiseta verde y blanca se abrazan sonriendo",
             "40% 40%"),
    "seleccion-gol": ("img-11.jpg", "seleccion-gol.webp", "foto",
             "Jugadores de la Selección con camiseta amarilla celebran un gol con los brazos en alto, "
             "con la tribuna amarilla de fondo", "40% 40%"),
    "junior-plantel-trofeo": ("Junior-de-Barranquilla-1200x740.jpg", "junior-plantel-trofeo.webp", "foto",
             "Plantel de Junior levanta un trofeo dorado entre serpentinas azules y rosadas", "50% 35%"),
    "junior-bicampeon": ("junior-de-barranquilla-es-bicampeon-de-liga-betplay-2026-I-en-estadio-atanasio-girardot.webp",
             "junior-bicampeon.webp", "foto",
             "Dos jugadores de Junior de espaldas, con los dorsales 77 y 10, se abrazan sobre el césped "
             "bajo humo rojo", "50% 45%"),
    "seleccion-derrota": ("seleccion-derrota-gira.webp", "seleccion-derrota.webp", "foto",
             "Jugadores de la Selección con camiseta amarilla, abatidos sobre el césped: uno de rodillas, "
             "otros tendidos", "50% 50%"),
    "banderas-junior-nacional": ("junior_nacional.png", "banderas-junior-nacional.webp", "foto",
             "Banderas fundidas de Junior y Nacional: franjas rojiblancas y verdes sobre fondo oscuro",
             "50% 50%"),
    "banderas-colombia-peru": ("colombia_peru.png", "banderas-colombia-peru.webp", "foto",
             "Banderas de Colombia y Perú fundidas en una sola imagen", "50% 50%"),
    "banderas-junior-millonarios": ("junior_millonarios.png", "banderas-junior-millonarios.webp", "foto",
             "Banderas fundidas de Junior y Millonarios: franjas rojas y blancas junto a franjas azules "
             "y blancas", "50% 50%"),
    # Fotos de las secciones por tema: Wikimedia Commons, licencias libres (créditos en la Home)
    "tema-fc-1": ("tema-fc-1.jpg", "tema-fc-1.webp", "foto",
             "Humo de bengalas sobre la tribuna de un estadio durante la final entre Deportivo Cali y Deportes Tolima", "50% 50%"),
    "tema-fc-2": ("tema-fc-2.jpg", "tema-fc-2.webp", "foto",
             "Futbolistas, árbitros y personal en el césped antes de un partido del Atlético Bucaramanga en la Liga BetPlay", "50% 50%"),
    "tema-fc-3": ("tema-fc-3.jpg", "tema-fc-3.webp", "foto",
             "Vista general de un partido de la Liga BetPlay en el estadio de Bucaramanga, con la tribuna al fondo", "50% 50%"),
    "tema-eu-1": ("tema-eu-1.jpg", "tema-eu-1.webp", "foto",
             "Dos jugadores del Olympique Lyonnais celebran un gol abrazados, uno de ellos con las manos en forma de triángulo", "50% 50%"),
    "tema-eu-2": ("tema-eu-2.jpg", "tema-eu-2.webp", "foto",
             "Hinchas del Atalanta en la tribuna con banderas antes de un partido de Champions League", "50% 50%"),
    "tema-eu-3": ("tema-eu-3.jpg", "tema-eu-3.webp", "foto",
             "Los equipos del Manchester City y el Burnley formados en el césped antes de un partido de la Premier League", "50% 50%"),
    "tema-ex-1": ("tema-ex-1.jpg", "tema-ex-1.webp", "foto",
             "Un jugador de camiseta naranja se prepara para entrar al campo mientras el cuarto árbitro revisa el cambio", "50% 50%"),
    "tema-ex-2": ("tema-ex-2.jpg", "tema-ex-2.webp", "foto",
             "Un jugador de la Selección Colombia conduce el balón entre rivales durante un partido de eliminatorias", "50% 50%"),
    "tema-ex-3": ("tema-ex-3.jpg", "tema-ex-3.webp", "foto",
             "Jugador de la Selección Colombia con la camiseta 10, de pie junto al balón sobre el césped", "50% 50%"),
    # Anuncio y logos
    "anuncio-transelca": ("publicidad.png", "anuncio-transelca.webp", "anuncio",
             "Anuncio de Transelca: Eso que menos ves es lo que más cuidamos", "50% 50%"),
    "logo-blanco": ("LogoHablaDeportes-white.png", "logo-habla-blanco.webp", "logo", "Habla Deportes", None),
    # Escudos (alt="" cuando el nombre del equipo está al lado)
    "escudo-junior": ("escudo-junior.png", "escudo-junior.webp", "escudo", "Escudo de Junior de Barranquilla", None),
    "escudo-nacional": ("escudo-nacional.png", "escudo-nacional.webp", "escudo", "Escudo de Atlético Nacional", None),
    "escudo-millonarios": ("escudo-millonarios.png", "escudo-millonarios.webp", "escudo", "Escudo de Millonarios", None),
    "escudo-tolima": ("escudo-tolima.png", "escudo-tolima.webp", "escudo", "Escudo de Deportes Tolima", None),
    "escudo-america": ("escudo-america.png", "escudo-america.webp", "escudo", "Escudo de América de Cali", None),
    "escudo-medellin": ("escudo-medellin.png", "escudo-medellin.webp", "escudo", "Escudo de Independiente Medellín", None),
    "escudo-colombia": ("escudo-colombia.png", "escudo-colombia.webp", "escudo", "Escudo de la Selección Colombia", None),
    # Perú: logotipo de la FPF (no el escudo de la Federación Colombiana)
    "Logotipo_FPF_2016": ("Logotipo_FPF_2016.png", "escudo-peru.webp", "escudo", "Escudo de la Selección Perú", None),
}

# Escudos provisionales de Datafactory (descargar_escudos.py) para el resto de la Liga BetPlay
_LIGA = {"cali": "Deportivo Cali", "bucaramanga": "Atlético Bucaramanga", "santa-fe": "Independiente Santa Fe",
         "once-caldas": "Once Caldas", "cucuta": "Cúcuta Deportivo", "aguilas": "Águilas Doradas",
         "llaneros": "Llaneros FC", "int-bogota": "Internacional de Bogotá", "boyaca-chico": "Boyacá Chicó",
         "alianza": "Alianza Valledupar", "pereira": "Deportivo Pereira", "pasto": "Deportivo Pasto",
         "jaguares": "Jaguares de Córdoba", "fortaleza": "Fortaleza FC"}
for _c, _n in _LIGA.items():
    CATALOGO[f"escudo-{_c}"] = (f"escudo-{_c}.png", f"escudo-{_c}.webp", "escudo", f"Escudo de {_n}", None)
ESCUDOS_LIGA = {c: f"escudo-{c}.png" for c in _LIGA}

_dims = {}
FONDO_OSCURO = {"invitado-estudio"}


def dims(clave):
    if clave not in _dims:
        with Image.open(ORIGEN / CATALOGO[clave][0]) as im:
            _dims[clave] = im.size
    return _dims[clave]


def dims_salida(ruta_rel):
    """Dimensiones de un archivo ya presente en html/nueva (logos de aliados)."""
    with Image.open(ORIGEN.parent.parent / "nueva" / ruta_rel) as im:
        return im.size


def ruta(clave):
    return "assets/img/" + CATALOGO[clave][1]


def convertir(claves, destino):
    """Convierte a WebP (calidad 80, mismas dimensiones) solo las imágenes usadas."""
    destino.mkdir(parents=True, exist_ok=True)
    hechas = []
    for clave in sorted(claves):
        src, out = CATALOGO[clave][0], CATALOGO[clave][1]
        o = destino / out
        s = ORIGEN / src
        if o.exists() and o.stat().st_mtime >= s.stat().st_mtime:
            continue
        with Image.open(s) as im:
            if clave in FONDO_OSCURO and "A" in im.getbands():
                # Recorte con transparencia: se asienta sobre la tinta del sistema (gray-900)
                base = Image.new("RGB", im.size, (17, 28, 39))
                base.paste(im, mask=im.convert("RGBA").split()[-1])
                im = base
            if im.mode == "P":
                im = im.convert("RGBA")
            if im.mode not in ("RGB", "RGBA"):
                im = im.convert("RGBA" if "A" in im.getbands() else "RGB")
            im.save(o, "WEBP", quality=80, method=6)
        hechas.append(out)
    # Limpia imágenes que ya no usa ninguna página
    usadas = {CATALOGO[c][1] for c in claves}
    for f in destino.iterdir():
        if f.is_file() and f.name not in usadas:
            f.unlink()
    return hechas
