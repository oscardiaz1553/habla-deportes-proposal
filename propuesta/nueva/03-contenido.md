# 03 — Contenido de ejemplo — Habla Deportes (propuesta nueva)

> **TODO ESTE CONTENIDO ES DE EJEMPLO.** Titulares, notas, citas, jugadores, árbitros, columnistas, cifras, resultados y fichajes son ficticios y existen solo para probar jerarquía, ritmo y longitud de textos. Son reales únicamente los equipos, torneos, estadios y escudos, además de Habla Deportes y Juan Salvador Bárcena como talento del medio (de él solo hay texto genérico, sin citas ni datos personales).
> Fecha de referencia: **viernes 6 de noviembre de 2026**. Datos numéricos: ver `data-ejemplo.json` (única fuente de verdad). Si un número de este documento difiere del JSON, manda el JSON.
> Sitemap: aún no existe `02-arquitectura.md` al escribir esto. Se usan las 11 páginas del prototipo anterior con nombres funcionales; si el arquitecto cambia rutas, solo hay que renombrar los encabezados `P1…P11`.

## 0. Voz y reglas de estilo

- Español de Colombia, tono de Barranquilla: cercano, directo, sin muletillas ("en el mundo del fútbol…"), sin chiste fácil. Hincha de Junior con criterio: se celebra y se critica.
- Titulares de 6 a 12 palabras, con un dato o una pregunta concreta. Bajadas de 18 a 30 palabras que añadan información, no que repitan el titular.
- Vocabulario fijo: "Selección" (no "la Tricolor" en etiquetas), "nota", "video", "Short", "programa", "al aire", "en vivo" (rojo solo para esto), "Publicidad".
- Rumor, negociando y cerrado son tres estados distintos y siempre se rotulan.
- Fechas: "6 de noviembre de 2026" en cuerpo; "6 nov" en tarjetas; horas "7:30 p. m." (con "m." para mediodía: "12:00 m.").
- Etiqueta global de ejemplo (para pie de módulos de datos): **«Datos de ejemplo. En producción: Data Factory.»**

## 1. Elementos globales

### 1.1 Menú principal (barra azul) — propuesta de agrupación
Primarios (6): **Noticias · Junior · Selección · Videos · Datos · Radio** · desplegable **Más ▾**: Fichajes, Opinión, Juan Salvador, Redes, Contacto y publicidad.
Móvil (barra inferior): Noticias · Videos · Datos · Radio.

Barra blanca contextual por sección (nombre de sección + enlaces):
- **Noticias:** Últimas · Junior · Selección · Liga BetPlay · Internacional · Opinión
- **Junior:** Resumen · Calendario · Plantilla · Tabla · Fichajes ▾ (panel: Rumores, Negociando, Cerrados)
- **Selección:** Resumen · Convocatoria · Calendario · Análisis · Historia
- **Videos:** Programas · Shorts · Redes · Canal de YouTube
- **Datos:** Partidos · Posiciones · Goleadores · Estadísticas · Torneos ▾ (panel: Liga BetPlay, Copa Colombia, Eliminatorias)
- **Radio:** En vivo · Parrilla · Episodios · Cómo escucharnos · Participa

### 1.2 Cabecera y pie
- Enlace de salto: «Saltar al contenido».
- Buscador: etiqueta «Buscar en Habla Deportes», placeholder «Equipo, jugador o tema».
- Pie, columna 1: «Habla Deportes. Fútbol colombiano desde Barranquilla, con Junior y la Selección en el centro.»
- Pie, columna 2 (Secciones): Noticias, Junior, Selección, Videos, Datos, Radio.
- Pie, columna 3: «El show de las mañanas · Lunes a viernes, 10:00 a. m. a 12:00 m.» + botón «Escuchar en vivo».
- Pie, columna 4: Contacto y publicidad · Política de privacidad · Términos y condiciones.
- Pie, base: «© 2026 Habla Deportes · Barranquilla, Colombia · Sitio de ejemplo para la propuesta de rediseño». Sello: «Programa presentado por ISA Transelca».
- Reproductor de radio discreto (sustituye al botón blanco): etiqueta «Radio en vivo», estados «Al aire · hasta las 12:00 m.» / «Fuera del aire · vuelve lunes a las 10:00 a. m.» (sin interpretar la fecha: usar la regla horaria).

### 1.3 Microcopy transversal
| Situación | Texto |
|---|---|
| Newsletter, título | Todo el fútbol en tu correo |
| Newsletter, bajada | Cada mañana a las 6:00 a. m.: titulares, datos y el tema que abre el programa. |
| Newsletter, campo | Correo electrónico (ejemplo: nombre@correo.com) |
| Newsletter, botón | Recibir el resumen |
| Newsletter, nota | Un correo al día. Te das de baja con un clic. |
| Newsletter, éxito | Listo. Mañana a las 6:00 a. m. te llega el primer resumen. (Demostración: no se envió nada.) |
| Newsletter, error | Revisa el correo: falta el @ o el dominio. Ejemplo: nombre@correo.com |
| Buscador sin resultados | No encontramos nada para «{término}». Prueba con el nombre del equipo o del jugador, o mira lo más leído. |
| Carga de datos | Cargando posiciones… |
| Error de datos | No pudimos cargar los datos. Vuelve a intentarlo en un minuto; los partidos en vivo siguen en la página del partido. |
| Sin datos | Aún no hay datos de esta jornada. |
| 404, título | Esta página no existe o cambió de lugar |
| 404, texto | Vuelve a la portada o busca por equipo, jugador o tema. |
| 404, botones | Ir a la portada · Buscar |
| Enlace copiado | Enlace copiado |
| Compartir | WhatsApp · X · Copiar enlace |
| Publicidad (rótulo) | Publicidad |
| Contenido de marca (rótulo) | Contenido de marca · Presentado por {marca} |

---

## P1. Portada

**Meta:** título «Habla Deportes: fútbol colombiano, Junior y Selección» · descripción «Noticias, datos en vivo, videos, Shorts y radio desde Barranquilla. Junior y la Selección Colombia, todos los días.» · h1 oculto: «Portada de Habla Deportes».

### 1.1 Noticia principal (horizontal: imagen izquierda, texto derecha)
- **Etiqueta:** Video · Junior
- **Titular:** El medio campo decide Junior–Nacional: lo que hay que mirar esta noche
- **Bajada:** Rivas, Castro y la salida de Nacional por dentro: el análisis del programa con pizarra, datos de la Fecha 19 y las dos bajas que cambian el once.
- **Crédito:** Redacción Habla Deportes · 6 nov · 24 min
- **Enlaces:** Ver el análisis · Leer la nota · Ir al partido en vivo
- **Imagen sugerida:** `juansalvador2.png` (recorte superior). **Alt:** «Juan Salvador Bárcena, de camiseta negra y gafas, sentado con la mano en la barbilla frente al micrófono azul de Habla Deportes, con el escudo de Junior en la pared de madera».
- **Insignia de duración:** 24:10

