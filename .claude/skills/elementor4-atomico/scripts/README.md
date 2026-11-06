# Scripts de `elementor4-atomico`

Herramientas para construir con **Elementor 4 atómico (V4)** por WP-CLI: sondas, clases
globales, páginas, parches, formularios en seco y medición contra el prototipo. Salen del código
real del caso de referencia (HomeMaker: Elementor 4.3.0, Pro 4.2.3, Hello 3.5.1, Polylang Pro
3.8.6), generalizado con prefijo configurable. Cada archivo lleva en su cabecera el propósito, el
uso exacto, qué editar, el estado («probado en HomeMaker» o «generalizado, no probado fuera de
HomeMaker») y los riesgos. **Lee la cabecera antes de ejecutar.**

El porqué de cada regla está en [`../references/gotchas-cli.md`](../references/gotchas-cli.md) y
el formato de los datos en [`../references/modelo-de-datos.md`](../references/modelo-de-datos.md).

## Cómo se ejecutan (siempre igual)

```bash
php -l local/<script>.php | tail -1                        # PHP 8.x local; JS: node --check; Python: python3 -m py_compile
ssh <SSH_ALIAS> 'mkdir -p ~/tmp-<PREFIJO>'                 # fuera del webroot; nunca el /tmp del servidor
scp -q local/<script>.php local/e4-helpers.php local/e4-sync-preview.php <SSH_ALIAS>:tmp-<PREFIJO>/
ssh <SSH_ALIAS> 'wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/<script>.php > ~/tmp-<PREFIJO>/salida.txt 2>&1; \
  grep -i -m3 "fatal\|uncaught" ~/tmp-<PREFIJO>/salida.txt | cut -c1-300; tail -12 ~/tmp-<PREFIJO>/salida.txt'
# después de guardar documentos o clases: purga la caché del hosting
#   SiteGround: wp sg purge · cPanel/Apache: wp cache flush && wp elementor flush_css   # guion bajo: trampa A10
```

- **`--user=1` siempre** que se guarde algo: sin usuario, `Document::save()` devuelve `false`
  sin avisar y no persiste nada.
- **Nunca `wp eval '…'` en línea por SSH**: el shell remoto rompe las comillas. Script en
  archivo, `php -l` en local, `scp`, `wp eval-file`.
- Los helpers son closures en variables (`$flex`, `$h`…): se cargan con
  `require __DIR__ . '/e4-helpers.php'` desde varios scripts sin redeclarar funciones. Los
  argumentos de la línea de comandos llegan en `$args`; las opciones, en variables de entorno
  (`E4_SECO=1 wp …`).
- **`E4_SECO=1`**: los scripts que escriben y lo admiten validan y enseñan qué harían, sin escribir.
- Sin `rm` encadenado a otros comandos: deja los restos en `~/tmp-<PREFIJO>/` y límpialos aparte.
- Prefijo: los scripts traen `pfx` por defecto (clases `pfx-`, atributos `data-pfx-*`, constante
  `E4_PREFIJO` en `e4-clases-upsert`). Cámbialo por el `<PREFIJO>` del proyecto (en HomeMaker, `hm`).

## Catálogo

