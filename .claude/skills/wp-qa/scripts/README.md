# Scripts de wp-qa

Herramientas del QA adversarial de una página o plantilla de Elementor 4. Salen de la réplica de
HomeMaker (caso de referencia) y están generalizadas: sin dominios, rutas ni prefijos del proyecto.
Cada archivo lleva en su cabecera propósito, uso exacto, salida, **estado** («probado en HomeMaker»
o «generalizado, no probado fuera de HomeMaker») y riesgos. Todos pasan `php -l`, `node --check` o
`python3 -m py_compile`. Qué mide cada uno y cómo se lee el resultado:
[`../references/mediciones.md`](../references/mediciones.md) (códigos M1–M19); en qué orden:
[`../references/checklist.md`](../references/checklist.md).

Marcadores: `<WP_PATH>` ruta de WordPress en el servidor, `<SSH_ALIAS>`, `<DOMINIO>` /
`<STAGING_URL>` (en los comandos, `$BASE`), `<PREFIJO>` (`pfx` por defecto en los scripts),
`<IDIOMA_2>`, `<ID_DOC>`.

Todo es **de solo lectura**: ninguno guarda documentos, opciones ni metas, envía correo, purga
cachés ni toca la cortina. Ninguno usa el navegador del usuario ni el de otra sesión.

## En tu equipo (Chrome headless propio)

Requisitos: Node ≥ 18, Python 3, Google Chrome (o `CHROME=<ruta>`). Una vez, en una carpeta de
trabajo fuera de `~/.claude` (no instales dependencias dentro de la skill):

```bash
mkdir -p ~/qa-headless && cp ~/.claude/skills/wp-qa/scripts/{package.json,qa-*.mjs,qa-*.py} ~/qa-headless/
cd ~/qa-headless && npm i        # puppeteer-core ^23.11.1, la versión que usó HomeMaker
```

| Archivo | Qué hace | Uso | M | Estado |
|---|---|---|---|---|
| `qa-medir.mjs` | geometría de secciones, tipografía computada, imágenes, opacidad 0, desborde, fuentes y consola, por página y ancho; movimiento reducido y entradas forzadas | `QA_REVELAR='[data-<PREFIJO>-reveal],[data-<PREFIJO>-stagger]' node qa-medir.mjs wp.json "$BASE" "/,/<ruta>/"` | M5 | generalizado; probado en local con una página de muestra |
| `qa-cmp.py` | compara dos salidas de `qa-medir.mjs` con tolerancia en px (`--todo`: también textos, botones e imágenes) | `python3 qa-cmp.py proto.json wp.json 1` | M6 | generalizado; probado en local con salidas de muestra |
| `qa-hover-foco.mjs` | ratón real sobre cada botón (contraste en hover, `#c36` de Hello) y Tab real (indicador de foco, foco dentro de `aria-hidden`) | `node qa-hover-foco.mjs "$BASE" /<ruta>/ 1440` | M9b | generalizado; probado en local con una página de muestra |
| `qa-html.py` | HTML servido: un `<main>`, skip-link, h1 y saltos, `<nav>` con nombre, formularios, alt, SVG, interactivos anidados, ids duplicados | `python3 qa-html.py p.html` | M13 | generalizado; probado en local con una página de muestra |
| `qa-es-en.py` | restos del idioma base en el HTML del idioma 2 (texto y atributos, `data-<PREFIJO>-*` incluidos) | `python3 qa-es-en.py en.html pfx` (tu `<PREFIJO>`) | M14 | probado en HomeMaker (par es→en) |
| `package.json` | dependencia de los dos `.mjs` | `npm i` | — | — |

Tolerancia canónica ([verificacion.md](../../wp-elementor-replica/references/verificacion.md)):
objetivo ≤1 px en 1440, 1280, 768 y 375; se aceptan ≤3 px por sección solo en anchos intermedios
(1100, 900, bordes de breakpoint); el idioma 2 frente al idioma base, ≤1 px; regresión de un cambio
invisible, ±1 px.

`qa-medir.mjs` no sustituye a `snap.mjs` + `cmp.py` de
[`wp-seo-rendimiento/scripts/medicion/`](../../wp-seo-rendimiento/scripts/medicion/): aquellos
fotografían todo el sitio antes y después de un cambio que no debe verse; este mide una página
contra el prototipo. Sus formatos JSON no son intercambiables.

## En el servidor (WP-CLI, solo lectura)

Archivo local → `php -l` → `scp` a `~/tmp-<PREFIJO>/` →
`ssh <SSH_ALIAS> 'cd <WP_PATH> && wp eval-file ~/tmp-<PREFIJO>/<archivo> … > ~/tmp-<PREFIJO>/<salida>.txt 2>&1'`.
Nunca `wp eval '…'` en línea por SSH. Nada de `rm`: los temporales se quedan y se avisa al usuario.

| Archivo | Qué hace | Uso | M | Estado |
|---|---|---|---|---|
| `qa-orden-clases.php` | orden de clases globales frontend = preview (si no, el editor borra clases al publicar) | `wp eval-file qa-orden-clases.php` | M11 | probado en HomeMaker |
| `qa-editable.php` | editabilidad por datos: parsers de Elementor en modo editor, ids, clases en frontend y preview, índice de clases, Loop Grid y filtro, carga y render; con guardián de escrituras | `wp eval-file qa-editable.php <ID>,<ID> --user=1` | M12 | generalizado, no probado fuera de HomeMaker |
| `qa-avisos.php` | avisos PHP, `_doing_it_wrong` y obsoletas del child al pintar una URL (con `render.php`) | `RENDER_URL=/<ruta>/ wp --require=… eval-file render.php` | M18 | generalizado, no probado |

## Scripts hermanos que el QA reutiliza (no se duplican aquí)

| Para | Script | Skill |
|---|---|---|
| medir en la consola del navegador propio, prototipo contra WP (M3/M4) | [`medir-vs-prototipo.js`](../../elementor4-atomico/scripts/medir-vs-prototipo.js) | `elementor4-atomico` |
| residuos de 10 px y `min-width:30px` (M7) | [`auditar-padding-atomico.js`](../../elementor4-atomico/scripts/auditar-padding-atomico.js) | `elementor4-atomico` |
| contraste sobre foto o vídeo con velo (M10b) | [`contrast.py`](../../elementor4-atomico/scripts/contrast.py) | `elementor4-atomico` |
| arreglo del orden de clases (escribe: lo ejecuta quien construye) | [`e4-sync-preview.php`](../../elementor4-atomico/scripts/e4-sync-preview.php) | `elementor4-atomico` |
| formularios en seco (M15) | [`probe-forms-seco.php`](../../elementor4-atomico/scripts/probe-forms-seco.php) | `elementor4-atomico` |
| códigos, redirecciones y cortina por curl (M16/M17) | [`comprobar-http.sh`](../../wp-migracion/scripts/comprobar-http.sh) | `wp-migracion` |
| HTML detrás de la cortina (M17) | [`render.php`](../../wp-seo-rendimiento/scripts/wp-cli/render.php) | `wp-seo-rendimiento` |
| consola de todo el sitio, Lighthouse, regresión visual (M18/M19) | [`scripts/medicion/`](../../wp-seo-rendimiento/scripts/medicion/) | `wp-seo-rendimiento` |
