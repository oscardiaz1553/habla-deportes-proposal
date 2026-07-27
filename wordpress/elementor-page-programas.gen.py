# -*- coding: utf-8 -*-
"""Genera elementor-page-programas.json (plantilla de pagina para Elementor 4.2.0).

Convenciones heredadas de las plantillas ya importadas:
  - claves raiz: title / type / version / page_settings / content
  - version "0.4", contenedores flexbox de 4.2.0
  - __globals__ apuntando al kit #50 con hex de respaldo
  - IDs hex de 7 caracteres, unicos
  - salida UTF-8 sin BOM
"""
import json, os

NAVY   = "#294D6E"   # accent
NAVYD  = "#1E3A55"   # hdnavyd
ROJO   = "#D20000"   # hdrojov
BGPAG  = "#F4F6F8"   # hdbgpag
TXTAT  = "#5E6B77"   # hdtxtat
TINTA  = "#17222E"   # primary
LINEA  = "#E3E9EF"

_n = [0]
def uid():
    """IDs hex de 7 caracteres, deterministas y unicos dentro de la plantilla."""
    _n[0] += 1
    return "b%06x" % (0x7a0100 + _n[0])

def dim(t, r, b, l, unit="px"):
    return {"unit": unit, "top": str(t), "right": str(r), "bottom": str(b),
            "left": str(l), "isLinked": t == r == b == l}

def sz(size, unit="px"):
    return {"unit": unit, "size": size, "sizes": []}

def gap(col, row=None, unit="px"):
    row = col if row is None else row
    return {"unit": unit, "column": str(col), "row": str(row), "isLinked": col == row}

def cont(settings, children):
    return {"id": uid(), "elType": "container", "isInner": False,
            "settings": settings, "elements": children}

def widget(wtype, settings):
    return {"id": uid(), "elType": "widget", "widgetType": wtype,
            "settings": settings, "elements": []}


# --------------------------------------------------------------------------
# 1. HERO DEL PROGRAMA
# --------------------------------------------------------------------------
hero = cont(
    {
        "container_type": "flex",
        "content_width": "full",
        "flex_direction": "column",
        "flex_align_items": "flex-start",
        "flex_gap": gap(0, 12),
        "padding": dim(40, 40, 40, 40),
        "padding_tablet": dim(32, 32, 32, 32),
        "padding_mobile": dim(24, 20, 24, 20),
        "background_background": "gradient",
        "background_color": NAVY,
        "background_color_stop": sz(0, "%"),
        "background_color_b": NAVYD,
        "background_color_b_stop": sz(100, "%"),
        "background_gradient_type": "linear",
        "background_gradient_angle": sz(160, "deg"),
        "border_radius": dim(16, 16, 16, 16),
        "css_classes": "hd-prog-hero",
        "__globals__": {
            "background_color": "globals/colors?id=accent",
            "background_color_b": "globals/colors?id=hdnavyd",
        },
    },
    [
        widget("heading", {
            "title": "Programa",
            "header_size": "p",
            "align": "left",
            "title_color": "rgba(255,255,255,0.7)",
            "typography_typography": "custom",
            "typography_font_family": "Inter",
            "typography_font_size": sz(13),
            "typography_font_weight": "600",
            "typography_text_transform": "uppercase",
            "typography_letter_spacing": sz(0.08, "em"),
            "typography_line_height": sz(1.2, "em"),
            "_css_classes": "hd-prog-eyebrow",
        }),
        widget("heading", {
            "title": "El show de la mañana",
            "header_size": "h1",
            "align": "left",
            "title_color": "#FFFFFF",
            "typography_typography": "custom",
            "typography_font_family": "Archivo",
            "typography_font_size": sz(44),
            "typography_font_size_tablet": sz(38),
            "typography_font_size_mobile": sz(32),
            "typography_font_weight": "700",
            "typography_line_height": sz(1.1, "em"),
            "typography_letter_spacing": sz(-0.6),
            "_css_classes": "hd-prog-title",
        }),
        widget("heading", {
            "title": "Lunes a viernes · 10:00 a.m. – 12:00 m.",
            "header_size": "p",
            "align": "left",
            "title_color": "rgba(255,255,255,0.85)",
            "typography_typography": "custom",
            "typography_font_family": "Inter",
            "typography_font_size": sz(16),
            "typography_font_size_mobile": sz(15),
            "typography_font_weight": "600",
            "typography_line_height": sz(1.5, "em"),
            "_css_classes": "hd-prog-schedule",
        }),
        widget("heading", {
            "title": ("Dos horas en directo para arrancar el día hablando de fútbol colombiano: "
                      "la actualidad del Junior, la Selección y todo lo que mueve al FPC."),
            "header_size": "p",
            "align": "left",
            "title_color": "rgba(255,255,255,0.75)",
            "typography_typography": "custom",
            "typography_font_family": "Inter",
            "typography_font_size": sz(16),
            "typography_font_size_mobile": sz(15),
            "typography_font_weight": "400",
            "typography_line_height": sz(1.6, "em"),
            "_margin": dim(4, 0, 0, 0),
            "_css_classes": "hd-prog-desc",
        }),
        widget("button", {
            "text": "Suscribirse en YouTube",
            "link": {
                "url": "https://www.youtube.com/@HablaDeportesRadio?sub_confirmation=1",
                "is_external": "on",
                "nofollow": "",
                "custom_attributes": "",
            },
            "size": "md",
            "align": "left",
            "selected_icon": {"value": "fab fa-youtube", "library": "fa-brands"},
            "icon_align": "left",
            "icon_indent": sz(8),
            "typography_typography": "custom",
            "typography_font_family": "Inter",
            "typography_font_size": sz(15),
            "typography_font_weight": "600",
            "button_text_color": "#FFFFFF",
            "background_background": "classic",
            "background_color": ROJO,
            "hover_color": "#FFFFFF",
            "button_background_hover_background": "classic",
            "button_background_hover_color": "#B00000",
            "button_hover_transition_duration": sz(0.2, "s"),
            "border_radius": dim(999, 999, 999, 999),
            "text_padding": dim(12, 24, 12, 24),
            "__globals__": {"background_color": "globals/colors?id=hdrojov"},
            "_margin": dim(16, 0, 0, 0),
            "_css_classes": "hd-prog-cta",
        }),
    ],
)

