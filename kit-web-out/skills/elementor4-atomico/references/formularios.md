# Formularios: widget Form de Pro dentro de V4, antispam, correo y pruebas en seco

Cómo montar formularios de contacto en un sitio Elementor 4 atómico que funcionen
con caché de página, sin captcha de terceros, con el correo del hosting y sin mandar
nunca un envío de prueba a los destinatarios del cliente.

Verificado sobre Elementor 4.3.0 · Elementor Pro 4.2.3 · Polylang Pro 3.8.6 · WP 7.1.
Las rutas `archivo.php:NN` son de esas versiones.

**Convención de los fragmentos.** Son patrones genéricos: `pfx_` / `pfx-` / `data-pfx-*` es el
`<PREFIJO>` (en el caso de referencia, `hm`); `c_` y `d_` son los prefijos de campo de sus
dos formularios (consumidores y distribuidores). Renómbralos. El código real de HomeMaker,
con `hm`, está en `../../wp-elementor-replica/ejemplos-homemaker/tema-hijo/homemaker-child/inc/formularios.php`.

## Lo primero, siempre

1. **Nunca envíes un formulario real con datos válidos**, ni hagas un POST a
   `admin-ajax.php` con datos válidos. Los destinatarios son los del cliente desde el
   primer día. Se prueba en seco (§8). El envío real desde el formulario lo hace el
   usuario, avisado de que llega al cliente; una prueba de entrega de Claude va solo
   al buzón del usuario y con su permiso.
2. **Widget Formulario de Pro (V3) dentro de un `e-flexbox`**, no el `e-form` atómico (§1).
3. **Remitente `no-reply@<DOMINIO>` derivado de `home_url()`** y remitente del sobre
   (`Sender`) igual al `From` (§7). Así sigue al sitio cuando cambie de dominio.
4. **Sin SMTP mientras el sendmail del hosting entregue y el SPF del dominio lo
   autorice.** Si alguna vez hace falta SMTP, la credencial la pone el usuario en un
   archivo del servidor fuera del repositorio. Nunca pasa por el chat.
5. **El correo bloquea el lanzamiento, no la migración.** Mientras SPF/DKIM no estén
   bien, los envíos quedan guardados en Elementor › Envíos (§9).

## 1. Por qué el widget Form de Pro (V3) y no el `e-form` atómico

El `e-form` atómico (experimento `e_pro_atomic_form`, activo por defecto pero con
estado «Development» en Pro 4.2.3) envía correo, pero se descartó tras una
investigación de solo lectura:

| | `e-form` (V4) | Widget Form de Pro (V3) |
|---|---|---|
| Nonce | lo exige y va impreso en el HTML (`atomicFormsSendForm`): la caché de página lo deja caducar (vida por defecto 86 400 s) | no lo necesita (`ajax-handler.php`: «No nonce is required») |
| Validación en servidor | ninguna: solo la del navegador (`required`, `type=email`) | obligatorios y email (`Form_Record::validate`, `sanitize_email`) |
| Antispam | sin honeypot ni campo oculto; solo el filtro `elementor_pro/atomic_forms/spam_check`, y los campos trampa acabarían en `[all-fields]` | campo nativo `honeypot`, que se quita del registro antes del correo; campo `hidden` |
| Id de campo | `_cssid` aleatorio en cada petición si no lo fijas | `custom_id` estable |
| Hooks | `elementor_pro/atomic_forms/*` | `elementor_pro/forms/*` (validación por tipo, cuerpo, cabeceras, registro) |
| Copia en BD | acción `collect-submissions` | acción `save-to-database` («Collect Submissions») |

Revisa la decisión en cada versión mayor de Pro: comprueba si `e-form` ya valida en
servidor, si ya no depende de un nonce horneado y si trae honeypot. Mientras no, V3.
El editor muestra un aviso de promoción del «Atomic form» sobre el widget V3: es
solo cosmético.

## 2. Montaje dentro del maquetado V4

- **Envoltorio:** el widget `form` va dentro de un `e-flexbox`. La base de
  `e-flexbox` es `flex-direction: row` y los inputs llevan `size="1"`: sin
  `.elementor .elementor-widget-form.pfx-form { width: 100%; }` el widget se encoge al
  ancho de su contenido.
