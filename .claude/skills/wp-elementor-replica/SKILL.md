---
name: wp-elementor-replica
description: >
  Playbook de punta a punta para reconstruir un prototipo o diseño aprobado (Next.js,
  HTML estático o Figma) como sitio WordPress con Elementor 4 atómico (V4), Hello + tema
  hijo, Polylang Pro y Yoast, todo por WP-CLI: arranque y accesos, decisiones que se le
  presentan al usuario, orden de fases F0–F12, verificación contra el prototipo, sesiones
  paralelas, migración a producción y lanzamiento. Úsala SIEMPRE que alguien quiera
  reconstruir, replicar, portar o «pasar» un prototipo, una maqueta o un diseño a
  WordPress/Elementor, empezar un proyecto WordPress o Elementor nuevo para un cliente, o
  retomar uno así y saber qué fase sigue. Es el punto de entrada del kit: después carga las
  skills de oficio que indica.
---

# Réplica de prototipo en WordPress + Elementor 4

Playbook de proceso, independiente de marca. La marca, los accesos (por alias, nunca con
credenciales) y el estado de cada proyecto viven en su `CLAUDE.md` y en su memoria. Esta
skill dice en qué orden se trabaja, qué se le pregunta al usuario y cuándo algo está hecho.
El cómo técnico está en las skills de oficio (tabla al final).

**Caso de referencia:** HomeMaker Premium. Prototipo Next.js 16 + Tailwind v4 reconstruido
sobre WordPress 7.1.2 · PHP 8.2 · Elementor 4.3.0 (V4 atómico) · Elementor Pro 4.2.3 ·
Polylang Pro 3.8.6 · Hello Elementor 3.5.1 · Yoast 27.7. Staging en SiteGround, producción en
un cPanel compartido. Resultado: réplica medida a ≤3 px por sección (≤1 px en el segundo
idioma), todo editable por el cliente, PageSpeed en verde y cambio a producción sin caída.
Lo que sabías de Elementor 3 no sirve aquí: compruébalo todo en la instalación.

## Lo primero, siempre

1. **Dónde estás y qué es producción.** Lee el `CLAUDE.md` y la memoria del proyecto, y
   confirma con `wp --path=<WP_PATH> option get home` que el entorno donde vas a escribir es
   el que dice la memoria. Tras una migración, editar el staging no llega al sitio real.
2. **Qué hay de verdad en el servidor y para qué es.** Inventaría el WordPress y el
   prototipo antes de proponer nada, sondea la versión de Elementor y sus experimentos
   (`e_atomic_elements`) y pregunta para qué es el servidor. En el caso de referencia no era
   un despliegue del prototipo sino un WordPress vacío preparado para la reconstrucción, y
   eso cambió el plan.
3. **El prototipo es la especificación, pero medido.** Construye contra valores computados
   del navegador (una spec por sección), no contra su código: el código puede decir
   `font-600` y el navegador pintar 400. Ver `references/prototipo-a-spec.md`.

## Reglas que no se negocian

- **Réplica exacta del diseño aprobado, todo editable por el cliente, sin plantillas
  importadas.** Los defectos funcionales del prototipo (enlaces muertos, contenido que no
  aparece, accesibilidad) se corrigen y se dicen. Las decisiones de contenido o de marca no
  se tocan: se replican y se listan para el cliente.
- **Todo por WP-CLI con scripts PHP idempotentes, en archivo**: `scp` y
  `wp --path=<WP_PATH> eval-file x.php --user=1`. Nunca `wp eval` inline por SSH. Cada script
  imprime un resumen que se puede comprobar.
- **Clases globales:** al final de todo script que las cree o modifique, sincroniza el
  orden del contexto *preview*. Si no, el editor borra clases del sitio al publicar.
- **Seguridad:** credenciales nunca por chat; ningún envío real de formulario; ningún
  borrado sin permiso, y de uno en uno; `blog_public=0` hasta el lanzamiento, que solo se
  hace en producción. Todas las reglas: `references/operacion-segura.md`.
- **Mide, no mires.** Una página está hecha cuando cuadra con el prototipo en los anchos
  acordados, pasó la revisión adversarial y el usuario la abrió en el editor (Claude no
  inicia sesión en WordPress). Protocolo: `references/verificacion.md`.
- **Las decisiones de estructura, dinero, contenido, seguridad y lanzamiento son del
  usuario.** Se le presentan como plan con opciones y una recomendada
  (`references/decisiones.md`). Lo demás se ejecuta sin preguntar y se informa, marcando lo
  que decidiste tú.

## Las fases

