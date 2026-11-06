# Decisiones de la segunda pasada (6 de noviembre de 2026)

> Nacen de las respuestas reales del cliente (`05-respuestas-cliente.md`) y de la aprobación del dueño del proyecto. **Esta página tiene prioridad sobre `01`, `02`, `03` y `04` donde se contradigan.** Lo no mencionado aquí sigue igual que en esos documentos.

## Las cinco decisiones aprobadas

### D1. "En vivo" es el video de YouTube, y la radio baja de peso
- La transmisión del programa en YouTube se ve **dentro de la web** (canal: https://youtube.com/@habladeportesradio).
- **"En vivo"** (página `en-vivo.html`, que reemplaza a `radio.html`) contiene: el reproductor de YouTube incrustado, el estado "Al aire / Próximo programa", la parrilla de lunes a viernes de 10:00 a. m. a 12:00 m., los episodios anteriores (que son videos del canal) y la participación del público.
- En el prototipo **no se incrusta un iframe real** (debe abrir sin red): se usa una **portada del video con botón de play** y un comentario `<!-- iframe youtube-nocookie.com/embed/... -->` que indique dónde irá. Enlace visible al canal.
- **Menú y barra inferior:** "Radio" deja de ser ítem primario. La barra azul queda: **Noticias · Junior FC · Selección · Videos · Datos · En vivo · Más ▾**. La barra inferior móvil: **Noticias · Videos · Datos · En vivo**. "En vivo" lleva un **punto rojo** (único uso del rojo) cuando el programa está al aire.
- **Radio en audio:** no hay confirmación de que exista. Queda como enlace secundario dentro de "En vivo" ("Escuchar solo el audio", marcado como pendiente de confirmar). **Se elimina** el mini-reproductor de audio acoplado y su lógica de sessionStorage. En la portada, el módulo "La escaleta" pasa a ser **"En vivo ahora"**: portada del video de YouTube + estado + próximo programa.

### D2. Más equipos y secciones, con Junior como centro
- Cobertura: Junior (centro), Selección Colombia, **fútbol colombiano (FPC)**, **fútbol europeo**, y **colombianos en el exterior** (clubes donde juegan los colombianos más destacados).
- `noticias.html` es el hub con una **barra de filtros por tema** que se comporta como pestañas: Todas · Junior · Selección · Fútbol colombiano · Europa · Colombianos en el exterior · Mercado · Opinión. Cada filtro es un enlace real (`noticias.html?tema=…`) y el JS filtra las tarjetas del lado del cliente.
- Barra blanca contextual de **Noticias**: los mismos temas. La de **Selección** y **Junior FC** no cambian.
- Etiquetas nuevas: Fútbol colombiano, Europa, Colombianos en el exterior. Las notas de ejemplo de estos temas son **ficticias** y sin nombres reales de jugadores (por rol o por club).
- La portada mantiene a Junior como protagonista; los otros temas aparecen en el carril de noticias por tema, no con un hub por club.

### D3. Transelca sigue como aliado principal; el cliente lista 10 aliados más
- El dueño confirmó que **Transelca continúa** (no está en la lista del cliente: **hay que confirmarlo con el cliente**; se marca como supuesto).
- Rol: **aliado principal**. Conserva el anuncio junto al banner de partidos y el sello "Presentado por" de la sección de datos.
- Los 10 aliados del cliente: **Alcaldía de Barranquilla, Gobernación del Atlántico, Tecnoglass, VP Global, Stake, Vitali, BQ City, Unisimón, Droguería Inglesa, Expreso Brasilia.**
- Nuevo módulo **"Aliados"**: muro de logos (una fila rotulada "Nuestros aliados", logos en gris que recuperan el color al pasar el mouse, todos del mismo alto), en la portada (cerca del final) y en el pie. No es publicidad ni lleva rótulo "Publicidad": es un sello institucional.
- **Stake** (marca de apuestas en línea, según entiendo; confirmar): aparece en el muro como cualquier aliado, **nunca** en el banner del encabezado, nunca como el primer elemento, y con la leyenda "Juega con responsabilidad. Solo mayores de 18 años." en el pie del muro. Revisar la normativa colombiana de publicidad de apuestas antes de publicar.
- **GIFs animados de patrocinadores** (cambian más o menos cada mes, no se sabe quién los cambia): regla para los huecos de pauta: peso máximo **250 KB** por pieza, máximo **3 repeticiones o 8 segundos**, sin sonido, con **imagen fija de respaldo** y con `prefers-reduced-motion` se muestra solo el primer cuadro. En producción debe haber un campo simple para reemplazar la pieza (la administración: Kathia y Sergio, sin confirmar).
- Se venden **patrocinios fijos y publicidad (banners)**. Se mantienen los tres huecos fijos (A, B, C) de `04-pauta.md` más el banner de partidos y el muro de aliados.

### D4. Fuente de los datos: por definir
- **No tienen proveedor** de datos. Se elimina toda mención a "Data Factory" y a "Datos en tiempo real por…". El sello de la sección dice **"Datos de ejemplo · fuente por definir"**. Los datos siguen saliendo de `data-ejemplo.json`.
- En la documentación queda como **pendiente comercial**: elegir proveedor (con costo) o carga manual por la redacción. No se investigan proveedores en esta pasada.

### D5. Sin manual de marca por ahora
- El cliente dice tener colores y tipografías definidos, pero **no los tenemos**. Se mantiene el sistema actual (**azul #294D6E** del logo, **Archivo** e **Inter**), marcado como **provisional hasta recibir el manual**. Los tokens ya están centralizados en `tokens.css`, así que cambiarlos es un ajuste de un solo archivo.
- **Logos de los aliados:** se obtienen de internet como **imágenes provisionales** (no oficiales), guardadas en `propuesta/html/nueva/assets/img/aliados/`. Se reemplazan por los archivos oficiales del cliente.

## Cambios derivados

| Área | Cambio |
|---|---|
| Redes | Quedan **YouTube, Instagram, X y Facebook**. Se quitan **TikTok** y el **canal de WhatsApp** de todo el sitio (menú, pie, página de redes, módulos). |
| Prioridad de módulos | Orden de importancia declarado: 1° videos del programa, 2° Shorts, 3° resultados y datos. La portada refleja ese orden. Las noticias escritas siguen presentes pero no dominan. |
| Juan Salvador | Su página incluye **sus videos y su timeline de X** (confirmado). |
| Referencias | ESPN, Diario Olé y Futbolred son referencias del cliente (se evita AS). Futbolred aún no está en el benchmark. Sensación pedida: **moderna y cercana**. |
| Contacto | Se mantiene; los datos de contacto reales siguen pendientes. |

## Pendientes abiertos (para el cliente)
- Confirmar Transelca como patrocinador y su acuerdo.
- Manual de marca (colores y tipografías) y logos oficiales de los 11 aliados.
- Quién cambia las piezas y GIFs, y cada cuánto.
- Si existe radio en audio y cuál es su enlace.
- Frecuencia de actualización de la web.
- Proveedor de datos de fútbol, o carga manual.
