<?php
/**
 * probe-forms-seco.php — Prueba EN SECO de los formularios de Elementor Pro (widget «form» V3)
 * guardados en una página: valida en memoria casos buenos y malos con los hooks desplegados en el
 * tema hijo, y simula el correo del caso válido (destinatario, asunto, cabeceras, cuerpo) SIN
 * enviar nada. Opcionalmente, muestra el remitente real que pondría PHPMailer.
 *
 * PROPÓSITO (síntoma → causa)
 *   Hay que demostrar antispam, validación y formato del correo (al construir, tras cambiar el
 *   dominio y tras migrar) sin mandar correos a los buzones reales del cliente ni ensuciar las
 *   Submissions: cualquier envío desde el navegador llega a los destinatarios de verdad.
 *   pre_wp_mail devolviendo true corta wp_mail() antes de enviar; Form_Record::validate() ejecuta
 *   los mismos hooks elementor_pro/forms/validation que un envío real.
 *
 * USO
 *   E4_PAGINA=<ID> wp --path=<WP_PATH> --url=https://<DOMINIO> --user=1 eval-file ~/tmp-<PREFIJO>/probe-forms-seco.php
 *   Repite con el ID de la página traducida para probar <IDIOMA_2> (mensajes y aviso de idioma).
 *   E4_FORMS=<id>,<id>   limita a esos widgets (por defecto, todos los «form» de la página).
 *   E4_CABECERAS=1       añade la parte 2: arma el correo con PHPMailer, vacía los destinatarios
 *                        justo antes de enviar (fallo esperado) e imprime From/FromName/Sender/ReplyTo.
 *   Salida esperada por formulario: «OK» en todos los casos (válido=true en «válido»; false en el resto,
 *   salvo «sin email» si el campo no es obligatorio);
 *   después «correo simulado: to=… | asunto=… | cabeceras=From: <MARCA> <no-reply@<DOMINIO>> / Reply-To: …».
 *
 * QUÉ EDITAR
 *   $FIJOS: valores de prueba por custom_id para campos con formato (tel, number, date, url,
 *   acceptance…) o selects cuyas opciones no sean una por línea. El resto se deduce del tipo.
 *   Convenciones del caso (ajústalas si tu formulario usa otras): honeypot nativo de Pro y un
 *   campo hidden cuyo custom_id termina en «_ts» con los milisegundos que la página lleva abierta
 *   (lo rellena el JS del hijo al enviar; el hook del hijo rechaza < 3000 ms y lo quita del correo).
 *
 * ESTADO
 *   Núcleo probado en HomeMaker (contacto/probe-antispam.php y migracion/probe-forms.php:
 *   pre_wp_mail, create_element_instance + get_settings_for_display, Form_Record + Ajax_Handler,
 *   acción 'email', $_POST['post_id'] y la parte 2 de correo/admin-y-prueba.php).
 *   Generalizado, no probado fuera de HomeMaker: descubrimiento automático de los widgets «form»,
 *   datos de prueba deducidos de form_fields (antes estaban escritos a mano), casos que solo se
 *   generan si existe el campo, veredicto esperado por caso y la guarda de wp_mail redefinido.
 *
 * RIESGOS
 *   - No envía nada mientras wp_mail() sea el del núcleo: si un plugin lo redefine (SMTP…), el
 *     script aborta, porque pre_wp_mail podría no aplicarse.
 *   - La parte 2 usa como destinatario prueba@example.com (dominio reservado) y vacía los
 *     destinatarios antes del envío: el fallo «debes facilitar al menos una dirección» es lo esperado.
 *   - La salida imprime los destinatarios reales del formulario: no la pegues en documentos públicos.
 *   - $_POST['post_id'] = página: los filtros del hijo (mensajes traducidos, aviso de idioma en el
 *     correo) leen el idioma de ahí, como en un envío AJAX real.
 */

use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Classes\Ajax_Handler;

if ( ! class_exists( Form_Record::class ) ) { WP_CLI::error( 'Elementor Pro (Forms) no está cargado.' ); }
$PAGINA = (int) getenv( 'E4_PAGINA' );
if ( ! $PAGINA ) { WP_CLI::error( 'Indica la página: E4_PAGINA=<ID> wp … eval-file probe-forms-seco.php' ); }

