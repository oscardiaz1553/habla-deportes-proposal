# Kickoff · qué pedir y qué comprobar el día 1

Checklist de la fase F0 del playbook ([`fases.md`](../references/fases.md)). Cada punto
es **Pide** (al usuario) o **Comprueba** (tú, en solo lectura). Donde la regla no es obvia va
el porqué del caso de referencia (HomeMaker). Marca `- [x]` al cerrar cada punto y copia lo
que salga al `CLAUDE.md` del proyecto (plantilla [`CLAUDE.md`](CLAUDE.md)) y a la memoria
([`memorias-semilla.md`](memorias-semilla.md)). Ningún punto requiere que el usuario te dé una contraseña.

---

## A · Objetivo y alcance

- [ ] **Pide:** ¿para qué es el servidor? ¿Despliegue del prototipo, WordPress vacío para
      reconstruir o sitio vivo? *Por qué:* en el caso, el WordPress era una instalación vacía
      preparada para la reconstrucción; se supo por el inventario y cambió todo el plan.
- [ ] **Pide:** ¿réplica exacta del prototipo o diseño con plantillas? Por defecto: réplica
      exacta, todo editable por el cliente, sin plantillas importadas.
- [ ] **Pide:** idiomas y esquema de URL (idioma base sin prefijo; el segundo en
      `/<IDIOMA_2>/`) y slugs (los del prototipo, para que su mapa 301 siga valiendo).
- [ ] **Pide:** funcionalidades con coste o con datos grandes aún sin aprobar (API de mapas,
      localizador con miles de puntos, pagos). Hasta que se aprueben: versión informativa como
      la del prototipo, fuera de menús si el cliente lo pide.
- [ ] **Pide:** quién abrirá cada página en el editor de Elementor cuando se le pida. Claude no
      inicia sesión: la prueba del editor es del usuario.
- [ ] **Pide:** fecha objetivo de lanzamiento, quién decide encender la cortina y si habrá
      sesiones paralelas (SEO/rendimiento, documento de entrega).

## B · Accesos por llave (nunca contraseñas)

- [ ] **Pide:** host, usuario y puerto SSH del staging. Van solo a `~/.ssh/config`; en
      `CLAUDE.md` y en la memoria, solo el alias.
- [ ] **Haz:** una llave dedicada al cliente, sin passphrase, y entrega **solo la pública**:
      ```bash
      ssh-keygen -q -t ed25519 -f ~/.ssh/<cliente>_ed25519 -N "" -C "claude-<cliente>" < /dev/null
      cat ~/.ssh/<cliente>_ed25519.pub
      ```
      Dile dónde importarla (en SiteGround: Site Tools → Devs → SSH Keys Manager → Import; en
      otro hosting, su gestor de llaves SSH). La privada no sale del Mac.
- [ ] **Haz:** alias en `~/.ssh/config` con `IdentitiesOnly yes` y prueba:
      `ssh -o BatchMode=yes -o ConnectTimeout=20 <SSH_ALIAS> 'echo CONECTADO; whoami'`.
      Un `Permission denied (publickey)` antes de que la autorice significa host vivo.
- [ ] **Comprueba:** en Claude Code, `ssh`, `scp` y `curl` remotos necesitan que esa llamada
      de Bash salga del sandbox.
- [ ] **Regla:** no pidas contraseñas de wp-admin, BD, SMTP ni licencias. Si el usuario pega
      una en el chat, no la repitas y recomiéndale cambiarla. No explores `~/.ssh` más allá
      del alias.

## C · Plugins, licencias y versiones

- [ ] **Pide:** lista de plugins de pago y quién los instala (el usuario, con sus licencias):
      Elementor Pro (imprescindible: Theme Builder, formularios, Loop Grid y filtro), Polylang
      Pro (imprescindible si se traducen slugs y la base del CPT) y Yoast Premium (opcional:
      valora si basta el gratuito).
