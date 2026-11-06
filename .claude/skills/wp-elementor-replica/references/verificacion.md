# Verificación: el protocolo que dio el «impecable»

Cómo se demuestra que una página, una plantilla o una fase está hecha. El checklist de
auditoría por página está en la skill `wp-qa`
([checklist.md](../../wp-qa/references/checklist.md),
[mediciones.md](../../wp-qa/references/mediciones.md)) y el agente que lo aplica es
`qa-elementor`. Aquí va el método completo y cómo se le cuenta al usuario.

## 1. La regla de oro y las capas

**Mide, no mires.** Un valor «cuadra» cuando lo has medido en el navegador contra el mismo
valor del prototipo; un guardado «funcionó» cuando lo has leído de vuelta en un proceso
nuevo; un formulario «funciona» cuando la prueba en seco lo demuestra. Una captura no prueba
nada: sirve para enseñar, no para verificar.

Cada cambio pasa por estas capas, en este orden:

| Capa | Qué se comprueba | Con qué |
|---|---|---|
| Datos | lo guardado existe y es lo esperado | API o meta leída de vuelta en otro proceso; REST interno |
| HTTP | código, cabeceras, marcadores en el HTML servido, CSS generado | `curl` con parámetro neutro de caché |
| Geometría | medidas contra el prototipo en los anchos acordados | el mismo JS en las dos pestañas |
| Comportamiento | clics, anclas, filtros, hovers, teclado, sin JS | navegador y sondas |
| Editor | que la página abre en Elementor y se puede editar | **el usuario** (Claude no inicia sesión) |
| Revisión adversarial | lo que la medición no ve | workflow de solo lectura con verificador |

## 2. Medir contra el prototipo

Esta sección es la **referencia canónica del kit** para anchos y tolerancias; el resto de
piezas (skills, agentes, plantillas y workflows) se alinean con ella.

### Anchos
| Ancho (viewport) | Cuándo | Por qué |
|---|---|---|
| 1440 × 900 | siempre | escritorio de referencia |
| 1280 × 900 | siempre | el ancho más estrecho con menú de escritorio: con `tablet_extra` = 1279 (el `xl` de Tailwind), a 1279 el menú ya colapsa |
| 768 × 900 | siempre | primer ancho por encima de `mobile` (767): tablet |
| 375 × 812 | siempre | móvil |
| 1100 y 900 | cabecera, pie y bloques que cambian entre tablet y escritorio | menú colapsado a anchos de portátil pequeño y tablet horizontal |
| 1279, 1024 y 767 | cuando la spec cambia justo en un borde de breakpoint | a cada lado del corte, Elementor y Tailwind no coinciden (a 1024, tablet de Elementor frente a `lg`) |

### Tolerancias
- **Objetivo: ≤1 px por sección** (top, ancho y alto) contra el prototipo en los cuatro
  anchos de «siempre».
- **Aceptable: ≤3 px por sección solo en anchos intermedios** (1100, 900 y bordes de
  breakpoint): ahí se acumulan los redondeos de los tamaños fluidos (`clamp()`, `vw`) y los
  cortes de Elementor y del prototipo no caen en el mismo píxel. Toda diferencia mayor tiene
  su causa localizada o está en «conocido y aceptado».
- **Segundo idioma frente al idioma base: ≤1 px**, sin el margen de los intermedios. Su
  árbol es una copia del idioma base ya medido y solo cambia el texto, así que se compara
  con la página del idioma base: mismas secciones, en el mismo orden, con el mismo `x` y
  ancho (≤1 px). Los altos que cambian porque el texto traducido ocupa otras líneas se
  miden contra el prototipo en ese idioma (en el caso, con `<PREFIJO>-lang=en`), también a
  ≤1 px. Una diferencia mayor apunta a la generación del idioma, no a la maquetación.
- **Regresión** de un cambio que debe ser invisible (rendimiento, SEO, accesibilidad):
  **±1 px** entre el antes y el después.