- **Estilos:** las clases globales V4 no llegan a un widget V3. Colores, bordes,
  radios, tipografías, gaps y colores de mensaje van en los ajustes del propio widget
  (el cliente los edita); lo que el widget no expone va en el CSS del child con una
  clase propia puesta en `_css_classes` (`pfx-form`). El CSS por elemento del widget
  sale con especificidad alta (`.elementor-<ID> .elementor-element.elementor-element-<id> …`,
  hasta (0,6,0) en el borde del campo), así que el foco y el estado deshabilitado del
  child necesitan `!important`.
- **Ajustes desde un JSON probado.** Guarda la configuración que validaste en un
  `forms.json` (un elemento completo por formulario) y en el script de construcción
  sobrescribe solo lo que depende del entorno: `email_to`, `email_from`,
  `form_metadata`, `_css_classes`, `_attributes`. Guarda con
  `documents->get( $id )->save( [ 'elements' => … ] )` y `--user=1`.
- **`custom_id`:** solo `A-z 0-9 _`. El id HTML es `form-field-<custom_id>`, así que
  con dos formularios en la misma página los ids tienen que ser únicos entre ambos:
  prefijos `c_` y `d_`.
- **`_id` de cada campo del repetidor:** Elementor no lo genera al guardar. Pon tú 7
  caracteres hexadecimales por campo.
- **Idiomas:** cada documento guarda sus propios ajustes del formulario (el envío se
  resuelve por `post_id` + id del widget), así que la versión del idioma 2 tiene sus
  etiquetas, mensajes, asunto y destinatarios. Mantén los mismos `custom_id` en ambos
  idiomas para que hooks y JS funcionen sin cambios. Los Envíos se indexan por
  `post_id` + id del elemento: repetir ids de widget entre idiomas no choca.

Esqueleto de un widget (abreviado; los estilos van en el mismo `settings`):

```json
{"id":"f0c0a01","elType":"widget","widgetType":"form","isInner":false,"elements":[],"settings":{
 "form_name":"Consumidores","form_id":"form-consumidores",
 "form_fields":[
  {"_id":"c0a0001","custom_id":"c_nombre","field_type":"text","field_label":"Nombre","placeholder":"Tu nombre completo","required":"","width":"50","width_tablet":"50","width_mobile":"100"},
  {"_id":"c0a0002","custom_id":"c_email","field_type":"email","field_label":"Email","placeholder":"tu@email.com","required":"true","width":"50","width_tablet":"50","width_mobile":"100"},
  {"_id":"c0a0004","custom_id":"c_mensaje","field_type":"textarea","field_label":"Mensaje","required":"true","rows":5,"width":"100"},
  {"_id":"c0a0005","custom_id":"c_hp","field_type":"honeypot","field_label":"No rellenes este campo"},
  {"_id":"c0a0006","custom_id":"c_ts","field_type":"hidden","field_label":"Tiempo","field_value":""}],
 "show_labels":"true","mark_required":"","label_position":"above","input_size":"sm","button_size":"sm","button_width":"100",
 "button_text":"Enviar mensaje","button_align":"start","step_type":"none",
 "submit_actions":["email","save-to-database"],
 "email_to":"<DESTINATARIOS>","email_subject":"Mensaje de un consumidor — <MARCA>","email_content":"[all-fields]",
 "email_from":"no-reply@<DOMINIO>","email_from_name":"<MARCA>","email_reply_to":"c_email",
 "email_to_cc":"","email_to_bcc":"","form_metadata":[],"email_content_type":"plain",
 "form_validation":"","custom_messages":"yes",
 "success_message":"¡Gracias! Recibimos tu mensaje y te respondemos pronto.",
 "error_message":"No pudimos enviar el mensaje. Inténtalo de nuevo o escríbenos directamente.",
 "server_message":"…","invalid_message":"…","required_field_message":"Este campo es obligatorio.",
 "_css_classes":"pfx-form",
 "_attributes":"data-pfx-enviando|Enviando…\ndata-pfx-error|No pudimos enviar el mensaje. Inténtalo de nuevo o escríbenos directamente."}}
```