### 1.2 Tres noticias pequeñas (una pieza distinta cada una)
1. **Selección** · Nota · «La lista para Perú y Chile: cinco novedades y una ausencia que se discute» — Imagen `img-04.jpg`. Alt: «Once titular de la Selección Colombia posando en el campo con la camiseta amarilla, con la tribuna llena detrás».
2. **Fichajes** · Nota · «Junior negocia un extremo con pasado europeo: qué está firmado y qué no» — Imagen `Junior-de-Barranquilla-1200x740.jpg`. Alt: «Jugadores de Junior con camiseta rojiblanca levantan un trofeo dorado entre serpentinas azules y rosadas».
3. **Datos** · Nota · «Cinco partidos sin perder: la racha de Junior en cuatro cifras» — Imagen `img-05.jpg`. Alt: «Escudo de Junior sobre una bandera rojiblanca ondeando, en tonos oscuros».

### 1.3 Tira del Centro de partidos (debajo de las tres noticias; rota por partidos; anuncio a la derecha)
- Título oculto: «Centro de partidos»
- Diapositiva 1: «Liga BetPlay · Fecha 20» · **En vivo · 67'** · Junior 2–1 Nacional · «Hoy, 7:30 p. m. · Estadio Metropolitano Roberto Meléndez · Win Sports». Fondo `junior_nacional.png`.
- Diapositiva 2: «Amistoso · Fecha FIFA» · Colombia vs. Perú · «Viernes 13 de noviembre · 7:30 p. m. · Estadio por confirmar · Caracol TV». Fondo `colombia_peru.png`. Escudos: `escudo-colombia.png` y `Logotipo_FPF_2016.png`.
- Diapositiva 3: «Liga BetPlay · Fecha 20» · Tolima vs. América · «Sábado 7 de noviembre · 4:00 p. m. · Estadio Manuel Murillo Toro · Win Sports».
- Controles (aria-label): «Partido anterior», «Partido siguiente», «Ir al partido {n} de 3». Enlace: «Todos los partidos».
- Anuncio (rótulo «Publicidad»): `publicidad.png`. **Alt:** «Anuncio de ISA Transelca: un ingeniero con casco blanco y tableta mira el atardecer junto a una línea de energía en la costa; texto: “Eso que menos ves, es lo que más cuidamos”».

### 1.4 Bloque «Junior» (4 titulares; no repite lo de arriba)
Encabezado: **Junior** · enlace «Todo Junior».
- Crónica · «El día que Junior se coronó bicampeón: la noche en tres escenas» (dato de ejemplo)
- Hinchada · «Barranquilla rojiblanca: el barrio que llega al estadio desde las 3:00 p. m.»
- Plantel · «Los once que más minutos suman en la Liga y por qué importa»
- Calendario · «Tras la fecha FIFA, Junior visita Bucaramanga: el 21 de noviembre»

### 1.5 Bloque «Videos» (programas completos) — carril 1
Encabezado: **El show de las mañanas, en video** · «Ver el canal». 4 tarjetas (título · duración · fecha): ver banco de videos V1, V2, V3, V4.
Bajo el carril: «Más de 265 videos en el canal» · botón **Suscribirse en YouTube**.

### 1.6 Bloque «Shorts» — carril 2 (separado, verticales)
Encabezado: **Shorts** · 5 tarjetas (S1, S2, S3, S5, S8 del banco). Botón «Ver todos los Shorts».

### 1.7 Bloque «Selección Colombia» (4 titulares)
- Convocatoria · «Qué pide el técnico al medio campo para el amistoso con Perú»
- Rival · «Perú, en cinco claves: cómo juega y dónde duele»
- Balance · «La gira de octubre: dos partidos, tres lecciones»
- Historia · «Colombia–Perú: lo que cuenta el historial reciente (cifras de ejemplo)»

### 1.8 Bloque «Mercado de fichajes» (5 movimientos del JSON: m1, m2, m4, m5, m6)
Cada fila: fecha · escudo → escudo · descripción · estado. Estados: **Negociando**, **Cerrado**, **Rumor**. Enlace: «Ver todo el mercado». Pie: «Estados de ejemplo, definidos por la redacción.»

### 1.9 Bloque «Radio» (franja)
- Etiqueta: **Al aire** · Lunes a viernes, 10:00 a. m. a 12:00 m.
- Título: «El show de las mañanas, en la radio y en tu bolsillo»
- Texto: «Escucha en vivo o ponte al día con los episodios de la semana cuando quieras.»
- Botones: «Escuchar en vivo» · «Episodios anteriores»
- Mini parrilla: 10:00 Apertura y titulares · 10:30 Junior en primer plano · 11:00 Selección y mercado · 11:30 El debate de los oyentes.

### 1.10 Bloque «Opinión» (3 columnas: C1, C2, C3; las otras 3 viven en la página de Juan Salvador y en Noticias)
Encabezado: **Opinión** · «Todas las columnas».

### 1.11 Interacción
- **Encuesta:** «¿Quién debe ganar el duelo del medio campo esta noche?» → Junior, por el ritmo de Rivas · Nacional, por la salida con balón · Quien cometa menos errores. Botón «Votar». Pie: «12.844 votos (ejemplo)». Tras votar: «Gracias por votar. Estos son los resultados hasta ahora.»
- **Un día como hoy:** «6 de noviembre» · «Aquí va la efeméride del día, escrita y verificada por la redacción.» (de ejemplo) · botón «Ver el archivo».
- **Canal de WhatsApp:** «Las alertas, directo a tu chat» · «Goles, fichajes confirmados y aviso de que arrancamos al aire. Sin grupos y sin ruido.» · botón «Unirme al canal».

### 1.12 Tres huecos «Publicidad» fijos
A: bajo la tira de partidos (la de Transelca de P1.3 cuenta como este hueco); B: columna lateral (300×250) en Datos y notas; C: cierre antes del pie (970×90). Todos con rótulo «Publicidad» y marco propio.

---

## P2. Centro de datos

