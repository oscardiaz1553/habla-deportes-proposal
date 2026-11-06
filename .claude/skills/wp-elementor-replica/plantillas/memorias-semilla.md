# Plantilla · memorias semilla del proyecto

Qué memorias crear el día 1 de un proyecto de réplica WordPress + Elementor 4, con qué forma y
cuándo se actualizan. Sale de lo que resultó útil en el caso de referencia (HomeMaker), donde la
memoria funcionó como sistema de trabajo: cada sesión nueva arrancaba sabiendo qué estaba
hecho, qué IDs tenía cada cosa y qué trampas ya habían costado tiempo.

## Dónde vive y qué no va nunca

- La memoria automática está en `~/.claude/projects/<carpeta-de-la-sesión>/memory/`: **es por
  carpeta de trabajo**. En el caso, las sesiones abiertas en el prototipo, en la raíz y en la
  carpeta de entrega tenían memorias distintas. Lo que todas las sesiones deben saber (estado,
  IDs, reglas) va **también** al `CLAUDE.md` de la raíz (plantilla [`CLAUDE.md`](CLAUDE.md)).
- **Nunca** en memoria: contraseñas, tokens, la llave de una cortina, claves SMTP o de
  licencias, credenciales de BD, IPs, host/usuario/puerto SSH (solo el alias). *Por qué:* en el
  caso, una memoria guardó la llave de la cortina y otra el usuario y la IP del hosting: al
  preparar este kit hubo que excluirlas, y el clasificador de permisos denegó una lectura amplia
  de memorias con datos de acceso.
- Correos y teléfonos personales, tampoco: los destinatarios se anotan por rol («ventas del
  cliente»); la dirección exacta vive en los ajustes del formulario.
- Lee la memoria **archivo a archivo** y solo lo necesario.

## Formato

Un archivo por tema, con frontmatter, y un índice `MEMORY.md` de una línea por archivo:

```markdown
---
name: <proyecto>-wp-rebuild
description: <una línea: qué es y para qué sirve este archivo>
metadata:
  type: project        # user | project | reference | feedback
---

<cuerpo corto y concreto; enlaces a otras memorias con [[otro-archivo]]>

**Why:** <por qué importa: el incidente o la decisión que lo motiva>
**How to apply:** <qué hacer distinto la próxima vez, en imperativo>
```

```markdown
# Memory Index
- [<Título corto>](<archivo>.md) — <gancho de una línea con lo esencial>
```

- Las memorias de proyecto terminan en **Why:** y **How to apply:**.
- Fecha absoluta en cada estado («2026-09-25: cortina APAGADA hasta el OK de SEO»).
- Estado explícito: «hecho», «hecho, pendiente de verificar por el usuario», «bloqueado por …».
- Si un diagnóstico escrito resulta falso, se corrige diciendo que fue un error; no se borra en
  silencio.
- Las trampas técnicas llevan la versión exacta («Elementor 4.3.0, Pro 4.2.3. No fiarse de lo
  que se sabía de Elementor 3»).

---

## Las semillas

### 1. `user-<nombre>.md` · type: user
- Quién es, rol, idioma de trabajo (en el caso: español, responder en español).
- Cómo dirige: órdenes cortas y encadenadas («Status y continúa», «Súbelo», «adelante con la
  1»); espera ejecución y un resumen, no preguntas innecesarias; decide rápido con opciones
  numeradas; pide ver el inventario completo antes de rondas grandes.
- Reparto: Claude hace el 80–90 % del desarrollo; el usuario instala plugins de pago, gestiona
  DNS y cuentas y prueba el editor.
- Qué rechazó y por qué (en el caso: tipografías «con cara de IA», fotos y vídeo generados por
  IA, emojis como iconos, desplegar fuera de su hosting, procesos que agotan el Mac).

### 2. `<proyecto>-wp-rebuild.md` · type: project (el archivo central)
- Objetivo («réplica exacta del prototipo, todo editable, sin plantillas importadas») y
  decisiones de stack confirmadas por el usuario, con fecha.
- Entorno: alias, `--path`, qué es staging y qué producción.
- **Registro de IDs** de todo lo creado: páginas, plantillas del Theme Builder por idioma,
  menús (nombre y slug), kit, adjuntos fijados en constantes del hijo, formularios (id de
  elemento). Otra sesión debe poder operar sin buscar.
- Estado por fase con fecha y lo «pendiente de verificar por el usuario».
- Cierre: dónde quedó el documento de entrega y qué supuestos quedaron abiertos.