## 3. Claves que engañan

| Clave | Lo correcto | Qué pasa si no |
|---|---|---|
| `email_reply_to` | el `custom_id` del campo: `"c_email"` | con un shortcode `[field id="c_email"]` sale `Reply-To:` vacío (`get_reply_to()` compara contra ids de campo después de expandir shortcodes) |
| `required_field_message` | solo se aplica con `form_validation: "custom"` | con `""` (validación del navegador) el texto de «obligatorio» del servidor sale en el inglés de Pro. El caso de referencia dejó `""` y lo tradujo con `gettext` (§5) |
| `custom_messages` | `"yes"` y **todos** los mensajes rellenos (`success_message`, `error_message`, `server_message`, `invalid_message`, `required_field_message`) | Pro no trae paquete es_*: cualquier mensaje por defecto sale en inglés |
| `add_error()` | `$ajax_handler->add_error( $id, $mensaje )` | `add_error_message()` solo **no** bloquea: las acciones se ejecutan y el correo sale |
| `form_metadata` | `[]` | el cuerpo termina con «Date / Time / Page URL» en inglés |
| `_attributes` (Avanzado › Atributos) | texto plano, una pareja `clave\|valor` por línea (`Utils::parse_custom_attributes( $s, "\n" )`) | — |
| `submit_actions` | `["email","save-to-database"]` | `save-to-database` es «Collect Submissions»: copia en Elementor › Envíos |
| `email_to_bcc` | — | cada Bcc es un `wp_mail` aparte |
| `form_name` | nombre del formulario **y** su `aria-label` | un lector de pantalla lo lee en voz alta: tradúcelo en el idioma 2 |
| Envelope | `wp_mail` llama a `setFrom( …, false )` y **no** fija el `Sender` | sin el `phpmailer_init` del §7, el sobre sale con el usuario del sistema |
| Honeypot | tipo `honeypot`: si trae valor, `add_error( 'invalid_form', … )`; si está vacío, se quita del registro | Pro responde con error al bot (un script propio suele devolver un 200 silencioso). Aceptado |

Orden de validación (`form-record.php`): por cada campo, comprobación de obligatorio
→ `elementor_pro/forms/validation/<field_type>` con `($field, $record, $ajax_handler)`
→ `elementor_pro/forms/validation` con `($record, $ajax_handler)` → `validate()`
devuelve `empty( $ajax_handler->errors )`. Si hay errores no corre **ninguna** acción
(ni correo ni Envíos) y el visitante ve el `error_message` del widget, editable.

Otros hooks: `elementor_pro/forms/wp_mail_fields`, `…/wp_mail_headers`,
`…/wp_mail_message`, `…/new_record`, `…/process`, `…/validation/skip_all`.

## 4. Antispam sin terceros: honeypot + tiempo mínimo medido en el navegador

- **Trampa:** campo nativo `honeypot` (se pinta `type=text` con
  `display:none !important`).
- **Tiempo mínimo** (3 s en el caso de referencia, como el prototipo): un campo
  `hidden` cuyo `custom_id` termina en `_ts`. Un JS escribe al enviar los
  **milisegundos que la página lleva abierta**: una duración, no una marca de tiempo.
  Un timestamp servido en el HTML se congela en la caché de página, y comparar el
  reloj del visitante con el del servidor bloquea para siempre a quien tiene el reloj
  adelantado (fallo real del prototipo).
- El listener va **en fase de captura sobre `document`**: así corre antes que el
  manejador jQuery de Pro que arma el `FormData`.
- En servidor, `add_error()` si no llega al mínimo y **siempre** `remove_field()`, para
  que el campo no aparezca en `[all-fields]` ni en Envíos.
- El script se encola solo en páginas que pintan un widget `form`.

