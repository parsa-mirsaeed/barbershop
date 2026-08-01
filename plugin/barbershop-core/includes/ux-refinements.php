<?php
/** Final account and mobile-navigation experience layer. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the account and navigation refinements after all previous public assets.
 *
 * @return void
 */
function bsc_enqueue_ux_refinements() {
	if ( is_admin() ) {
		return;
	}

	wp_enqueue_style(
		'barbershop-core-ux-refinements',
		BSC_URL . 'assets/ux-refinements.css',
		array( 'barbershop-core-mobile-live-fixes' ),
		BSC_VERSION
	);
	wp_enqueue_script(
		'barbershop-core-ux-refinements',
		BSC_URL . 'assets/ux-refinements.js',
		array(),
		BSC_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bsc_enqueue_ux_refinements', 1000 );
