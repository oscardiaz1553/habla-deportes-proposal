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

# Barra azul, de izquierda a derecha (pedido del dueño, 7-nov-2026, según las
# respuestas del cliente): Inicio · Videos · Junior · FPC · Fútbol internacional
# · Más. Noticias, Selección, Datos y En vivo pasan al panel «Más».
# Todos los ítems son enlaces reales; todos menos Inicio abren un megamenú.
# sr = (prefijo, sufijo) solo para lector de pantalla: el nombre accesible
#      contiene la etiqueta visible («Fútbol colombiano (FPC)»).
# temas = filtros de noticias.html?tema= que marcan este ítem como activo (main.js).
# punto: "aire" o "partido" (ver componentes.punto_vivo); "cualquiera" = uno u otro.
PRIMARIO = [
    {"clave": "inicio", "texto": "Inicio", "href": "index.html"},
    {"clave": "videos", "texto": "Videos", "href": "videos.html"},
    {"clave": "junior", "texto": "Junior", "href": "junior.html", "temas": "junior"},
    {"clave": "fpc", "texto": "FPC", "href": "noticias.html?tema=futbol-colombiano",
     "sr": ("Fútbol colombiano (", ")"), "temas": "futbol-colombiano"},
    {"clave": "internacional", "texto": "Fútbol internacional", "href": "noticias.html?tema=europa",
     "temas": "europa colombianos-exterior"},
    {"clave": "mas", "texto": "Más", "href": "#pie-enlaces", "punto": "cualquiera"},
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
    "fpc": {
        "z0": ("Fútbol colombiano (FPC)", "Ir a ", "noticias.html?tema=futbol-colombiano"),
        "z1": [("Fútbol colombiano", [("Noticias del FPC", "noticias.html?tema=futbol-colombiano"),
                                      ("Videos del FPC", "videos.html?tema=fpc"),
                                      ("Posiciones", "datos.html#posiciones"),
                                      ("Resultados de la fecha", "datos.html#resultados")]),
               ("Torneos", [("Liga BetPlay", "datos.html?t=liga"), ("Copa Colombia", "datos.html?t=copa"),
                            ("Eliminatorias", "datos.html?t=eliminatorias"),
                            ("Copa Libertadores", "datos.html?t=libertadores"),
                            ("Copa Sudamericana", "datos.html?t=sudamericana")])],
        # Nota n-fc1 de data-ejemplo.json (Tolima y América). Foto: nacional-celebracion,
        # la única de fútbol colombiano de clubes que no es destacada en otro panel.
        "z2": {"tipo": "nota", "nota_id": "n-fc1", "imagen": "nacional-celebracion",
               "href": "noticias.html?tema=futbol-colombiano"},
        # resultados.filtro_dias: hoy y mañana
        "z3": {"tipo": "agenda", "titulo": "Hoy y mañana"},
        "movil": ("Liga, tabla y resultados", "nacional-celebracion"),
    },
    "internacional": {
        "z0": ("Fútbol internacional", "Ir a ", "noticias.html?tema=europa"),
        "z1": [("Europa", [("Noticias de Europa", "noticias.html?tema=europa")]),
               ("Colombianos en el exterior", [("Noticias de colombianos en el exterior",
                                                "noticias.html?tema=colombianos-exterior")]),
               ("Mercado", [("Mercado de fichajes", "mercado.html")])],
        # Sin foto de Europa ni del exterior en el catálogo: titulares grandes, sin imagen.
        # data-ejemplo.json > noticias_por_tema.notas (la primera, destacada)
        "z2": {"tipo": "titulares", "titulo": "Europa", "href": "noticias.html?tema=europa",
               "filas": ["n-eu1", "n-eu2", "n-eu3"]},
        "z3": {"tipo": "titulares", "titulo": "Colombianos en el exterior",
               "href": "noticias.html?tema=colombianos-exterior", "filas": ["n-ex1", "n-ex2"]},
        "movil": ("Europa y colombianos afuera", "banderas-colombia-peru"),
    },
    # «Más»: ancho completo, cinco columnas de enlaces + el perfil del presentador.
    # Aquí viven Selección, Datos, En vivo y Noticias (ya no están en la barra).
    "mas": {
        "z1": [("Selección Colombia", [("Selección Colombia", "seleccion.html"),
                                       ("Convocatoria", "seleccion.html#convocatoria"),
                                       ("Partidos de la Selección", "seleccion.html#partidos"),
                                       ("Noticias de la Selección", "noticias.html?tema=seleccion"),
                                       ("Videos de la Selección", "videos.html?tema=seleccion")]),
               ("Datos", [("Partidos de hoy", "datos.html#hoy"),
                          ("Partido en vivo", "partido.html"),   # «Último partido» si no hay partido en juego
                          ("Posiciones", "datos.html#posiciones"),
                          ("Resultados de la fecha", "datos.html#resultados"),
                          ("Goleadores", "datos.html#goleadores"),
                          ("Estadísticas", "datos.html#estadisticas")]),
               ("En vivo", [("El programa en vivo", "en-vivo.html"),   # punto rojo si está al aire
                            ("Parrilla", "en-vivo.html#parrilla"),
                            ("Programas anteriores", "en-vivo.html#episodios"),
                            ("Participa", "en-vivo.html#participa")]),
               ("Noticias", [("Todas las noticias", "noticias.html"),
                             ("Opinión", "noticias.html?tema=opinion"),
                             ("Un día como hoy", "noticias.html#efemerides")]),
               ("Habla Deportes", [("Quiénes somos", "contacto.html#nosotros"), ("Contacto", "contacto.html#contacto"),
                                   ("Publicidad y patrocinios", "contacto.html#publicidad"),
                                   ("Nuestros aliados", "#pie-aliados"), ("Redes", "redes.html"),
                                   ("Legales", "legales.html")])],
        "perfil": {"etiqueta": "El programa", "nombre": "Juan Salvador Bárcena",
                   "cargo": "Presentador y creador de contenido", "imagen": "js-2", "href": "juan-salvador.html",
                   "enlaces": [("Videos de Juan Salvador", "juan-salvador.html#videos"),
                               ("Su timeline en X", "juan-salvador.html#x"),
                               ("Pregúntale a Juan", "juan-salvador.html#pregunta")]},
        "movil": ("Selección, Datos y En vivo", "js-1"),
    },
}

