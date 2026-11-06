# Correo y DNS al cambiar de hosting (D7)

El correo de los formularios y de WordPress suele romperse en silencio al cambiar de
servidor: el sitio sigue «enviando», pero los mensajes caen en spam o rebotan. En el
caso de referencia fallaban a la vez SPF, DKIM y DMARC, el cPanel se quedaba en local
el correo del propio dominio y los cambios DNS hechos en cPanel no llegaban a los
servidores de nombres públicos. **El correo bloquea el lanzamiento, no el cambio de
servidor:** mientras no esté resuelto, los envíos se guardan en Elementor › Envíos
(acción «save-to-database» del formulario).

---

## El modelo que funcionó: sendmail del hosting, sin SMTP

- Remitente `«<Marca>» <no-reply@<dominio en uso>>`, **derivado de `home_url()`**: sigue
  al sitio cuando cambia de dominio sin tocar nada.
- `Sender` del sobre igual al `From` (el `-f` de sendmail) en `phpmailer_init`. Sin
  `Sender`, `wp_mail()` sale con el sobre del usuario del sistema
  (`<usuario>@<servidor del hosting>`): el SPF de ese dominio puede pasar, pero **no está
  alineado** con el From y no cuenta para DMARC.
- `wp_mail_from` sustituye solo el `wordpress@…` por defecto y `wp_mail_from_name` el
  nombre «WordPress», para que los avisos del núcleo salgan como los formularios.
- El código vive en el tema hijo (módulo de formularios). Patrón y prueba en seco de
  los formularios: skill `elementor4-atomico`, `../../elementor4-atomico/references/formularios.md`.
- **Por qué sin SMTP:** el diagnóstico correcto es «¿el SPF del dominio del From
  autoriza a este servidor?», no «¿hay SMTP?». Añadir SMTP mete una credencial en el
  servidor para un problema que se arregla en el DNS. Si alguna vez hace falta SMTP (o un
  proveedor transaccional), la clave la pega el usuario en el servidor o en el wp-admin,
  **nunca por chat**.

---

## Qué se rompe al cambiar de servidor (diagnóstico sin tocar nada)

| Síntoma | Causa | Cómo verlo |
|---|---|---|
| SPF **FAIL** | el TXT del dominio termina en `-all` y solo autoriza al proveedor de correo (p. ej. `include:spf.protection.outlook.com`), no la IP del nuevo servidor | `uapi EmailAuth validate_current_spfs domain=<DOMINIO>` («mechanism '-all' matched»); `dig +short TXT <DOMINIO> @<NS_AUTORITATIVO>` |
| DKIM **FAIL** | el `default._domainkey` publicado no coincide con la clave privada del servidor (`MISMATCH`) | `uapi EmailAuth validate_current_dkims domain=<DOMINIO>` |
| DMARC **FAIL** | falla por las dos vías; con `p=none` no se rechaza por política, pero Gmail y Outlook lo mandan a spam y algunos filtros aplican el SPF `-all` sin excepciones | `dig +short TXT _dmarc.<DOMINIO> @<NS_AUTORITATIVO>` |
| Lo que el servidor manda a `*@<DOMINIO>` rebota («No Such User Here») | cPanel trata el dominio como correo **local** (`mxcheck: local`, `alwaysaccept: 1`) aunque el MX apunte fuera (Microsoft 365, Google) | `uapi --output=json Email list_mxs domain=<DOMINIO>` |
| El remitente sigue siendo el del staging | `email_from` guardado en los formularios | search-replace del dominio sin esquema (runbook F5) |

Guarda el estado antes de cambiar nada (sirve para volver atrás, `rollback.md` R5):
`dns-antes.txt` del runbook F0 y, en cPanel, un volcado de los TXT/MX de la zona
(`uapi --output=json DNS parse_zone zone=<DOMINIO>`, campos `*_b64` en base64) a
`~/<PREFIJO>-respaldos/dns-correo-antes-<TS>.txt`.

Script de lectura para todo esto: `../scripts/dns-correo.sh`.

---

## El arreglo: tres cambios sin guardar ninguna credencial

Se coordinan con quien gestione el correo del cliente (M365, Google): son sus registros.

1. **SPF: editar el TXT que ya existe, nunca crear un segundo.** Dos registros
   `v=spf1` en el mismo nombre invalidan el SPF entero.
   Valor nuevo: `v=spf1 ip4:<IP_SERVIDOR> include:<include del proveedor de correo> -all`
   (solo se añade la IP; el proveedor sigue autorizado igual).
2. **DKIM: publicar la clave «sugerida» por cPanel** (Email Deliverability; por API,
   `uapi EmailAuth enable_dkim domain=<DOMINIO>`). El selector `default` es solo del
   hosting: no afecta al DKIM del proveedor de correo (M365 usa `selector1`/`selector2`).
   La clave pública no es secreta.
3. **Enrutamiento remoto** cuando el MX está fuera: cPanel › Email Routing › **Remote Mail
   Exchanger** (`uapi Email set_always_accept domain=<DOMINIO> mxcheck=remote`). Este es
   un ajuste del propio servidor (Exim), no del DNS: no depende de la propagación.

Por API (cPanel), el SPF es `uapi EmailAuth install_spf_records domain=<DOMINIO> record='v=spf1 ip4:<IP_SERVIDOR> include:<…> -all'`.
Cada uno devuelve `"status":1`; comprueba después con `validate_current_spfs`,
`validate_current_dkims` y `list_mxs`.

**DMARC:** en el caso de referencia estaba en `p=none` sin `rua` (no llegan informes).
Cambiar la política es decisión del dueño del correo, no de la migración.

---