- [ ] **Comprueba** la procedencia de cada premium, sin tocar nada:
      ```bash
      wp --path=<WP_PATH> plugin verify-checksums --all      # solo cubre los de wordpress.org
      head -45 <WP_PATH>/wp-content/plugins/<premium>/<archivo-principal>.php
      grep -rnE "pre_http_request|update_option\(.*licen" <WP_PATH>/wp-content/plugins/<premium> | head
      ```
      *Por qué:* en el caso, Elementor Pro, Polylang Pro y Yoast (Premium y gratuito) venían
      modificados de fábrica en el staging: falseaban la licencia y Elementor Pro desviaba la
      biblioteca de plantillas a un servidor ajeno sin verificar SSL. Se descubrió al migrar.
      Si aparecen copias modificadas: decisión del usuario ([`decisiones.md`](../references/decisiones.md) §3.1) y, mientras
      tanto, nadie usa «Insertar plantilla».
- [ ] **Comprueba:** plugins del hosting (optimizador, seguridad, IA) y restos de temas
      anteriores. *Por qué:* un plugin residual (Child Theme Configurator) reescribía el
      `?ver=` de todas las hojas del hijo, también en la pantalla de acceso.
- [ ] **Comprueba** con la primera sonda la versión real de Elementor y sus experimentos
      (`experiments->get_features()` + `is_feature_active()`), antes de escribir nada. *Por
      qué:* se escribió un kit clásico dando por hecho Elementor 3 y el sitio tenía el editor
      atómico activo (`e_atomic_elements`). Sonda: [`e4-sondeo-kit.php`](../../elementor4-atomico/scripts/e4-sondeo-kit.php) de la skill `elementor4-atomico`.

## D · Prototipo y material del cliente

- [ ] **Pide:** ruta del prototipo y qué versión está aprobada. Las rondas del cliente se
      cierran en el prototipo, no en WordPress.
- [ ] **Haz:** `git init` + commit si falta, un único servidor de desarrollo
      (`.claude/launch.json` + `preview_start`) y, en Next.js dentro de una carpeta con varios
      proyectos, `turbopack: { root: path.join(__dirname) }`. *Por qué:* el Mac se congeló con
      un watcher sobre ~278.000 archivos y varios `next dev` vivos.
- [ ] **Pide:** brief, guía de estilo y las referencias visuales que admira el cliente. Si la
      guía contradice lo aprobado (tipografías, hex), pesa lo aprobado y se valida con el
      usuario.
- [ ] **Pide:** feedback en su formato original. Los `.docx` esconden adjuntos:
      `unzip -l <archivo>.docx | grep word/media`.
- [ ] **Pide:** fotos originales (reales, no generadas por IA), logo en todas sus versiones y
      licencias de tipografías. **Comprueba** dimensiones y `md5` de lo que ya esté en la
      Biblioteca contra lo del prototipo (en el caso, el logo subido era otra versión).
- [ ] **Pide:** contenido que solo da el cliente: contactos, claims regulados, datos
      nutricionales, redes sociales. Lo inventado o de relleno del prototipo se lista y no se
      publica.
- [ ] **Pide:** vocabulario y localización de la marca (en el caso: «china», no «naranja»).
      Va a la memoria desde el primer día.

## E · Dominio y DNS

- [ ] **Pide:** dominio final y si tuvo un sitio anterior indexado. *Por qué:* con historial,
      la cortina de producción es 503 y hace falta un mapa 301/410 sacado de la BD del sitio
      viejo (el prototipo solo conoce sus propias páginas).
- [ ] **Pide:** quién gestiona el DNS y dónde. **Comprueba** los NS autoritativos:
      `dig NS <DOMINIO> +short`. *Por qué:* en el caso, lo editado en el Zone Editor de cPanel
      no llegaba a los DNS públicos.
- [ ] **Comprueba:** con o sin `www` (lo que usaba el sitio anterior) y el certificado
      (`echo | openssl s_client -connect <DOMINIO>:443 -servername <DOMINIO> 2>/dev/null | openssl x509 -noout -enddate`).
- [ ] **Pide:** respaldo del sitio anterior (BD y archivos) si existe. Lleva datos
      personales: carpeta con permisos 700 fuera de Documentos/iCloud y la borra el usuario.

## F · Correo del dominio