- **Meta:** «Centro de datos: posiciones, resultados y goleadores · Habla Deportes» · «Tabla de la Liga BetPlay, resultados por fecha, goleadores y estadísticas del fútbol colombiano.»
- **Eyebrow:** Datos · **H1:** Liga BetPlay, fecha a fecha · **Bajada:** Posiciones, resultados, goleadores y estadísticas. Los marcadores se actualizan solos mientras se juega.
- **Sello de patrocinio:** «Sección de datos presentada por ISA Transelca».
- **Franja de partidos por día:** botones **Ayer · Hoy · Mañana**. Ayer (jueves 5): estado vacío «Ayer no hubo partidos de Liga BetPlay. Mira los resultados de la Fecha 19.» · enlace «Ver resultados de la Fecha 19». Hoy: 1 partido (Junior–Nacional, en vivo 67'). Mañana: Pasto–Pereira 2:00 p. m., Tolima–América 4:00 p. m., Águilas D.–Alianza 6:00 p. m.
- **Filtros:** Torneo: Liga BetPlay · Copa Colombia · Eliminatorias. Temporada: Finalización 2026.
- **Pestañas:** Posiciones · Resultados · Goleadores · Estadísticas.
- **Posiciones:** leyenda «Líder» · «Cuadrangulares (1.º a 8.º)» · «Sin clasificar». Nota bajo tabla: «Tabla hasta la Fecha 19. No incluye el partido en vivo: si Junior gana, sube a 42 puntos.» Columnas: # Equipo PJ G E P GF GC DG Pts Forma. Etiqueta de forma accesible: «Últimos cinco: ganó, ganó, empató, ganó, ganó».
- **Resultados:** selector «Fecha» → Fecha 19 · Fecha 18 · Fecha 17 · Fecha 20 (próxima).
- **Goleadores:** «Goleadores» y «Asistencias», cada uno con barra; pie «Sin el partido en vivo».
- **Estadísticas:** «2,34 goles por partido» · «Más goles a favor» · «Menos goles en contra» · «Más tiros de esquina».
- **Lateral:** panel «Racha de Junior»: «Cinco partidos sin perder» (G, G, E, G, G) · enlace «Ver Junior». Panel «Próximos partidos».
- **Fuente:** «Datos de ejemplo. En producción: Data Factory. Actualizado hace 1 min.»
- **Alts:** escudos decorativos con `alt=""` y nombre del equipo en texto. Equipos sin escudo: siglas en círculo (CAL, PAS…) con `aria-hidden`.

## P3. Partido en vivo

- **Meta:** «Junior 2–1 Nacional, en vivo · Liga BetPlay» · «Minuto a minuto, alineaciones y estadísticas del Junior–Nacional de la Fecha 20.»
- **H1 (oculto):** Junior 2–1 Nacional, Fecha 20 de la Liga BetPlay, en vivo.
- **Cabecera:** En vivo · 67' · «Liga BetPlay · Fecha 20» · Local: Junior · Visitante: Nacional · «Estadio Metropolitano Roberto Meléndez · Hoy, 7:30 p. m. · Árbitro por confirmar (ejemplo)». Fondo `junior_nacional.png` con alt vacío (decorativo).
- **Pestañas:** Resumen · Cronología · Alineaciones · Estadísticas · Cara a cara.
- **Resumen (minuto a minuto):** 67' «Junior retrocede unos metros y Nacional busca por las bandas.» · 65' «Gran atajada de Ibáñez a remate de Salas desde el borde del área.» · 64' «Amarilla para Londoño: frena un contragolpe con falta táctica.» · 61' «Cambio en Junior: sale Arrieta, entra Díaz para refrescar la banda.» · 58' «Gol de Junior. Pertuz define de zurda tras la jugada de Rivas por el centro. 2–1.» · 46' «Arranca el segundo tiempo con cambio en Nacional.» · 45' «Final del primer tiempo: 1–1.» · 34' «Gol de Nacional: cabezazo de Mosquera al segundo palo.» · 12' «Gol de Junior: Pertuz, tras pared con Rivas.»
- **Cronología, Alineaciones, Estadísticas, H2H:** exactamente los datos de `partido_en_vivo` en el JSON. Pie: «Datos de ejemplo, incluidos jugadores y árbitro.»
- **Caja lateral «Míralo y escúchalo con Habla Deportes»:** «Narración y comentarios en directo, con tus mensajes al aire.» Botones «Ver en YouTube» · «Ir a la radio».
- **Datos del partido:** Asistencia 41.250 (ejemplo) · Clima despejado, 29 °C (ejemplo) · Torneo Liga BetPlay.
- **Microcopy de estado:** antes «El partido empieza a las 7:30 p. m.» · descanso «Descanso» · final «Final del partido» · error «No pudimos actualizar el marcador. Reintentando…».

## P4. Junior FC

- **Meta:** «Junior de Barranquilla: partidos, plantilla, tabla y noticias · Habla Deportes».
- **Eyebrow:** Equipo · **H1:** Junior · **Bajada:** El Tiburón de Barranquilla: partidos, plantilla, mercado y todo lo que el programa dice sobre el equipo rojiblanco.
- **Escudo alt:** «Escudo de Junior de Barranquilla».
- **Ficha:** Fundación: 7 de agosto de 1924 · Estadio: Metropolitano Roberto Meléndez · Apodo: El Tiburón · Colores: rojo y blanco.
- **Partidos:** tarjeta «En vivo» (Junior 2–1 Nacional, 67') con botón «Seguir el partido» · «Últimos 5 resultados» (F15 a F19, ver JSON) · «Próximos partidos» (F21 a F24 y fecha de reanudación tras la fecha FIFA).
- **Plantilla:** título «Plantilla» · grupos Porteros, Defensas, Mediocampistas, Delanteros. Nota: «Nombres y dorsales de ejemplo. En producción: plantilla oficial vía Data Factory.»
- **Tabla:** «Así va en la Liga BetPlay» (primeros 6 + Junior resaltado) · «Ver tabla completa».
- **Fichajes de Junior (JSON m1, m2, m4, m7):** título «Mercado de Junior».
- **Noticias (4–6):** «El medio campo de Junior, por qué Rivas y Castro sostienen el liderato» (A1) · «Barranquilla rojiblanca…» · «Crónica del bicampeonato» · «Bicampeón: los números de un equipo que no se rinde» (dato de ejemplo) · «Cómo se le juega a Nacional» · «El Metropolitano antes del clásico».
- **Video destacado:** V2 «El medio campo de Junior, pizarra en mano» (24:10).
- **Alts:** `bicampeón….webp`: «Dos jugadores de Junior de espaldas, uno con el número 77, se abrazan sobre el césped bajo humo rojo de la tribuna». `img-03.jpg`: «Jugadores de Junior celebran con el trofeo y una bandera, rodeados de serpentinas».

## P5. Selección Colombia (sección)

- **Meta:** «Selección Colombia: convocatorias, rivales y análisis · Habla Deportes».
- **Eyebrow:** Sección · **H1:** Selección Colombia · **Bajada:** Convocatorias, rivales y análisis de la Selección, con datos y la mirada del programa.
- **Chips:** Todas · Convocatoria · Rival · Análisis · Historia · Opinión.
- **Destacada:** «La lista para Perú y Chile: cinco novedades y una ausencia que se discute» (artículo A2) · bajada «El técnico llamó a 26 jugadores para la fecha FIFA de noviembre. La ausencia más comentada abre el debate en el medio campo.» · 7 min de lectura.
- **Dos secundarias:** «El medio campo que pide el técnico: ¿creatividad o equilibrio?» · «Perú, en cinco claves».
- **Lo último (6):** «Balance de la gira de octubre: tres lecciones» · «Quién juega de lateral izquierdo: tres opciones y una duda» · «El once que pediría la hinchada, según tus mensajes» · «Qué se espera de la sede del amistoso (por confirmar)» · «Los jóvenes que el cuerpo técnico sigue de cerca» · «Colombia–Perú: el historial en cifras de ejemplo».
- **Calendario Selección (lateral):** Vie 13 nov, 7:30 p. m., Colombia–Perú · Mar 17 nov, 6:00 p. m., Chile–Colombia. «Rivales y sedes de ejemplo.»
- **Cuenta regresiva:** «Faltan {d} días, {h} h y {m} min para Colombia–Perú» (ejemplo, calculado desde 2026-11-13 19:30 -05:00).
- **Lo más leído (4):** A2 · «Cinco partidos sin perder: la racha de Junior» · «Junior negocia un extremo…» · A1.
- **Alts:** `img-02.jpg`: «Retrato del técnico de la Selección: hombre de cabello castaño con chaqueta oscura y camisa de cuadros, con el estadio desenfocado al fondo». `seleccion-derrota-gira.webp`: «Seis jugadores de la Selección con camiseta amarilla, abatidos sobre el césped: algunos tendidos, otros de rodillas». `img-11.jpg`: «Jugadores de la Selección con camiseta amarilla celebran un gol con los brazos en alto ante una tribuna llena de hinchas de amarillo».

## P6. Artículo (plantilla)
Ver cuerpo completo en el Banco 2 (A1 Junior y A2 Selección). Elementos de plantilla:
- Migas: Inicio › Junior › {titular corto}.
- Eyebrow: sección · tema. Metadatos: autor · fecha · «{n} min de lectura». Etiqueta de ejemplo: «Contenido de ejemplo para la propuesta».
- Compartir: WhatsApp · X · Copiar enlace.
- Pie de foto: «{descripción}. Foto: Habla Deportes.» (o «Foto de archivo»).
- Bloque «Temas relacionados» (chips): Junior · Liga BetPlay · Selección · Fichajes · Análisis.
- «Sigue leyendo»: 3 notas. Comentarios: «Comentarios ({n})» · campo «Tu comentario» · «Tu nombre» · botón «Publicar comentario» · éxito «Gracias. Tu comentario se publica después de la revisión.» · error «Escribe un comentario antes de publicarlo.»
- Lateral: «Lo más leído», mini tabla, encuesta, publicidad.

## P7. Video (ficha)

- **Meta:** «Programa completo: la previa de Junior–Nacional y la lista de la Selección · Habla Deportes».
- **Eyebrow:** El show de las mañanas · **H1:** Programa completo: la previa de Junior–Nacional y la lista de la Selección
- **Metadatos:** 6 de noviembre de 2026 · 1 h 51 min · «Grabado en directo».
- **Descripción:** «En el programa de hoy: el duelo del medio campo entre Junior y Nacional, la lista de la Selección para Perú y Chile y el mercado de fichajes. Sale al aire de lunes a viernes, de 10:00 a. m. a 12:00 m., por YouTube y la radio.» + «Mostrar más: Incluye el debate de los oyentes y la encuesta del día. Los datos son de ejemplo.»
- **Capítulos:** 00:00 Apertura y titulares · 14:10 Junior–Nacional: el medio campo · 41:30 La lista de la Selección · 1:07:20 Mercado: qué es rumor y qué es firma · 1:30:00 El debate de los oyentes.
- **Botones:** «Suscribirse al canal» · «Compartir» · «Enviar por WhatsApp».
- **Siguientes videos:** V2–V7 (sin repetir el actual). **Más de este tema (Shorts):** S1, S4, S6, S9.
- **Transcripción resumida (ejemplo):** «00:00 Arrancamos con la noche de Junior y Nacional. / 14:10 La clave está entre el círculo central y el área rival. / 1:07:20 Separamos lo firmado de lo que es rumor.»
- **Alt del reproductor:** `juansalvador1.png`: «Juan Salvador Bárcena se cubre la boca con ambas manos frente al micrófono azul, en el estudio con repisas de recuerdos de fútbol y el escudo de Junior».

## P8. Videos y redes

- **Meta:** «Videos y redes · Habla Deportes» · «Programas, Shorts, Instagram, X, TikTok y el canal de WhatsApp en un solo lugar.»
- **H1:** Videos y redes · **Bajada:** El programa sigue cuando se apaga el micrófono: en YouTube, en corto, en Instagram, en X y en tu WhatsApp.
- **Navegación interna:** YouTube · Shorts · Instagram · X · TikTok · WhatsApp.
- **YouTube:** «El show de las mañanas, en video» · botón «Suscribirse» · «Más de 265 videos» (real) · listas: Programa completo, Junior, Selección, Reacciones, Análisis táctico (los conteos por lista se omiten: «cifra por definir»). Filtros: Todos · Programa · Junior · Selección · Reacciones · Análisis.
- **Shorts:** carril vertical separado. Nota: «Los Shorts se cargan solos desde el canal: la redacción sube el vertical y aparece aquí.»
- **Instagram:** «@habladeportes · Reels, historias y fotos del programa». Historias destacadas: Final · Selección · Detrás de cámaras · Hinchada · Mercado · Programa · Datos.
- **X:** dos perfiles: «Habla Deportes (@habladeportes)» y «Juan Salvador Bárcena (@juansalvador)» con enlace «Ver su perfil». Publicaciones de ejemplo, claramente rotuladas «Publicación de ejemplo» y sin atribuir frases a personas reales (textos en Banco 7).
- **TikTok:** «Fútbol en 30 segundos».
- **WhatsApp:** «Las noticias, directo a tu chat» · «Los titulares del día, el aviso de que arrancamos al aire y el resultado al final de cada partido de Junior. Un canal de difusión: nadie ve tu número.» · lista: «Máximo cinco mensajes al día · Tu número queda oculto · Te sales cuando quieras» · botón «Unirme al canal». Vista previa de chat (ejemplo): «Estamos al aire: la previa de Junior–Nacional. Míranos aquí» · «Final del partido: Junior 2–1 Nacional (ejemplo)».
- **Totales:** «Dónde seguirnos» con cifras de ejemplo (ver JSON `redes`).

## P9. Juan Salvador Bárcena

- **Meta:** «Juan Salvador Bárcena · Habla Deportes» · «Presentador y creador de contenido de Habla Deportes: su perfil, sus videos, sus columnas y su presencia en X.»
- **Eyebrow:** Presentador y creador de contenido · **H1:** Juan Salvador Bárcena
- **Bajada (texto genérico):** «Conduce El show de las mañanas, de lunes a viernes, y reacciona a los partidos en directo. Su canal más activo es X, donde conversa con la afición a toda hora.»
- **Botones:** «Seguir en X» · «YouTube» · «Instagram».
- **Foto hero:** `juansalvador2.png`. **Alt:** «Juan Salvador Bárcena, de camiseta negra y gafas transparentes, pensativo con la mano en la barbilla ante el micrófono de Habla Deportes».
- **Secciones:** «Su timeline en X» (publicaciones de ejemplo) · «Sus columnas» (C1 y C2 del Banco 6, en rótulo «Columna de ejemplo») · «Sus mejores videos» (V1, V5, V8) · «Reacciones en corto» (S2, S4, S7) · «Pregúntale a Juan» · «El equipo del programa».
- **Pregúntale a Juan:** «Manda tu pregunta y puede que se lea al aire en el programa de la mañana, de lunes a viernes a las 10:00 a. m.» Campos: Tu nombre · Tema (Junior · Selección · Mercado · Otro) · Tu pregunta (máx. 280 caracteres, contador «{n} de 280»). Botón «Enviar pregunta». Éxito: «Recibimos tu pregunta. Si la leemos al aire, te avisamos.» (Demostración: no se envió nada.) Error: «La pregunta no puede estar vacía.»
- **Equipo (ficticio):** Mauricio Reyes · Análisis táctico; Andrea Caballero · Hinchada y cultura; Luis Pérez · Datos; Natalia Ospino · Mercado y negocios; Rafael Mendoza · Historia. Una línea cada uno (Banco 6).
- Sin cifras de seguidores ni citas atribuidas.

## P10. Radio

- **Meta:** «Radio en vivo y episodios · Habla Deportes» · «Escucha El show de las mañanas de lunes a viernes, de 10:00 a. m. a 12:00 m., y ponte al día con los episodios anteriores.»
- **Estado:** «Al aire · L–V 10:00 a. m. a 12:00 m.» / fuera de horario «Fuera del aire. El programa vuelve el lunes a las 10:00 a. m.».
- **H1:** El show de las mañanas · **Bajada:** Escucha en vivo o ponte al día con los episodios de la semana. Donde estés, cuando quieras.
- **Reproductor:** botón «Escuchar en vivo» / «Pausar radio en vivo» · etiqueta «Habla Deportes · En directo» · «Ahora: {bloque}».
- **Ahora y a continuación:** según parrilla (Banco 5).
- **Parrilla semanal:** tabla L a V de 10:00 a 12:00 (Banco 5). Nota: «El bloque resaltado es el que está al aire ahora.»
- **Episodios anteriores:** 8 (Banco 4) con filtros Todos · Junior · Selección · Mercado · Debate. Vacío: «No hay episodios de este tema esta semana. Prueba con otro filtro.»
- **Cómo escucharnos:** YouTube («Míranos en directo y en repetición») · Radio en la web · Spotify («Los episodios como podcast») · Apple Podcasts · WhatsApp («Te avisamos cuando arrancamos»).
- **Participa:** «Tu voz, al aire». Texto: «Escríbenos o envía una nota de voz por WhatsApp al +57 300 000 0000 (número de ejemplo), o usa el formulario.» Campos: Tu nombre y ciudad · Tipo de mensaje (Pregunta · Opinión · Saludo) · Tu mensaje. Botón «Enviar al programa». Éxito: «Recibimos tu mensaje. Gracias por escribir.» (demo)
- **Encuesta del programa:** «¿Qué tema quieres escuchar mañana?» → La Fecha 20 · La lista de la Selección · El mercado de fichajes.
- **Sello:** «Programa presentado por ISA Transelca».

## P11. Contacto y publicidad

- **Meta:** «Contacto y publicidad · Habla Deportes» · «Escríbenos o conoce cómo tu marca puede estar en el programa, en video, en radio y en la web.»
- **Eyebrow:** Habla Deportes · **H1:** Contacto y publicidad · **Bajada:** Escríbenos para cualquier consulta o conversemos sobre cómo tu marca puede acompañar al programa.
- **Formulario «Escríbenos»:** Nombre · Correo electrónico · Asunto (Consulta general · Publicidad y patrocinios · Prensa · Sugerencia para el programa) · Mensaje · botón «Enviar mensaje». Éxito: «Gracias por escribirnos. Respondemos en un máximo de dos días hábiles.» (Demostración: este formulario no envía nada.) Errores: «Escribe tu nombre.» · «Revisa el correo: falta el @.» · «Cuéntanos en qué podemos ayudarte.»
- **Datos:** Correo: info@habladeportes.co · Ciudad: Barranquilla, Colombia · Programa: lunes a viernes, 10:00 a. m. a 12:00 m. · Escuchar la radio.
- **Anuncia con nosotros** (texto): «Una audiencia de fútbol colombiano que nos sigue en video, en radio y en redes. Las cifras de alcance se entregan con el media kit; aquí van como “por definir”.»
  - **Huecos fijos de publicidad (3):** «Bajo la tira de partidos» (formato vertical del anunciante, portada) · «Columna lateral 300×250» (Datos y notas) · «Cierre 970×90» (antes del pie). Descripción de cada uno: «Posición fija, rotulada “Publicidad”, nunca flotante.»
  - **Sello de datos:** «Sección de datos presentada por {marca}.» · exclusivo.
  - **Patrocinio de programa:** «Tu marca junto al show de la mañana: mención al aire, cortinilla y presencia en YouTube y radio.»
  - **Contenido de marca:** «Piezas en video y texto hechas con la redacción, siempre rotuladas.»
  - Botón: «Pedir el media kit» (demo).
- **Política de pauta:** «Todo anuncio lleva el rótulo “Publicidad” y su marco propio. Las posiciones son fijas y predecibles. No publicamos anuncios flotantes sobre el contenido.»
- **Logo Transelca:** el vector oficial está pendiente; usar `publicidad.png` y el texto «ISA Transelca».

---

## Banco 1. Alt de imágenes disponibles (lo que realmente se ve)

| Archivo | Alt sugerido |
|---|---|
| `juansalvador1.png` | Juan Salvador Bárcena se cubre la boca con las manos, con gesto de sorpresa, frente al micrófono azul de Habla Deportes; detrás, repisas con recuerdos de fútbol y el escudo de Junior |
| `juansalvador2.png` | Juan Salvador Bárcena, de camiseta negra y gafas, pensativo con la mano en la barbilla, sentado ante el micrófono de Habla Deportes |
| `img-02.jpg` | Retrato del técnico de la Selección: hombre de cabello castaño y chaqueta oscura, con las luces del estadio desenfocadas |
| `img-03.jpg` | Jugadores de Junior alzan el trofeo y una bandera con un mensaje religioso entre serpentinas azules |
| `img-04.jpg` | Once de la Selección Colombia en fila antes del partido, con camiseta amarilla y la tribuna detrás |
| `img-05.jpg` | Escudo de Junior sobre una bandera rojiblanca, imagen oscura y de textura (decorativa) |
| `img-06.png` | Hombre mayor con gafas de pasta y camisa rosada, sonriente, con micrófono de solapa, sobre fondo blanco |
| `img-07.jpg` | Futbolista de Argentina con el número 10 corre celebrando con los brazos abiertos; detrás, compañeros |
| `img-08.jpg` | Futbolista de Francia con la camiseta número 10 azul, que lleva el nombre Mbappé, señala al cielo |
| `img-09.jpg` | El técnico de la Selección en la zona técnica, de traje oscuro y camisa de cuadros, ante un cartel de la Copa América 2024 |
| `img-10.jpg` | Dos jugadores de Atlético Nacional con camiseta verde y blanca se abrazan sonriendo |
| `img-11.jpg` | Jugadores de la Selección con camiseta amarilla celebran un gol con los brazos en alto, con la tribuna amarilla de fondo |
| `Junior-de-Barranquilla-1200x740.jpg` | Plantel de Junior levanta un trofeo dorado entre serpentinas azules y rosadas |
| `junior-de-barranquilla-es-bicampeon-…webp` | Dos jugadores de Junior de espaldas, con los dorsales 77 y 10, se abrazan sobre el césped bajo humo rojo |
| `seleccion-derrota-gira.webp` | Jugadores de la Selección con camiseta amarilla, abatidos sobre el césped: uno de rodillas, otros tendidos |
| `junior_nacional.png` | Banderas fundidas: franjas rojiblancas y verdes sobre fondo oscuro (decorativa, `alt=""`) |
| `junior_millonarios.png` | Banderas fundidas: franjas rojas y blancas junto a franjas azules y blancas (decorativa, `alt=""`) |
| `colombia_peru.png` | Banderas de Colombia y Perú fundidas en una sola imagen (decorativa, `alt=""`) |
| `publicidad.png` | Anuncio de ISA Transelca: un ingeniero con casco blanco y tableta mira al horizonte frente a un atardecer con líneas eléctricas; texto «Eso que menos ves, es lo que más cuidamos» |
| `efemerides.png` | Planeta Tierra de noche en tonos azules oscuros (decorativa; no ilustra una efeméride concreta, mejor no usarla como foto de una nota) |
| `img-01.png` | Logotipo de «Diario deportes»: **no usar** (es el nombre del prototipo anterior) |
| Escudos `escudo-*.png` | `alt=""` si el nombre del equipo está al lado; si va solo: «Escudo de {equipo}». `escudo-fcf.png` = Federación Colombiana; para Perú usar `Logotipo_FPF_2016.png` |
| `LogoHablaDeportes*.webp/png` | Logo de Habla Deportes |

Aviso de reutilización: hay 11 imágenes de contenido; en una misma página no repetir foto en módulos distintos salvo que no haya alternativa, y variar el encuadre.

---

## Banco 2. Dos artículos completos

### A1. Análisis de Junior
- **Eyebrow:** Junior · Análisis
- **Titular:** Rivas y Castro: el eje que sostiene al líder
- **Bajada:** Junior suma cinco partidos sin perder y lidera la Liga con 39 puntos antes de recibir a Nacional. La explicación está entre el círculo central y el borde del área rival.
- **Autor:** Mauricio Reyes (ejemplo) · **Fecha:** 6 de noviembre de 2026 · **Lectura:** 5 min · **Etiqueta:** Contenido de ejemplo.
- **Imagen:** `img-03.jpg`, pie «Junior celebra con la hinchada. Foto: archivo Habla Deportes.» (o `Junior-de-Barranquilla-1200x740.jpg`).

**Cuerpo**

Cuando un equipo suma cinco partidos sin perder, la mirada se va a los goles. En el caso de Junior conviene mirar un poco antes: a los 20 metros previos al área rival, donde dos jugadores ordenan lo que hace el resto. Con 33 goles a favor y 21 en contra, el líder de la Liga BetPlay presume de equilibrio, y ese equilibrio tiene nombres: Fabián Castro y Carlos Rivas.

**Un volante que ordena, otro que decide**

Castro juega unos metros por delante de los centrales y cumple una función poco vistosa: recuperar y soltar rápido. Es el primer pase de la salida y el que cierra el ataque rival. Rivas, el diez, hace lo contrario: recibe de espaldas, gira y abre el campo. Suma nueve asistencias en 19 partidos, las más de cualquier jugador del torneo (dato de ejemplo).

**Dónde se gana el partido de esta noche**

Nacional sale jugando por dentro, con dos volantes de corte defensivo y un creativo, Echeverri, que baja a recibir. Si Castro y Ospino le cortan el primer pase al creativo, Nacional se queda sin la conexión con Mosquera. Si no lo logran, los centrales de Junior tendrán que defender de frente a un equipo que corre bien hacia el área.

> «Cuando Rivas recibe con la cara al arco, todo el equipo sube diez metros de golpe.» — Un integrante del cuerpo técnico (cita ficticia, de ejemplo)

**El otro lado: lo que todavía duele**

No todo es cuenta a favor. Junior perdió cuatro partidos en 19 fechas y en tres de ellos el rival encontró la espalda de los laterales cuando el equipo estaba ya adelantado. Zuleta y Vergara suben mucho; si el medio campo no ajusta, ahí está el hueco.

**Qué mirar en el partido**

Tres señales: cuántos pases completa Castro en campo propio, si Rivas recibe entre líneas y cuánto espacio dejan los laterales en transición. Si las tres salen bien, la ventaja de ser líder pasa de ser un número a ser una forma de jugar.

**Después de esta noche**

Con una victoria, Junior llegaría a 42 puntos y a 20 partidos jugados, y quedaría siete puntos por encima del tercero. Después viene la fecha FIFA y la Liga se detiene hasta la visita a Bucaramanga el 21 de noviembre. Ese descanso llega, para unos, como alivio y, para otros, como una pausa en mal momento.

- **Temas relacionados:** Junior · Liga BetPlay · Análisis táctico · Nacional · Fecha 20
- **Sigue leyendo:** «Cinco partidos sin perder: la racha en cuatro cifras» · «Cómo se le juega a Nacional» · «El Metropolitano antes del clásico».
- **Nota final:** «Contenido de ejemplo para la propuesta de rediseño. Nombres, cifras y citas son ilustrativos.»

### A2. Selección
- **Eyebrow:** Selección · Convocatoria
- **Titular:** La lista para Perú y Chile: cinco novedades y una ausencia que se discute
- **Bajada:** El técnico llamó a 26 jugadores para la fecha FIFA de noviembre. El medio campo es la línea con más cambios, y la ausencia del volante de contención más usado abre el debate.
- **Autor:** Redacción Habla Deportes · **Fecha:** 6 de noviembre de 2026 · **Lectura:** 6 min · **Etiqueta:** Contenido de ejemplo.
- **Imagen:** `img-04.jpg`, pie «La Selección, en una foto de archivo. Foto: archivo.»

**Cuerpo**

La lista de la Selección para los amistosos ante Perú y Chile trae cinco novedades y una ausencia que nadie esperaba. Es una convocatoria de transición: un grupo base que se repite y una zona, el medio campo, donde el cuerpo técnico decidió probar.

**Las cinco novedades**

Entran un portero que viene de una buena racha en la Liga, un lateral izquierdo de 21 años y tres volantes: uno de recuperación, uno mixto y un creativo de club costeño. No se trata de nombres ni de apuestas: es el mapa de lo que el técnico quiere ver en el partido del 13 de noviembre.

**La ausencia que se discute**

El volante de contención que jugó casi todos los minutos de la última gira no aparece en la lista. El cuerpo técnico habló de «oxigenar» la línea, pero la hinchada lo leyó como una señal. Esta mañana, en el programa, la mitad de los mensajes pedía que volviera y la otra mitad aplaudía la apuesta por caras nuevas (dato de ejemplo).

> «Queremos ver cómo responden los nuevos cuando el rival aprieta la salida. Esa es la pregunta de estos dos partidos.» — Fuente del cuerpo técnico (cita ficticia, de ejemplo)

**Perú: el rival del viernes 13**

El primer partido es contra Perú, el viernes 13 de noviembre a las 7:30 p. m., con transmisión por Caracol TV. Perú suele presionar alto y jugar largo a los extremos. Para la Selección, eso significa que el trabajo del medio campo en la salida será más importante que la posesión.

**Chile, cuatro días después**

El 17 de noviembre llega el segundo amistoso, contra Chile. Con cuatro días de diferencia entre uno y otro, el técnico tiene la oportunidad de rotar sin perder el hilo del equipo. Esa será la verdadera prueba de la lista: cuántos de los 26 aparecen en el once del martes.

**Lo que viene**

La convocatoria se completa con los jugadores que lleguen de la Liga el lunes 9 de noviembre, después de la Fecha 20. El programa seguirá la lista minuto a minuto en la radio y en YouTube, de 10:00 a. m. a 12:00 m.

- **Temas relacionados:** Selección Colombia · Convocatoria · Perú · Chile · Fecha FIFA
- **Sigue leyendo:** «Perú, en cinco claves» · «Balance de la gira de octubre» · «Quién juega de lateral izquierdo».

---

## Banco 3. Videos de programa (8) — carril de programas

| # | Título | Duración | Fecha | Etiqueta | Descripción (1–2 líneas) |
|---|---|---|---|---|---|
| V1 | Programa completo: la previa de Junior–Nacional y la lista de la Selección | 1:51:20 | 6 nov | Programa | El show del viernes, con la previa de la noche, la convocatoria para Perú y Chile y el mercado. |
| V2 | El medio campo de Junior, pizarra en mano | 24:10 | 5 nov | Junior | Cómo salen Castro y Rivas, dónde pierde Nacional el balón y qué cambia con las dos bajas. |
| V3 | Lista de la Selección: cinco novedades y una ausencia | 18:32 | 4 nov | Selección | Línea por línea, quién entra, quién sale y qué pide el técnico en el medio campo. |
| V4 | Mercado de Junior: quién llega, quién se va y qué es humo | 22:51 | 3 nov | Mercado | El tablero de rumores y negociaciones, separado entre lo firmado y lo que no. |
| V5 | Programa completo del lunes: balance de la Fecha 19 | 1:46:05 | 2 nov | Programa | El repaso de la jornada, el debate de los oyentes y la encuesta de la semana. |
| V6 | El Metropolitano antes del clásico, en primera persona | 15:20 | 1 nov | Hinchada | Recorrido con la hinchada desde la tarde hasta la entrada al estadio. |
| V7 | Análisis táctico de Nacional: cómo se le juega | 31:44 | 31 oct | Análisis | Cinco movimientos de la presión de Nacional y cómo responder a cada uno. |
| V8 | Debate: ¿es Perú el rival ideal para esta fecha FIFA? | 19:05 | 30 oct | Debate | Tres posturas en el estudio y los mensajes de los oyentes. |

Alt de miniaturas: «Miniatura del video: {título}. {descripción de la foto real usada}.»

## Banco 4. Shorts (10) — verticales, duración y etiqueta

| # | Título | Duración |
|---|---|---|
| S1 | El gol de Pertuz, en 30 segundos | 0:30 |
| S2 | Lo que dice la tabla: Junior primero, Nacional a un punto | 0:41 |
| S3 | Rivas y el pase que rompió la línea | 0:22 |
| S4 | Las dos bajas de Nacional, explicadas con una pizarra | 0:55 |
| S5 | Tres datos de la Fecha 19 en menos de un minuto | 0:48 |
| S6 | El Metropolitano a dos horas del partido | 0:27 |
| S7 | ¿Titular o suplente? El debate del extremo | 0:36 |
| S8 | Qué significa «oxigenar» una lista | 0:44 |
| S9 | Así se prepara la hinchada para Colombia–Perú | 0:33 |
| S10 | Pregunta del día: ¿qué lateral izquierdo quieres ver? | 0:19 |

Etiqueta: «Short · {duración}». Cifra de vistas: se omite en el prototipo («cifra por definir»).

## Banco 5. Radio

### Episodios anteriores (8)
| # | Título | Duración | Fecha | Tema |
|---|---|---|---|---|
| E1 | Viernes: la previa de Junior–Nacional | 1:51:20 | vie 6 nov | Junior |
| E2 | Jueves: la lista de la Selección y el mercado | 1:49:40 | jue 5 nov | Selección |
| E3 | Miércoles: quién llega y quién se va del Tiburón | 1:42:15 | mié 4 nov | Mercado |
| E4 | Martes: ¿es Junior favorito esta semana? El debate | 1:51:28 | mar 3 nov | Debate |
| E5 | Lunes: balance de la Fecha 19 | 1:46:05 | lun 2 nov | Junior |
| E6 | Viernes: la gira de octubre, tres lecciones | 1:44:50 | vie 30 oct | Selección |
| E7 | Jueves: el clásico costeño, sin filtros | 1:53:11 | jue 29 oct | Junior |
| E8 | Miércoles: cierre de la ventana y salidas | 1:40:02 | mié 28 oct | Mercado |

Cada fila: botón «Reproducir: {título}» (aria-label), duración, fecha, etiqueta de tema, una línea de descripción.

### Parrilla de lunes a viernes, de 10:00 a 12:00
| Hora | Lunes | Martes | Miércoles | Jueves | Viernes |
|---|---|---|---|---|---|
| 10:00 | Apertura: balance del fin de semana | Apertura y titulares | Apertura y titulares | Apertura y titulares | Apertura: lo que se juega esta noche |
| 10:30 | Junior en primer plano: la Fecha | Junior: análisis y plantel | Junior: el mercado | Junior: el rival y la previa | Junior: la previa del partido |
| 11:00 | Selección: la semana | Selección: rivales | Selección y mercado | Selección: la convocatoria | Selección: lista y cifras |
| 11:30 | Debate de los oyentes | Debate de los oyentes | Debate: el tema de la semana | Debate de los oyentes | Debate de los oyentes y encuesta |

Cada celda de 30 minutos; el último bloque cierra a las 12:00 m. Detalle por bloque: ver `programa.parrilla` en el JSON.

## Banco 6. Opinión (6 columnas)

Etiqueta común: «Columna de ejemplo». Autores ficticios salvo Juan Salvador, de quien solo se muestra el título genérico.

| # | Autor / sección | Titular | Bajada | Lectura |
|---|---|---|---|---|
| C1 | Juan Salvador Bárcena · La voz del programa | Lo que se espera de una final de barrio | Una columna sobre cómo se vive un partido grande en Barranquilla, antes y después del pitazo (texto de ejemplo). | 4 min |
| C2 | Juan Salvador Bárcena · La voz del programa | La Selección y el once ideal | Reflexión sobre por qué todos tenemos una alineación en la cabeza (texto de ejemplo). | 5 min |
| C3 | Mauricio Reyes · Análisis táctico | Equilibrio o creatividad: el dilema del medio campo | Dos formas de armar el centro de la cancha y lo que cada una le cuesta al equipo. | 5 min |
| C4 | Andrea Caballero · Hinchada | Lo que el Metropolitano le debe a su gente | Una mirada a la tribuna: horarios, precios y la experiencia de ir al estadio. | 3 min |
| C5 | Luis Pérez · Datos | Los números que cuentan la Fecha 19 | Cinco cifras de la jornada y lo que dicen, y lo que no, de cada equipo. | 4 min |
| C6 | Natalia Ospino · Mercado | Cómo leer un rumor de fichaje | Una guía para separar fuente, filtración y firma antes de creerse una noticia. | 4 min |

Perfiles de una línea (alt del avatar con iniciales: «Iniciales de {nombre}», decorativo):
- Mauricio Reyes: «Desmenuza cada partido: pizarra, datos y decisiones de los técnicos.»
- Andrea Caballero: «La voz de las tribunas: la afición, el estadio y las historias de barrio.»
- Luis Pérez: «Convierte los números de Data Factory en historias que se entienden.»
- Natalia Ospino: «Sigue el mercado de fichajes: quién negocia, quién firma y qué no se confirma.»
- Rafael Mendoza: «Archivo y memoria: la historia de Junior y la Selección, fecha a fecha.»

## Banco 7. Publicaciones de redes (ejemplo, rotuladas)

Rótulo: «Publicación de ejemplo». Sin citas atribuidas a personas reales. Texto de ejemplo de la cuenta del medio (sin hablar en nombre de Juan Salvador):
- «Estamos al aire hasta las 12:00 m. con la previa de Junior–Nacional, la lista de la Selección y el mercado.»
- «Tarea del fin de semana: manda tu once ideal para el amistoso con Perú. Leemos los mejores en el programa.»
- «Hoy en datos: Junior suma cinco partidos sin perder. El detalle, en habladeportes.co/datos.»
Para el perfil de Juan Salvador, en X, solo se muestran publicaciones neutras y claramente rotuladas «Texto de ejemplo» (por ejemplo: «Hoy al aire, a las 10:00 a. m., con el análisis de la noche. Los esperamos.»).

## Banco 8. Un día como hoy y Encuesta
- **Efeméride (de ejemplo):** «6 de noviembre» · «Espacio para la efeméride del día. En producción la redacción la verifica y la ilustra con una foto de archivo.»
- **Encuesta:** ver P1.11 y `encuesta` en el JSON.

## Banco 9. Mercado (texto de las 8 filas)
Ver `mercado_fichajes.movimientos` en el JSON. Etiquetas: **Rumor** (sin confirmación), **Negociando** (hay conversaciones), **Cerrado** (firmado y comunicado). Ninguna fila nombra a un jugador real.

---

## Qué queda fuera (decidido)
- Cifras de seguidores, visualizaciones y alcance: solo marcadas como ejemplo o «por definir».
- Nombres reales de jugadores, técnicos, árbitros y columnistas, y todo hecho sobre ellos.
- Títulos de liga y nombre del técnico de Junior en la ficha (no se afirman).
- Textos legales (privacidad, términos): pendientes del cliente.
