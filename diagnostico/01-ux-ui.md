# Diagnóstico UX/UI — Habla Deportes

**Sitio auditado:** https://habladeportes.co/
**Fecha:** 2026-07-13
**Auditor:** agente `auditor-ux-ui`
**Método:** inspección del sitio real con navegador in-app (árbol de accesibilidad,
texto renderizado, estilos computados y backend WordPress REST API). Vistas desktop
(1280 px) y móvil (375 px).

> **Nota de método sobre capturas.** El motor de captura de pantalla quedó
> bloqueado de forma consistente en este dominio (timeouts de 30 s, incluso con la
> página ya cargada y sin iframes de anuncios), atribuible a la inyección continua
> de publicidad / comportamiento anti-bot del propio sitio. En consecuencia, la
> evidencia se preservó en formato estructural (DOM, texto, tokens de estilo y
> datos del CMS) en `referencias/observaciones-sitio-actual.md`. Todos los
> hallazgos de este informe están basados en observaciones concretas, no en
> supuestos.

---

## Resumen ejecutivo

1. **El sitio es, en la práctica, un contenedor de una galería de YouTube.** La
   home entera es un carrusel de miniaturas de video (contador "265") con un botón
   "Suscríbete". No hay portada editorial, ni jerarquía de titulares, ni destacado
   del día. Para un medio *video-first* esto suena coherente en intención, pero la
   ejecución delega el 100% de la experiencia en YouTube y desperdicia el sitio
   propio como activo de marca.

2. **No existe navegación ni arquitectura de contenido.** No hay menú principal en
   desktop ni móvil (solo logo, patrocinador, "YouTube" y búsqueda). El único
   "menú" son dos enlaces legales en el footer. No hay secciones por tema
   (Selección, Junior, Fichajes), ni página de Radio, ni Contacto navegable. El
   usuario no puede explorar por interés.

3. **El contenido editorial propio es inexistente / demo.** El backend confirma
   que las únicas "noticias" son las entradas demo del tema (en inglés, de 2021:
   "When Chocolate was Medicine", "Women Balancing Family And Work During COVID-19",
   "¡Hola, mundo!"), y las categorías son las del tema (Branding, SEO, Society…),
   ninguna deportiva. Cualquier flujo de "encontrar noticias por tema" **no se
   puede completar** porque el contenido no existe fuera de YouTube.

