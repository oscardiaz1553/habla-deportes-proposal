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
             "sobre fondo negro", "50% 30%"),
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

_dims = {}


def dims(clave):
    if clave not in _dims:
        with Image.open(ORIGEN / CATALOGO[clave][0]) as im:
            _dims[clave] = im.size
    return _dims[clave]


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
