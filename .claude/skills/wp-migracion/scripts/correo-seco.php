<?php
/**
 * correo-seco.php · Cabeceras reales del correo en el servidor nuevo, sin enviar nada.
 *
 * Propósito: tras migrar, ver con qué From, FromName, Sender (sobre, el «-f» de sendmail) y
 *   Mailer saldrían los avisos del núcleo de WordPress en el servidor nuevo, si el dominio del
 *   From es el de home_url() y si el Sender está alineado para DMARC, y qué sendmail_path usa el
 *   hosting. Justo antes de enviar se vacían los destinatarios: PHPMailer no manda nada y el fallo
 *   que se imprime es el esperado. Receta: elementor4-atomico, references/formularios.md §8.B.
 *   Los correos de los formularios se prueban con la sonda de esa skill (no se duplica aquí):
 *   E4_PAGINA=<ID> E4_CABECERAS=1 wp … eval-file ../../elementor4-atomico/scripts/probe-forms-seco.php
 * Uso:     wp --path=<WP_PATH> --url=https://<DOMINIO> eval-file correo-seco.php --user=1
 * Parámetros: ninguno.
 * Estado:  probado en HomeMaker (la prueba en seco del núcleo); las comprobaciones de dominio y
 *   alineación son generalización.
 * Riesgos: ninguno; no cambia opciones. La dirección de prueba es de example.com.
 * Esperado: From no-reply@<dominio de home_url()> y Sender igual al From si el tema hijo lo fija
 *   en phpmailer_init; Mailer «mail» con el sendmail del hosting (o «smtp» si hay plugin SMTP).
 */

defined( 'ABSPATH' ) || exit;

$dominio = preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

$visto = array();
add_action(
	'phpmailer_init',
	function ( $m ) use ( &$visto ) {
		$visto[] = array(
			'From'     => $m->From,
			'FromName' => $m->FromName,
			'Sender'   => $m->Sender,
			'Mailer'   => $m->Mailer,
			'ReplyTo'  => array_keys( $m->getReplyToAddresses() ),
		);
		$m->clearAllRecipients(); // no sale nada
	},
	PHP_INT_MAX
);
$fallos = array();
add_action(
	'wp_mail_failed',
	function ( $e ) use ( &$fallos ) {
		$fallos[] = $e->get_error_message();
	}
);

wp_mail( 'prueba@example.com', 'Prueba núcleo', 'x' ); // como un aviso de WordPress

foreach ( $visto as $i => $v ) {
	$ok = ( 0 === strcasecmp( substr( strrchr( (string) $v['From'], '@' ), 1 ), $dominio ) ) ? 'dominio OK' : 'DOMINIO DISTINTO';
	$al = ( '' === (string) $v['Sender'] ) ? 'Sender vacío (sobre del usuario del sistema: no alinea para DMARC)' : ( ( $v['Sender'] === $v['From'] ) ? 'Sender = From' : 'Sender distinto del From' );
	echo "correo $i: " . wp_json_encode( $v, JSON_UNESCAPED_UNICODE ) . " · $ok · $al\n";
}
echo 'envío cortado (esperado): ' . implode( ' | ', array_unique( $fallos ) ) . "\n";
echo 'sendmail_path: ' . ini_get( 'sendmail_path' ) . "\n";
echo 'dominio de home_url(): ' . $dominio . "\n";
echo "correo-seco: fin\n";
