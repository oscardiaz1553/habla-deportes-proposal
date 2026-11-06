# Workflows del kit

Scripts para la herramienta **Workflow** de Claude Code: reparten el trabajo entre subagentes
(buscar, verificar, sintetizar) cuando una sola conversación no basta para ser exhaustivo o
para ser escéptico con lo propio. En el caso de referencia (HomeMaker) la revisión adversarial
con workflows encontró lo que la medición no veía: el borrado de clases al publicar desde el
editor, un parámetro de URL que daba 500, el hover invisible de todos los botones verdes y un
riesgo de error fatal tras actualizar Elementor.

Para escribir o modificar uno, carga antes la skill `workflow-authoring` si la sesión la tiene.

## Qué hay

| Workflow | Fase | Qué hace | Escribe | Estado |
|---|---|---|---|---|
| `auditar-prototipo.js` | F0 | un agente por dimensión + verificador por hallazgo + síntesis con procedencia | solo el informe (si `salida`) | generalizado de `analisis-diseno-homemaker` |
| `extraer-spec.js` | F3–F10 | mide el prototipo con Chrome headless y escribe `spec-prototipo.md`; un escéptico re-mide una muestra | la spec y los JSON de medición | **nuevo, no probado** |
| `investigar-plugin.js` | cualquiera | código del plugin instalado (archivo:línea) + sondas que no guardan; contraste independiente | `research-<key>.md` (si `salida`) | generalizado de `orangeology-research` y `contacto-forms-research` |
| `construir-pagina.js` | F3–F10 | plan → `classes.php` + `build.php` → (staging) ejecutar, medir, corregir | scripts locales; **en modo `staging`, el WordPress de staging** | **nuevo, no probado** |
| `revisar-pagina.js` | F3–F11 | buscadores por lente + un escéptico por hallazgo; presets `pagina`, `idioma`, `cambios` | nada (sondas temporales en el servidor) | generalizado de `contacto-review`, `historia-review`, `orangeology-review`, `review-tiendas`, `review-en` |
| `disenar-pantalla.js` | F10 | investigación + dos propuestas + juez que escribe la spec final con código | `research-*.md` y `final-<pantalla>.md` | generalizado de `plan-404-login` |
| `plan-rendimiento-seo.js` | F11 (sesión SEO) | cinco analistas + crítico → un plan con esquema fijo | nada | generalizado de `perf-seo-plan` |
| `revisar-entrega.js` | F11 (entrega) | hechos, rutas del panel y completitud del documento de entrega | nada | generalizado de `review-handover-doc` |
| `planificar-migracion.js` | F12 | auditorías de origen y destino, procedimiento, runbook con decisiones y control de secretos | informes en `salida` | generalizado de `plan-migracion-bluehost` |

«Generalizado de X» = el patrón y los prompts salen de un workflow que se ejecutó en HomeMaker;
la versión parametrizada **no se ha ejecutado** fuera de allí. Revisa la salida de la primera
ejecución en un proyecto nuevo con más cuidado que de costumbre.

## Cómo se lanzan

```text
Workflow({ scriptPath: '<HOME>/.claude/skills/wp-elementor-replica/plantillas/workflows/revisar-pagina.js',
           args: { pagina: 'Contacto', url: 'https://<STAGING_URL>/contact/', docIds: [<ID>], … } })
```

- Los scripts viajan con la skill `wp-elementor-replica`: la copia instalada está en
  `~/.claude/skills/wp-elementor-replica/plantillas/workflows/`. En `scriptPath` usa la ruta
  **absoluta** (`<HOME>` = la salida de `echo $HOME`): no cuentes con que `~` se expanda ahí.
- `args` va como **JSON de verdad** (objetos y listas), no como texto con JSON dentro: si no,
  los scripts reciben una cadena y fallan al recorrerla.
- Cada script valida sus argumentos obligatorios al empezar y se detiene con un mensaje que
  dice cuáles faltan. La cabecera de cada archivo trae un ejemplo completo de `args`.
- Revisiones y auditorías largas: en segundo plano, mientras avanzas con lo siguiente.
- Si la salida llega vacía, los resultados ya emitidos están en el `journal.jsonl` de la
  carpeta del workflow (eventos `started` y `result`); para reanudar tras editar el script,
  `Workflow({ scriptPath, resumeFromRunId })` reutiliza lo que no cambió.
- Una ejecución de `construir-pagina.js` a la vez por sitio: el repositorio de clases globales
  es uno y un script de clases escribe el conjunto completo.

### Argumentos comunes

| Arg | Qué es |
|---|---|
| `url` | URL base o de la página en staging |
| `sshAlias`, `wpPath` | alias de `~/.ssh/config` y `--path` de WordPress (sin ellos, solo curl y archivos) |
| `prefijo` | `<PREFIJO>` del proyecto (clases, `data-*`, temporales `~/tmp-<PREFIJO>…/`) |
| `stack` | versiones en una línea (WordPress, Elementor, Pro, Polylang, Yoast, tema) |
| `kit` | carpeta que contiene `skills/`: `<HOME>/.claude` para la copia instalada o `<KIT>` para la fuente. Los scripts leen de ahí `skills/<skill>/references`, `skills/<skill>/scripts` y `skills/wp-elementor-replica/plantillas` |
| `prototipo`, `prototipoUrl` | código del prototipo y dónde lo sirve su único servidor de desarrollo |
| `sondas` | carpeta local donde los agentes escriben sondas PHP de solo lectura |
| `conocidos`, `medido` | listas de «conocido y aceptado» y de lo ya medido: no se reportan |
| `dirMedicion` | carpeta de trabajo para medir (por defecto `~/qa-headless`) con `npm i puppeteer-core` hecho y una copia de `qa-medir.mjs` y `qa-cmp.py` de `~/.claude/skills/wp-qa/scripts/` (preparación en el [`README.md`](../../../wp-qa/scripts/README.md) de esa carpeta; uso en [`mediciones.md`](../../../wp-qa/references/mediciones.md) M5 y M6). *Por qué una copia:* Node resuelve `import 'puppeteer-core'` desde la carpeta del script, no desde la de trabajo: el script tiene que estar junto a su `node_modules`, y la carpeta de trabajo evita instalar paquetes dentro de `~/.claude/skills`. `construir-pagina.js` (los dos) y `extraer-spec.js` (`qa-medir.mjs`) copian lo que falte; ningún workflow instala paquetes |
| `tolerancia`, `toleranciaIntermedios` | px por sección: 1 en 1440, 1280, 768 y 375; 3 solo en anchos intermedios (la canónica de [`verificacion.md`](../../references/verificacion.md) §2) |
| `agentType`, `agenteConstructor`, `agenteQa` | opcionales: tipo de subagente del kit (`qa-elementor` para buscar, verificar y medir; `elementor-dev` para escribir y ejecutar). Solo si los agentes del kit están instalados en `~/.claude/agents/`: un tipo que no existe hace fallar la llamada |

