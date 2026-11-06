# Brief para los agentes de diseño — Habla Deportes, propuesta nueva

> Este documento reúne TODO lo que sabemos hasta hoy (6 de noviembre de 2026). Léelo completo antes de proponer nada. La meta: que los agentes de diseño, con sus skills y su criterio, propongan una versión **nueva, más organizada, más profesional y más apropiada** para este medio. No es un retoque de lo anterior: se puede cambiar lo que haga falta, siempre que se respeten las decisiones firmes del cliente de la sección 4 y se justifique cada desvío.

---

## 1. Quién es el cliente

- **Habla Deportes** (https://habladeportes.co): medio deportivo y plataforma de radio de **Barranquilla, Colombia**.
- **Foco editorial:** fútbol colombiano. **Junior de Barranquilla es el centro** (más que los demás clubes: no es un medio "de todos los equipos por igual") y la **Selección Colombia**. También fichajes y mercado.
- **Formato principal: video-first.** Canal de YouTube con más de 265 videos; Shorts; Instagram muy activo; X, usado sobre todo por el creador **Juan Salvador Bárcena** (cara del programa).
- **Radio:** programa matutino "El show de las mañanas", de lunes a viernes, 10:00 a.m. a 12:00 m.
- **Monetización:** patrocinios. Patrocinador conocido: **Transelca (ISA Transelca)**. Aún no sabemos si venden pauta (banners) o solo patrocinios fijos.
- **Datos de fútbol:** se quiere un centro de datos (posiciones, resultados, goleadores, estadísticas, partido en vivo) alimentado por **Data Factory**, el proveedor de datos del fútbol colombiano. En el prototipo todos los datos son de ejemplo y se marcan así.
- **Stack actual del sitio:** WordPress (hecho por spacerock), con el tema `livecast`. Contenido demo en inglés de 2021, sin menú, sin radio, SEO casi nulo. El diagnóstico completo está en `diagnostico/00-informe-consolidado.md`.

### Equivalencias de nombres (la voz del usuario a veces confunde)
- "Aula Deportes" = Habla Deportes.
- "Diario Deportes" = el nombre del mockup y prototipo anterior (`propuesta/html/`, y la imagen de Figma "Home - Diario Deportes.png"). **Es lo que ya hicimos y no queremos repetir en la portada.**

## 2. Modalidad y objetivo comercial

- Trabajo **por recomendación**: la calidad y el pulido son parte del entregable. Debe verse a nivel producción.
- Esta fase es **solo HTML/CSS/JS navegable** (se abre con doble clic, sin build obligatorio, sin frameworks). **Nada de WordPress por ahora.** Pero el marcado debe ser semántico y portable a WP después.
- **No existe ni se debe proponer nada que dependa de Mac.** El dueño del proyecto no usa Mac. No ejecutes binarios ni scripts de los skills; lee solo sus textos.
- Idioma: español de Colombia, tono cercano pero profesional.

## 3. Qué hay hoy (versión "completa", la que se reemplaza)

- **Código:** `propuesta/completa-src/pages/*.html` (11 páginas, fuente), `propuesta/completa-src/build.py` (generador que pone cabecera, pie y menú), salida en `propuesta/html/completa/`. Estilos en `propuesta/html/completa/assets/css/completa.css` (**1.325 líneas acumuladas en capas v1…v12 con muchos `override`**: es el principal problema de organización) y `assets/js/completa.js`.
- **Publicada (privada):** https://claude.ai/artifact/NCSwmowZFgPGmD748eG8aF
- **Las 11 páginas:** portada, datos, partido en vivo, Junior FC, sección (Selección), artículo, video, videos y redes, Juan Salvador, radio, contacto.
- **Estado de calidad:** pasa axe-core con 0 problemas, sin scroll horizontal, sin imágenes rotas, 11 páginas enlazadas.
- **Imágenes disponibles** (en `propuesta/html/assets/img/`): fotos de Juan Salvador (`juansalvador1.png`, `juansalvador2.png`), 11 imágenes del programa, selección y Junior (`img-02…img-11`), escudos (`escudo-junior|nacional|millonarios|tolima|america|medellin|colombia|fcf.png`), logo (`LogoHablaDeportes.webp` y `LogoHablaDeportes-white.png`), anuncio de Transelca (`publicidad.png`), fondos de partido (`junior_nacional.png`, `junior_millonarios.png`, `colombia_peru.png`) y fotos de Junior bicampeón. Son pocas y se repiten: el diseño debe sobrevivir a eso.
- **Falta:** logo oficial de Transelca en vector (hoy hay un texto "TRANSELCA" provisional), fotos propias del estudio, datos reales.

## 4. Decisiones firmes del cliente / del dueño (NO negociables salvo justificación fuerte)

Cada línea salió de una instrucción explícita. Si un agente quiere desviarse, debe decirlo y justificarlo por escrito.

**Identidad visual**
1. **Una sola familia tipográfica** de titulares + una de texto: **Archivo** (titulares y números) e **Inter** (texto). **Nada de serifas.** (Se rechazó explícitamente una serifada y el exceso de tipografías.)
2. **Pocos colores.** Un único color de marca, el **azul** (base `#294D6E`, extraído del logo). **Rojo solo para "en vivo".** Se rechazó el amarillo y cualquier segundo acento. Se rechazó "demasiado azul" en fondos.
3. **Fondo blanco.** Tiene que ser blanco, no azul claro ni beige.
4. **Encabezado azul con el logo blanco** y **barra de menú azul**: el dueño pidió explícitamente que el topbar y el navbar fueran "azules Habla Deportes" para dar **personalidad**. Pero el resto del sitio sobre blanco.
5. **Imágenes y miniaturas con esquinas rectas.** Sin redondeado, incluidos avatares. Solo los controles (play, flechas, puntos, botones) pueden ser redondos.
6. **No usar el hero cinematográfico** (titular encima de una imagen a pantalla completa): es lo que ya se hizo en Diario Deportes.

**Estructura de la portada**
7. Portada al estilo **Sky Sports**: una noticia/video principal **horizontal** (imagen a la izquierda, texto a la derecha, con etiqueta y enlaces) y debajo **solo tres noticias pequeñas**. Sin muro de tarjetas.
8. **Sin tira de "última hora"**, ni franja amarilla, ni lista lateral de última hora.
9. **Sin tira de marcadores bajo el menú.** La tira de marcadores se **rehace al estilo del Centro de Partidos de Diario Deportes** (banner oscuro con escudos grandes, fecha, estadio y canal, que rota por partidos, con el anuncio del sponsor a la derecha) y va **debajo de las tres noticias**.
10. **Quitar el botón blanco "Al aire · Escuchar" del encabezado** "por el momento": desentona con la unidad visual. Hay que ubicarlo bien más adelante (propuesta de los agentes bienvenida: por ejemplo un reproductor discreto ligado a la sección Radio).
11. Debajo de la portada no debe haber exceso de tarjetas.

**Navegación**
12. **Menú de dos niveles, sin escudos de clubes** (el diario no gira alrededor de todos los clubes). Barra azul de secciones + **barra blanca contextual siempre visible**, como **ESPN**: nombre de la sección y sus enlaces, y algunos ítems abren un panel blanco con sombra (por ejemplo "Torneos ▾"). Los paneles se abren **solo con hover**, no con clic ni con teclado.
13. En móvil: **barra inferior** con Noticias, Videos, Datos y Radio; la barra contextual se desliza de lado.

**Contenido y módulos acordados** (tras un benchmark de 12 diarios)
14. **Datos:** marcadores y datos de Data Factory, con una columna lateral de clasificación + próximos partidos en notas y secciones; página de datos con franja de partidos por día (Ayer / Hoy / Mañana).
15. **Video:** carril de **clips verticales (Shorts)** separado de la cuadrícula de **programas** completos. Nunca mezclar ambos en el mismo carril.
16. **Redes:** YouTube, Shorts, Instagram, X (con perfil propio de Juan Salvador), TikTok, canal de WhatsApp.
17. **Publicidad:** **tres huecos fijos y rotulados "Publicidad"**, nunca flotantes ni intercalados al azar, más un sello de patrocinio sobrio en la sección de datos.
18. Módulos adicionales: radio con parrilla y episodios anteriores, mercado de fichajes, efemérides ("un día como hoy"), encuesta, boletín, opinión.

## 5. Benchmark (qué se aprendió de 12 diarios deportivos)

Fuente: https://claude.ai/artifact/VmbTuTUijvBmceBksq3tCA (capturas del 2-nov-2026 de Marca, Mundo Deportivo, Sport, Olé, La Gazzetta, BBC Sport, Sky Sports, ESPN, Globo Esporte, Win Sports, OneFootball, FotMob).

- Cada diario es dueño de **un color** (barra de navegación y detalles); el resto es blanco.
- **Navegación en dos niveles** (aquí sin escudos).
- **Marcadores como tarjetas** con selector de competición y estado claro.
- **El titular no va sobre la foto** (BBC, Sky, ESPN, Marca).
- **Video como carril** con duración y categoría.
- **Datos en columna lateral**, no solo en página aparte.
- **La publicidad define la calidad percibida**: BBC, ESPN y OneFootball (sobrios) se ven mejor que Marca y Win Sports (saturados).
- No se pudo capturar AS en escritorio, L'Équipe ni The Athletic (bloquean navegadores automáticos).

Archivos locales de apoyo: `diagnostico/*.md`, `propuesta/secciones-benchmark.md`, `propuesta/megamenu-ux.md`, `propuesta/megamenu-contenido.md`, `propuesta/centro-partidos-spec.md`, `propuesta/sistema-de-diseno.md`.

## 6. El cliente YA respondió el cuestionario (6-nov-2026)

**Lee `propuesta/nueva/05-respuestas-cliente.md`: es la fuente más reciente y tiene prioridad sobre las hipótesis de este brief.** Resumen: video-first confirmado (1° videos del programa, 2° Shorts, 3° resultados y datos); la transmisión de YouTube se ve dentro de la web; NO tienen proveedor de datos; quieren noticias también de la Selección, equipos grandes del FPC, Europa y colombianos destacados (Junior sigue siendo el centro); 10 patrocinadores (sin Transelca en su lista) con GIFs que cambian cada mes; venden patrocinios y publicidad; redes: YouTube, Instagram, X y Facebook; sentir moderno y cercano; ya tienen colores y tipografías definidos; referencias ESPN, Diario Olé y Futbolred (no AS).

## 7. Qué se pide a los agentes de diseño

**Objetivo:** una **propuesta nueva** del sitio, más organizada, más profesional y más apropiada para un medio deportivo video-first de Barranquilla centrado en Junior. Usar plenamente su criterio y los skills del proyecto.

**Skills disponibles (léelos directamente; no ejecutes nada):**
- `.agents/skills/design-taste-frontend/SKILL.md` (antidiseño genérico "de IA"; el dueño dijo que lo anterior "se ve demasiado IA")
- `.agents/skills/impeccable/SKILL.md` y sus `reference/*.md` (critique, layout, typeset, polish, audit, new-work, craft-floor). **No corras `scripts/impeccable` ni ningún binario.** Ojo: el skill empuja a "ir con todo, atrevido"; aquí **manda el brief del cliente** (sobrio, blanco, un solo color). Usa critique, layout, typeset, polish y audit; evita bolder/overdrive/delight.
- `.agents/skills/redesign-existing-projects/SKILL.md`, `.agents/skills/high-end-visual-design/SKILL.md`, `.agents/skills/ui-ux-pro-max/SKILL.md`, `.agents/skills/web-design-guidelines/SKILL.md`, `.agents/skills/mobile-native/SKILL.md`, `.agents/skills/prototype/SKILL.md`.
- Los skills de animación (`animate`, `emil-design-eng`, etc.) solo si aportan movimiento sobrio y justificado.

**Criterios de calidad (obligatorios):**
- HTML semántico (`header/nav/main/article/aside/section/footer`), un `h1` por página, orden de encabezados correcto, `alt` descriptivo, landmarks con nombre.
- Contraste AA, objetivos táctiles de **44 px**, foco visible, cero scroll horizontal a 360, 768 y 1440 px, `prefers-reduced-motion`.
- Rendimiento: imágenes con dimensiones y `loading="lazy"` (salvo la primera), nada que bloquee el render.
- **CSS organizado:** tokens (`:root`) → base → componentes → páginas, sin capas de overrides ni `!important` salvo casos justificados. Espaciado en múltiplos de 8 px. Nombres de clase estables y legibles, portables a WordPress.
- Datos de ejemplo siempre **marcados como ejemplo**; ningún hecho real inventado sobre personas reales presentado como cierto.

**Entrega:** en las carpetas que indique cada agente, dentro de `propuesta/nueva/` (documentos) y `propuesta/html/nueva/` (sitio). **No sobrescribas** `propuesta/html/completa/` ni `propuesta/html/index.html`: son las versiones anteriores y se conservan para comparar.

## 8. Pendientes conocidos que la propuesta nueva puede resolver

- Ubicar bien el botón de radio en vivo.
- Menús desplegables en móvil (hoy solo hay hover en escritorio).
- Datos de ejemplo y textos de las notas son ficticios.
- Fotos repetidas (hay pocas).
- Optimizar el peso de las imágenes (~12 MB).
- Logo oficial de Transelca en vector.