4. **La identidad visual es genérica y de baja credibilidad.** Una sola tipografía
   (Rubik), paleta gris-clara plana (fondo #F6FAFC, texto gris #6D727C de contraste
   pobre), y como único color de acento el rojo de YouTube (#E62117). Detalles que
   restan profesionalismo: la palabra **"YOTUBE" mal escrita** en el header,
   "Dirección: Barranquilla" sin dirección real, WooCommerce (Tienda/Carrito)
   abandonado y créditos de la agencia visibles.

5. **La radio —uno de los dos pilares del negocio— no está en el sitio.** No hay
   reproductor, stream ni indicador "en vivo": la radio solo se menciona como texto
   de horario en el footer. La mitad de la propuesta de valor es invisible.

**Diagnóstico global:** el sitio actual no funciona como medio digital; funciona
como una landing que redirige a YouTube. La oportunidad del rediseño es alta: hay
contenido de valor real (los videos y el programa son buenos), pero está atrapado
en una estructura sin arquitectura, sin identidad y sin los activos propios
(noticias, radio) que darían autonomía y credibilidad al medio.

---

## Hallazgos detallados

### 1. Navegación y arquitectura visual

**H1.1 — Ausencia total de menú de navegación**
- **Qué observé:** el `<header>` en desktop y móvil contiene solo: logo, enlace a
  Transelca, enlace "YOTUBE" y un icono de búsqueda. No hay `nav` con secciones. El
  único menú real (footer) tiene únicamente "Política de privacidad" y "Términos".
- **Por qué importa:** el usuario no tiene forma de orientarse ni de explorar. No
  puede llegar a un tema, a la radio o a contacto salvo por el buscador o por scroll
  del carrusel. Se pierde retención, páginas por sesión y descubrimiento.
- **Impacto:** Alto · **Esfuerzo:** Medio
- **Dirección de mejora:** header con navegación clara: Inicio · Videos/Programas ·
  Noticias (por tema: Selección, Junior, Fichajes) · Radio (en vivo) · Contacto.
  Menú hamburguesa accesible en móvil con los mismos destinos.

**H1.2 — La home no tiene estructura de portada**
- **Qué observé:** `main` es un único bloque = galería de YouTube. No hay hero
  destacado, ni bloques diferenciados (último video / noticia principal / radio en
  vivo / próximos partidos).
- **Por qué importa:** no hay jerarquía que guíe la atención ni que comunique "qué
  es esto y qué hago aquí" en los primeros segundos.
- **Impacto:** Alto · **Esfuerzo:** Medio
- **Dirección de mejora:** portada con secciones: hero (video/nota destacada),
  franja de radio en vivo, grid de últimos videos, bloque por temas, patrocinador.

### 2. Jerarquía y foco (video-first)

**H2.1 — Video-first mal ejecutado: todo delegado a YouTube**
- **Qué observé:** la experiencia de video es un carrusel de ~44 miniaturas +
  botón "Suscríbete al canal" (rojo YouTube). El reproductor usa una fachada
  (`epyt-facade`) que abre YouTube. El sitio no aporta contexto propio a cada video.
- **Por qué importa:** ser video-first es correcto para este medio, pero enviar todo
  el peso a YouTube significa que el sitio no construye audiencia propia, no genera
  SEO ni retiene. Los títulos de video sí son de calidad (Selección, Junior,
  fichajes), pero se presentan como thumbnails planos sin curaduría.
- **Impacto:** Alto · **Esfuerzo:** Medio
- **Dirección de mejora:** mantener el video como protagonista, pero con jerarquía:
  un video destacado grande, agrupación por programa/tema, descripción y contexto
  editorial alrededor de cada pieza, reproducción embebida in-situ.

**H2.2 — La radio y el "en vivo" no tienen presencia**
- **Qué observé:** sin `<audio>`, sin reproductor, sin badge "EN VIVO". Solo el
  texto en footer: "El show de las mañanas... 10:00 a.m. a 12:00 m.".
- **Por qué importa:** la radio es uno de los dos pilares del negocio y está
  invisible. En horario de emisión no hay forma de escuchar ni de saber que están al
  aire.
- **Impacto:** Alto · **Esfuerzo:** Medio
- **Dirección de mejora:** franja/reproductor de radio persistente con estado "EN
  VIVO" durante la emisión (L–V 10:00–12:00) y "programación" fuera de horario;
  botón de escuchar siempre visible.

### 3. Diseño visual e identidad

**H3.1 — Identidad de marca débil y genérica**
- **Qué observé:** tipografía única Rubik para todo; paleta plana (fondo #F6FAFC,
  texto #6D727C); único acento el rojo #E62117 (rojo de YouTube). Logo PNG de 2021.
- **Por qué importa:** no hay sistema visual con personalidad deportiva. El sitio se
  ve como una plantilla neutra, no como un medio de deportes con energía. La marca no
  es memorable ni diferenciable.
- **Impacto:** Alto · **Esfuerzo:** Medio (lo aborda `director-arte`)
- **Dirección de mejora:** sistema de identidad propio (color de marca distinto del
  rojo YouTube o usado con intención, jerarquía tipográfica con una fuente
  display/condensada para titulares deportivos, acentos, uso de imagen y contraste).

**H3.2 — Contraste de texto insuficiente**
- **Qué observé:** texto de cuerpo #6D727C sobre fondo #F6FAFC; header a 14px en ese
  mismo gris.
- **Por qué importa:** legibilidad y accesibilidad comprometidas, especialmente en
  móvil y a la luz del día (audiencia deportiva móvil). Roza el mínimo AA.
- **Impacto:** Medio · **Esfuerzo:** Bajo
- **Dirección de mejora:** subir contraste del texto de cuerpo (gris más oscuro) y
  garantizar AA (≥4.5:1) en cuerpo y ≥3:1 en textos grandes.

### 4. Usabilidad de flujos clave

**H4.1 — "Encontrar noticias por tema" es imposible**
- **Qué observé:** no hay categorías deportivas ni contenido editorial; las
  categorías del CMS son demo (Branding, SEO, Society…) y las notas son artículos
  demo en inglés de 2021. Al entrar a una "nota" aparece contenido ajeno al fútbol.
- **Por qué importa:** el flujo simplemente no existe; además genera desconfianza al
  toparse con contenido demo irrelevante indexable.
- **Impacto:** Alto · **Esfuerzo:** Alto (requiere estrategia de contenido —
  `arquitecto-contenido`)
- **Dirección de mejora:** definir taxonomía deportiva real (Selección, Junior, Liga,
  Fichajes) y poblar notas propias; en el prototipo, diseñar la plantilla de
  sección/nota como si el contenido existiera.

**H4.2 — Suscribirse a YouTube: funciona, pero es el único CTA**
- **Qué observé:** botón "Suscríbete al canal" con `sub_confirmation=1` (correcto,
  abre la suscripción directa). Es prácticamente la única acción del sitio.
- **Por qué importa:** el objetivo de conversión está bien resuelto técnicamente,
  pero al ser el único CTA refuerza que el sitio solo empuja a YouTube en lugar de
  ofrecer valor propio.
- **Impacto:** Bajo (ya funciona) · **Esfuerzo:** Bajo
- **Dirección de mejora:** conservar el CTA de suscripción, pero equilibrarlo con
  otras acciones (escuchar radio, ver nota, seguir tema).

**H4.3 — Contacto mínimo y sin flujo**
- **Qué observé:** solo un correo (info@habladeportes.co) y "Dirección: Barranquilla"
  (sin dirección) en el footer. Sin formulario, sin redes sociales enlazadas visibles,
  sin WhatsApp.
- **Por qué importa:** dificulta contacto comercial (sponsors) y de audiencia; reduce
  señales de credibilidad.
- **Impacto:** Medio · **Esfuerzo:** Bajo
- **Dirección de mejora:** sección de contacto con formulario, redes, correo y datos
  del programa; CTA específico para "Pauta con nosotros" (sponsors).

**H4.4 — WooCommerce/Tienda abandonado**
- **Qué observé:** existen páginas Tienda, Carrito, Mi cuenta, Finalizar compra
  (WooCommerce activo), sin productos ni propósito visible.
- **Por qué importa:** superficie muerta que puede indexarse, generar rutas rotas y
  restar profesionalismo.
- **Impacto:** Bajo · **Esfuerzo:** Bajo
- **Dirección de mejora:** eliminar o justificar (merch oficial) en el rediseño; no
  arrastrar el scaffold del tema.

### 5. Experiencia móvil

**H5.1 — Sin navegación en móvil**
- **Qué observé:** en 375 px el header no ofrece menú hamburguesa; solo logo,
  Transelca, "Yotube" y búsqueda. No hay overflow horizontal (bien).
- **Por qué importa:** la audiencia de un medio deportivo es mayoritariamente móvil;
  quedarse sin navegación en el dispositivo principal es crítico.
- **Impacto:** Alto · **Esfuerzo:** Medio
- **Dirección de mejora:** menú móvil accesible (hamburguesa) con todas las secciones
  y la radio.

**H5.2 — Objetivos táctiles por debajo del mínimo**
- **Qué observé:** botón "Suscríbete" mide 163×38 px (alto 38 px < 44 px
  recomendado).
- **Por qué importa:** toques imprecisos, sobre todo en botones de carrusel
  (Anterior/Siguiente) y controles pequeños.
- **Impacto:** Medio · **Esfuerzo:** Bajo
- **Dirección de mejora:** objetivos táctiles ≥44×44 px con espaciado suficiente.

### 6. Presentación de patrocinadores

**H6.1 — Sponsor presente pero sin narrativa de valor**
- **Qué observé:** Transelca aparece como logo enlazado en header y footer. Coexiste
  con anuncios de Google AdSense inyectados en el DOM (incluso sobre contenido).
- **Por qué importa:** el patrocinio de marca (Transelca) es un activo comercial; se
  presenta como logo suelto, sin contexto ("Presentado por…"), y compite visualmente
  con la publicidad programática de AdSense, lo que abarata la percepción del sponsor.
- **Impacto:** Medio · **Esfuerzo:** Medio
- **Dirección de mejora:** espacios de patrocinio diseñados e integrados
  ("Presentado por Transelca", franjas de programa patrocinadas) y separar/limitar el
  AdSense intrusivo para no canibalizar al sponsor principal.

### 7. Confianza y credibilidad

**H7.1 — Errores y señales que restan profesionalismo**
- **Qué observé:** (a) "YOTUBE"/"Yotube" mal escrito en el header; (b) "Dirección:
  Barranquilla" sin dirección; (c) contenido demo en inglés indexable; (d) créditos
  "Realizado por spacerock" en el footer del cliente; (e) publicidad AdSense
  intrusiva.
- **Por qué importa:** cada detalle suma a la percepción de un medio poco cuidado.
  Para un objetivo comercial (ganar sponsors y recomendaciones), la credibilidad es
  determinante.
- **Impacto:** Medio · **Esfuerzo:** Bajo
- **Dirección de mejora:** corregir textos, añadir datos reales (o quitarlos), retirar
  contenido demo, cuidar la densidad publicitaria y presentar señales de confianza
  (equipo, alcance del canal +265 videos, horario de radio, redes).

---

## Recomendaciones priorizadas (quick wins primero)

### Quick wins — Alto impacto / Bajo esfuerzo
1. **Corregir "YOTUBE" → "YouTube"** y demás textos (dirección real o quitarla).
   *(H7.1)*
2. **Subir el contraste del texto de cuerpo** a AA. *(H3.2)*
3. **Eliminar el contenido demo del tema** (notas en inglés 2021) y las categorías
   no deportivas. *(H4.1, H7.1)*
4. **Agrandar objetivos táctiles** (≥44 px) en botones y controles de carrusel.
   *(H5.2)*
5. **Retirar/ocultar el scaffold de WooCommerce** (Tienda/Carrito) si no se usa.
   *(H4.4)*
6. **Reducir la publicidad AdSense intrusiva** para no dañar la experiencia ni al
   sponsor. *(H6.1, H7.1)*

### Impacto alto / esfuerzo medio (núcleo del rediseño)
7. **Añadir navegación real** en desktop y móvil (Inicio · Videos · Noticias por
   tema · Radio · Contacto), con hamburguesa accesible en móvil. *(H1.1, H5.1)*
8. **Rediseñar la home como portada** con jerarquía: hero destacado, radio en vivo,
   últimos videos, bloques por tema, patrocinio. *(H1.2, H2.1)*
9. **Incorporar la radio al sitio** con reproductor y estado "EN VIVO" según horario
   (L–V 10:00–12:00). *(H2.2)*
10. **Construir un sistema de identidad visual propio** (tipografía display para
    titulares, color de marca, acentos, imagen) que se sienta deportivo y moderno.
    *(H3.1)* — para `director-arte`.
11. **Integrar patrocinios con narrativa** ("Presentado por Transelca", franjas
    patrocinadas) separados de AdSense. *(H6.1)*
12. **Diseñar sección/plantilla de Contacto** con formulario, redes y CTA para
    sponsors ("Pauta con nosotros"). *(H4.3)*

### Impacto alto / esfuerzo alto (depende de contenido)
13. **Definir y poblar arquitectura de contenido editorial** (taxonomía deportiva +
    notas propias) para habilitar el flujo "noticias por tema" y generar SEO/
    retención propios. *(H4.1)* — coordinar con `arquitecto-contenido`.

---

## Anexo — Evidencia
Registro estructural (DOM, texto renderizado, tokens de estilo y datos del CMS) en:
`referencias/observaciones-sitio-actual.md`.