## La trampa: el Zone Editor de cPanel no es siempre el DNS público

En el caso de referencia (Bluehost), `enable_dkim` e `install_spf_records` respondieron
`"status":1` y la zona local de cPanel cambió (serial más nuevo), pero los NS
autoritativos siguieron sirviendo la zona vieja: el serial SOA público no se movió en
más de dos minutos de consultas cada 25 s. En ese hosting la zona pública del dominio se gestiona desde el panel del
dominio (Domains › DNS), no desde la zona local de cPanel.

**Regla:** después de cualquier cambio DNS, pregunta al **autoritativo**, no a un
resolver ni al panel:
```bash
NS=$(dig +short NS <DOMINIO> | head -1)                 # <NS_AUTORITATIVO>
dig +short SOA <DOMINIO> @"$NS"                          # ¿avanzó el serial?
dig +short TXT <DOMINIO> @"$NS"                          # ¿sale el SPF nuevo?
dig +short TXT default._domainkey.<DOMINIO> @"$NS"
```
Compara con el serial local de cPanel (`uapi DNS parse_zone`, SOA en base64). Si el
público no avanza en unos minutos (`../scripts/dns-correo.sh esperar` repite la consulta
cada 25 s), **no des el correo por arreglado**: prepara para el usuario un archivo con
los registros exactos y que los ponga él en el panel del dominio o del registrador.

### Plantilla del archivo para el usuario

```markdown
# <DOMINIO>: registros DNS para que los formularios no caigan en spam

Dónde: panel del dominio (<ruta del panel del proveedor>) › DNS.
El cPanel ya los tiene, pero sus cambios no llegan a los DNS públicos
(<NS_AUTORITATIVO> sirve la zona vieja, serial <serial>).

## 1. SPF: EDITAR el TXT que ya existe en @ (no crear un segundo SPF)
- Tipo: TXT · Host: @ (<DOMINIO>)
- Valor antes: <valor actual>
- Valor nuevo: v=spf1 ip4:<IP_SERVIDOR> include:<…> -all

## 2. DKIM: EDITAR el TXT default._domainkey
- Tipo: TXT · Host: default._domainkey
- Valor nuevo (una sola línea): v=DKIM1; k=rsa; p=<clave pública del servidor>
  (no es secreta; el selector «default» no afecta al DKIM de <proveedor de correo>)

## Comprobación (de minutos a unas horas)
    dig +short TXT <DOMINIO> @<NS_AUTORITATIVO>
    dig +short TXT default._domainkey.<DOMINIO> @<NS_AUTORITATIVO>
Cuando salgan los valores nuevos, avísame y hago la prueba final con un envío
SOLO a tu buzón (con tu permiso).

## Ya hecho en el servidor
- Enrutamiento de correo en «Remote».
- Respaldo del estado anterior: ~/<PREFIJO>-respaldos/dns-correo-antes-<TS>.txt
```
Se le envía el archivo (no solo el texto en el chat) y se explica por qué no se pudo
hacer desde cPanel.

---

## Inventario de destinatarios (antes de la entrega)

`admin_email` no es el único destino. Cada plugin guarda los suyos:
- `wp option list --search='*email*'`; avisos de plugins de seguridad
  (`sg_security_notification_emails` en el caso de referencia seguía con un correo
  personal del desarrollador y su informe semanal fallaría el SPF), de SEO y de
  formularios; `email_to` de **cada** formulario en **cada** idioma.
- `wp db search '@gmail.com'` (o el dominio personal que toque): los correos personales
  del equipo no se quedan en producción.
- Cambiar `admin_email` por script sin disparar el flujo de confirmación (y sin avisar
  a la dirección vieja), solo cuando el dueño lo pide:
  `add_filter( 'send_site_admin_email_change_email', '__return_false' ); update_option( 'admin_email', '<ADMIN_EMAIL>' ); delete_option( 'new_admin_email' );`
  Comprobación: `wp option get admin_email` y que no exista `new_admin_email`.

Un destino del propio `<DOMINIO>` rebota mientras el enrutamiento siga en «local».

---

## Pruebas: en seco siempre; real, solo al buzón del usuario y con su permiso

- **En seco (servidor nuevo):** `../scripts/correo-seco.php` arma un aviso del núcleo y,
  justo antes de enviarlo, vacía los destinatarios (`phpmailer_init` a `PHP_INT_MAX` +
  `clearAllRecipients()`): muestra `From`, `FromName`, `Sender`, `Mailer`, si el dominio
  del `From` es el de `home_url()` y si el `Sender` está alineado, y el `sendmail_path`
  del hosting. El fallo del envío es el esperado. Los formularios, con la sonda de
  `elementor4-atomico` (`../../elementor4-atomico/scripts/probe-forms-seco.php`, con
  `E4_CABECERAS=1` para ver su `From`/`Sender`): validación por casos (válido, trampa
  llena, demasiado rápido, sin JS, sin email, email sin TLD) y correo simulado con
  `pre_wp_mail`, una vez por idioma.
- **Nunca** un envío real de un formulario con datos válidos: los destinatarios son del
  cliente desde el primer día.
- **Prueba final de entregabilidad:** cuando el `dig` al autoritativo muestre los valores
  nuevos, un único envío al buzón del usuario, con su permiso explícito. En «Mostrar
  original» deben salir `spf=pass`, `dkim=pass` y `dmarc=pass`.

---

## Volver atrás (R5)

Restaurar los valores de `dns-antes.txt` (SPF, DMARC y el DKIM `default` anteriores),
enrutamiento otra vez en **Local** si así estaba, y comprobar con `dig` al
autoritativo. Ver `rollback.md`.