- Sin scroll horizontal en ningún ancho: `document.documentElement.scrollWidth` igual a
  `innerWidth`.
- En el caso de referencia, cada página se corregía hasta ≤1–2 px y la memoria registró el
  cierre como «≤3 px por sección a 1440 y 375» (cabecera y pie también a 1100 y 900); el
  segundo idioma quedó a ≤1 px medido contra el prototipo en ese idioma. Los anchos 1280 y
  768 como fijos y el control frente a la página del idioma base son añadidos del kit.

### Preparación
- Prototipo servido por un único servidor de desarrollo y WordPress en otra pestaña, **al
  mismo tamaño**. Pestaña visible (`document.visibilityState === 'visible'`): en una oculta,
  `requestAnimationFrame` e `IntersectionObserver` se pausan y las entradas no se disparan.
- Idioma del prototipo fijado (en el caso, `localStorage` `<PREFIJO>-lang` = `es` o `en`).
- Entradas animadas forzadas en los dos lados antes de medir: en WordPress,
  `[data-<PREFIJO>-reveal]` y `[data-<PREFIJO>-stagger]` con `transition:none` + clase
  `is-in` (las animaciones de arranque, como `rise`, ya terminaron o se fijan con
  `animation:none; opacity:1`); en el prototipo, sus
  clases equivalentes con `animation:none; opacity:1`. Alternativa: recorrer el scroll en
  pasos de 300 px con esperas de 120–150 ms y volver arriba.
- Nunca recargues dentro del script de medición: corta el valor devuelto.
- Si has emulado móvil en esa pestaña, lo que veas después en escritorio sin recargar no es
  un fallo (SmartMenus queda en modo táctil).

### El script (núcleo)
El mismo en las dos pestañas, con selectores equivalentes (en WordPress por clase
`<PREFIJO>-*` o `data-<PREFIJO>-*`; en el prototipo por estructura, p. ej. `main > section`):

```js
document.querySelectorAll('[data-<PREFIJO>-reveal],[data-<PREFIJO>-stagger]').forEach(e=>{e.style.transition='none';e.classList.add('is-in')});
await new Promise(r=>setTimeout(r,300));
const R=el=>el?(({x,y,width,height})=>[Math.round(x),Math.round(y+scrollY),Math.round(width),Math.round(height)])(el.getBoundingClientRect()):null;
const cs=(e,p)=>e?Object.fromEntries(p.map(k=>[k,getComputedStyle(e)[k]])):null;
const T=['fontFamily','fontSize','lineHeight','fontWeight','letterSpacing','color','textTransform'];
const secs=[...document.querySelector('main').querySelectorAll('section')].filter((s,i,a)=>!a.some(o=>o!==s&&o.contains(s)));
JSON.stringify({doc:document.documentElement.scrollHeight, hs:document.documentElement.scrollWidth>innerWidth,
  secs:secs.map(R), h1:[R(document.querySelector('h1')),cs(document.querySelector('h1'),T)]})
```

Para medir muchas URL a 375 px en una sola llamada, crea por cada una un `iframe` del mismo
origen de 375 × 812 fuera de pantalla, espera ~2,5 s, mide y elimínalo: las media queries
responden al ancho del iframe. Añade `?nocache=<ts>` (nunca `?m=`).

Versión completa y configurable (comparación automática contra el JSON del otro lado, modo
iframes):
[`medir-vs-prototipo.js`](../../elementor4-atomico/scripts/medir-vs-prototipo.js) de la
skill `elementor4-atomico`. Sin navegador compartido (subagentes, QA), Chrome headless con
[`qa-medir.mjs`](../../wp-qa/scripts/qa-medir.mjs) y [`qa-cmp.py`](../../wp-qa/scripts/qa-cmp.py)
de `wp-qa/scripts/` (instalada: `~/.claude/skills/wp-qa/scripts/`; preparación y uso en su
[README](../../wp-qa/scripts/README.md); cómo se lee la salida, en
[mediciones.md](../../wp-qa/references/mediciones.md) M5 y M6).

