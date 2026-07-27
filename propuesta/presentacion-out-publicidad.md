# Dossier editorial — Out. para Habla Deportes
## Sección nueva: Publicidad y monetización

> **[REEMPLAZADO — julio 2026]** El copy de esta sección se REESCRIBIÓ para ser
> honesto y sin jerga, a pedido del cliente (que no entiende términos como "UTM",
> "SEO por entidad", "CPM", "AdSense/programática", etc.). La versión vigente y
> maquetada vive en `presentacion-out/index.html` (páginas 7 y 8) y en
> `presentacion-out-guion.md` (Páginas 7–8). El contenido de este archivo QUEDÓ
> OBSOLETO: menciona AdSense/programática y "dos fuentes de ingreso", premisas que
> se descartaron por especulativas. La verdad de partida vigente es simple: Habla
> Deportes tiene patrocinadores en sus programas (radio y videos); la web hoy no
> participa de ese patrocinio y puede ampliarlo, ordenarlo y hacerlo mostrable.
> Se conserva solo como registro histórico. NO usar como fuente de copy.

> **Guion de contenido, página por página.** Este documento entrega solo el copy final de la nueva sección, listo para maquetar e insertar en `presentacion-out-guion.md`. No es HTML. El maquetador aplica la identidad de Out. (colores, tipografías, retícula, láminas) según `BRAND.md`, igual que en el resto del dossier.
>
> **Convenciones (idénticas al guion base):**
> - `[PLACA]` = lámina/plate a sangre o media sangre. Marca dónde va imagen editorial grande.
> - `[EXCEPCIÓN CÁLIDA]` = el único elemento cálido de la página (carne/naranja). Una sola excepción por vista.
> - `[PULL-QUOTE]` = cita destacada que se parte en dos líneas; la segunda va en cálido.
> - `[PIE]` = pie de foto pequeño.
> - Todo el copy va en sentence case. Nada de mayúsculas forzadas.

---

## Ubicación recomendada dentro del dossier

**Insertar como sección 03, inmediatamente después de "Antes → Después" (página 6 actual) y antes de "Alcance y fases".**

Razón: el cliente pidió explícitamente esta sección, y la publicidad es la pieza que cierra el argumento comercial — viene mejor *después* de haber mostrado el antes/después (ya convenció visualmente) y *antes* de pedir la decisión de fases (ya convenció comercialmente). Ponerla antes del cierre y del CTA "Hablemos" la deja como el último argumento de negocio antes de pedir la firma.

**Renumeración que esto implica** (no se toca el archivo base en esta entrega; lo indico para quien maquete):

| Página | Antes | Después |
|---|---|---|
| 1–6 | Portada … Antes→Después | Sin cambios |
| 7 | 03 / Alcance y fases | **03 / Publicidad y monetización (nueva, parte 1)** |
| 8 | Cierre | **03 / Publicidad y monetización (nueva, parte 2)** |
| 9 | — | 04 / Alcance y fases *(era 03, pasa a 04; solo cambia el número de sección, el copy no cambia)* |
| 10 | — | Cierre *(era página 8, sin cambios de copy)* |

El dossier pasa de **8 a 10 páginas**. Propongo **2 páginas** para esta sección (no 1): el pedido del cliente tiene cuatro bloques (importancia, cómo, herramientas, SEO) y forzarlo a una sola página lo dejaría en lista de bullets sin espacio para el pull-quote ni la cadena de valor del SEO — rompería el ritmo editorial del resto del dossier, que siempre da una página de planteamiento + una de desarrollo (ver páginas 3–4 y 5–6).

---

## Página 7 — 03 / Publicidad y monetización

**Sección:** 03
**Kicker:** La pauta, en serio

**H1:**
Un sitio mejor no solo se ve mejor: vende mejor pauta.

**Frase-tesis (destacada):**
Para un medio, la publicidad no es un anexo. Es el negocio. Habla Deportes ya factura por pauta — hoy, mal aprovechada.

**Párrafo 1 — el punto de partida:**
Habla Deportes no arranca de cero en esto. Ya tiene dos fuentes de ingreso publicitario: AdSense programático y un patrocinador ancla, hoy Transelca. El problema no es la falta de pauta. Es que ninguna de las dos está bien administrada.

**Párrafo 2 — qué falla hoy:**
El patrocinador vive como un logo repetido en el header y el footer, sin espacio propio ni narrativa — y termina dictando el color de todo el sitio. AdSense, en paralelo, se inserta donde el tema lo permite, a veces encima del contenido, sin una zona definida ni forma de medir cuánta gente lo vio. Dos negocios que se estorban entre sí, y ningún inventario claro que mostrarle a un anunciante nuevo.

**Párrafo 3 — la idea fuerza:**
Un sitio profesional no solo se ve mejor. Vende mejor pauta. La credibilidad visual es, en la práctica, el primer argumento comercial frente a un anunciante: nadie paga bien por aparecer en una plantilla genérica que además comparte protagonismo con un sponsor que no es suyo.

**Lista — lo que cuesta hoy:**
- **Sin espacios definidos** — el sponsor es un logo suelto, no un inventario que se pueda vender por partes.
- **Sin separación** — la publicidad programática y el patrocinio directo se pisan en vez de convivir.
- **Sin medición** — no hay forma de reportarle a un anunciante cuánta gente vio su marca.
- **Sin marca propia** — vender pauta sobre una identidad prestada al sponsor sale más barato de lo que debería.

