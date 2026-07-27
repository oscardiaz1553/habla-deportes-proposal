# -*- coding: utf-8 -*-
"""Genera el JSON de la home de Habla Deportes para Elementor."""
import json, itertools

LOOP_ITEM = 146          # "Habla Deportes - Tarjeta de video"
NAVY, TEXTO, MUTED, NARANJA = "#294D6E", "#17222E", "#5E6B77", "#E36209"

_ids = ("hd%05x" % n for n in itertools.count(0x10000))
def nid():
    return next(_ids)


def con(children, **s):
    """Contenedor de Elementor."""
    base = {"content_width": "boxed", "flex_direction": "column"}
    base.update(s)
    return {"id": nid(), "elType": "container", "settings": base, "elements": children}


def widget(kind, **s):
    return {"id": nid(), "elType": "widget", "widgetType": kind,
            "settings": s, "elements": []}


def px(v, linked=False):
    t, r, b, l = v if isinstance(v, tuple) else (v, v, v, v)
    return {"unit": "px", "top": str(t), "right": str(r),
            "bottom": str(b), "left": str(l), "isLinked": linked}


def size(v, unit="px"):
    return {"unit": unit, "size": v, "sizes": []}


def titulo_seccion(texto, enlace_texto="Ver todo"):
    """Encabezado de sección: titular a la izquierda, enlace a la derecha."""
    return con(
        [
            widget("heading", title=texto, header_size="h2", title_color=TEXTO,
                   typography_typography="custom", typography_font_family="Archivo",
                   typography_font_size=size(26), typography_font_weight="700",
                   typography_letter_spacing=size(-0.4),
                   typography_line_height=size(1.2, "em")),
            widget("heading", title=enlace_texto, header_size="p", title_color=NAVY,
                   typography_typography="custom", typography_font_family="Inter",
                   typography_font_size=size(15), typography_font_weight="600"),
        ],
        flex_direction="row", content_width="full",
        flex_justify_content="space-between", flex_align_items="center",
        flex_gap={"unit": "px", "size": 16, "column": "16", "row": "8", "isLinked": True},
        padding=px(0), margin=px((0, 0, 20, 0)),
    )


def consulta(term_ttid=None, cuantos=4):
    """Ajustes del grupo de consulta del Loop Grid / Loop Carousel."""
    q = {"query_post_type": "video", "query_posts_per_page": cuantos}
    if term_ttid is not None:
        q["query_include"] = ["terms"]
        q["query_include_term_ids"] = [str(term_ttid)]
    return q


def rejilla(term_ttid=None, cuantos=4, columnas=4):
    return widget(
        "loop-grid", _skin="post", template_id=LOOP_ITEM,
        columns=str(columnas), columns_tablet="2", columns_mobile="1",
        pagination_type="",
        row_gap=size(24), column_gap=size(20),
        **consulta(term_ttid, cuantos),
    )


def carrusel(term_ttid=None, cuantos=8, por_vista=4, clase=""):
    return widget(
        "loop-carousel", _skin="post", template_id=LOOP_ITEM,
        slides_to_show=str(por_vista), slides_to_show_tablet="2", slides_to_show_mobile="1",
        autoplay="", infinite="yes", arrows="yes", pagination="",
        gap=size(20),
        _css_classes=clase,
        **consulta(term_ttid, cuantos),
    )


def seccion(titulo, contenido):
    """Sección contenida, con su encabezado y su rejilla/carrusel."""
    return con([titulo_seccion(titulo), contenido],
               content_width="boxed",
               padding=px((48, 24, 48, 24)))


# ---------------------------------------------------------------- HOME
contenido = [
    # 1. Hero — a sangre
    con([widget("hd-hero", cantidad=3, flechas="yes", dots="yes",
                autoplay="yes", interval=6000)],
        content_width="full", padding=px(0)),

    # 2. Marquesina — a sangre
    con([widget("hd-ticker", etiqueta="Noticias", categoria="Videos",
                cantidad=8, duracion=size(38, "s"))],
        content_width="full", padding=px(0)),

    # 3. Centro de Partidos + publicidad, en dos columnas
    con(
        [
            con([widget("hd-partidos", cantidad=5, dots="yes",
                        autoplay="yes", interval=6000)],
                content_width="full", width=size(74, "%"), padding=px(0)),
            con([widget("image", image={"url": "", "id": ""},
                        _css_classes="hd-publicidad")],
                content_width="full", width=size(26, "%"), padding=px(0)),
        ],
        content_width="boxed", flex_direction="row",
        flex_gap={"unit": "px", "size": 24, "column": "24", "row": "24", "isLinked": True},
        padding=px((48, 24, 48, 24)),
    ),

    # 4-7. Secciones de video
    seccion("El show de la mañana", carrusel(None, cuantos=8, por_vista=4)),
    seccion("Mundo Junior", rejilla(2, cuantos=4)),
    seccion("Clips", carrusel(11, cuantos=10, por_vista=5, clase="hd-clips")),
    seccion("Selección Colombia", rejilla(3, cuantos=4)),
]

doc = {
    "title": "Habla Deportes — Inicio",
    "type": "page",
    "version": "0.4",
    "page_settings": {
        "custom_css": (
            "/* ===== Home Habla Deportes =====\n"
            "   El hero y la marquesina van a sangre; el resto, contenido a 1280.\n"
            "   La publicidad del Centro de Partidos es un widget Imagen: se cambia\n"
            "   el creativo desde el panel, sin tocar codigo. */\n\n"
            "/* La publicidad iguala el alto del banner de partidos en escritorio. */\n"
            "@media (min-width: 900px) {\n"
            "\t.hd-publicidad img { height: 424px; width: 100%; object-fit: cover;\n"
            "\t\tborder-radius: 8px; display: block; }\n"
            "}\n"
        )
    },
    "content": contenido,
}

ruta = r"C:\Users\OscarD\Documents\habla-deportes-proposal\wordpress\elementor-home-habla-deportes.json"
with open(ruta, "w", encoding="utf-8") as f:
    json.dump(doc, f, ensure_ascii=False, indent=2)

print("Escrito:", ruta)


def resumen(els, p=""):
    for e in els:
        k = e.get("widgetType") or e["elType"]
        extra = ""
        if k in ("loop-grid", "loop-carousel"):
            s = e["settings"]
            extra = f"  tpl={s['template_id']} terms={s.get('query_include_term_ids', '-')} n={s['query_posts_per_page']}"
        elif k == "heading":
            extra = "  " + e["settings"].get("title", "")
        print(p + k + extra)
        resumen(e.get("elements", []), p + "  ")


resumen(doc["content"])
