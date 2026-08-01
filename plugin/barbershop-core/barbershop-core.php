<?php
/**
 * Plugin Name: Barbershop Core
 * Description: Premium store setup, dedicated barber dashboard, simplified customer accounts, Persian WooCommerce UX, editable category icons, persistent cart access, security hardening, and privacy-aware portfolio controls.
 * Version: 3.2.1
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: Project Contributors
 * License: GPL-2.0-or-later
 * Text Domain: barbershop-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BSC_VERSION', '3.2.1' );
define( 'BSC_FILE', __FILE__ );
define( 'BSC_DIR', plugin_dir_path( __FILE__ ) );
define( 'BSC_URL', plugin_dir_url( __FILE__ ) );
define( 'BSC_POST_TYPE', 'barbershop_result' );
define( 'BSC_VAZIRMATN_VERSION', '33.003' );
define( 'BSC_VAZIRMATN_URL', 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/fonts/webfonts/Vazirmatn%5Bwght%5D.woff2' );

/**
 * WordPress provides checked(), selected(), disabled(), and readonly(), but no
 * hidden() helper. Older dashboard code used hidden() while rendering media
 * previews, which caused a PHP fatal error before the logo or portfolio editor
 * could finish loading. Keep a small compatible helper for those attributes.
 *
 * @param mixed $hidden Current value.
 * @param mixed $current Value to compare against.
 * @param bool  $display Whether to echo the attribute.
 * @return string
 */
if ( ! function_exists( 'hidden' ) ) {
	function hidden( $hidden, $current = true, $display = true ) {
		$result = ( (string) $hidden === (string) $current ) ? ' hidden="hidden"' : '';
		if ( $display ) {
			echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static HTML attribute.
		}
		return $result;
	}
}

require_once BSC_DIR . 'includes/setup.php';
require_once BSC_DIR . 'includes/security.php';
require_once BSC_DIR . 'includes/account.php';
require_once BSC_DIR . 'includes/categories.php';
require_once BSC_DIR . 'includes/cart.php';
require_once BSC_DIR . 'includes/woocommerce.php';
require_once BSC_DIR . 'includes/products.php';
require_once BSC_DIR . 'includes/portfolio.php';
require_once BSC_DIR . 'includes/barber.php';
require_once BSC_DIR . 'includes/content-overrides.php';
require_once BSC_DIR . 'includes/settings.php';
require_once BSC_DIR . 'includes/dashboard-extras.php';
require_once BSC_DIR . 'includes/qa-fixes.php';
require_once BSC_DIR . 'includes/runtime-repairs.php';

register_activation_hook( __FILE__, 'bsc_activate' );