# --------------------------------------------------------------------------
# 2. PRESENTADOR
# --------------------------------------------------------------------------
presentador = cont(
    {
        "container_type": "flex",
        "content_width": "full",
        "flex_direction": "row",
        "flex_align_items": "center",
        "flex_gap": gap(14),
        "padding": dim(10, 24, 10, 10),
        "background_background": "classic",
        "background_color": "rgba(41,77,110,0.08)",
        "border_radius": dim(999, 999, 999, 999),
        "css_classes": "hd-prog-host",
    },
    [
        widget("icon", {
            "selected_icon": {"value": "fas fa-microphone", "library": "fa-solid"},
            "view": "stacked",
            "shape": "circle",
            "size": sz(22),
            "primary_color": NAVY,
            "secondary_color": "#FFFFFF",
            "icon_padding": sz(21),
            "align": "left",
            "__globals__": {"primary_color": "globals/colors?id=accent"},
            "_element_width": "initial",
            "_element_custom_width": sz(64),
            "_css_classes": "hd-prog-host-avatar",
        }),
        cont(
            {
                "container_type": "flex",
                "content_width": "full",
                "flex_direction": "column",
                "flex_gap": gap(0, 2),
                "padding": dim(0, 0, 0, 0),
                "_flex_size": "grow",
            },
            [
                widget("heading", {
                    "title": "Presenta",
                    "header_size": "p",
                    "align": "left",
                    "title_color": TXTAT,
                    "typography_typography": "custom",
                    "typography_font_family": "Inter",
                    "typography_font_size": sz(13),
                    "typography_font_weight": "600",
                    "typography_text_transform": "uppercase",
                    "typography_letter_spacing": sz(0.06, "em"),
                    "typography_line_height": sz(1.2, "em"),
                    "__globals__": {"title_color": "globals/colors?id=hdtxtat"},
                }),
                widget("heading", {
                    "title": "Juan Salvador Bárcena",
                    "header_size": "p",
                    "align": "left",
                    "title_color": NAVY,
                    "typography_typography": "custom",
                    "typography_font_family": "Archivo",
                    "typography_font_size": sz(18),
                    "typography_font_weight": "700",
                    "typography_line_height": sz(1.25, "em"),
                    "__globals__": {"title_color": "globals/colors?id=accent"},
                }),
            ],
        ),
    ],
)

