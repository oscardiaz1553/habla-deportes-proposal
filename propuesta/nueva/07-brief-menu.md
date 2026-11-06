# Brief: rediseño del menú y del megamenú

> Encargo del dueño del proyecto (7 de noviembre de 2026). Léelo junto con `00-brief-para-agentes.md` (cliente y criterios) y `06-decisiones-segunda-pasada.md`. Si algo choca, **este documento manda sobre 01–04 en todo lo que toque la navegación**.

## Lo que pidió el dueño, con sus palabras
> "Hay que revisar el menú y el megamenú. **No se entiende.** Revísalo, rediséñalo, consúltalo con los agentes, busca qué es lo mejor en estos casos. Me gustaría un **megamenú con imágenes o noticias destacadas, o algo destacado**, cuando se expanda: le da mucho estilo y funciona bastante bien. **Recuerda que aquí debemos ser muy visuales.**"

## Diagnóstico de quien coordina (con capturas)
Capturas del estado actual en `/tmp/claude-0/-home-user-habla-deportes-proposal/7073dd0e-9b1a-5fbc-a4b3-346201c79ee6/scratchpad/menu/` (`actual-*.png`). Referencias de otros sitios en la misma carpeta (`ref-*.png`, las que se pudieron capturar).

1. **Tres capas apiladas en escritorio** (logo azul, barra azul de secciones, barra blanca contextual): unos 160 px de cabecera y dos jerarquías que compiten.
2. **La barra blanca contextual solo existe para la sección en la que ya estás.** Quien llega no puede saber qué hay dentro de Junior FC, Selección o Videos sin entrar primero: el menú no "vende" el contenido.
3. **Los ítems de la barra azul no despliegan nada.** Solo "Más ▾" lo hace, y dentro hay una lista de texto.
4. El único panel desplegable visible (Torneos) es una **lista de texto sin imágenes**: no transmite que es un medio visual.
5. **Ambigüedad de etiquetas**: "Noticias" (barra azul) y "Noticias" (título de la barra blanca) y "Todas" repiten; "Hoy", "Datos" y "Estadísticas" se solapan; "En vivo" significa programa y partido a la vez.
6. **Título de la barra contextual en cursiva** que no es un enlace ni un botón y confunde.
7. En **móvil** la barra contextual se corta ("Fút…") y no hay un menú completo comprensible; el botón de hamburguesa del encabezado no abrió nada en la prueba.
8. Sin **contenido destacado** en el menú (ni noticia, ni video, ni partido).

## Lo que hay que conseguir
Un sistema de navegación **que se entienda a primera vista**, **muy visual**, con **megamenús** que, al abrirse, muestren **imágenes y contenido destacado** (una noticia, un video, el partido en vivo, el próximo partido…), además de los accesos rápidos a lo que hay en cada sección. Debe verse propio de un medio deportivo video-first de Barranquilla con Junior al centro.

## Restricciones que se mantienen (decisiones firmes del dueño)
- Encabezado azul con logo blanco y barra de menú azul (pidió que fueran "azules Habla Deportes" para darle personalidad). El resto sobre fondo blanco.
- Un solo color de marca (azul `#294D6E`, provisional hasta el manual de marca); **rojo solo para "en vivo"**. Archivo + Inter, sin serifas. Esquinas rectas en imágenes, miniaturas y avatares (solo los controles pueden ser redondos).
- **Los paneles se abren solo con hover** en escritorio (pidió expresamente quitar teclado y clic). Cada ítem que abre un panel debe seguir siendo un **enlace real** a una página con los mismos destinos. En **móvil** no hay hover: ahí se usa un menú completo que se abre con el botón (diseñarlo bien).
- **Sin escudos de clubes en el menú** como atajo a "todos los equipos" (el medio no se enfoca en todos por igual). Sí se pueden usar escudos dentro de un módulo de partido (marcador, próximo partido).
- Los **textos de ejemplo** son de ejemplo; sin personas reales (usa roles); datos de `propuesta/nueva/data-ejemplo.json`; aliados en `aliados.json`.
- Barra inferior móvil: Noticias · Videos · Datos · En vivo (puede reconsiderarse si el rediseño lo justifica, con razones).
- Sin botón blanco "Al aire · Escuchar" en el encabezado; "En vivo" lleva punto rojo cuando el programa está al aire.
- Secciones del sitio (de 02-arquitectura.md y 06): Noticias (con temas Junior, Selección, Fútbol colombiano, Europa, Colombianos en el exterior, Mercado, Opinión), Junior FC, Selección, Videos (Programas, Shorts, redes), Datos, En vivo, y "Más". Las 18 páginas están en `propuesta/nueva-src/sitio.py`.
- **Regla de fotos:** el generador no permite repetir una foto dentro de una página. Las imágenes del megamenú **quedan exentas** (el panel se superpone al contenido y no se ve a la vez), pero deben usar solo fotos del catálogo (`propuesta/nueva-src/imagenes.py`, 26 en WebP) y no pueden ser las que el diseño reserva para otras funciones. Menciona cualquier foto nueva que se necesite.

## Lo que queda abierto (los agentes deciden y justifican)
- Si la **barra blanca contextual se mantiene, se fusiona o se elimina.** Es decisión de diseño; si se elimina o cambia, di qué la reemplaza y por qué.
- Cuántos ítems primarios y con qué etiquetas.
- Qué lleva cada megamenú y qué se destaca en cada uno.
- Cómo se ve y funciona el menú en móvil.
- Retardo de apertura/cierre del hover (para evitar paneles que parpadean o se cierran al mover el mouse en diagonal).

## Criterios de calidad
Contraste AA, objetivos táctiles de 44 px, sin scroll horizontal, `prefers-reduced-motion`, marcado semántico (`nav` con nombre, enlaces reales), un `h1` por página, panel no tapa contenido crítico ni el contador de la cabecera, rendimiento (imágenes del panel con `loading="lazy"` y dimensiones).