### 3. `<proyecto>-marca.md` · type: project
- Paleta con **contraste medido** y reglas de uso («naranja solo como fondo o texto ≥ 24 px»).
  *Por qué:* las reglas de uso se fijaron tarde, después de medir 2,86:1 en un color de acento.
- Tipografías aprobadas (pesos disponibles, sin itálica) y las rechazadas con su motivo.
- Localización y vocabulario del cliente, al pie de la letra (en el caso: «china», no
  «naranja»; los nombres de recetas en inglés se quedan en inglés).
- Qué contenido solo puede venir del cliente (claims, cifras, contactos, redes).

### 4. `<proyecto>-accesos.md` · type: project (sin secretos)
- Alias SSH por entorno y `--path`; cómo se entra al administrador (`/<LOGIN_SLUG>`, sin
  credenciales) y que Claude no inicia sesión.
- **La trampa del hosting**, en mayúsculas si la hay: «LA RAÍZ ES `<docroot>`, NO
  `public_html`: ahí vive otro sitio». Otros sitios de la cuenta: intocables.
- Qué hacer si el agente SSH se vacía tras reiniciar el Mac
  (`ssh-add -l >/dev/null 2>&1 || ssh-add --apple-load-keychain`, si la llave tiene
  passphrase).
- Cortinas: qué modo hay en cada entorno y quién decide encenderla o apagarla. La llave, si
  existe, **no** se escribe: se lee del servidor sin imprimirla cuando haga falta.

### 5. `elementor4-cli-gotchas.md` · type: reference (archivo vivo)
- Una entrada por tropiezo verificado: síntoma → causa → regla, con la versión y el fragmento
  exacto. Se añade en el momento, no al final.
- Punto de partida: las trampas del kit ([`gotchas-cli.md`](../../elementor4-atomico/references/gotchas-cli.md) de la skill `elementor4-atomico`); aquí
  solo lo nuevo o lo que cambió con la versión instalada.

### 6. `<proyecto>-sesiones-paralelas.md` · type: project (si hay más de una sesión)
- Quién es dueño de qué (archivos del hijo, documentos por idioma, Yoast, caché, cadenas de
  Polylang) y qué no toca cada una.
- Protocolo: bajar del servidor, comparar md5, avisar antes y después de cada subida; la otra
  sesión edita solo el idioma base y esta regenera el idioma 2.
- Estados pactados vigentes («no actives la cortina hasta que diga *listo*», «FREEZE»).
- Detalle: [`sesiones-paralelas.md`](../references/sesiones-paralelas.md).

### 7. `<proyecto>-feedback-rondas.md` · type: feedback
- Por ronda del cliente (R1…Rn): qué pidió, qué está hecho, qué está bloqueado y por qué
  material, contradicciones con rondas anteriores y cómo se resolvieron.
- Sin nombres, teléfonos ni correos de las personas del cliente.

### 8. `<proyecto>-formularios-correo.md` · type: project
- Modelo de envío (sendmail del hosting sin SMTP mientras el SPF autorice al servidor), remitente
  (`no-reply@<DOMINIO>`), antispam sin terceros, destinatarios por rol y en cuántos lugares se
  cambian (formularios × idiomas).
- Regla: nunca un envío real; pruebas en seco con `pre_wp_mail`.
- Estado del SPF/DKIM/DMARC y del MX del dominio (quién lo gestiona).

### 9. `entorno-local.md` · type: reference (si hubo incidentes)
- Lo que se aprendió de la máquina: en el caso, Next.js tomó como raíz una carpeta con varios
  proyectos (~278.000 archivos vigilados) y congeló el Mac → `turbopack.root` fijado y un solo
  servidor de desarrollo; hooks de telemetría de terceros que duplicaban cada llamada.

### Después: memorias que nacen en el camino
- `<proyecto>-acceso-y-cortina.md` (F10): URL de acceso propia, modo de la cortina por entorno,
  `admin_email` y correo del núcleo.
- `<proyecto>-rendimiento-seo.md` (F11, sesión de SEO): qué se hizo, dónde vive y cómo medir.
- `<proyecto>-wp-en-produccion.md` (F12): estado tras la migración, lo que falta para lanzar
  (DNS de correo, OK de SEO, licencias), respaldos y fechas de limpieza del usuario.

## Cuándo se actualizan

- Al cerrar cada hito (página, plantilla, fase): el archivo central (estado e IDs) y las
  trampas nuevas.
- Al recibir una decisión del usuario: con fecha y, si fue contra la recomendación, el riesgo
  que queda y cómo se mitiga.
- Mientras corre un workflow largo: es buen momento para ponerla al día.
- Al terminar el proyecto: repaso para fusionar duplicados y quitar estados viejos.