| Archivo | Qué hace | ¿Escribe? | Estado |
|---|---|---|---|
| `e4-sondeo-kit.php` | Versiones, experimentos (¿`e_atomic_elements` activo?), kit, variables V4, clases por contexto, breakpoints activos y guardados, opciones de colapso del nav-menu | No | Probado |
| `e4-sondeo-esquema.php` | Elementos `e-*` registrados, settings de cada uno con sus uniones y enum de `tag`, propiedades de estilo y su forma `$$type`; render de prueba opcional | No | Probado |
| `e4-variables.plantilla.php` | Crea las variables V4 (color, fuente, medida); salta las existentes | Sí (kit) | Núcleo probado |
| `e4-kit-breakpoints.plantilla.php` | Activa/quita breakpoints escribiendo el meta del kit (no `$kit->save()`), recompila y pone el colapso del nav-menu | Sí (kit, plantillas) | Núcleo probado |
| `e4-helpers.php` | Biblioteca: elementos V4, valores `$$type`, `$cls`, estilos locales, setters dinámicos, `$e4_guardar`, `$e4_plantilla`, `$e4_condiciones`, utilidades de árbol | Solo al llamar a guardar | Núcleo probado |
| `e4-dynamic-tags-preambulo.php` | Preámbulo para guardar etiquetas dinámicas desde CLI + constructores `$dyn`, `$d_titulo`, `$d_url`, `$d_campo`, `$d_sc`, `$d_terminos`, `$d_foto_destacada` | No | Probado (respaldo de imagen: generalizado) |
| `e4-clases-upsert.plantilla.php` | Plantilla del `<pagina>-classes.php`: leer → fusionar → ordenar (modificadores antes de su base) → validar → `put` → sync | Sí (clases) | Núcleo probado |
| `e4-sync-preview.php` | Copia el orden de clases frontend → preview y comprueba que cuadran (metas, REST, repositorio) | Sí (orden preview) | Núcleo probado |
| `e4-build-pagina.plantilla.php` | Plantilla del `<pagina>-build.php`: comprueba clases e ids, busca o crea la página y reescribe su árbol | Sí (página) | Núcleo probado |
| `e4-parche-por-id.plantilla.php` | Cambia atributos, `tag` o claves V3 de elementos concretos por id, sin reescribir el árbol | Sí (documentos) | Núcleo probado |
| `e4-dump-clases.php` | Vuelca clases (todas o las pedidas) con posición en el orden, variantes y variables | No | Probado |
| `probe-forms-seco.php` | Valida en memoria los formularios de Pro de una página y simula el correo sin enviarlo | No | Núcleo probado |
| `medir-vs-prototipo.js` | Cajas y estilos computados con los reveals forzados; compara contra el otro lado con tolerancia | No | Núcleo probado |
| `auditar-padding-atomico.js` | Contenedores con el padding 10px de base y bloques vacíos con min-width 30px | No | Núcleo probado |
| `contrast.py` | Contraste WCAG de bandas de texto sobre foto o vídeo con velo, fotograma a fotograma | No | Núcleo probado |

«Núcleo probado» = el código central se ejecutó en el caso; lo añadido para generalizarlo está
listado en la cabecera como «generalizado, no probado fuera de HomeMaker».

## Orden de uso en un proyecto nuevo

**Arranque (una vez)**
1. `e4-sondeo-kit.php`: confirma Elementor 4 con el editor atómico activo antes de escribir nada.
   No te fíes de lo que sabes de Elementor 3.
2. `e4-sondeo-esquema.php`: formas reales de los props de esta instalación.
3. `e4-variables.plantilla.php` con la paleta y las fuentes medidas en el prototipo.
4. `e4-kit-breakpoints.plantilla.php` si el prototipo tiene un corte que Elementor no trae
   (el `xl` 1280 de Tailwind → `tablet_extra` 1279). Comprueba después con el sondeo en otro proceso.

**Por página** (se repitió igual en todas las páginas del caso)
1. Spec medida del prototipo a 1440×900 y 375×812 (ver skill `wp-elementor-replica`,
   `references/prototipo-a-spec.md`).
2. Medios: TSV `archivo<TAB>título<TAB>alt` y `wp media import … --porcelain` en bucle →
   JSON clave → id de adjunto. Los ids nunca a mano en el código.
3. `<pagina>-classes.php` (de `e4-clases-upsert.plantilla.php`): primero con `E4_SECO=1`, luego de
   verdad. Termina siempre con `e4-sync-preview.php`.
4. `<pagina>-build.php` (de `e4-build-pagina.plantilla.php`). Aborta si falta alguna clase.
5. Medición: `medir-vs-prototipo.js` en los dos lados a 1440 y 375 (y 1100/900 para cabecera
   y pie) + `auditar-padding-atomico.js` (debe dar `total: 0`).
