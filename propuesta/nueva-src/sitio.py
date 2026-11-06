"""Configuración del sitio: ÚNICO origen de verdad de la navegación.

Todo lo que aparece en la cabecera, la barra contextual, el menú móvil, la barra
inferior y el pie sale de aquí (02-arquitectura.md, secciones 2, 3 y 8).
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

# --- Barra azul (02 §3.2) ---------------------------------------------------
# (clave, texto, destino)
PRIMARIO = [
    ("noticias", "Noticias", "noticias.html"),
    ("junior", "Junior FC", "junior.html"),
    ("seleccion", "Selección", "seleccion.html"),
    ("videos", "Videos", "videos.html"),
    ("datos", "Datos", "datos.html"),
    ("en-vivo", "En vivo", "en-vivo.html"),
]
# Panel "Más ▾": su padre enlaza al bloque de enlaces del pie, que contiene
# los mismos destinos (mitigación de los paneles solo-hover, 02 I3).
MAS = {
    "texto": "Más",
    "destino": "#pie-enlaces",
    "items": [
        ("Mercado de fichajes", "mercado.html"),
        ("Opinión", "juan-salvador.html"),
        ("Redes", "redes.html"),
        ("Quiénes somos", "contacto.html#nosotros"),
        ("Contacto y publicidad", "contacto.html#publicidad"),
    ],
}

# --- Barras blancas contextuales (02 §3.3) ----------------------------------
# Cada ítem: (texto, destino) o (texto, destino, [panel]) -> panel por hover.
# "extra" es un enlace a la derecha (p. ej. Suscríbete en YouTube).
CONTEXTO = {
    "noticias": {
        "titulo": "Noticias", "destino": "noticias.html",
        "items": [
            ("Todas", "noticias.html"),
            ("Junior", "noticias.html?tema=junior"),
            ("Selección", "noticias.html?tema=seleccion"),
            ("Fútbol colombiano", "noticias.html?tema=futbol-colombiano"),
            ("Europa", "noticias.html?tema=europa"),
            ("Colombianos en el exterior", "noticias.html?tema=colombianos-exterior"),
            ("Mercado", "noticias.html?tema=mercado"),
            ("Opinión", "noticias.html?tema=opinion"),
        ],
    },
    "junior": {
        "titulo": "Junior FC", "destino": "junior.html",
        "items": [
            ("Resumen", "junior.html"),
            ("Noticias", "noticias.html?tema=junior"),
            ("Partidos", "junior.html#partidos"),
            ("Plantilla", "junior.html#plantilla"),
            ("Mercado", "mercado.html?equipo=junior"),
            ("Videos", "videos.html?tema=junior"),
            ("Datos", "datos.html", [
                ("Posiciones", "datos.html#posiciones"),
                ("Resultados y calendario", "datos.html#resultados"),
                ("Goleadores", "datos.html#goleadores"),
                ("Estadísticas", "datos.html#estadisticas"),
            ]),
        ],
    },
    "seleccion": {
        "titulo": "Selección Colombia", "destino": "seleccion.html",
        "items": [
            ("Resumen", "seleccion.html"),
            ("Noticias", "noticias.html?tema=seleccion"),
            ("Convocatoria", "seleccion.html#convocatoria"),
            ("Partidos", "seleccion.html#partidos"),
            ("Eliminatorias", "datos.html?t=eliminatorias"),
            ("Videos", "videos.html?tema=seleccion"),
        ],
    },
    "videos": {
        "titulo": "Videos", "destino": "videos.html",
        "items": [
            ("Todos", "videos.html"),
            ("Programas completos", "videos.html#programas"),
            ("Shorts", "shorts.html"),
            ("Junior", "videos.html?tema=junior"),
            ("Selección", "videos.html?tema=seleccion"),
        ],
        "extra": ("Suscríbete en YouTube", "https://youtube.com/@habladeportesradio"),
    },
    "datos": {
        "titulo": "Datos", "destino": "datos.html",
        "items": [
            ("Hoy", "datos.html#hoy"),
            ("Posiciones", "datos.html#posiciones"),
            ("Resultados", "datos.html#resultados"),
            ("Goleadores", "datos.html#goleadores"),
            ("Estadísticas", "datos.html#estadisticas"),
            ("Partido en vivo", "partido.html"),
            ("Torneos", "datos.html#torneos", [
                ("Liga BetPlay", "datos.html?t=liga"),
                ("Copa Colombia", "datos.html?t=copa"),
                ("Eliminatorias", "datos.html?t=eliminatorias"),
                ("Copa Libertadores", "datos.html?t=libertadores"),
                ("Copa Sudamericana", "datos.html?t=sudamericana"),
            ]),
        ],
    },
    "en-vivo": {
        "titulo": "En vivo", "destino": "en-vivo.html",
        "items": [
            ("Programa en vivo", "en-vivo.html"),
            ("Parrilla", "en-vivo.html#parrilla"),
            ("Episodios", "en-vivo.html#episodios"),
            ("Participa", "en-vivo.html#participa"),
        ],
    },
    "habla": {
        "titulo": "Habla Deportes", "destino": "contacto.html",
        "items": [
            ("Quiénes somos", "contacto.html#nosotros"),
            ("Redes", "redes.html"),
            ("Contacto", "contacto.html#contacto"),
            ("Publicidad", "contacto.html#publicidad"),
            ("Legales", "legales.html"),
        ],
    },
}

# --- Barra inferior móvil (02 §3.4) -----------------------------------------
INFERIOR = [
    ("noticias", "Noticias", "index.html", "newspaper"),
    ("videos", "Videos", "videos.html", "monitor-play"),
    ("datos", "Datos", "datos.html", "chart-bar"),
    ("en-vivo", "En vivo", "en-vivo.html", "broadcast"),
]

# --- Menú móvil (hamburguesa): dos grupos de enlaces simples ---------------
MENU_MOVIL = [
    [(t, h) for _, t, h in PRIMARIO],
    [("Mercado de fichajes", "mercado.html"), ("Opinión", "juan-salvador.html"),
     ("Redes", "redes.html"), ("Quiénes somos", "contacto.html#nosotros"),
     ("Contacto y publicidad", "contacto.html#publicidad"), ("Legales", "legales.html")],
]

# --- Pie (02 §3.6) -----------------------------------------------------------
PIE = [
    ("Noticias", [("Todas las noticias", "noticias.html"), ("Junior FC", "junior.html"), ("Selección", "seleccion.html"),
                  ("Fútbol colombiano", "noticias.html?tema=futbol-colombiano"), ("Europa", "noticias.html?tema=europa"),
                  ("Colombianos en el exterior", "noticias.html?tema=colombianos-exterior"),
                  ("Mercado de fichajes", "mercado.html"), ("Opinión", "juan-salvador.html")]),
    ("Videos y en vivo", [("Videos", "videos.html"), ("Shorts", "shorts.html"), ("En vivo", "en-vivo.html"),
                          ("Parrilla", "en-vivo.html#parrilla"), ("Episodios", "en-vivo.html#episodios")]),
    ("Datos", [("Posiciones", "datos.html#posiciones"), ("Resultados", "datos.html#resultados"),
               ("Goleadores", "datos.html#goleadores"), ("Partido en vivo", "partido.html")]),
    ("Habla Deportes", [("Quiénes somos", "contacto.html#nosotros"), ("Redes", "redes.html"),
                        ("Contacto", "contacto.html#contacto"), ("Publicidad", "contacto.html#publicidad"),
                        ("Legales", "legales.html")]),
]

# --- Sitemap (02 §2.2): las 18 páginas --------------------------------------
# seccion   -> barra contextual (CONTEXTO)
# primario  -> ítem activo de la barra azul (None en la portada)
# inferior  -> ítem activo de la barra inferior móvil
# activo    -> texto del ítem activo en la barra contextual (None = ninguno)
PAGINAS = {
    "index":         {"seccion": "noticias",  "primario": None,        "inferior": "noticias", "activo": None},
    "noticias":      {"seccion": "noticias",  "primario": "noticias",  "inferior": "noticias", "activo": "Todas"},
    "articulo":      {"seccion": "noticias",  "primario": "noticias",  "inferior": "noticias", "activo": None},
    "mercado":       {"seccion": "noticias",  "primario": "noticias",  "inferior": "noticias", "activo": None},
    "juan-salvador": {"seccion": "noticias",  "primario": "noticias",  "inferior": "noticias", "activo": None},
    "junior":        {"seccion": "junior",    "primario": "junior",    "inferior": "noticias", "activo": "Resumen"},
    "seleccion":     {"seccion": "seleccion", "primario": "seleccion", "inferior": "noticias", "activo": "Resumen"},
    "videos":        {"seccion": "videos",    "primario": "videos",    "inferior": "videos",   "activo": "Todos"},
    "shorts":        {"seccion": "videos",    "primario": "videos",    "inferior": "videos",   "activo": "Shorts"},
    "video":         {"seccion": "videos",    "primario": "videos",    "inferior": "videos",   "activo": None},
    "datos":         {"seccion": "datos",     "primario": "datos",     "inferior": "datos",    "activo": None},
    "partido":       {"seccion": "datos",     "primario": "datos",     "inferior": "datos",    "activo": "Partido en vivo"},
    "en-vivo":       {"seccion": "en-vivo",   "primario": "en-vivo",   "inferior": "en-vivo",  "activo": "Programa en vivo"},
    "redes":         {"seccion": "habla",     "primario": None,        "inferior": None,       "activo": "Redes"},
    "contacto":      {"seccion": "habla",     "primario": None,        "inferior": None,       "activo": None},
    "legales":       {"seccion": "habla",     "primario": None,        "inferior": None,       "activo": "Legales"},
    "buscar":        {"seccion": "habla",     "primario": None,        "inferior": None,       "activo": None},
    "404":           {"seccion": "habla",     "primario": None,        "inferior": None,       "activo": None},
}
