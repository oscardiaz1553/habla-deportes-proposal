"""Configuración del sitio: ÚNICO origen de verdad de la navegación.

Todo lo que aparece en la cabecera, los megamenús, la hoja del menú móvil, la
barra inferior y el pie sale de aquí (09-menu-arquitectura §7; 02 §8 para el pie).
Para añadir o renombrar una página se toca SOLO este archivo y su fuente en
paginas/.
"""

SITIO = {
    "nombre": "Habla Deportes",
    "url": "https://habladeportes.co/",
    "idioma": "es-CO",
    "og_imagen": "assets/img/js-2.webp",
    "descripcion": "Videos del programa, Shorts, resultados y noticias desde Barranquilla. "
                   "Junior y la Selección Colombia, todos los días.",
}

# Redes. YouTube es el canal real; las demás apuntan a la raíz de cada red hasta tener perfiles [H].
REDES = [
    {"clave": "youtube", "nombre": "YouTube", "icono": "youtube-logo", "url": "https://youtube.com/@habladeportesradio"},
    {"clave": "instagram", "nombre": "Instagram", "icono": "instagram-logo", "url": "https://www.instagram.com/"},
    {"clave": "x", "nombre": "X", "icono": "x-logo", "url": "https://x.com/"},
    {"clave": "facebook", "nombre": "Facebook", "icono": "facebook-logo", "url": "https://www.facebook.com/"},
]
# Solo YouTube, Instagram, X y Facebook (05-respuestas-cliente; 06 «Cambios derivados»).

# ==========================================================================
# MENÚ Y MEGAMENÚS (09-menu-arquitectura §7, forma visual en 08-menu-diseno)
# Dos capas azules (logo 64 + secciones 48). La barra blanca contextual se
# eliminó: sus enlaces viven en la zona 1 (Z1) de cada megamenú y en las
# fichas locales de cada página. Regla: una etiqueta, un destino (se valida
# al final de este archivo).
# Estados vivos: el partido en juego sale de data-ejemplo.json
# (partido_en_vivo.estado); el programa al aire lo decide main.js (EN_VIVO).
# ==========================================================================
YOUTUBE = REDES[0]["url"]

# Tiempos del hover (09 §2.5; 08 §3.5). main.js los lee de los tokens --mega-*.
HOVER = {"abrir_ms": 250, "entre_items_ms": 0, "cerrar_ms": 400, "zona_diagonal_ms": 80}

# Barra azul, de izquierda a derecha. punto: "partido" (Junior o Selección en
# juego) o "aire" (programa al aire). Todos los ítems son enlaces reales.
PRIMARIO = [
    {"clave": "videos", "texto": "Videos", "href": "videos.html"},
    {"clave": "noticias", "texto": "Noticias", "href": "noticias.html"},
    {"clave": "junior", "texto": "Junior FC", "href": "junior.html"},
    {"clave": "seleccion", "texto": "Selección", "href": "seleccion.html"},
    {"clave": "datos", "texto": "Datos", "href": "datos.html", "punto": "partido"},
    {"clave": "en-vivo", "texto": "En vivo", "href": "en-vivo.html", "punto": "aire"},
    {"clave": "mas", "texto": "Más", "href": "#pie-enlaces"},
]