# Hoja móvil: fila «Inicio» (sin acordeón) y sugerencias del buscador
INICIO_MOVIL = ("La portada de hoy", "junior-plantel-trofeo")
SUGERENCIAS = [("Junior", "junior"), ("Selección", "seleccion"), ("Mercado", "mercado"), ("Europa", "europa")]

# --- Barra inferior móvil: las mismas 6 opciones que la barra azul -------------
# (decisión del dueño, 7-nov). Mismo ítem activo que la barra azul (PAGINAS).
# A 360 px cada pestaña mide 60: «Fútbol internacional» no cabe a 12 px, así que
# SOLO aquí se muestra «Mundo» y el nombre accesible es «Mundo: fútbol internacional».
# «Más» es un botón que abre la hoja del menú (no navega).
INFERIOR = [
    {"clave": "inicio", "texto": "Inicio", "href": "index.html", "icono": "house"},
    {"clave": "videos", "texto": "Videos", "href": "videos.html", "icono": "monitor-play"},
    {"clave": "junior", "texto": "Junior", "href": "junior.html", "icono": "shield"},
    {"clave": "fpc", "texto": "FPC", "href": "noticias.html?tema=futbol-colombiano", "icono": "trophy",
     "sr": ("Fútbol colombiano (", ")")},
    {"clave": "internacional", "texto": "Mundo", "href": "noticias.html?tema=europa", "icono": "globe",
     "sr": ("", ": fútbol internacional")},
    {"clave": "mas", "texto": "Más", "icono": "dots-three", "punto": "cualquiera", "abre_menu": True},
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
# primario -> ítem activo de la barra azul Y de la barra inferior (None = ninguno)
PAGINAS = {
    "index":         {"primario": "inicio"},
    "videos":        {"primario": "videos"},
    "shorts":        {"primario": "videos"},
    "video":         {"primario": "videos"},
    "junior":        {"primario": "junior"},
    "mercado":       {"primario": "internacional"},
    # noticias.html: «Más» (Todas las noticias); main.js pasa el activo a Junior, FPC o
    # Fútbol internacional según ?tema= (atributo temas de PRIMARIO)
    "noticias":      {"primario": "mas"},
    "articulo":      {"primario": "mas"},
    "seleccion":     {"primario": "mas"},
    "datos":         {"primario": "mas"},
    "partido":       {"primario": "mas"},
    "en-vivo":       {"primario": "mas"},
    "juan-salvador": {"primario": "mas"},
    "redes":         {"primario": "mas"},
    "contacto":      {"primario": "mas"},
    "legales":       {"primario": "mas"},
    "buscar":        {"primario": None},
    "404":           {"primario": None},
}


def validar_etiquetas():
    """09 §0.4: una etiqueta nunca lleva a dos destinos (menú, hoja móvil, pie y barra inferior)."""
    pares = [(p["texto"], p["href"]) for p in PRIMARIO] + [(i["texto"], i["href"]) for i in INFERIOR if "href" in i]
    pares += [x for _, links in PIE for x in links]
    for panel in PANELES.values():
        pares += [x for _, links in panel["z1"] for x in links]
        perfil = panel.get("perfil")
        if perfil:
            pares += perfil["enlaces"] + [(perfil["nombre"], perfil["href"])]
    destinos = {}
    for texto, href in pares:
        destinos.setdefault(texto, set()).add(href)
    dobles = {t: sorted(h) for t, h in destinos.items() if len(h) > 1}
    if dobles:
        raise ValueError(f"sitio.py: etiquetas con más de un destino: {dobles}")


validar_etiquetas()
