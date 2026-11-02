<?php
/**
 * post-cambio.php · Cachés que guardan el dominio o la IP del servidor, tras el cambio de carpeta.
 *
 * Propósito: después del `mv` (runbook F9), limpiar la caché de idiomas y de slugs traducidos
 *   de Polylang (guardaban el dominio de staging) y, si SG Security viaja, registrar la IP del
 *   servidor nuevo para que no anote sus propias peticiones. Imprime la portada y los idiomas.
 * Uso:     scp post-cambio.php <SSH_ALIAS_DESTINO>:<PREFIJO>-migracion/
 *          wp --path=<WP_PATH> --url=https://<DOMINIO> eval-file ~/<PREFIJO>-migracion/post-cambio.php --user=1
 *          (en cPanel, con el PHP de la web: wp82 … ; ver runbook, «Convenciones»)
 * Parámetros: ninguno. Todo se detecta; si Polylang o SG Security no están, se salta su parte.
 * Estado:  probado en HomeMaker (Polylang Pro 3.8.6, SG Security 1.6.8, WP 7.1.2).
 * Riesgos: ninguno conocido; solo borra transitorios y cachés que se regeneran.
 *          Va junto a: transient delete --all, cache flush, elementor flush_css,
 *          elementor-pro theme-builder clear_conditions, rewrite flush, yoast index --reindex.
 */

defined( 'ABSPATH' ) || exit;

/* Polylang: caché de idiomas y de slugs traducidos (guardan URL absolutas del dominio viejo). */
if ( function_exists( 'PLL' ) && PLL() && isset( PLL()->model ) ) {
	PLL()->model->clean_languages_cache();
}
delete_transient( 'pll_languages_list' );
delete_transient( 'pll_translated_slugs' );

/* SG Security: IP de este servidor (la guardada era la del hosting de origen). */
if ( class_exists( '\SG_Security\Helper\Helper' ) && method_exists( '\SG_Security\Helper\Helper', 'set_server_ip' ) ) {
	\SG_Security\Helper\Helper::set_server_ip();
	echo "SG Security: IP del servidor actualizada\n";
}

$idiomas = function_exists( 'pll_languages_list' ) ? pll_languages_list() : array();
echo 'home=' . home_url() . ' idiomas=' . implode( ',', $idiomas ) . "\n";
if ( function_exists( 'PLL' ) && isset( PLL()->model ) ) {
	foreach ( $idiomas as $slug ) {
		$lang = PLL()->model->get_language( $slug );
		if ( $lang ) {
			echo "portada {$slug}: " . $lang->get_home_url() . "\n";
		}
	}
}
echo "post-cambio: fin\n";