$FIJOS = [
	/* 'c_telefono' => '7875550100', */
];

/* ---------- guarda: wp_mail tiene que ser el del núcleo ---------- */
$rf = new ReflectionFunction( 'wp_mail' );
if ( ! str_ends_with( wp_normalize_path( (string) $rf->getFileName() ), 'wp-includes/pluggable.php' ) ) {
	WP_CLI::error( 'wp_mail() está redefinida en ' . $rf->getFileName() . ': esta prueba no puede garantizar que no se envíe nada.' );
}

$_POST['post_id'] = $PAGINA;
$capturado = [];
$corta     = function ( $r, $atts ) use ( &$capturado ) { $capturado[] = $atts; return true; };
add_filter( 'pre_wp_mail', $corta, 1, 2 );
echo 'hook de validación desplegado: ' . ( has_action( 'elementor_pro/forms/validation' ) ? 'sí' : 'NO' ) . "\n";

$data = json_decode( (string) get_post_meta( $PAGINA, '_elementor_data', true ), true );
if ( ! is_array( $data ) ) { WP_CLI::error( "La página #$PAGINA no tiene _elementor_data." ); }
$forms = [];
$buscar = function ( array $els ) use ( &$buscar, &$forms ) {
	foreach ( $els as $e ) {
		if ( ( $e['widgetType'] ?? '' ) === 'form' ) { $forms[ $e['id'] ] = $e; }
		$buscar( $e['elements'] ?? [] );
	}
};
$buscar( $data );
if ( getenv( 'E4_FORMS' ) ) { $forms = array_intersect_key( $forms, array_flip( array_map( 'trim', explode( ',', getenv( 'E4_FORMS' ) ) ) ) ); }
if ( ! $forms ) { WP_CLI::error( "No hay widgets «form» en #$PAGINA." ); }

$valor = function ( array $f ) use ( $FIJOS ) {
	$id = (string) ( $f['custom_id'] ?? '' );
	if ( array_key_exists( $id, $FIJOS ) ) { return (string) $FIJOS[ $id ]; }
	switch ( $f['field_type'] ?? 'text' ) {
		case 'email':    return 'prueba@example.com';
		case 'honeypot': return '';
		case 'hidden':   return str_ends_with( $id, '_ts' ) ? '5200' : (string) ( $f['field_value'] ?? '' );
		case 'textarea': return 'Mensaje de prueba';
		case 'select':
		case 'radio':
			$ops = preg_split( '/\R/', (string) ( $f['field_options'] ?? '' ) );
			return trim( (string) ( $ops[0] ?? '' ) );
		default:         return 'Prueba';
	}
};

