<?php
/**
 * Plugin Name: <MARCA> — caché de páginas
 * Description: Quita la cookie de idioma de Polylang (pll_language) para que la caché de página pueda guardar el HTML. El idioma ya va en la URL.
 *
 * Instalación: wp-content/mu-plugins/<PREFIJO>-cache.php (tal cual; solo cambia el nombre).
 * Por qué: Polylang manda Set-Cookie: pll_language en cada respuesta cuando no detecta una caché
 *   (pll_is_cache_active() es false sin WP_CACHE), y ninguna caché de página guarda respuestas que
 *   ponen cookies. Síntoma en el caso de referencia: x-proxy-cache: MISS con
 *   «UP:SKIP_CACHE_SET_COOKIE», TTFB de 0,3-1 s por visitante nuevo; era el factor número uno de la
 *   nota de PageSpeed. Con idioma por directorio (/en/), browser=0 y hide_default=1 la cookie solo
 *   es el respaldo del ajax sin «lang».
 * Tiene que ser mu-plugin (o wp-config.php): Polylang define sus constantes en plugins_loaded
 *   (prioridad 1); functions.php del tema llega tarde.
 * Variante: si se activa la detección de idioma por navegador, NO uses esto; usa
 *   add_filter( 'pll_is_cache_active', '__return_true' ); (cookie por JS; Polylang marca la
 *   portada como DONOTCACHEPAGE).
 * Consecuencia: el ajax de frontend sin «lang» cae al idioma por defecto; los formularios y el
 *   editor deben tomar el idioma del post (ver la skill wp-polylang).
 * Viaja en cada migración: sin él, ningún hosting cachea.
 * Estado: probado en HomeMaker (Polylang Pro 3.8.6; SiteGround y Bluehost).
 * Verificar: dos curl -sD - -o /dev/null a la misma URL → sin set-cookie y la 2.ª HIT.
 */

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'PLL_COOKIE' ) ) {
	define( 'PLL_COOKIE', false );
}