## Reglas que llevan todos los prompts

- Solo lectura estricta salvo lo que el workflow declara en «Escribe».
- Sin herramientas de navegador en los subagentes: el navegador es de la sesión y una pestaña
  oculta pausa `requestAnimationFrame` e `IntersectionObserver` (capturas en blanco, falsos
  fallos). Se mide con curl, sondas o Chrome headless propio.
- Nunca un envío real de formulario: `pre_wp_mail` antes de simular.
- Nunca leer ni imprimir `wp-config.php`, `.env`, `.my.cnf` ni volcados. *Por qué:* en el
  caso, un agente de auditoría imprimió las credenciales de BD de otro sitio de la cuenta
  aunque el prompt lo prohibía. Revisa la salida de cada agente antes de reenviarla.
- PHP por archivo y `eval-file`; nunca `wp eval` inline por SSH.
- Si el Bash del subagente corre en sandbox, `ssh`, `scp` y `curl` remotos necesitan
  `dangerouslyDisableSandbox: true`.
- Evidencia con archivo:línea o comando + salida; lo que no se pudo determinar se dice.

## Qué hacer con la salida

- **Revisiones** (`revisar-pagina`, `revisar-entrega`): aplica **solo lo confirmado**; informa
  también de lo descartado y por qué, y de lo no verificado (nunca como bueno). `lineas` trae el
  formato corto `id [gravedad→corregida] real=… :: título`. La revisión siguiente comprueba la
  regresión de los arreglos de la anterior.
- **Auditorías y planes** (`auditar-prototipo`, `planificar-migracion`, `plan-rendimiento-seo`):
  verifica tú los hechos que vayas a usar para decidir, también las conclusiones del agente
  sintetizador (en el caso, su «nota operativa» era una suposición falsa), y presenta las
  decisiones con [`plan-con-decisiones.md`](../plan-con-decisiones.md).
- **Construcción** (`construir-pagina`): termina siempre con la tarea del usuario (abrir la
  página en el editor, cambiar algo y publicar) y una comprobación CLI posterior de que las
  clases frontend = preview; después, `revisar-pagina.js`.
- Actualiza la memoria y el `CLAUDE.md` del proyecto con lo aprendido y los IDs nuevos.

## Cómo se validaron (y cómo validarlos tras editarlos)

`node --check` falla con estos scripts tal cual porque usan `return` y `await` en el nivel
superior (el motor los ejecuta dentro de una función). Se validaron envolviendo el cuerpo en
una función `async` y evaluando `meta` como literal puro:

```bash
python3 - <archivo>.js <<'PY'
import sys, subprocess
src = open(sys.argv[1], encoding='utf-8').read()
lines = src.split('\n'); end = next(i for i, l in enumerate(lines) if l == '}')   # fin de «meta»
meta, body = '\n'.join(lines[:end + 1]), '\n'.join(lines[end + 1:])
assert '`' not in meta and '${' not in meta, 'meta debe ser un literal puro'
lit = meta.replace('export const meta = ', '', 1)
print(subprocess.run(['node', '-e', 'const m=(' + lit + ');console.log("meta ok", m.name)'], capture_output=True, text=True).stdout.strip())
envuelto = meta + '\nasync function __c(agent, parallel, pipeline, phase, log, args, budget, workflow) {\n' + body + '\n}\n'
r = subprocess.run(['node', '--check', '--input-type=module'], input=envuelto, capture_output=True, text=True)
print('node --check:', 'ok' if r.returncode == 0 else r.stderr)
PY
```

Además se ejecutaron en seco con `agent`, `parallel`, `pipeline`, `phase` y `log` simulados
(respuestas generadas a partir de cada esquema) para comprobar el flujo, las guardas de
argumentos y que ningún prompt quedara con `undefined`. Eso prueba la lógica del script, no el
comportamiento de los agentes.

## Reglas del motor que conviene no olvidar al editarlos

- `export const meta = {…}` al principio y como **literal puro** (sin variables, llamadas ni
  plantillas); los títulos de `meta.phases` iguales a los de `phase()` y `opts.phase`.
- `pipeline()` por defecto; barrera (`parallel` y esperar) solo cuando hace falta el conjunto
  entero: ordenar y deduplicar hallazgos antes de verificar, o fusionar dimensiones.
- Sin `Date.now()`, `Math.random()` ni `new Date()` (rompen la reanudación): las fechas se
  pasan por `args`.
- Nada de TypeScript ni `import`/`require`: JavaScript plano.
- Sin límites silenciosos: si se verifica solo una parte, `log()` de lo que quedó fuera.
