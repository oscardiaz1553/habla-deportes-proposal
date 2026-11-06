<?php
/**
 * qa-orden-clases.php — SOLO LECTURA. ¿El editor verá las mismas clases globales que la web?
 * M11 de ../references/mediciones.md. Es lo primero que mira el QA.
 *
 * Propósito: Global_Classes_Repository::put() solo escribe el orden del contexto en el que corre
 *   (por CLI, frontend: meta del kit `_elementor_global_classes_order`). El editor lee
 *   `_elementor_global_classes_order_preview` y, al publicar cualquier página, borra del sitio las
 *   clases que no ve allí. Esta sonda compara las dos metas.
 * Uso:     wp --path=<WP_PATH> eval-file ~/tmp-<PREFIJO>/qa-orden-clases.php
 * Salida:  «kit N · frontend N · preview N · iguales: sí|NO» y, si hay, las clases solo en frontend.
 *   Si no son iguales, el arreglo es e4-sync-preview.php (skill elementor4-atomico): escribe, así que
 *   lo ejecuta quien construye, no el QA.
 *   Preview vacío: en el código de Elementor 4.2.1, un orden de preview sin guardar cae al de frontend
 *   (no borra nada); se indica aparte. Aun así, sincroniza tras cada script de clases.
 * Estado:  probado en HomeMaker (como one-liner tras la migración); aquí, en archivo para eval-file.
 * Riesgos: ninguno: solo get_option y get_post_meta.
 */
$kit = (int) get_option( 'elementor_active_kit' );
$lee = function ( $clave ) use ( $kit ) {
	$d = get_post_meta( $kit, $clave, true );
	while ( is_string( $d ) ) { $d = json_decode( $d, true ); }
	return is_array( $d ) ? (array) ( $d['order'] ?? $d ) : [];
};
$oa = $lee( '_elementor_global_classes_order' );
$ob = $lee( '_elementor_global_classes_order_preview' );
echo "kit $kit · frontend " . count( $oa ) . ' · preview ' . count( $ob ) . ' · iguales: ' . ( $oa === $ob ? 'sí' : 'NO' ) . "\n";
if ( ! $ob && $oa ) { echo "preview sin orden guardado: Elementor 4.2+ usa el de frontend como respaldo; sincroniza igualmente\n"; }
elseif ( array_diff( $oa, $ob ) ) { echo 'solo en frontend (el editor las borraría al publicar): ' . implode( ',', array_diff( $oa, $ob ) ) . "\n"; }