### Cuando no cuadra
1. Compara sección por sección (top y alto de cada hijo del documento) hasta aislar la que
   difiere. En el caso, 33 px de más en una página venían del pie, no de la página.
2. Busca en la memoria (o en el historial de la sesión) si esa diferencia fue una decisión.
3. Si lo fue, pásala a la lista **«conocido y aceptado»** que reciben los revisores (en el
   caso: breakpoints de Elementor 767/1024/1279 frente a los de Tailwind; a exactamente
   1024 px el tablet de Elementor da una columna y el `lg` del prototipo dos; un enlace
   añadido a propósito; un defecto del prototipo que no se copió).
4. Si no lo fue, replica el valor implícito del prototipo: los defaults de Tailwind
   (interlineado 1,5 del cuerpo, `h1–h4` a 1,15, `space-y-2.5` = 10 px) y los estilos base
   atómicos (`padding:10px`, `width:100%`, `min-width:30px`) explican casi todo.

## 3. Comprobaciones por HTTP

- Siempre con parámetro neutro de caché (`?nocache=$RANDOM`) y, en rutas de administración
  o si el hosting desafía a `curl`, con un User-Agent de navegador (`-A`).
- Lee las cabeceras que importan: `x-proxy-cache` / `x-proxy-cache-info`, `set-cookie`
  (ninguna `pll_language`), `cache-control`, `content-encoding`, `vary`, `retry-after`.
- Busca en el HTML servido lo que debe estar y lo que no:

```bash
curl -s -o /dev/null -w '%{http_code} %{time_total}s\n' "https://<DOMINIO>/<ruta>/?nocache=$RANDOM"
curl -s "https://<DOMINIO>/<ruta>/" | grep -o 'data-elementor-type="[^"]*" data-elementor-id="[0-9]*"'
curl -s "https://<DOMINIO>/<ruta>/" | grep -c '<main'                      # esperado: 1
curl -s -D- -o /dev/null "https://<DOMINIO>/" | grep -iE 'x-proxy-cache|set-cookie'
curl -s "https://<DOMINIO>/wp-content/uploads/elementor/css/global-<id>-frontend-desktop.css" \
  | tr '}' '\n' | grep '<PREFIJO>-<clase>'                                  # la clase llegó al CSS
```

- Rellenos y datos inventados: `grep` de «Lorem», «demo», «pendiente», teléfonos de
  relleno y dominios inventados → 0. Contactos reales → presentes.
- Enlaces a una página retirada del menú: `grep -o 'href="[^"]*<slug>[^"]*"' | sort | uniq -c`
  en cada página.
- 404: con slugs absurdos (`/no-existe-xyz/`), nunca con uno que pueda existir.
- Segundo idioma: escaneo del HTML sin `<script>`/`<style>` buscando palabras con tildes,
  `ñ`, `¿`, `¡` y una lista de palabras frecuentes del idioma base, también en `alt`,
  `aria-label`, `title`, `placeholder`, `data-*` y metas.

## 4. Datos leídos de vuelta y el editor

- Todo script imprime una línea comprobable y se verifica **en un proceso nuevo**: número de
  elementos por sección, clases y variantes, `frontend N · preview N · iguales`, relectura
  de `_elementor_data` tras guardar. Si no aparece la última línea del resumen, el script
  no terminó.
- Por REST interno sin navegador: `wp_set_current_user(1)` + `rest_do_request` de
  `/elementor/v1/global-classes?context=preview` y `?context=frontend` (mismo número y
  orden) y `/global-classes/styles?context=preview&ids=…` sin estilos nulos.