| Fase | Qué | Se cierra cuando |
|---|---|---|
| F0 | Análisis y acceso: inventario, SSH, versiones, procedencia de plugins, auditoría del prototipo | informe inicial y decisiones de F1 listas |
| F1 | Decisiones de base: editor V4, Hello + hijo, menú, idioma y URL, slugs | confirmadas y escritas en la memoria |
| F2 | Fundaciones: variables V4, globales del kit, clases globales, breakpoints, base del hijo | leídas de vuelta; preview = frontend |
| F3 | Cabecera y pie (Theme Builder), páginas vacías y menús | medida en tolerancia a 1440/1280/768/375 y también a 1100 y 900 |
| F4 | Inicio | medida en tolerancia por sección y revisión adversarial |
| F5 | Resto de páginas | ídem, página a página |
| F6 | Contenido dinámico: CPT, Loop Grid, filtro, ficha | el filtro responde por URL; datos editables |
| F7 | Formularios y correo | prueba en seco correcta y remitente alineado |
| F8 | Ronda de ajustes del cliente | inventario numerado cerrado |
| F9 | Segundo idioma, generado por script | medido ≤1 px y sin restos del idioma base |
| F10 | 404, acceso con marca y «Próximamente» | `curl` con los códigos esperados |
| F11 | En paralelo: SEO/rendimiento y documento de entrega | PageSpeed en verde; documento revisado |
| F12 | Migración a producción y lanzamiento | puertas GO/NO-GO A–D |

«En tolerancia» = objetivo ≤1 px por sección; ≤3 px solo en anchos intermedios; el segundo
idioma, ≤1 px (`references/verificacion.md` §2). La F12 contiene el runbook de la skill
`wp-migracion`, que numera sus propias fases F0–F13: cítalas como «runbook F5» para no
confundirlas con las de este playbook.

Objetivo, entradas, pasos, criterio de «hecho», trampas y qué preguntar en cada fase:
`references/fases.md`.

## Cómo se trabaja cada página o plantilla (F3–F10)

1. Lee el prototipo y mídelo: `spec-prototipo.md` de la página.
2. Si algo es nuevo, investígalo antes con agentes de solo lectura que leen el código del
   plugin instalado (archivo:línea) y entregan la recomendación en la primera línea.
3. Script de clases (lee, fusiona, valida con el parser, guarda, sincroniza preview) y
   después script de construcción (helpers compartidos, IDs de elemento deterministas).
4. CSS y JS del tema hijo solo para lo que V4 no expresa, enganchado con atributos
   `data-<PREFIJO>-*` para que el cliente cambie estilos sin romper el comportamiento.
5. Despliega (`php -l` → `scp` → `eval-file` → purga de caché) y mide contra el prototipo con
   el mismo script en los dos lados.
6. Lanza la revisión adversarial en segundo plano y sigue con la siguiente página; aplica
   solo lo confirmado.
7. Actualiza la memoria (estado, IDs, trampas nuevas) e informa al usuario, terminando con
   «pendiente de tu lado: abrir X en el editor».

## Con qué más

| Necesitas | Carga |
|---|---|
| Elementor 4 atómico por CLI: variables, clases, Theme Builder, CPT/Loop, formularios, tema hijo | skill `elementor4-atomico` (agente `elementor-dev`) |
| Segundo idioma con Polylang Pro | skill `wp-polylang` |
| SEO con Yoast, PageSpeed y accesibilidad | skill `wp-seo-rendimiento` (agente `wp-seo`) |
| Migración staging → producción, DNS, correo, lanzamiento y vuelta atrás | skill `wp-migracion` |
| Auditar una página o plantilla | skill `wp-qa` (agente `qa-elementor`) |
| Diseñar o rehacer el prototipo antes de replicarlo, si aún no existe o está flojo (huecos, datos inventados, diseño tímido) | skill `metodo-diseno-web`, si está instalada. Esta skill empieza con un diseño aprobado |

## Plantillas y ejemplos (viajan con esta skill)

Se instalan con ella, en `~/.claude/skills/wp-elementor-replica/`:

- `plantillas/`: `CLAUDE.md` del proyecto, `kickoff-checklist.md`, `spec-seccion.md`,
  `plan-con-decisiones.md`, `documento-entrega.md`, `memorias-semilla.md` y `workflows/*.js`
  (qué workflow va en cada fase: `plantillas/workflows/README.md`). Las `.md` se copian al
  proyecto y se rellenan allí; los workflows se lanzan desde aquí con sus `args`. Ninguna se
  edita en la skill para un proyecto concreto.
- `ejemplos-homemaker/`: la implementación real del caso, saneada (tema hijo de producción,
  mu-plugins y scripts ejecutados). Sirve para ver un patrón con su contexto completo; no es
  una plantilla: para un proyecto nuevo, los scripts generalizados de las skills de oficio.

## References

- `references/fases.md`: F0 a F12 en detalle.
- `references/decisiones.md`: lo que se le pregunta siempre al usuario, con la
  recomendación por defecto, y cómo presentarlo.
- `references/operacion-segura.md`: reglas de seguridad y de operación.
- `references/verificacion.md`: el protocolo de medición, pruebas en seco, revisión
  adversarial e informe.
- `references/sesiones-paralelas.md`: varias sesiones sobre el mismo WordPress.
- `references/prototipo-a-spec.md`: auditar el prototipo y convertirlo en spec.
