<?php
/**
 * Runtime repairs for defects found during live acceptance testing.
 *
 * These guards intentionally sit after the original modules so they can repair
 * old installations without requiring the customer to delete the database.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep exactly one callback on the shared dashboard hook.
 *
 * Three modules historically registered the same page slug. WordPress keeps
 * every callback attached to that hook even when the visible menu item is
 * replaced, which rendered the setup page and barber form multiple times and
 * created duplicate field IDs. Duplicate IDs also made media selections update
 * one form while another form was submitted.
 */
function bsc_runtime_dedupe_dashboard_callbacks() {
	$hook = 'toplevel_page_bsc-store-setup';
	remove_action( $hook, 'bsc_render_setup_page' );
	remove_action( $hook, 'bsc_render_barber_dashboard' );
	remove_action( $hook, 'bsc_render_complete_barber_dashboard' );

	if ( function_exists( 'bsc_render_complete_barber_dashboard' ) ) {
		add_action( $hook, 'bsc_render_complete_barber_dashboard' );
	} elseif ( function_exists( 'bsc_render_barber_dashboard' ) ) {
		add_action( $hook, 'bsc_render_barber_dashboard' );
	}
}
add_action( 'admin_menu', 'bsc_runtime_dedupe_dashboard_callbacks', 2000 );

/**
 * Load navigation/icon CSS before block templates render.
 *
 * Enqueuing from a shortcode or wp_footer is too late for wp_head, leaving the
 * mobile dock's inline SVGs unstyled and several hundred pixels wide.
 */
function bsc_runtime_enqueue_frontend_navigation_assets() {
	if ( ! is_admin() && function_exists( 'bsc_frontend_assets' ) ) {
		bsc_frontend_assets();
	}
}
add_action( 'wp_enqueue_scripts', 'bsc_runtime_enqueue_frontend_navigation_assets', 5 );

/** Load the final live-screenshot layout guard after theme/WooCommerce CSS. */
function bsc_runtime_enqueue_storefront_repairs() {
	if ( is_admin() ) {
		return;
	}
	$dependencies = array( 'barbershop-core-frontend' );
	if ( wp_style_is( 'persian-barbershop-woocommerce', 'registered' ) ) {
		$dependencies[] = 'persian-barbershop-woocommerce';
	}
	wp_enqueue_style(
		'barbershop-core-runtime-storefront',
		BSC_URL . 'assets/runtime-storefront.css',
		$dependencies,
		BSC_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'bsc_runtime_enqueue_storefront_repairs', 120 );

/** Keep the final dashboard overrides after both legacy admin stylesheets. */
function bsc_runtime_enqueue_admin_repairs( $hook ) {
	if ( 'toplevel_page_bsc-store-setup' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'barbershop-core-runtime-admin',
		BSC_URL . 'assets/runtime-admin.css',
		array( 'barbershop-core-qa-admin', 'barbershop-core-barber' ),
		BSC_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'bsc_runtime_enqueue_admin_repairs', 200 );

/**
 * Translate checkout privacy text that can remain English when language packs
 * are incomplete or a gateway prints the source string late.
 */
function bsc_runtime_checkout_privacy_translation( $translated, $text, $domain ) {
	if ( 'woocommerce' !== $domain ) {
		return $translated;
	}
	$map = array(
		'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our privacy policy.' => 'اطلاعات شخصی شما برای پردازش سفارش، پشتیبانی از تجربه خرید در این وب‌سایت و اهداف توضیح‌داده‌شده در سیاست حفظ حریم خصوصی استفاده می‌شود.',
		'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.' => 'اطلاعات شخصی شما برای پردازش سفارش، پشتیبانی از تجربه خرید در این وب‌سایت و اهداف توضیح‌داده‌شده در %s استفاده می‌شود.',
	);
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}
add_filter( 'gettext', 'bsc_runtime_checkout_privacy_translation', 999, 3 );

/** @return string */
function bsc_runtime_gateland_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'gateland_transactions';
}

/** @return bool */
function bsc_runtime_gateland_table_exists() {
	global $wpdb;
	$table = bsc_runtime_gateland_table_name();
	$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	return $table === $found;
}

/**
 * Find the installed Gateland main plugin basename without assuming its file.
 *
 * @return string
 */
function bsc_runtime_gateland_plugin_basename() {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	foreach ( (array) get_plugins( '/gateland' ) as $file => $data ) {
		unset( $data );
		return 'gateland/' . $file;
	}
	return '';
}

/**
 * Re-run Gateland's registered activation migration when an earlier activation
 * was interrupted (for example, before pdo_mysql was available).
 *
 * Calling the registered activation action is safer than including the active
 * plugin a second time in the same request and preserves Gateland's own schema.
 *
 * @return true|WP_Error
 */
function bsc_runtime_repair_gateland_schema() {
	if ( bsc_runtime_gateland_table_exists() ) {
		delete_option( 'bsc_gateland_schema_error' );
		return true;
	}
	$plugin = bsc_runtime_gateland_plugin_basename();
	if ( ! $plugin ) {
		return new WP_Error( 'bsc_gateland_missing', 'افزونه گیت‌لند نصب نشده است.' );
	}
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( ! is_plugin_active( $plugin ) ) {
		return new WP_Error( 'bsc_gateland_inactive', 'افزونه گیت‌لند فعال نیست.' );
	}

	do_action( 'activate_' . $plugin, false );

	if ( bsc_runtime_gateland_table_exists() ) {
		delete_option( 'bsc_gateland_schema_error' );
		update_option( 'bsc_gateland_schema_repaired', BSC_VERSION, false );
		return true;
	}
	return new WP_Error(
		'bsc_gateland_schema_missing',
		'جدول تراکنش‌های گیت‌لند پس از اجرای دوباره مهاجرت افزونه ساخته نشد.'
	);
}

/** Repair old installs from wp-admin or any installer/WP-CLI command. */
function bsc_runtime_maybe_repair_gateland_schema() {
	if ( bsc_runtime_gateland_table_exists() ) {
		return;
	}
	$is_cli = defined( 'WP_CLI' ) && WP_CLI;
	if ( ! $is_cli && ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) ) {
		return;
	}
	if ( get_transient( 'bsc_gateland_schema_repair_lock' ) ) {
		return;
	}
	set_transient( 'bsc_gateland_schema_repair_lock', 1, 60 );
	$result = bsc_runtime_repair_gateland_schema();
	delete_transient( 'bsc_gateland_schema_repair_lock' );
	if ( is_wp_error( $result ) ) {
		update_option( 'bsc_gateland_schema_error', $result->get_error_message(), false );
	}
}
add_action( 'init', 'bsc_runtime_maybe_repair_gateland_schema', 120 );

function bsc_runtime_gateland_schema_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$message = get_option( 'bsc_gateland_schema_error', '' );
	if ( ! $message || bsc_runtime_gateland_table_exists() ) {
		return;
	}
	printf(
		'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
		esc_html( 'ترمیم پایگاه‌داده گیت‌لند انجام نشد.' ),
		esc_html( $message )
	);
}
add_action( 'admin_notices', 'bsc_runtime_gateland_schema_notice' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	function bsc_runtime_cli_repair_gateland_schema() {
		$result = bsc_runtime_repair_gateland_schema();
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::success( 'Gateland transaction schema is ready.' );
	}
	WP_CLI::add_command( 'bsc gateland repair', 'bsc_runtime_cli_repair_gateland_schema' );
}