- Detrás de una cortina o sin sesión, el HTML real se pinta desde WP-CLI con un script de
  render (`WP_USE_THEMES`, `wp_set_current_user(1)`, `REQUEST_URI`, `HTTPS=on`, sin
  `redirect_canonical`, `wp()` + `template-loader.php`). Limitación: Polylang no resuelve
  `/<IDIOMA_2>/` como portada desde CLI; esa URL se comprueba en vivo.
- **El editor lo comprueba el usuario.** Antes, valida por datos lo que el editor usará
  (toda clase referenciada existe en el repositorio y en *preview*, no hay IDs duplicados ni
  ausentes, los `template_id` apuntan a plantillas publicadas). Después, pídele: «abre X en
  el editor, cambia Y, publica y avísame». En cuanto confirme, vuelve a comprobar por CLI
  que no se perdió nada (sobre todo las clases en *preview*). Hasta entonces queda como
  pendiente explícito.

## 5. Pruebas en seco

Nada que llegue a un buzón real. Formularios de Elementor Pro:

1. `add_filter('pre_wp_mail', function ($r, $atts) { /* guarda to, subject, headers, message */ return true; }, 1, 2);`
2. Monta el widget desde `_elementor_data` con
   `Plugin::$instance->elements_manager->create_element_instance($form)`, fijando
   `form_post_id` y `edit_post_id`.
3. Por cada caso, `new Form_Record($datos, $form)` y `->validate(new Ajax_Handler())`:
   válido · trampa (honeypot) llena · 0,9 s desde que se abrió · sin JS (sin el campo de
   tiempo) · sin email · email sin TLD.
4. Para el válido, `actions_registrar->get('email')->run($rec, $ah)` e imprime destinatario,
   asunto, `From`, `Reply-To`, `Sender` y cuerpo simulados.
5. Parametriza la página por variable de entorno para probar los dos idiomas.

Para los correos del núcleo, lo mismo con `pre_wp_mail`. La sonda imprime
`válido=true/false` y los errores de cada caso. La única prueba real va al buzón del usuario
y con su permiso (`operacion-segura.md` §3). Detalle:
[formularios.md](../../elementor4-atomico/references/formularios.md).

## 6. Revisión adversarial con workflows

Lo que la medición no ve lo encontró la revisión adversarial: el borrado de clases al
publicar desde el editor, un parámetro de URL tipo array que daba 500, metacampos sin
revisiones, el enlace de salto roto, URL en `http://`, `fetchpriority` duplicado, el hover
invisible de los botones de todo el sitio y un riesgo de error fatal en todo el sitio tras
una actualización de Elementor. Se lanza **en segundo plano** al cerrar cada página o bloque
grande, mientras se avanza con lo siguiente.

**Diseño** (plantilla
[`../plantillas/workflows/revisar-pagina.js`](../plantillas/workflows/revisar-pagina.js)):
- **Buscadores** independientes por lente, 2–3 por revisión, con un máximo de hallazgos
  cada uno (6 en el caso). Lentes que funcionaron: fidelidad visual y de contenido contra
  los datos del prototipo; accesibilidad, validez HTML y JS; editabilidad e integridad de
  los datos de Elementor; código y seguridad. Para el segundo idioma: texto, enlaces y
  regresión. Para SEO: código PHP, frontend, SEO. Para el documento de entrega: hechos,
  rutas del panel, completitud.
- Cada hallazgo: `id`, `severity` (`critical|high|medium|low`), `title`, `where`,
  `evidence`, `suggested_fix`.
- **Un escéptico por hallazgo** que intenta reproducirlo desde cero y lo marca como no real
  si no lo reproduce, si es comportamiento aceptado, si es idéntico al del prototipo (no una
  regresión) o si la evidencia es falsa; **por defecto, no real**. Corrige la severidad si
  está inflada y puede proponer un arreglo mejor.
- Se verifican los más graves (7 en el caso) y el resto se lista como `unverified`, nunca
  como bueno.