# Contenido de cada panel. z0 = (texto visible, prefijo solo para lector de
# pantalla, destino). z1 = [(rótulo de grupo, [(etiqueta, destino), ...])].
# z2 = pieza destacada (imagen del catálogo o dato). z3 = «lo que pasa ahora»;
# sus cifras se leen de data-ejemplo.json en componentes.megamenu().
# movil = descripción y miniatura de la fila-tarjeta de la hoja móvil (08 §6.3).
PANELES = {
    "videos": {
        "z0": ("Videos", "Todos los ", "videos.html"),
        "z1": [("Qué ver", [("Programas completos", "videos.html#programas"), ("Shorts", "shorts.html")]),
               ("Por tema", [("Videos de Junior", "videos.html?tema=junior"),
                             ("Videos de la Selección", "videos.html?tema=seleccion"),
                             ("Videos de Juan Salvador", "juan-salvador.html#videos")])],
        "boton": ("Suscríbete en YouTube", YOUTUBE),
        "z2": {"tipo": "video", "etiqueta": "Último programa", "imagen": "js-2", "href": "video.html",
               "titular": "Programa completo: la previa de Junior vs. Nacional y la lista de la Selección",
               "duracion": "1:51:20", "fecha": "2026-11-06"},
        # 08 §4.1: S5 usa banderas-junior-millonarios (junior-escudo-bandera mide 372 px de alto y se pixela en 9:16)
        "z3": {"tipo": "shorts", "titulo": "Shorts", "href": "shorts.html", "enlace": "Ver todos",
               "piezas": [("El gol de Pertuz, en 30 segundos", "0:30", "junior-plantel-trofeo"),
                          ("Tres datos de la Fecha 19 en menos de un minuto", "0:48", "banderas-junior-millonarios")]},
        "movil": ("Programas y Shorts", "js-2"),
    },
    "noticias": {
        "z0": ("Noticias", "Todas las ", "noticias.html"),
        "z1": [("Nuestro foco", [("Noticias de Junior", "noticias.html?tema=junior"),
                                 ("Noticias de la Selección", "noticias.html?tema=seleccion")]),
               ("Más fútbol", [("Fútbol colombiano", "noticias.html?tema=futbol-colombiano"),
                               ("Europa", "noticias.html?tema=europa"),
                               ("Colombianos en el exterior", "noticias.html?tema=colombianos-exterior")]),
               ("Seguimiento y opinión", [("Mercado de fichajes", "mercado.html"),
                                          ("Opinión", "noticias.html?tema=opinion")])],
        # Titular y lectura: NOTAS_BASE[tema=junior] de componentes.py (la nota de articulo.html)
        "z2": {"tipo": "nota", "etiqueta": "Junior · Análisis", "nota": "junior", "imagen": "junior-trofeo-bandera",
               "href": "articulo.html"},
        # Filas: data-ejemplo.json > noticias_por_tema.notas (por id)
        "z3": {"tipo": "titulares", "titulo": "Más cobertura",
               "filas": [("n-fc1", "noticias.html?tema=futbol-colombiano"), ("n-eu1", "noticias.html?tema=europa"),
                         ("n-ex1", "noticias.html?tema=colombianos-exterior")]},
        "movil": ("Todos los temas", "junior-trofeo-bandera"),
    },
    "junior": {
        "z0": ("Junior FC", "Ir a ", "junior.html"),
        "z1": [("El equipo", [("Partidos de Junior", "junior.html#partidos"),
                              ("Plantilla de Junior", "junior.html#plantilla"),
                              ("Tabla de posiciones", "datos.html#posiciones")]),
               ("Lo último", [("Noticias de Junior", "noticias.html?tema=junior"),
                              ("Videos de Junior", "videos.html?tema=junior"),
                              ("Mercado de Junior", "mercado.html?equipo=junior")])],
        "z2": {"tipo": "nota", "etiqueta": "Junior · Crónica", "imagen": "junior-bicampeon", "href": "articulo.html",
               "titular": "El día que Junior se coronó bicampeón: la noche en tres escenas", "meta": "Texto de ejemplo"},
        # Partido en juego (partido_en_vivo) o, si no lo hay, calendario_junior.proximos[1]
        "z3": {"tipo": "partido", "titulo": "Partido", "equipo": "junior"},
        "movil": ("Partidos, plantilla y noticias", "junior-bicampeon"),
    },
    "seleccion": {
        "z0": ("Selección Colombia", "Ir a la ", "seleccion.html"),
        "z1": [("La Selección", [("Convocatoria", "seleccion.html#convocatoria"),
                                 ("Partidos de la Selección", "seleccion.html#partidos"),
                                 ("Eliminatorias", "datos.html?t=eliminatorias")]),
               ("Lo último", [("Noticias de la Selección", "noticias.html?tema=seleccion"),
                              ("Videos de la Selección", "videos.html?tema=seleccion")])],
        "z2": {"tipo": "nota", "etiqueta": "Selección · Convocatoria", "nota": "seleccion", "imagen": "seleccion-once",
               "href": "articulo.html"},
        # calendario_seleccion.proximos[0] y, debajo, proximos[1]
        "z3": {"tipo": "partido", "titulo": "Próximo partido", "equipo": "seleccion", "href": "seleccion.html#partidos"},
        "movil": ("Convocatoria y partidos", "seleccion-once"),
    },
    "datos": {
        "z0": ("Datos", "Ir a ", "datos.html"),
        "z1": [("Hoy y tablas", [("Partidos de hoy", "datos.html#hoy"),
                                 ("Partido en vivo", "partido.html"),   # «Último partido» si no hay partido en juego
                                 ("Posiciones", "datos.html#posiciones"),
                                 ("Resultados de la fecha", "datos.html#resultados"),
                                 ("Goleadores", "datos.html#goleadores"),
                                 ("Estadísticas", "datos.html#estadisticas")]),
               ("Torneos", [("Liga BetPlay", "datos.html?t=liga"), ("Copa Colombia", "datos.html?t=copa"),
                            ("Eliminatorias", "datos.html?t=eliminatorias"),
                            ("Copa Libertadores", "datos.html?t=libertadores"),
                            ("Copa Sudamericana", "datos.html?t=sudamericana")])],
        # Sin foto: aquí la imagen es el dato (tabla_posiciones.filas[0:6])
        "z2": {"tipo": "tabla", "titulo": "Liga BetPlay · Finalización 2026 (ejemplo)", "filas": 6,
               "enlace": ("Ver la tabla completa", "datos.html#posiciones")},
        # resultados.filtro_dias: hoy y mañana
        "z3": {"tipo": "agenda", "titulo": "Hoy y mañana"},
        "movil": ("Tabla y resultados", "banderas-junior-nacional"),
    },
    "en-vivo": {
        "z0": ("En vivo", "Ir a ", "en-vivo.html"),
        "z1": [("El programa", [("Parrilla de lunes a viernes", "en-vivo.html#parrilla"),
                                ("Participa en el programa", "en-vivo.html#participa")]),
               ("Para ponerte al día", [("Programas anteriores", "en-vivo.html#episodios")])],
        "boton": ("Seguir el canal en YouTube", YOUTUBE),
        "z2": {"tipo": "programa", "imagen": "js-1", "href": "en-vivo.html",
               "titular": "El show de las mañanas, en vivo desde Barranquilla"},
        # programa.parrilla (Ahora / Después) + partido en juego
        "z3": {"tipo": "parrilla", "titulo": "Ahora y después"},
        "movil": ("El show de las mañanas", "js-1"),
    },
    "mas": {
        "z1": [("Habla Deportes", [("Quiénes somos", "contacto.html#nosotros"), ("Contacto", "contacto.html#contacto"),
                                   ("Publicidad y patrocinios", "contacto.html#publicidad"),
                                   ("Nuestros aliados", "#pie-aliados"), ("Redes", "redes.html"),
                                   ("Legales", "legales.html")])],
        "perfil": {"etiqueta": "El programa", "nombre": "Juan Salvador Bárcena",
                   "cargo": "Presentador y creador de contenido", "imagen": "js-2", "href": "juan-salvador.html",
                   "enlaces": [("Videos de Juan Salvador", "juan-salvador.html#videos"),
                               ("Su timeline en X", "juan-salvador.html#x"),
                               ("Pregúntale a Juan", "juan-salvador.html#pregunta")]},
    },
}