```php
const PFX_FORM_MIN_MS = 3000;

add_action( 'elementor_pro/forms/validation', function ( $record, $ajax_handler ) {
	foreach ( $record->get( 'fields' ) as $id => $field ) {
		if ( 'hidden' !== $field['type'] || '_ts' !== substr( (string) $id, -3 ) ) {
			continue;
		}
		if ( (int) $field['raw_value'] < PFX_FORM_MIN_MS ) { // vacío (sin JS) también cuenta como demasiado rápido
			$ajax_handler->add_error( $id, 'too_fast' );
		}
		$record->remove_field( $id );
	}
}, 20, 2 );

add_action( 'wp_enqueue_scripts', function () {
	$rel = '/js/pfx-form-timer.js';
	wp_register_script( 'pfx-form-timer', get_stylesheet_directory_uri() . $rel, [], (string) filemtime( get_stylesheet_directory() . $rel ), [ 'in_footer' => true ] );
} );
add_action( 'elementor/frontend/widget/before_render', function ( $widget ) {
	if ( 'form' === $widget->get_name() ) {
		wp_enqueue_script( 'pfx-form-timer' );
	}
} );
```

```js
(function () {
	var t0 = Date.now();
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || !form.classList || !form.classList.contains('elementor-form')) { return; }
		var campos = form.querySelectorAll('input[type="hidden"][name^="form_fields["][name$="_ts]"]');
		for (var i = 0; i < campos.length; i++) { campos[i].value = String(Date.now() - t0); }
	}, true); // captura: antes que el manejador de Pro
})();
```

El error `too_fast` cae en el grupo oculto, así que el visitante solo ve el
«Error del formulario» general del widget. Si se quiere un mensaje propio, la
investigación propuso `wp_send_json_error( [ 'message' => $record->get_form_settings( 'invalid_message' ), 'errors' => [], 'data' => [] ] )`
(misma forma JSON que `Ajax_Handler::send()`); no se usó.

## 5. Mensajes, idioma y textos que el widget no expone

**Email escrito pero inválido** («ana@example»): el `type=email` del navegador lo deja
pasar, el servidor lo vacía con `sanitize_email()` y Pro responde «Este campo es
obligatorio», que confunde. El hook por tipo corre justo después de la comprobación
de obligatorio de ese campo y sobrescribe su error:

```php
add_action( 'elementor_pro/forms/validation/email', function ( $field, $record, $ajax_handler ) {
	$escrito = trim( (string) ( $field['raw_value'] ?? '' ) );
	if ( '' === $escrito || is_email( $escrito ) ) {
		return;
	}
	$lang = function_exists( 'pll_get_post_language' ) && isset( $_POST['post_id'] ) ? pll_get_post_language( (int) $_POST['post_id'] ) : '';
	$ajax_handler->add_error( $field['id'], 'en' === $lang ? 'Check the email address.' : 'Revisa la dirección de correo.' );
}, 10, 3 );
```

**Cadenas del servidor en inglés.** Filtro `gettext_elementor-pro` con un mapa exacto
(localízalas con `grep -rn "esc_html__( 'This field is required" elementor-pro/modules/forms`).
Durante el envío (admin-ajax) `pll_current_language()` no es fiable: el idioma sale
de la página que lleva el formulario.

```php
add_filter( 'gettext_elementor-pro', function ( $traduccion, $texto ) {
	$lang = function_exists( 'pll_current_language' ) ? pll_current_language() : '';
	if ( wp_doing_ajax() && isset( $_POST['post_id'] ) && function_exists( 'pll_get_post_language' ) ) {
		$lang = pll_get_post_language( (int) $_POST['post_id'] );
	}
	if ( 'en' === $lang ) {
		return $traduccion; // o un mapa EN propio con la redacción del prototipo
	}
	$es = [
		'This field is required.'                             => 'Este campo es obligatorio.',
		'Your submission failed because of a server error.'   => 'No pudimos enviar el mensaje.',
		'Your submission failed because of an error.'         => 'No pudimos enviar el mensaje.',
		'Your submission failed because the form is invalid.' => 'No pudimos enviar el mensaje. Recarga la página e inténtalo de nuevo.',
	];
	return $es[ $texto ] ?? $traduccion; // sin coincidencia, devuelve la original
}, 10, 2 );
```

