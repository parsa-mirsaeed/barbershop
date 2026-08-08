<?php
/**
 * Plugin Name: Barbershop Shared Hosting Guard
 * Description: Early shared-host safety checks that run before normal plugins.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gateland uses PDO MySQL before WordPress can recover from a normal plugin
 * failure. On hosts without pdo_mysql, prevent it from entering the active
 * plugin load list so the storefront and wp-admin remain recoverable.
 */
function bsc_hosting_guard_active_plugins( $plugins ) {
	if ( extension_loaded( 'pdo_mysql' ) || ! is_array( $plugins ) ) {
		return $plugins;
	}

	$filtered = array();
	$blocked  = false;
	foreach ( $plugins as $plugin ) {
		if ( 'gateland/gateland.php' === $plugin || 0 === strpos( $plugin, 'gateland/' ) ) {
			$blocked = true;
			continue;
		}
		$filtered[] = $plugin;
	}
	if ( $blocked ) {
		$GLOBALS['bsc_hosting_guard_blocked_gateland'] = true;
	}
	return $filtered;
}
add_filter( 'option_active_plugins', 'bsc_hosting_guard_active_plugins', 1 );

/** Multisite equivalent of the active plugin guard. */
function bsc_hosting_guard_sitewide_plugins( $plugins ) {
	if ( extension_loaded( 'pdo_mysql' ) || ! is_array( $plugins ) ) {
		return $plugins;
	}
	foreach ( array_keys( $plugins ) as $plugin ) {
		if ( 'gateland/gateland.php' === $plugin || 0 === strpos( $plugin, 'gateland/' ) ) {
			unset( $plugins[ $plugin ] );
			$GLOBALS['bsc_hosting_guard_blocked_gateland'] = true;
		}
	}
	return $plugins;
}
add_filter( 'site_option_active_sitewide_plugins', 'bsc_hosting_guard_sitewide_plugins', 1 );

function bsc_hosting_guard_notice() {
	if ( empty( $GLOBALS['bsc_hosting_guard_blocked_gateland'] ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error"><p><strong>Gateland برای جلوگیری از Fatal Error بارگذاری نشد.</strong> افزونه PHP با نام <code>pdo_mysql</code> روی این هاست فعال نیست. ابتدا از پنل یا پشتیبانی هاست آن را فعال کنید، سپس Gateland را دوباره فعال کنید.</p></div>
	<?php
}
add_action( 'admin_notices', 'bsc_hosting_guard_notice' );
