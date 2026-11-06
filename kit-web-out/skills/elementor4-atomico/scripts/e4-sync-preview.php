<?php
/**
 * e4-sync-preview.php — Copia el orden de las clases globales del contexto «frontend» al
 * «preview» y comprueba que ambos contextos coinciden. OBLIGATORIO al final de cada script que
 * toque clases globales (Global_Classes_Repository::put()) y después de migrar o importar el sitio.
 * Tras un script que solo guarda documentos no hace falta; algunas plantillas del kit lo incluyen
 * igual como red de seguridad (es idempotente).
 *
 * PROPÓSITO (síntoma → causa)
 *   Síntoma: publicar CUALQUIER página desde el editor de Elementor borra del sitio las clases
 *   creadas por CLI y las quita de los documentos (hallazgo real en el caso de referencia).
 *   Causa: Global_Classes_Repository::put() escribe solo el contexto en el que trabaja (frontend:
 *   meta _elementor_global_classes_order del kit). El editor lee el contexto preview
 *   (_elementor_global_classes_order_preview) y, al publicar, elimina las clases que no ve allí.
 *   Arreglo: Global_Classes_Order::make( $kit )->set_preview( true )->set_order( $orden_frontend ),
 *   conservando AL FINAL las clases que solo existan en preview (pueden ser del cliente).
 *
 * USO
 *   Al final de <pagina>-classes.php:   require __DIR__ . '/e4-sync-preview.php';
 *   Suelto (tras migrar o ante la duda): wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/e4-sync-preview.php
 *   Salida esperada (el mismo N en todo):
 *     orden: frontend N · preview N · iguales: sí | REST items: frontend N · preview N | posts de clase: N | solo en frontend: ninguna | estilos nulos en preview: ninguno
 *
 * QUÉ EDITAR
 *   Nada. Variable de entorno opcional E4_SYNC_IDS="pfx-a,pfx-b": limita la comprobación de estilos
 *   nulos a esas clases (por defecto, todas las del orden frontend, en lotes de 50).
 *
 * ESTADO
 *   Núcleo probado en HomeMaker (sincronización idéntica a la original; comprobación de metas
 *   crudas «frontend N · preview N · iguales» usada tras la migración).
 *   Generalizado, no probado fuera de HomeMaker: la lista de ids ya no está fija en el código
 *   (sale del orden, en lotes de 50 por petición REST), el recuento de posts de clase y la
 *   comparación de claves por repositorio. Se quitó el recuento de «order» por REST del original:
 *   siempre daba 0 porque la respuesta no trae el orden en esa forma; solo el de items es fiable.
 *
 * RIESGOS
 *   - Escribe el orden preview del kit activo (nada más). Es idempotente.
 *   - Usa wp_set_current_user( 1 ) si no hay usuario: ejecútalo con --user=1 (o el id del admin).
 *   - Se ejecuta dentro de una función anónima para no pisar variables del script que lo incluye.
 */

use Elementor\Plugin;
use Elementor\Modules\GlobalClasses\Global_Classes_Order;
use Elementor\Modules\GlobalClasses\Global_Classes_Repository;

