<?php
/**
 * <PREFIJO> — accesibilidad que Elementor Pro y Hello no traen (cambios invisibles).
 *
 * Plantilla del módulo inc/accesibilidad.php del caso de referencia (HomeMaker: Accesibilidad
 * 93–97 en las 14 corridas sin tocar colores de marca).
 * Estado: generalizado, NO probado fuera de HomeMaker. El original sí está probado.
 *
 * Instalación: copia a wp-content/themes/<child>/inc/accesibilidad.php, sustituye el prefijo
 *   (sed -i '' 's/pfx_/<PREFIJO>_/g; s/PFX_/<PREFIJO_MAYÚSCULAS>_/g; s/pfx-/<PREFIJO>-/g' accesibilidad.php),
 *   rellena CONFIGURACIÓN y añade require_once __DIR__ . '/inc/accesibilidad.php'; en functions.php.
 * Qué resuelve:
 *   1. aria-hidden-focus: el panel móvil del widget Menú de Pro, cerrado, lleva aria-hidden="true"
 *      pero los enlaces que añade el child (CTA, selector de idioma) seguían en el orden de
 *      tabulación: Pro solo pone tabindex=-1 a sus propios ítems. Salía en los 7 informes móviles.
 *   2. «Menu Toggle» (botón hamburguesa de Pro) sin traducción al locale del sitio.
 *   3. autocomplete en los campos de los formularios de Pro (WCAG 1.3.5), por ID de campo, sin
 *      tocar el antispam.
 * Riesgos: scripts inline en wp_footer (una CSP estricta los bloquearía). Depende de las clases
 *   .elementor-nav-menu--dropdown.elementor-nav-menu__container de Pro: revisa tras actualizar.
 */

defined( 'ABSPATH' ) || exit;

/* ================= CONFIGURACIÓN ================= */

/* ID de campo del widget Formulario (custom_id) → valor de autocomplete.
   En el caso se usaron name, email y organization. */
const PFX_AUTOCOMPLETE = [
	// 'c_nombre' => 'name', 'c_email' => 'email', 'd_empresa' => 'organization',
];
/* Etiqueta del botón hamburguesa por idioma de Polylang ('' = sitio sin Polylang). */
const PFX_TOGGLE_LABEL = [
	// 'es' => 'Abrir o cerrar el menú',
];
/* Regex de los enlaces que el child añade a la copia «dropdown» del menú (para quitarles el foco
   en servidor, cuando no hay JS). '' = el child no añade enlaces. */
const PFX_EXTRAS_MENU = ''; // '/<a (class="(?:pfx-nav-cta|pfx-lang__opt)[^"]*")/'

/* ================= 1. foco del panel móvil ================= */

/* Con JS: los enlaces del panel salen del orden de tabulación mientras está cerrado y vuelven al
   abrirlo (se observa el aria-hidden que Pro alterna). */
add_action( 'wp_footer', function () {
	if ( is_admin() || isset( $_GET['elementor-preview'] ) ) {
		return;
	}
	echo "<script>(function(){document.querySelectorAll('.elementor-nav-menu--dropdown.elementor-nav-menu__container').forEach(function(p){function s(){var a=p.getAttribute('aria-hidden')==='false';p.querySelectorAll('a').forEach(function(l){l.setAttribute('tabindex',a?'0':'-1')})}new MutationObserver(s).observe(p,{attributes:true,attributeFilter:['aria-hidden']});s()})})();</script>\n";
}, 99 );

/* Sin JS: los extras del child en la copia «dropdown» salen ya sin foco (el script de arriba los
   activa al abrir el panel). */
if ( PFX_EXTRAS_MENU ) {
	add_filter( 'wp_nav_menu_items', function ( $items, $args ) {
		if ( ( $args->menu_type ?? '' ) !== 'dropdown' ) {
			return $items;
		}
		return preg_replace( PFX_EXTRAS_MENU, '<a tabindex="-1" $1', $items );
	}, 20, 2 );
}

/* ================= 2. «Menu Toggle» ================= */

if ( PFX_TOGGLE_LABEL ) {
	add_filter( 'gettext_elementor-pro', function ( $traduccion, $texto ) {
		if ( 'Menu Toggle' !== $texto ) {
			return $traduccion;
		}
		$lang = function_exists( 'pll_current_language' ) ? (string) pll_current_language() : '';
		return PFX_TOGGLE_LABEL[ $lang ] ?? $traduccion;
	}, 10, 2 );
}

/* ================= 3. autocomplete en formularios de Pro ================= */

if ( PFX_AUTOCOMPLETE ) {
	add_filter( 'elementor_pro/forms/render/item', function ( $item, $index, $form ) {
		$id = $item['custom_id'] ?? '';
		if ( isset( PFX_AUTOCOMPLETE[ $id ] ) ) {
			$form->add_render_attribute( 'input' . $index, 'autocomplete', PFX_AUTOCOMPLETE[ $id ] );
		}
		return $item;
	}, 10, 3 );
}
