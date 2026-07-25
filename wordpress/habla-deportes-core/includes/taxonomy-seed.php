<?php
/**
 * Habla Deportes Core — Siembra de términos base (equipo / torneo / tema).
 * ---------------------------------------------------------------
 * Inserta UNA sola vez los términos que el proyecto usa como eje de
 * organización (menús dinámicos, hubs, clasificador de videos).
 * Idempotente: guarda un flag con update_option y NO duplica si el
 * término ya existe (respeta el slug explícito).
 *
 * Los slugs junior / seleccion-colombia (equipo) y clip (tema) son
 * los que ya espera el resto del código: NO cambiarlos.
 *
 * Corre en la activación del plugin y, como red de seguridad, también
 * en `init` (por si el plugin se copió con el sitio ya montado).
 * Para re-sembrar tras añadir términos nuevos a la lista, sube el
 * número de versión del flag (hd_terms_seeded).
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'hd_seed_terms', 20 ); // 20 = después de registrar las taxonomías (prioridad 10).

function hd_seed_terms() {

	if ( '1' === get_option( 'hd_terms_seeded' ) ) {
		return;
	}

	// name => slug. El slug manda (respeta los que el código ya espera).
	$seed = array(
		'equipo' => array(
			'Junior FC'          => 'junior',
			'Selección Colombia' => 'seleccion-colombia',
			'Atlético Nacional'  => 'nacional',
			'Millonarios'        => 'millonarios',
			'América de Cali'    => 'america',
		),
		'torneo' => array(
			'Liga BetPlay'      => 'liga-betplay',
			'Copa Colombia'     => 'copa-colombia',
			'Eliminatorias'     => 'eliminatorias',
			'Copa Libertadores' => 'libertadores',
		),
		'tema' => array(
			'Clips'       => 'clip',
			'Fichajes'    => 'fichaje',
			'Análisis'    => 'analisis',
			'Entrevistas' => 'entrevista',
			'Nota'        => 'nota',
		),
	);

	foreach ( $seed as $taxonomy => $terms ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}
		foreach ( $terms as $name => $slug ) {
			// No duplicar: si ya existe por slug, saltar.
			if ( get_term_by( 'slug', $slug, $taxonomy ) ) {
				continue;
			}
			wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
		}
	}

	update_option( 'hd_terms_seeded', '1' );
}