Con Polylang, además, el mu-plugin `pll_preferred_language` que usa el idioma de
`post_id` para `action=elementor_pro_forms_send_form` (ver
`../../wp-polylang/references/polylang-elementor.md` y `tema-hijo.md`).

**«Enviando…» y fallo de red, editables.** El widget no tiene control para esos textos
y, ante un fallo de red, Pro pinta la palabra de jQuery («error», «timeout») y deja
el botón aplanado. Patrón:

- Los textos van en Avanzado › Atributos del widget:
  `data-pfx-enviando|Enviando…` y `data-pfx-error|…`.
- El JS copia `data-pfx-enviando` al `.elementor-button-content-wrapper` **solo si
  existe**, y el CSS lo pinta con `content: attr(data-pfx-enviando)`; sin atributo, el
  respaldo es CSS por idioma (`:lang(en)` → «Sending…»). Un JS que escribiera siempre
  un texto anularía el `:lang()`.
- `jQuery(document).ajaxError` filtrado por `settings.data.get('action') === 'elementor_pro_forms_send_form'`
  sustituye el último `form > .elementor-message-danger` por `data-pfx-error` (o por el
  respaldo del idioma de `document.documentElement.lang`) y restaura el `innerHTML`
  original del botón. Requiere que `settings.data` sea un `FormData`.

```css
.elementor .pfx-form .elementor-form { opacity: 1 !important; }            /* Pro lo baja a .45 al enviar */
.elementor .pfx-form .elementor-form-spinner,
.elementor .pfx-form .elementor-form-waiting .elementor-button-text { display: none; } /* <i class="fa fa-spinner"> sin Font Awesome cargado */
.elementor .pfx-form .elementor-form-waiting .elementor-button-content-wrapper::after { content: "Enviando…"; }
.elementor .pfx-form:lang(en) .elementor-form-waiting .elementor-button-content-wrapper::after { content: "Sending…"; }
.elementor .pfx-form .elementor-form-waiting .elementor-button-content-wrapper[data-pfx-enviando]::after { content: attr(data-pfx-enviando); }
.elementor .pfx-form .elementor-message::before { display: none; }         /* icono de eicons, que no se carga */
```

Otras trampas de CSS para igualar el prototipo al píxel: el `textarea` es
`inline-block` (deja unos 7 px debajo), Elementor pinta su propio triángulo en los
`select` (`.select-caret-down-wrapper`, ocúltalo y usa `appearance:auto; min-height:0`)
y el placeholder necesita `opacity:1`.

**Formulario del idioma 2.** El correo interno sigue en el idioma del cliente: asunto
en el idioma base con una marca « (EN)» y una línea en el cuerpo que avisa del
idioma, para que el equipo sepa en qué idioma contestar. `form_name` (nombre
accesible) traducido. Si el cliente quiere valores homogéneos en los `select`, usa
`Etiqueta EN|valor ES` en `field_options`.

**Autocompletado** (WCAG 1.3.5) sin tocar el diseño ni el antispam:

```php
add_filter( 'elementor_pro/forms/render/item', function ( $item, $index, $form ) {
	$mapa = [ 'c_nombre' => 'name', 'c_email' => 'email', 'd_empresa' => 'organization' ];
	$id   = $item['custom_id'] ?? '';
	if ( isset( $mapa[ $id ] ) ) {
		$form->add_render_attribute( 'input' . $index, 'autocomplete', $mapa[ $id ] );
	}
	return $item;
}, 10, 3 );
```

## 6. Cuerpo del correo igual al del prototipo, sin pisar al cliente

`[all-fields]` imprime también los opcionales vacíos («Etiqueta: ») y Pro no tiene
variante que los omita. Reescribe el cuerpo en `elementor_pro/forms/wp_mail_fields`
**solo si** el contenido del widget sigue siendo `[all-fields]`; si el cliente lo
personaliza en Elementor, se respeta.