`[PULL-QUOTE]` (se parte en dos líneas; segunda en cálido):
Hoy la pauta se regala.
Mañana se vende.

`[PLACA]` Placa lateral o inferior con mockup del módulo "presentado por" del prototipo (home o hub de Junior), mostrando el espacio de sponsor separado del bloque de anuncios.

`[PIE]` Fuente: diagnóstico de arquitectura de contenido y auditoría técnica/SEO, julio 2026.

`[EXCEPCIÓN CÁLIDA]` La segunda línea del pull-quote ("se vende.") en cálido. Único elemento cálido de la página.

---

## Página 8 — 03 (continuación) / Las herramientas y el círculo del SEO

**Sección:** 03 (continuación)
**Kicker:** Cómo se administra

**H1:**
Publicidad que el equipo administra solo, sin abrir código.

**Bajada:**
En WordPress, cada uno de estos espacios se convierte en algo que se edita desde el escritorio del sitio — no en algo que hay que llamar a un desarrollador para cambiar.

**Estructura (5 fichas cortas, título + descripción):**

**01 — Gestión de espacios, con plugin.**
Un plugin de anuncios (tipo Advanced Ads o Ad Inserter) define zonas fijas — header, entre párrafos, barra lateral, previo al video — con reglas de rotación e inserción automática. Cambiar, pausar o reemplazar un anuncio es una tarea de minutos, sin tocar una línea de código.

**02 — Módulo de patrocinio nativo.**
El bloque "presentado por" que ya vive en el prototipo (home, hub de Junior, franja de radio) pasa a ser un campo del propio tema: cambiar de patrocinador es subir un logo y un enlace, no editar HTML. Y queda separado del bloque de anuncios — el sponsor no compite con AdSense por el mismo espacio.

**03 — Programática, con margen para crecer.**
Hoy: AdSense, bien ubicado y diferido para no golpear la velocidad de carga. Mañana, cuando el tráfico lo justifique: dar el salto a Google Ad Manager o a una red gestionada (Ezoic, Mediavine) para mejores tarifas, sin cambiar de plataforma ni de tema.

**04 — Patrocinio por franja y contenido de marca.**
Sección patrocinada ("Mundo Junior, presentado por…"), patrocinio de la franja de radio en vivo, del newsletter, o notas y videos marcados como contenido patrocinado. Cada franja del sitio, un espacio de venta adicional y ya montado en la plantilla.

**05 — Medición que se puede reportar.**
Eventos y UTM en cada espacio, con un panel simple de rendimiento por anuncio o por sponsor. Hoy no existe forma de decirle a un anunciante cuánta gente vio su marca. Con esto, sí — y eso es lo que sostiene una renovación de contrato.

**Bloque — la ventaja del SEO (H2 corto):**
El SEO y la publicidad no son temas separados. El SEO es el motor que hace que la pauta valga más.

**Cadena de valor (lista secuencial):**
- SEO por entidad (Junior FC, Selección) → tráfico orgánico cualificado → más impresiones y mejor tarifa por mil.
- Marca creíble y profesional → pauta directa mejor pagada, sin depender solo de lo programático.
- Datos estructurados y velocidad (Core Web Vitals) → mejor experiencia → los anuncios se ven de verdad (viewability) → más ingresos con el mismo tráfico.
- Audiencia segmentable — el hincha de Junior, de la Selección, del fútbol colombiano — como argumento de venta concreto para el anunciante que busca justo ese público.

**Cierre de la sección (frase-tesis destacada):**
Mejor sitio. Más y mejor tráfico. Pauta más valiosa y medible.

`[PIE]` Fuente: recomendaciones de plugins y SEO del diagnóstico técnico (sección 4); manejo de patrocinadores en el benchmark de competencia, julio 2026.

`[EXCEPCIÓN CÁLIDA]` La palabra "solo" en el H1 ("el equipo administra solo") o el número de la ficha 05 ("Medición que se puede reportar"), como único acento cálido de la página — es el argumento que más pesa frente al cliente.

---

## Resumen

Propongo **2 páginas** para la nueva sección "03 / Publicidad y monetización", insertadas después de "Antes → Después" (página 6) y antes de "Alcance y fases" — que pasa a ser la sección 04 y se corre a las páginas 9–10, dejando el dossier en 10 páginas totales.

- **Página 7** plantea el problema: por qué la pauta es el negocio, qué falla hoy (AdSense intrusivo + sponsor sin narrativa ni inventario ni medición) y por qué un sitio profesional vende mejor pauta. Cierra con el pull-quote "Hoy la pauta se regala. Mañana se vende."
- **Página 8** entrega la solución concreta en WordPress —cinco herramientas administrables sin developer (plugin de anuncios, módulo de sponsor nativo, ruta de escalado programático, patrocinio por franja, medición reportable)— y remata con el círculo virtuoso del SEO como motor que hace más valiosa esa pauta, cerrando con la frase-tesis "Mejor sitio. Más y mejor tráfico. Pauta más valiosa y medible."

Archivo escrito en `C:\Users\OscarD\Documents\habla-deportes-proposal\propuesta\presentacion-out-publicidad.md`. No se modificó `presentacion-out-guion.md`; la tabla de renumeración queda documentada arriba para quien haga la fusión y el maquetado final.