# --------------------------------------------------------------------------
# 3. ULTIMAS EMISIONES
# --------------------------------------------------------------------------
emisiones = cont(
    {
        "container_type": "flex",
        "content_width": "full",
        "flex_direction": "column",
        "flex_gap": gap(0, 20),
        "padding": dim(0, 0, 0, 0),
        "css_classes": "hd-prog-emisiones",
    },
    [
        widget("heading", {
            "title": "Últimas emisiones",
            "header_size": "h2",
            "align": "left",
            "title_color": NAVY,
            "typography_typography": "custom",
            "typography_font_family": "Archivo",
            "typography_font_size": sz(28),
            "typography_font_size_mobile": sz(24),
            "typography_font_weight": "700",
            "typography_line_height": sz(1.2, "em"),
            "typography_letter_spacing": sz(-0.4),
            "__globals__": {"title_color": "globals/colors?id=accent"},
        }),
        widget("loop-grid", {
            "_skin": "post",
            "template_id": 146,
            "columns": "4",
            "columns_tablet": "2",
            "columns_mobile": "1",
            "posts_per_page": 8,
            "pagination_type": "",
            "row_gap": sz(24),
            "column_gap": sz(20),
            "post_query_post_type": "video",
            "_css_classes": "hd-prog-grid",
        }),
    ],
)

# --------------------------------------------------------------------------
# 4. SEGMENTOS DEL PROGRAMA
# --------------------------------------------------------------------------
SEGMENTOS = [
    ("10:00", "Arranca la mañana",
     "El repaso de todo lo que pasó en la noche: resultados, la tabla y lo que se viene en la fecha."),
    ("10:40", "Mesa tiburona",
     "Análisis a fondo de Junior de Barranquilla: rendimiento, fichajes y el pulso del vestuario."),
    ("11:20", "La hinchada habla",
     "Las llamadas y mensajes de la afición al aire. El termómetro real de lo que se dice en la calle."),
]

def fila(hora, nombre, desc):
    return cont(
        {
            "container_type": "flex",
            "content_width": "full",
            "flex_direction": "row",
            "flex_align_items": "flex-start",
            "flex_gap": gap(16),
            "padding": dim(18, 0, 18, 0),
            "border_border": "solid",
            "border_width": dim(0, 0, 1, 0),
            "border_color": LINEA,
            "css_classes": "hd-prog-seg",
        },
        [
            widget("heading", {
                "title": hora,
                "header_size": "p",
                "align": "left",
                "title_color": NAVY,
                "typography_typography": "custom",
                "typography_font_family": "Archivo",
                "typography_font_size": sz(16),
                "typography_font_weight": "700",
                "typography_line_height": sz(1.45, "em"),
                "__globals__": {"title_color": "globals/colors?id=accent"},
                "_element_width": "initial",
                "_element_custom_width": sz(72),
                "_css_classes": "hd-prog-seg-hora",
            }),
            cont(
                {
                    "container_type": "flex",
                    "content_width": "full",
                    "flex_direction": "column",
                    "flex_gap": gap(0, 4),
                    "padding": dim(0, 0, 0, 0),
                    "_flex_size": "grow",
                },
                [
                    widget("heading", {
                        "title": nombre,
                        "header_size": "h3",
                        "align": "left",
                        "title_color": TINTA,
                        "typography_typography": "custom",
                        "typography_font_family": "Archivo",
                        "typography_font_size": sz(16),
                        "typography_font_weight": "700",
                        "typography_line_height": sz(1.35, "em"),
                        "__globals__": {"title_color": "globals/colors?id=primary"},
                    }),
                    widget("heading", {
                        "title": desc,
                        "header_size": "p",
                        "align": "left",
                        "title_color": TXTAT,
                        "typography_typography": "custom",
                        "typography_font_family": "Inter",
                        "typography_font_size": sz(14),
                        "typography_font_weight": "400",
                        "typography_line_height": sz(1.6, "em"),
                        "__globals__": {"title_color": "globals/colors?id=hdtxtat"},
                    }),
                ],
            ),
        ],
    )