```php
function pfx_form_dominio() {
	return preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
}
add_filter( 'elementor_pro/forms/wp_mail_fields', function ( $campos, $record ) {
	if ( str_starts_with( (string) ( $campos['email_from'] ?? '' ), 'no-reply@' ) ) {
		$campos['email_from'] = 'no-reply@' . pfx_form_dominio(); // el remitente sigue al dominio en uso
	}
	if ( '[all-fields]' !== trim( (string) $record->get_form_settings( 'email_content' ) ) ) {
		return $campos;
	}
	$orden = [ 'nombre' => 'Nombre', 'email' => 'Email', 'mensaje' => 'Mensaje' ]; // clave = custom_id sin prefijo c_/d_
	$por_clave = [];
	$otros     = [];
	foreach ( (array) $record->get( 'fields' ) as $id => $campo ) {
		$valor = trim( (string) ( $campo['value'] ?? '' ) );
		if ( '' === $valor ) {
			continue;
		}
		$clave = preg_replace( '/^[a-z]_/', '', (string) $id );
		if ( isset( $orden[ $clave ] ) ) {
			$por_clave[ $clave ] = $orden[ $clave ] . ': ' . $valor;
		} else {
			$otros[] = trim( (string) ( $campo['title'] ?? $id ) ) . ': ' . $valor; // campos que añada el cliente
		}
	}
	$lineas   = array_merge( array_values( array_filter( array_map( fn( $k ) => $por_clave[ $k ] ?? null, array_keys( $orden ) ) ) ), $otros );
	/* el correo va en el idioma del cliente; si llega del formulario del idioma 2, se avisa para contestar en ese idioma */
	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	if ( $post_id && function_exists( 'pll_get_post_language' ) && 'en' === pll_get_post_language( $post_id ) ) {
		$lineas[] = 'Idioma: inglés (formulario de la web en inglés)';
	}
	$lineas[] = '';
	$lineas[] = '— Enviado desde ' . pfx_form_dominio() . ' el ' . wp_date( 'd/m/Y H:i' );
	$campos['email_content'] = implode( "\n", $lineas );
	return $campos;
}, 10, 2 );
```

Ante un «igual que el prototipo», lee el script de envío del prototipo en local **y**
la versión en producción (solo lectura), compara destinatarios, asunto, cuerpo,
remitente y `Reply-To`, y dile al usuario qué ya coincidía y qué cambiaste.

## 7. Correo del hosting sin SMTP: From y Sender del dominio

La pregunta correcta no es «¿hay SMTP?» sino «¿el SPF del dominio del `From` autoriza
a este servidor?». Antes de proponer SMTP, comprueba `ini_get( 'sendmail_path' )` y
si el sitio anterior entregaba en bandeja con el sendmail del hosting.

Todos los correos de WordPress (avisos al administrador, restablecer contraseña…)
salen igual que los formularios: `«<MARCA>» <no-reply@<DOMINIO>>`.

```php
/* solo sustituye los valores por defecto de WP: si un plugin o el widget fija otro, se respeta */
add_filter( 'wp_mail_from', function ( $from ) {
	return str_starts_with( (string) $from, 'wordpress@' ) ? 'no-reply@' . pfx_form_dominio() : $from;
} );
add_filter( 'wp_mail_from_name', function ( $nombre ) {
	return 'WordPress' === $nombre ? '<MARCA>' : $nombre;
} );
/* remitente del sobre = From (equivale al -f de sendmail): alinea SPF para DMARC */
add_action( 'phpmailer_init', function ( $mailer ) {
	if ( str_starts_with( (string) $mailer->From, 'no-reply@' ) ) {
		$mailer->Sender = $mailer->From;
	}
} );
```

- El dominio sale de `home_url()` sin `www.`: al migrar de `<STAGING_URL>` a
  `<DOMINIO>` el remitente cambia solo.
- **Inventario de destinatarios antes de entregar**, no solo `admin_email`: los de
  cada plugin (p. ej. `sg_security_notification_emails`), el `email_to` de cada
  formulario en cada idioma y los informes de plugins
  (`wp option list --search='*email*'`, `wp option list --search='*notification*'`).
  Ningún correo personal del equipo queda en producción.