$em      = \Elementor\Plugin::$instance->elements_manager;
$ultimo  = null;
foreach ( $forms as $fid => $form ) {
	$w = $em->create_element_instance( $form );
	if ( ! $w ) { WP_CLI::warning( "No se pudo instanciar el formulario $fid" ); continue; }
	$form['settings']                 = $w->get_settings_for_display();
	$form['settings']['id']           = $fid;
	$form['settings']['form_post_id'] = $PAGINA;
	$form['settings']['edit_post_id'] = $PAGINA;

	$campos = (array) ( $form['settings']['form_fields'] ?? [] );
	$base   = [];
	$hp = $ts = $mail = null;
	$mail_req = false;
	foreach ( $campos as $f ) {
		$cid = (string) ( $f['custom_id'] ?? '' );
		if ( '' === $cid ) { continue; }
		$base[ $cid ] = $valor( $f );
		$tipo = $f['field_type'] ?? '';
		if ( 'honeypot' === $tipo ) { $hp = $cid; }
		if ( 'hidden' === $tipo && str_ends_with( $cid, '_ts' ) ) { $ts = $cid; }
		if ( 'email' === $tipo && null === $mail ) { $mail = $cid; $mail_req = ! empty( $f['required'] ); }
	}
	/* caso => [ datos, válido esperado ] */
	$casos = [ 'válido' => [ $base, true ] ];
	if ( $hp )   { $casos['trampa llena'] = [ array_replace( $base, [ $hp => 'http://spam' ] ), false ]; }
	if ( $ts )   { $casos['en 0,9 s']     = [ array_replace( $base, [ $ts => '900' ] ), false ];
	               $casos['sin JS']       = [ array_replace( $base, [ $ts => '' ] ), false ]; }
	if ( $mail ) { $casos['sin email']    = [ array_replace( $base, [ $mail => '' ] ), ! $mail_req ];
	               /* «ana@example»: Pro lo vacía al sanearlo; solo falla si el campo es obligatorio o si el
	                  hijo engancha elementor_pro/forms/validation/email para decir qué pasa */
	               $casos['email sin TLD'] = [ array_replace( $base, [ $mail => 'ana@example' ] ), ! ( $mail_req || has_action( 'elementor_pro/forms/validation/email' ) ) ]; }

	$s = $form['settings'];
	echo "\n== " . ( $s['form_name'] ?? $fid ) . " ($fid) → para: " . ( $s['email_to'] ?? '' ) . ' | reply-to campo: ' . ( $s['email_reply_to'] ?? '' )
		. ' | acciones: ' . implode( ',', (array) ( $s['submit_actions'] ?? [] ) ) . "\n";
	foreach ( $casos as $n => [ $enviado, $esperado ] ) {
		$ah  = new Ajax_Handler();
		$rec = new Form_Record( $enviado, $form );
		$ok  = $rec->validate( $ah );
		printf( "  %-14s válido=%-5s %s errores=%s campos=%s\n", $n, var_export( $ok, true ), $ok === $esperado ? 'OK     ' : 'REVISAR',
			wp_json_encode( $ah->errors, JSON_UNESCAPED_UNICODE ), implode( ',', array_keys( (array) $rec->get( 'fields' ) ) ) );
		if ( 'válido' === $n && $ok && in_array( 'email', (array) ( $s['submit_actions'] ?? [] ), true ) ) {
			$capturado = [];
			\ElementorPro\Modules\Forms\Module::instance()->actions_registrar->get( 'email' )->run( $rec, $ah );
			$m = $capturado[0] ?? [];
			$ultimo = $m ?: $ultimo;
			$cab = $m['headers'] ?? '';
			echo '  correo simulado: to=' . ( is_array( $m['to'] ?? '' ) ? implode( ',', $m['to'] ) : ( $m['to'] ?? '' ) )
				. ' | asunto=' . ( $m['subject'] ?? '' )
				. ' | cabeceras=' . str_replace( "\r\n", ' / ', is_array( $cab ) ? implode( ' / ', $cab ) : $cab ) . "\n";
			echo '  cuerpo: ' . str_replace( "\n", ' ⏎ ', trim( (string) ( $m['message'] ?? '' ) ) ) . "\n";
			if ( $ts && str_contains( (string) ( $m['message'] ?? '' ), $ts ) ) { echo "  AVISO: el campo $ts aparece en el correo (el hook debería quitarlo)\n"; }
		}
	}
}

/* ---------- parte 2 (opcional): cabeceras que pondría PHPMailer, sin enviar ---------- */
if ( getenv( 'E4_CABECERAS' ) ) {
	remove_filter( 'pre_wp_mail', $corta, 1 );
	$visto  = [];
	$fallos = [];
	add_action( 'phpmailer_init', function ( $m ) use ( &$visto ) {
		$visto[] = [ 'From' => $m->From, 'FromName' => $m->FromName, 'Sender' => $m->Sender, 'Mailer' => $m->Mailer, 'ReplyTo' => array_keys( $m->getReplyToAddresses() ) ];
		$m->clearAllRecipients();   // no sale nada: wp_mail_failed es lo esperado
	}, PHP_INT_MAX );
	add_action( 'wp_mail_failed', function ( $e ) use ( &$fallos ) { $fallos[] = $e->get_error_message(); } );
	wp_mail( 'prueba@example.com', 'Prueba núcleo', 'x' );   // como un aviso de WordPress
	if ( $ultimo ) {
		wp_mail( 'prueba@example.com', (string) ( $ultimo['subject'] ?? 'Prueba form' ), 'x', $ultimo['headers'] ?? [] );   // con las cabeceras del formulario
	}
	echo "\n";
	foreach ( $visto as $i => $v ) { echo "PHPMailer $i: " . wp_json_encode( $v ) . "\n"; }
	echo 'envío cortado (esperado): ' . implode( ' | ', array_unique( $fallos ) ) . "\n";
	echo 'sendmail_path: ' . ini_get( 'sendmail_path' ) . "\n";
}
