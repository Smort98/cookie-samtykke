<?php
/**
 * Plugin Name: Cookie-samtykke
 * Description: Simpel, GDPR-venlig cookie-samtykkebanner. Farverne tilpasser sig automatisk temaets theme.json (block-temaer), med manuel override for temaer uden. Beregnet til at blive genbrugt på tværs af sites.
 * Version: 1.0.0
 * Author: Søren
 * Text Domain: cookie-samtykke
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'COOKIE_SAMTYKKE_VERSION', '1.0.0' );
define( 'COOKIE_SAMTYKKE_FIL', __FILE__ );
define( 'COOKIE_SAMTYKKE_STI', plugin_dir_path( __FILE__ ) );
define( 'COOKIE_SAMTYKKE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Version til brug ved wp_enqueue_style/script — baseret på filens sidste
 * ændringstidspunkt i stedet for det faste COOKIE_SAMTYKKE_VERSION-tal, så
 * browsere automatisk henter den nye fil, hver gang den redigeres, i stedet
 * for at blive ved med at vise en cachet, forældet udgave.
 */
function cookie_samtykke_asset_version( string $relativ_sti ): string {
	$fuld_sti = COOKIE_SAMTYKKE_STI . ltrim( $relativ_sti, '/' );
	$tid      = file_exists( $fuld_sti ) ? filemtime( $fuld_sti ) : false;
	return $tid ? (string) $tid : COOKIE_SAMTYKKE_VERSION;
}

require_once COOKIE_SAMTYKKE_STI . 'inc/farver.php';
require_once COOKIE_SAMTYKKE_STI . 'inc/indstillinger.php';
require_once COOKIE_SAMTYKKE_STI . 'inc/banner.php';
require_once COOKIE_SAMTYKKE_STI . 'inc/scanner.php';
require_once COOKIE_SAMTYKKE_STI . 'inc/generator.php';
require_once COOKIE_SAMTYKKE_STI . 'inc/anmodninger.php';
require_once COOKIE_SAMTYKKE_STI . 'inc/api.php';