segmentos = cont(
    {
        "container_type": "flex",
        "content_width": "full",
        "flex_direction": "column",
        "flex_gap": gap(0, 16),
        "padding": dim(0, 0, 0, 0),
        "css_classes": "hd-prog-segmentos",
    },
    [
        widget("heading", {
            "title": "Los segmentos del show",
            "header_size": "h2",
            "align": "left",
            "title_color": NAVY,
            "typography_typography": "custom",
            "typography_font_family": "Archivo",
            "typography_font_size": sz(28),
            "typography_font_size_mobile": sz(24),
            "typography_font_weight": "700",
            "typography_line_height": sz(1.2, "em"),
            "typography_letter_spacing": sz(-0.4),
            "__globals__": {"title_color": "globals/colors?id=accent"},
        }),
        cont(
            {
                "container_type": "flex",
                "content_width": "full",
                "flex_direction": "column",
                "flex_gap": gap(0, 0),
                "padding": dim(6, 24, 6, 24),
                "padding_mobile": dim(4, 16, 4, 16),
                "background_background": "classic",
                "background_color": "#FFFFFF",
                "border_border": "solid",
                "border_width": dim(1, 1, 1, 1),
                "border_color": LINEA,
                "border_radius": dim(16, 16, 16, 16),
                "css_classes": "hd-prog-seg-card",
            },
            [fila(*s) for s in SEGMENTOS],
        ),
    ],
)

# --------------------------------------------------------------------------
# RAIZ
# --------------------------------------------------------------------------
raiz = cont(
    {
        "container_type": "flex",
        "content_width": "boxed",
        "boxed_width": sz(1280),
        "flex_direction": "column",
        "flex_gap": gap(40),
        "flex_gap_mobile": gap(32),
        "padding": dim(32, 48, 72, 48),
        "padding_tablet": dim(28, 24, 56, 24),
        "padding_mobile": dim(24, 16, 48, 16),
        "background_background": "classic",
        "background_color": BGPAG,
        "__globals__": {"background_color": "globals/colors?id=hdbgpag"},
        "css_classes": "hd-prog-page",
    },
    [hero, presentador, emisiones, segmentos],
)

CSS = """/* ===== Programas — El show de la mañana =====
   Solo lo que los controles de Elementor no cubren de forma fiable. */

/* 1. Medida de lectura de la bajada del hero. */
.hd-prog-desc { max-width: 60ch; }

/* 2. El bloque del presentador no se estira a todo el ancho. */
.hd-prog-host { width: auto; max-width: 100%; align-self: flex-start; }

/* 3. La ultima fila de segmentos no lleva linea inferior. */
.hd-prog-seg-card > .hd-prog-seg:last-child { border-bottom: 0 !important; }

/* 4. En movil el segmento apila hora sobre nombre. */
@media (max-width: 767px) {
\t.hd-prog-seg { flex-direction: column; gap: 4px; }
\t.hd-prog-seg-hora { width: auto !important; max-width: none !important; }
}

/* 5. Rejilla intermedia de emisiones: 2 columnas entre 520 y 767 px,
   franja que Elementor no expone como breakpoint propio. */
@media (min-width: 520px) and (max-width: 767px) {
\t.hd-prog-grid .elementor-loop-container { grid-template-columns: repeat(2, 1fr); }
}
"""

doc = {
    "title": "Habla Deportes — Programas",
    "type": "page",
    "version": "0.4",
    "page_settings": {"custom_css": CSS},
    "content": [raiz],
}

out = os.path.join(os.path.dirname(os.path.abspath(__file__)),
                   "elementor-page-programas.json")
with open(out, "w", encoding="utf-8", newline="\n") as f:
    json.dump(doc, f, ensure_ascii=False, indent=2)
    f.write("\n")
print("OK ->", out)
