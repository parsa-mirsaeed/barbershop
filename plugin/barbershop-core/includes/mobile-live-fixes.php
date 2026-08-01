<?php
/**
 * Real-device mobile repairs discovered from Chrome device-emulation captures.
 *
 * The repository fixture already contained a viewport meta tag, while the live
 * WordPress theme did not. That made browser tests pass even though phones used
 * a desktop-sized layout viewport. Keep the runtime repair here so existing
 * installations receive the fix immediately after updating the plugin.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Use the physical device width and expose display-cutout safe-area variables.
 *
 * @return void
 */
function bsc_mobile_live_viewport_meta() {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";
}
add_action( 'wp_head', 'bsc_mobile_live_viewport_meta', 0 );

/**
 * Load the capture-driven fixes after every theme, component, and WooCommerce
 * stylesheet so old Site Editor content and inline block widths cannot win.
 *
 * @return void
 */
function bsc_mobile_live_enqueue_fixes() {
	if ( is_admin() ) {
		return;
	}

	wp_enqueue_style(
		'barbershop-core-mobile-live-fixes',
		BSC_URL . 'assets/mobile-live-fixes.css',
		array( 'barbershop-core-runtime-storefront' ),
		BSC_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'bsc_mobile_live_enqueue_fixes', 999 );
