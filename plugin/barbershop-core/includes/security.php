<?php
/** Layered WordPress/WooCommerce hardening that stays compatible with hosted payment redirects. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Send conservative browser security headers from WordPress as a fallback. */
function bsc_send_security_headers() {
	if ( headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );

	if ( function_exists( 'is_account_page' ) && ( is_account_page() || is_cart() || is_checkout() ) ) {
		header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
	}
}
add_action( 'send_headers', 'bsc_send_security_headers' );

/** Remove public WordPress version disclosure. */
function bsc_remove_generator_version() {
	return '';
}
add_filter( 'the_generator', 'bsc_remove_generator_version' );

/** Disable XML-RPC by default; the owner can change the option for a known integration. */
function bsc_xmlrpc_enabled( $enabled ) {
	return 'no' === get_option( 'bsc_disable_xmlrpc', 'yes' ) ? $enabled : false;
}
add_filter( 'xmlrpc_enabled', 'bsc_xmlrpc_enabled' );

/** Use a generic login error so usernames are not disclosed. */
function bsc_generic_login_error() {
	return 'اطلاعات ورود صحیح نیست. دوباره تلاش کنید یا از بازیابی رمز عبور استفاده کنید.';
}
add_filter( 'login_errors', 'bsc_generic_login_error' );

/** Block unauthenticated REST user enumeration while preserving authorized API use. */
function bsc_restrict_rest_user_routes( $result, $server, $request ) {
	unset( $server );
	$route = $request->get_route();
	if ( 0 === strpos( $route, '/wp/v2/users' ) && ! current_user_can( 'list_users' ) ) {
		return new WP_Error( 'bsc_rest_forbidden', 'دسترسی به فهرست کاربران مجاز نیست.', array( 'status' => 403 ) );
	}
	return $result;
}
add_filter( 'rest_pre_dispatch', 'bsc_restrict_rest_user_routes', 10, 3 );

/** Prevent browser caching of account and transaction pages. */
function bsc_private_page_nocache() {
	if ( function_exists( 'is_account_page' ) && ( is_account_page() || is_cart() || is_checkout() ) ) {
		nocache_headers();
	}
}
add_action( 'template_redirect', 'bsc_private_page_nocache', 1 );