6. Arreglos: clases con `$parchear` en el `<pagina>-classes.php`; elementos con
   `e4-parche-por-id.plantilla.php`. Vuelve a medir.
7. Revisión adversarial y checklist: skill `wp-qa`.

**Transversal**
- Contenido dinámico (CPT, loop, single): `e4-dynamic-tags-preambulo.php` como primera línea del
  build; detalles en [`../references/contenido-dinamico.md`](../references/contenido-dinamico.md)
  y [`../references/theme-builder.md`](../references/theme-builder.md).
- Formularios: `probe-forms-seco.php` al construir, al cambiar de dominio y tras migrar
  (skill `wp-migracion`). Reglas en [`../references/formularios.md`](../references/formularios.md).
- Hero con vídeo o banner con foto: `contrast.py`. El contraste de texto sobre colores planos y las
  mediciones PSI/Lighthouse están en la skill `wp-seo-rendimiento`.
- Segundo idioma: la generación de la versión traducida es de la skill `wp-polylang`
  (`$e4_guardar` ya guarda cada documento en su idioma de Polylang).
- Tras cualquier migración: `e4-sync-preview.php` suelto antes de que nadie publique desde el editor.

## Uso rápido de cada uno

```bash
# sondas (solo lectura)
wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/e4-sondeo-kit.php
wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/e4-sondeo-esquema.php "gap,flex,box-shadow" "e-flexbox,e-image"
wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/e4-dump-clases.php "pfx-btn,pfx-btn--sm"
E4_CONTEXTO=preview wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/e4-dump-clases.php

# clases y página (sube también e4-helpers.php y e4-sync-preview.php)
E4_SECO=1 wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/<pagina>-classes.php
wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/<pagina>-classes.php
E4_ARBOL=1 wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/<pagina>-build.php

# parches por id
E4_SECO=1 E4_SOLO=<ID> wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/parche-<motivo>.php

# comprobación de contextos de clases (tras migrar, o si alguien va a publicar)
wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/e4-sync-preview.php

# formularios en seco (no envía nada; imprime destinatarios reales: no pegues la salida en documentos públicos)
E4_PAGINA=<ID> E4_CABECERAS=1 wp --path=<WP_PATH> --url=https://<DOMINIO> --user=1 eval-file ~/tmp-<PREFIJO>/probe-forms-seco.php

# contraste sobre vídeo o foto (local)
python3 contrast.py --ejemplo > contraste.json
python3 contrast.py contraste.json 'frames/*.png'
```

`medir-vs-prototipo.js` y `auditar-padding-atomico.js` se pegan en la consola del navegador (o se
pasan a la herramienta de JS del navegador o a `page.evaluate()` de Puppeteer). Mide por JS, no
por capturas: una pestaña oculta pausa los revelados y las capturas salen en blanco.

## Salidas que confirman que todo fue bien

| Script | Línea esperada |
|---|---|
| `<pagina>-classes.php` | `clases: antes N → después M (…)` con M ≥ N, pares en orden y la línea del sync |
| `e4-sync-preview.php` | `orden: frontend N · preview N · iguales: sí` … `estilos nulos en preview: ninguno` |
| `<pagina>-build.php` | `documento #ID — secciones: S elementos: E` y un resumen por sección |
| `e4-parche-por-id` | `#doc: ids (hechos/total)` con hechos = total |
| `probe-forms-seco.php` | `OK` en todos los casos: `válido=true` en el válido y `válido=false` en el resto (salvo `sin email` si el campo no es obligatorio) |
| `auditar-padding-atomico.js` | `"total":0` |
| `medir-vs-prototipo.js` | `"pendientes":0`, `"desborde":false` y `diferencias` vacío (≤1 px; ≤3 px por sección solo en anchos intermedios, ver `wp-elementor-replica/references/verificacion.md`) |
