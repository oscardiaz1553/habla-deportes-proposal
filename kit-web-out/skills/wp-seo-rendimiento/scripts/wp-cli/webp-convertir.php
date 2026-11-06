<?php
/**
 * webp-convertir.php — copias WebP de toda la Biblioteca por CLI (foto.jpg → foto.jpg.webp,
 * logo.png → logo.png.webp), incluidos los tamaños intermedios y original_image.
 *
 * - JPG: con el conversor de SiteGround Speed Optimizer si está activo
 *   (Images_Optimizer_Webp::generate_webp_file, cwebp q80); si no, cwebp -q 80 directamente.
 * - PNG: cwebp sin pérdida (-lossless -z 9): los bordes de los logos no se ensucian.
 * - Se descarta la copia si no pesa menos que el original.
 * - Con SiteGround activo marca cada adjunto y las opciones del plugin como convertidos.
 * Por qué por CLI: `wp sg optimize webp enable` solo cambia la opción; la conversión masiva la
 * lanza el panel con un ajax (Rest_Helper_Webp::optimize_webp_images).
 *
 * Estado: probado en HomeMaker con SiteGround (156 archivos, 11,2 MB → 6,3 MB en 31 s). La rama
 *         sin SiteGround (cwebp para JPG) está generalizada y no probada.
 * Uso:    wp --path=<WP_PATH> --user=1 eval-file webp-convertir.php
 * Requisitos: `cwebp` en el servidor (which cwebp) y exec() habilitado.
 * Riesgos: escribe un archivo junto a cada imagen. Las copias SOLO se sirven si el servidor las
 *   entrega según la cabecera Accept (nginx de SiteGround; en Apache, las reglas de .htaccess de
 *   references/rendimiento.md, con Vary: Accept). Las subidas nuevas no se convierten solas.
 */

defined( 'ABSPATH' ) || exit;

$sg = class_exists( '\SiteGround_Optimizer\Images_Optimizer\Images_Optimizer_Webp' );
if ( ! function_exists( 'exec' ) ) {
	WP_CLI::error( 'exec() no está disponible en este PHP.' );
}
exec( 'command -v cwebp', $w, $st );
if ( $st ) {
	WP_CLI::error( 'cwebp no está instalado en el servidor.' );
}
if ( $sg ) {
	update_option( 'siteground_optimizer_webp_support', 1 );
}

$ids = get_posts( [ 'post_type' => 'attachment', 'post_mime_type' => [ 'image/jpeg', 'image/png' ], 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'inherit', 'lang' => '' ] );
$tot = [ 'files' => 0, 'kept' => 0, 'orig' => 0, 'webp' => 0, 'dropped' => 0, 'errores' => 0 ];
$t0  = microtime( true );
foreach ( $ids as $id ) {
	$main = get_attached_file( $id );
	if ( ! $main || ! file_exists( $main ) ) {
		continue;
	}
	$meta  = wp_get_attachment_metadata( $id );
	$files = [ $main ];
	foreach ( $meta['sizes'] ?? [] as $s ) {
		$files[] = dirname( $main ) . '/' . $s['file'];
	}
	if ( ! empty( $meta['original_image'] ) ) {
		$files[] = dirname( $main ) . '/' . $meta['original_image'];
	}
	$ok = true;
	foreach ( array_unique( $files ) as $f ) {
		if ( ! file_exists( $f ) ) {
			continue;
		}
		$tot['files']++;
		$es_png = function_exists( 'exif_imagetype' ) ? IMAGETYPE_PNG === exif_imagetype( $f ) : (bool) preg_match( '/\.png$/i', $f );
		$o      = [];
		if ( $es_png ) {
			@unlink( $f . '.webp' );
			exec( sprintf( 'cwebp -quiet -lossless -z 9 %s -o %s 2>&1', escapeshellarg( $f ), escapeshellarg( $f . '.webp' ) ), $o, $st );
		} elseif ( $sg ) {
			$st = \SiteGround_Optimizer\Images_Optimizer\Images_Optimizer_Webp::generate_webp_file( $f ) ? 1 : 0; // como en el original: retorno verdadero = fallo
		} else {
			exec( sprintf( 'cwebp -quiet -q 80 %s -o %s 2>&1', escapeshellarg( $f ), escapeshellarg( $f . '.webp' ) ), $o, $st );
		}
		if ( $st || ! file_exists( $f . '.webp' ) ) {
			$ok = false;
			$tot['errores']++;
			continue;
		}
		$a = filesize( $f );
		$b = filesize( $f . '.webp' );
		if ( $b >= $a ) {
			unlink( $f . '.webp' ); // solo la copia recién creada, nunca el original
			$tot['dropped']++;
			continue;
		}
		$tot['kept']++;
		$tot['orig'] += $a;
		$tot['webp'] += $b;
	}
	if ( $ok && $sg ) {
		update_post_meta( $id, 'siteground_optimizer_is_converted_to_webp', 1 );
	}
}
if ( $sg ) {
	update_option( 'siteground_optimizer_total_non_converted_images', 0 );
	update_option( 'siteground_optimizer_webp_conversion_completed', 1 );
}
printf( "adjuntos %d | archivos %d | webp guardados %d (%.0f KB → %.0f KB) | descartados por no ahorrar %d | errores %d | %.0f s\n",
	count( $ids ), $tot['files'], $tot['kept'], $tot['orig'] / 1024, $tot['webp'] / 1024, $tot['dropped'], $tot['errores'], microtime( true ) - $t0 );