( function () {
	$kit = Plugin::$instance->kits_manager->get_active_kit();
	if ( ! $kit ) { WP_CLI::error( 'No hay kit activo de Elementor.' ); }

	/* ---------- 1. sincronizar: preview = frontend + (solo preview, al final) ---------- */
	$front        = Global_Classes_Order::make( $kit )->get_order();
	$prev         = Global_Classes_Order::make( $kit )->set_preview( true );
	$solo_preview = array_values( array_diff( $prev->get_order(), $front ) );
	if ( $solo_preview ) {
		echo 'AVISO: clases solo en preview (se conservan al final; pueden ser del cliente): ' . implode( ', ', $solo_preview ) . "\n";
	}
	$prev->set_order( array_values( array_merge( $front, $solo_preview ) ) );
	Plugin::$instance->files_manager->clear_cache();

	/* ---------- 2. metas crudas del kit ---------- */
	$orden_meta = function ( $clave ) use ( $kit ) {
		$d = get_post_meta( $kit->get_id(), $clave, true );
		while ( is_string( $d ) && '' !== $d ) {
			$d = json_decode( $d, true );
		}
		return is_array( $d ) && isset( $d['order'] ) ? (array) $d['order'] : null;
	};
	$of = $orden_meta( '_elementor_global_classes_order' );
	$op = $orden_meta( '_elementor_global_classes_order_preview' );
	$nf = null === $of ? 0 : count( $of );
	$np = null === $op ? 0 : count( $op );
	/* con clases solo-preview, el orden preview es el frontend más esas al final */
	$iguales = null !== $of && null !== $op && array_slice( $op, 0, $nf ) === $of;
	if ( null === $of || null === $op ) {
		/* forma de meta distinta a la conocida: comparar por la API */
		$of = $front;
		$op = Global_Classes_Order::make( $kit )->set_preview( true )->get_order();
		$nf = count( $of );
		$np = count( $op );
		$iguales = array_slice( $op, 0, $nf ) === $of;
	}

	/* ---------- 3. REST, como lo verá el editor ---------- */
	if ( ! get_current_user_id() ) { wp_set_current_user( 1 ); }
	$items_rest = function ( $ctx ) {
		$req = new WP_REST_Request( 'GET', '/elementor/v1/global-classes' );
		$req->set_param( 'context', $ctx );
		$data  = rest_do_request( $req )->get_data();
		$items = $data['data'] ?? $data;
		return is_array( $items ) ? count( $items ) : 0;
	};
	$ids = getenv( 'E4_SYNC_IDS' ) ? array_filter( array_map( 'trim', explode( ',', getenv( 'E4_SYNC_IDS' ) ) ) ) : $front;
	$nulos = [];
	foreach ( array_chunk( array_values( $ids ), 50 ) as $lote ) {
		$sreq = new WP_REST_Request( 'GET', '/elementor/v1/global-classes/styles' );
		$sreq->set_param( 'context', 'preview' );
		$sreq->set_param( 'ids', implode( ',', $lote ) );
		$styles = rest_do_request( $sreq )->get_data();
		$styles = $styles['data'] ?? $styles;
		foreach ( (array) $styles as $k => $v ) {
			if ( null === $v ) { $nulos[] = $k; }
		}
	}

	/* ---------- 4. claves por repositorio y posts de clase ---------- */
	$keys_f     = array_keys( Global_Classes_Repository::make()->all( true )->get_items()->all() );
	$keys_p     = array_keys( Global_Classes_Repository::make()->set_preview( true )->all( true )->get_items()->all() );
	$solo_front = array_values( array_diff( $keys_f, $keys_p ) );
	$posts      = 'n/d';
	if ( class_exists( '\Elementor\Modules\GlobalClasses\Global_Class_Post_Type' ) ) {
		$c     = wp_count_posts( \Elementor\Modules\GlobalClasses\Global_Class_Post_Type::CPT );
		$posts = isset( $c->publish ) ? (string) $c->publish : 'n/d';
	}

	printf( "orden: frontend %d · preview %d · iguales: %s | REST items: frontend %d · preview %d | posts de clase: %s | solo en frontend: %s | estilos nulos en preview: %s\n",
		$nf, $np, $iguales ? 'sí' : 'NO', $items_rest( 'frontend' ), $items_rest( 'preview' ), $posts,
		$solo_front ? implode( ',', $solo_front ) : 'ninguna', $nulos ? implode( ',', $nulos ) : 'ninguno' );

	if ( ! $iguales || $solo_front || $nulos ) {
		WP_CLI::warning( 'Los contextos frontend y preview NO cuadran. Nadie debe publicar desde el editor hasta resolverlo.' );
	}
} )();