# Hoja móvil: bloque «Más» (filas sencillas, sin acordeón) y sugerencias del buscador
MAS_MOVIL = [("Juan Salvador", "juan-salvador.html"), ("Redes", "redes.html"),
             ("Quiénes somos", "contacto.html#nosotros"), ("Contacto y publicidad", "contacto.html"),
             ("Nuestros aliados", "#pie-aliados"), ("Legales", "legales.html")]
SUGERENCIAS = [("Junior", "junior"), ("Selección", "seleccion"), ("Mercado", "mercado"), ("Europa", "europa")]

# --- Barra inferior móvil: 5 pestañas (09 §4.3) --------------------------------
INFERIOR = [
    {"clave": "inicio", "texto": "Inicio", "href": "index.html", "icono": "house"},
    {"clave": "videos", "texto": "Videos", "href": "videos.html", "icono": "monitor-play"},
    {"clave": "noticias", "texto": "Noticias", "href": "noticias.html", "icono": "newspaper"},
    {"clave": "datos", "texto": "Datos", "href": "datos.html", "icono": "chart-bar", "punto": "partido"},
    {"clave": "en-vivo", "texto": "En vivo", "href": "en-vivo.html", "icono": "broadcast", "punto": "aire"},
]

# --- Pie (02 §3.6) -----------------------------------------------------------
PIE = [
    ("Noticias", [("Todas las noticias", "noticias.html"), ("Junior FC", "junior.html"), ("Selección", "seleccion.html"),
                  ("Fútbol colombiano", "noticias.html?tema=futbol-colombiano"), ("Europa", "noticias.html?tema=europa"),
                  ("Colombianos en el exterior", "noticias.html?tema=colombianos-exterior"),
                  ("Mercado de fichajes", "mercado.html"), ("Opinión", "noticias.html?tema=opinion")]),
    ("Videos y en vivo", [("Videos", "videos.html"), ("Shorts", "shorts.html"), ("En vivo", "en-vivo.html"),
                          ("Parrilla", "en-vivo.html#parrilla"), ("Episodios", "en-vivo.html#episodios")]),
    ("Datos", [("Posiciones", "datos.html#posiciones"), ("Resultados", "datos.html#resultados"),
               ("Goleadores", "datos.html#goleadores"), ("Partido en vivo", "partido.html")]),
    ("Habla Deportes", [("Juan Salvador", "juan-salvador.html"), ("Quiénes somos", "contacto.html#nosotros"), ("Redes", "redes.html"),
                        ("Contacto", "contacto.html#contacto"), ("Publicidad", "contacto.html#publicidad"),
                        ("Legales", "legales.html")]),
]

