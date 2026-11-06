<?php
/**
 * qa-avisos.php — avisos PHP, _doing_it_wrong y funciones obsoletas del tema hijo al pintar una página.
 * M18 de ../references/mediciones.md. Se carga con --require (antes de WordPress) junto a render.php
 * de wp-seo-rendimiento, que pinta la URL como usuario 1.
 *
 * Propósito: un aviso PHP del child no rompe la página y nadie lo ve; con WP_DEBUG apagado tampoco
 *   sale en el HTML. Aquí se imprimen por STDERR mientras se pinta la página.
 * Uso:     RENDER_URL=/<ruta>/ wp --path=<WP_PATH> --require=$HOME/tmp-<PREFIJO>/qa-avisos.php eval-file ~/tmp-<PREFIJO>/render.php > /dev/null
 *          QA_CHILD=<carpeta-del-child> fija la carpeta del tema a vigilar; por defecto, la del tema activo.
 * Salida:  por STDERR, «DOING_IT_WRONG …», «DEPRECATED …» y «PHP[n] mensaje @ archivo:línea» (solo de
 *          archivos del child). Nada = sin avisos.
 * Estado:  generalizado, no probado: la sonda existió en una revisión de HomeMaker, pero su uso con
 *          --require no consta. Pruébala primero en una página conocida.
 * Riesgos: ninguno: solo lee; devuelve false en el manejador para no ocultar nada al resto.
 */
WP_CLI::add_wp_hook( 'doing_it_wrong_run', function ( $f, $m ) { fwrite( STDERR, "DOING_IT_WRONG $f: $m\n" ); }, 10, 2 );
WP_CLI::add_wp_hook( 'deprecated_function_run', function ( $f ) { fwrite( STDERR, "DEPRECATED $f\n" ); }, 10, 1 );
set_error_handler( function ( $no, $str, $file, $line ) {
	$child = getenv( 'QA_CHILD' ) ?: ( function_exists( 'get_stylesheet' ) ? get_stylesheet() : '' );
	if ( '' !== $child && strpos( $file, '/themes/' . $child . '/' ) !== false ) {
		fwrite( STDERR, "PHP[$no] $str @ $file:$line\n" );
	}
	return false;
} );
