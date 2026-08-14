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

require_once COOKIE_SAMTYKKE_STI . 'inc/farver.php';
require_once COOKIE_SAMTYKKE_STI . 'inc/indstillinger.php';
require_once COOKIE_SAMTYKKE_STI . 'inc/banner.php';
require_once COOKIE_SAMTYKKE_STI . 'inc/api.php';