- **Contexto que se le pasa:** lo ya medido y la lista «conocido y aceptado» (no se
  reporta), y las reglas de solo lectura: sin navegador, sin escrituras por WP-CLI, sin
  envíos reales (`pre_wp_mail` si simulan), sin leer secretos.
- La revisión siguiente comprueba también la **regresión** de los arreglos de la anterior.

**Después:** procesa el resultado (`id [severidad→corregida] real=… :: título`), aplica
**solo lo confirmado**, informa también de lo descartado y por qué, y añade a la memoria las
trampas nuevas. Si el `.output` del workflow sigue vacío, los hallazgos ya emitidos están en
`~/.claude/projects/<proyecto>/<sesión>/subagents/workflows/<wf_id>/journal.jsonl`
(eventos `started` y `result`). Para escribir el script, la skill `workflow-authoring` si la
sesión la tiene.

La auditoría del prototipo (F0) y las auditorías de hosting (F12) usan el mismo patrón:
agentes por dimensión + verificador. Verifica todo lo que vaya a usarse para decidir, y
también las conclusiones del agente sintetizador (en el caso, su «nota operativa» era una
suposición falsa).

## 7. Informe al usuario

En español llano, con el enlace a la página y en este orden (adáptalo al tamaño del hito):

1. **Qué quedó hecho y con qué fidelidad**: tabla «Ancho | Resultado» («diferencias de 1 px
   o menos a 1440/1280/768/375; 3 px a 900») y qué comportamientos se probaron y cómo
   (medidas, simulación sin enviar nada).
2. **Cómo queda editable**: qué es elemento de Elementor, dónde vive el diseño y los trucos
   de edición («para poner palabras en color de acento basta con ponerlas en negrita»;
   «el alt se edita en la Biblioteca de medios»).
3. **Defectos del prototipo que no se replicaron** (botones que no hacían nada, contenido
   invisible sin JS, PNG de 2,3 MB convertido a WebP de 163 KB).
4. **Decisiones propias**, marcadas como tales («Una decisión mía: …»).
5. **Correcciones a lo dicho antes** («Dos correcciones a lo que te dije antes»). Los
   errores propios se cuentan sin rodeos, incluido lo que se ejecutó sin leerse.
6. **Lo que se dejó igual que el prototipo y conviene consultar con el cliente**
   (contrastes, claims, enlaces genéricos, semántica perdida).
7. **Lo que se evitó**, si una revisión destapó algo grave, en consecuencias para el cliente
   y no en jerga.
8. **Pendiente de tu lado** (abrir en el editor, prueba real a tu buzón, DNS) y **siguiente
   paso propuesto**; o «No necesito nada tuyo para seguir». Si algo no se desplegó: «No
   desplegué todavía, dime cuándo». Si algo no se pudo verificar, dilo y por qué.

Además: capturas enviadas con SendUserFile al cerrar cada bloque (hero y página completa,
tomadas con la pestaña visible); los valores que el usuario debe copiar en otro panel (DNS)
van en un archivo; titulares cortos en negrita por tema («X, hecho.», «Y, pendiente
(menor).»); tablas cortas y decisiones numeradas. Nunca secretos en el informe.

## 8. Rendimiento y después de migrar

- Rendimiento y SEO: Lighthouse de la misma versión que PSI, 3 corridas por URL y formato,
  mediana, descartando las corridas con latencia de origen > 300 ms; PSI de Google por
  Puppeteer para la cifra de referencia. Skill `wp-seo-rendimiento`
  ([medicion.md](../../wp-seo-rendimiento/references/medicion.md)).
- Tras el cambio de servidor, la batería de verificación del runbook (su F10): público sin
  llave, todas las URL publicadas en los dos idiomas, rutas especiales, mapa 301/410,
  contenido sin dominio viejo y cabeceras, más formularios en seco y clases *preview* =
  *frontend*. Skill `wp-migracion`
  ([runbook.md](../../wp-migracion/references/runbook.md)).