- **Cambiar `admin_email` sin el flujo de confirmación** (solo si el dueño lo pide):
  ```php
  add_filter( 'send_site_admin_email_change_email', '__return_false' );
  update_option( 'admin_email', '<ADMIN_EMAIL>' );
  delete_option( 'new_admin_email' );
  ```
  Desde el panel el cambio queda pendiente en `new_admin_email` hasta confirmarlo por
  correo. Dilo en el resumen al usuario.
- **Privacidad:** «Collect Submissions» guarda datos personales en la BD
  (Elementor › Envíos). En el caso de referencia no se habló de retención: plantéalo
  al cliente.

## 8. Pruebas en seco

### A. Formularios de Pro guardados: validación por casos y correo simulado

Reconstruye el widget desde `_elementor_data` (`create_element_instance()` +
`get_settings_for_display()`), pasa cada caso por `Form_Record::validate()` con los
hooks desplegados en el child y ejecuta la acción `email` con `pre_wp_mail`
devolviendo `true`, que corta el envío y captura lo que se habría mandado. Fija
`$_POST['post_id']` a la página: la lógica de idioma del child lo lee de ahí, como en
un envío real.

Script: `../scripts/probe-forms-seco.php`. Descubre los widgets `form` de la página,
deduce los datos de prueba de `form_fields` y aborta si un plugin redefine `wp_mail()`
(con SMTP, `pre_wp_mail` podría no aplicarse):

```bash
E4_PAGINA=<ID> wp --path=<WP_PATH> --url=https://<DOMINIO> --user=1 eval-file ~/tmp-<PREFIJO>/probe-forms-seco.php
```

Casos, cada uno solo si su campo existe: `válido`, `trampa llena` (honeypot con valor),
`en 0,9 s` y `sin JS` (`<pre>_ts` a `900` o vacío), `sin email` y `email sin TLD`
(`ana@example`).

Resultado esperado: `hook de validación desplegado: sí` y `OK` en todos los casos
(el veredicto coincide con el esperado): `válido=true` **solo** en el caso válido, con
los campos visibles y sin `_hp` ni `_ts`; `trampa llena` → `{"invalid_form":…}`;
`en 0,9 s` y `sin JS` → error en `<pre>_ts`; `sin email` → obligatorio;
`email sin TLD` → «Revisa la dirección de correo.»; `correo simulado` con
`From: <MARCA> <no-reply@<DOMINIO>>` y el `Reply-To` del visitante. Ejecútalo con el
ID de la página de cada idioma y después de cada migración. La salida imprime los
destinatarios reales: no la pegues en documentos públicos.

### B. Correo del núcleo y cabeceras del sobre: vaciar destinatarios

`pre_wp_mail` corta antes de PHPMailer, así que no enseña el `Sender`. Para ver
`From`, `FromName`, `Sender` y `Mailer` reales, deja que WordPress arme el correo y
vacía los destinatarios en el último momento: PHPMailer no envía y `wp_mail_failed`
recibe el error esperado («Debes facilitar al menos una dirección de correo…» o su
equivalente en inglés). `probe-forms-seco.php` lo hace con `E4_CABECERAS=1`, también
con las cabeceras del formulario; el núcleo de la técnica es este:

```php
$visto  = [];
$fallos = [];
add_action( 'phpmailer_init', function ( $m ) use ( &$visto ) {
	$visto[] = [ 'From' => $m->From, 'FromName' => $m->FromName, 'Sender' => $m->Sender, 'Mailer' => $m->Mailer, 'ReplyTo' => array_keys( $m->getReplyToAddresses() ) ];
	$m->clearAllRecipients();
}, PHP_INT_MAX );
add_action( 'wp_mail_failed', function ( $e ) use ( &$fallos ) { $fallos[] = $e->get_error_message(); } );
wp_mail( 'prueba@example.com', 'Prueba núcleo', 'x' );
foreach ( $visto as $i => $v ) { echo "correo $i: " . wp_json_encode( $v ) . "\n"; }
echo 'envío cortado (esperado): ' . implode( ' | ', array_unique( $fallos ) ) . "\n";
echo 'sendmail: ' . ini_get( 'sendmail_path' ) . "\n";
```

### C. En el navegador, solo lo que no envía

- Envíos que **fallan** la validación (campos vacíos, email sin TLD): no corre ninguna
  acción. Lee `.elementor-message` y `.elementor-form-help-inline`.