- [ ] **Comprueba** quién hospeda el MX y qué autoriza el SPF:
      ```bash
      dig MX <DOMINIO> +short
      dig TXT <DOMINIO> +short | grep -i spf
      dig TXT default._domainkey.<DOMINIO> +short
      dig TXT _dmarc.<DOMINIO> +short
      ```
      *Por qué:* los formularios salen como `no-reply@<DOMINIO>` por el sendmail del hosting;
      si el SPF termina en `-all` y no autoriza al servidor, caen en spam o se rechazan. En el
      caso, el MX era Microsoft 365 y el SPF solo autorizaba a Outlook.
- [ ] **Pide:** quién administra el correo del cliente (para coordinar SPF/DKIM) y los
      destinatarios de cada formulario y de los avisos del sistema. Al entregar, ningún
      buzón personal del equipo de desarrollo.
- [ ] **Regla:** sin SMTP mientras el hosting entregue y el SPF lo autorice. Si hace falta
      SMTP, la credencial la pone el usuario en el servidor.

## G · Hosting de producción

- [ ] **Pide:** proveedor, tipo de panel y acceso SSH al destino (misma técnica de llave).
- [ ] **Comprueba** el docroot real, nunca deducido: un archivo sonda con nombre aleatorio
      pedido por HTTPS y, en cPanel,
      `uapi --output=json DomainInfo single_domain_data domain=<DOMINIO>`. *Por qué:* en el
      caso, `~/public_html` era el WordPress vivo de otro cliente y el dominio vivía en una
      subcarpeta suya.
- [ ] **Pide y anota:** otros sitios en la misma cuenta. Son intocables: `--path` en todo
      `wp` y guarda de `table_prefix` en cada bloque que escribe.
- [ ] **Comprueba:** PHP web frente a CLI, versión de MySQL (8 frente a 5.7 cambia las
      colaciones), caché de página disponible y `.well-known`. Auditoría completa en F12:
      [`auditoria-hosting.md`](../../wp-migracion/references/auditoria-hosting.md) (skill `wp-migracion`).

## H · Ajustes base del staging (anótalos para revertirlos al lanzar)

- [ ] `wp --path=<WP_PATH> option update blog_public 0`, zona horaria del cliente
      (`timezone_string`) y enlaces permanentes `/%postname%/`.
- [ ] IndexNow de Yoast Premium apagado: ignora `blog_public`.
- [ ] Relleno de WordPress (entrada y página de ejemplo): se borra con permiso y en su propio
      comando, después de leer el anterior. Nunca encadenado detrás de otro. *Por qué:* si el
      comando previo falla con una página de error de PHP, el fallo pasa inadvertido y el borrado
      se ejecuta igual; además, el clasificador de permisos bloquea los borrados encadenados.
- [ ] Todo lo anterior va a la «Lista de lanzamiento» del `CLAUDE.md`.

## I · Proyecto y memoria

- [ ] `CLAUDE.md` en la raíz desde [`CLAUDE.md`](CLAUDE.md) de estas plantillas, con los marcadores rellenos.
- [ ] Memorias semilla ([`memorias-semilla.md`](memorias-semilla.md)): usuario, proyecto, marca, accesos
      sin secretos, trampas técnicas y, si las hay, sesiones paralelas.
- [ ] Carpeta de scripts y copia de trabajo del hijo bajo git (`<PROYECTO>/wp/`).
- [ ] Lista de permisos del proyecto con patrones genéricos de solo lectura; nunca comandos
      con URL firmadas o tokens.

## J · Lo que entregas al terminar el día 1

1. **Informe** con la tabla «Stack / Contenido / Usuarios / Extras / URL y cabeceras de caché»
   y **la pregunta que puede cambiar el plan**.
2. **Acceso** funcionando en `BatchMode`.
3. **Auditoría del prototipo** lanzada o entregada, con su procedencia (propuestos,
   confirmados y sin verificar): [`auditar-prototipo.js`](workflows/auditar-prototipo.js).
4. **Decisiones de F1 redactadas** con recomendación (editor V4 o V3 con evidencia del
   esquema, Hello + hijo, menú clásico, plugin de idiomas y esquema de URL):
   [`plan-con-decisiones.md`](plan-con-decisiones.md), variante A.
5. `blog_public = 0` comprobado.