# --- Sitemap (02 §2.2): las 18 páginas --------------------------------------
# primario -> ítem activo de la barra azul (None = ninguno; «mas» marca Más)
# inferior -> pestaña activa de la barra inferior móvil (None = ninguna)
PAGINAS = {
    "index":         {"primario": None,        "inferior": "inicio"},
    "videos":        {"primario": "videos",    "inferior": "videos"},
    "shorts":        {"primario": "videos",    "inferior": "videos"},
    "video":         {"primario": "videos",    "inferior": "videos"},
    "noticias":      {"primario": "noticias",  "inferior": "noticias"},
    "articulo":      {"primario": "noticias",  "inferior": "noticias"},
    "mercado":       {"primario": "noticias",  "inferior": "noticias"},
    "junior":        {"primario": "junior",    "inferior": "noticias"},
    "seleccion":     {"primario": "seleccion", "inferior": "noticias"},
    "datos":         {"primario": "datos",     "inferior": "datos"},
    "partido":       {"primario": "datos",     "inferior": "datos"},
    "en-vivo":       {"primario": "en-vivo",   "inferior": "en-vivo"},
    "juan-salvador": {"primario": "mas",       "inferior": None},
    "redes":         {"primario": "mas",       "inferior": None},
    "contacto":      {"primario": "mas",       "inferior": None},
    "legales":       {"primario": "mas",       "inferior": None},
    "buscar":        {"primario": None,        "inferior": None},
    "404":           {"primario": None,        "inferior": None},
}


def validar_etiquetas():
    """09 §0.4: una etiqueta nunca lleva a dos destinos (menú, hoja móvil, pie y barra inferior)."""
    pares = [(p["texto"], p["href"]) for p in PRIMARIO] + [(i["texto"], i["href"]) for i in INFERIOR]
    pares += MAS_MOVIL + [x for _, links in PIE for x in links]
    for panel in PANELES.values():
        pares += [x for _, links in panel["z1"] for x in links]
        pares += panel.get("perfil", {}).get("enlaces", [])
    destinos = {}
    for texto, href in pares:
        destinos.setdefault(texto, set()).add(href)
    dobles = {t: sorted(h) for t, h in destinos.items() if len(h) > 1}
    if dobles:
        raise ValueError(f"sitio.py: etiquetas con más de un destino: {dobles}")


validar_etiquetas()