- Fallo de red: DevTools › Network › Offline y enviar; debe salir el texto de
  `data-pfx-error` y el botón restaurado.
- `curl -s <url> | grep -o 'class="elementor-form"[^>]*>'` → un `<form>` por widget con
  `id`, `name` y `aria-label`; `grep -o 'data-pfx-enviando="[^"]*"'` y
  `grep -o 'autocomplete="[a-z]*"'`.

## 9. SPF, DKIM y DMARC

Con `From` y `Sender` en `no-reply@<DOMINIO>`, el servidor web tiene que estar
autorizado por el dominio. Sin eso, el correo de los formularios va a spam o lo
rechaza el filtro del destinatario (en el caso de referencia, un filtro corporativo
que aplica SPF sin excepciones). Sin `Sender`, el sobre sale con el usuario del
sistema: el SPF puede pasar, pero no alinea con el `From` para DMARC.

Lo que suele romperse al cambiar de hosting, sobre todo si el correo del dominio vive
fuera (Microsoft 365, Google):

- **SPF** `-all` que solo autoriza al proveedor de correo. Se **edita** el TXT existente
  añadiendo la IP del servidor (`v=spf1 ip4:<IP_SERVIDOR> include:<spf del proveedor> -all`);
  nunca un segundo registro SPF.
- **DKIM** del hosting publicado distinto de la clave del servidor (`MISMATCH`): se
  publica la clave que sugiere cPanel › Email Deliverability (selector `default`, no
  toca los selectores del proveedor de correo).
- **Enrutamiento de cPanel en «Local»** con el MX fuera: lo que el servidor manda a
  `@<DOMINIO>` (avisos a `admin_email`, destinatarios del propio dominio) se entrega
  en local y rebota. Va en «Remote Mail Exchanger».
- **Zonas editadas en cPanel que no llegan a los NS autoritativos**: comprueba siempre
  contra el autoritativo (`dig +short @<ns> <DOMINIO> TXT`), no contra un resolver.

Diagnóstico sin tocar nada: `uapi EmailAuth validate_current_spfs`,
`uapi EmailAuth validate_current_dkims`, `uapi Email list_mxs`. Los registros los
pone el usuario (o el cliente) en el panel del dominio; entrégale los valores
«antes / nuevo». Procedimiento completo, orden y reversión:
`../../wp-migracion/references/correo-dns.md`.

Prueba final: un único envío real, solo al buzón del responsable y con su permiso;
en «Mostrar original» deben salir `spf=pass`, `dkim=pass` y `dmarc=pass`.

## 10. Checklist

- [ ] Widget `form` V3 dentro de `e-flexbox`, con `width:100%` y clase propia.
- [ ] `custom_id` únicos en la página y `_id` de 7 hex en cada campo.
- [ ] `email_reply_to` = `custom_id` del email; `custom_messages: "yes"` con todos los
      mensajes; `form_metadata: []`.
- [ ] Honeypot nativo + `<pre>_ts` validado en servidor y quitado del registro.
- [ ] Email inválido con mensaje propio; cadenas de Pro traducidas; idioma desde `post_id`.
- [ ] «Enviando…» y error de red editables en Avanzado › Atributos.
- [ ] Remitente `no-reply@` derivado de `home_url()`, `wp_mail_from(_name)` solo sobre
      los valores por defecto, `Sender = From`.
- [ ] Prueba en seco A y B en cada idioma y tras cada migración.
- [ ] Inventario de destinatarios (formularios por idioma, plugins, `admin_email`).
- [ ] SPF/DKIM/DMARC comprobados contra el NS autoritativo antes de lanzar.

## Dudas abiertas

- El `Sender = From` se verificó en seco (prueba B), no con cabeceras de un correo
  entregado en el dominio final: en el caso de referencia el SPF/DKIM del dominio
  final quedó pendiente de DNS al cierre.
- Los mapas `gettext` dependen de las cadenas inglesas exactas de Pro y el
  `ajaxError` de que Pro envíe un `FormData`: revísalos tras cada actualización de Pro.
