<?php
/** Persistent cart, account access, product search, and mobile purchase navigation. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bsc_frontend_assets() {
	wp_enqueue_style( 'barbershop-core-frontend', BSC_URL . 'assets/frontend.css', array(), BSC_VERSION );
	wp_enqueue_script( 'barbershop-core-frontend', BSC_URL . 'assets/frontend.js', array(), BSC_VERSION, true );
	wp_script_add_data( 'barbershop-core-frontend', 'strategy', 'defer' );
}

/**
 * Return one of the plugin's accessible inline icons.
 *
 * @param string $name Icon name.
 * @return string
 */
function bsc_icon_svg( $name ) {
	$icons = array(
		'cart' => '<svg viewBox="0 0 32 32" focusable="false" aria-hidden="true"><path d="M4 5h3l3 15h14l3-10H9M12 26h.01M23 26h.01" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'user' => '<svg viewBox="0 0 32 32" focusable="false" aria-hidden="true"><circle cx="16" cy="11" r="5" fill="none" stroke="currentColor" stroke-width="2.4"/><path d="M6.5 27c1-6 4.3-9 9.5-9s8.5 3 9.5 9" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>',
		'home' => '<svg viewBox="0 0 32 32" focusable="false" aria-hidden="true"><path d="M5 15.5 16 6l11 9.5V27h-8v-7h-6v7H5z" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linejoin="round"/></svg>',
		'grid' => '<svg viewBox="0 0 32 32" focusable="false" aria-hidden="true"><rect x="5" y="5" width="8" height="8" rx="2" fill="none" stroke="currentColor" stroke-width="2.3"/><rect x="19" y="5" width="8" height="8" rx="2" fill="none" stroke="currentColor" stroke-width="2.3"/><rect x="5" y="19" width="8" height="8" rx="2" fill="none" stroke="currentColor" stroke-width="2.3"/><rect x="19" y="19" width="8" height="8" rx="2" fill="none" stroke="currentColor" stroke-width="2.3"/></svg>',
		'search' => '<svg viewBox="0 0 32 32" focusable="false" aria-hidden="true"><circle cx="14" cy="14" r="8" fill="none" stroke="currentColor" stroke-width="2.4"/><path d="m20 20 7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>',
	);
	return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

function bsc_cart_count() {
	return function_exists( 'WC' ) && WC()->cart ? absint( WC()->cart->get_cart_contents_count() ) : 0;
}

function bsc_cart_link_markup( $floating = false ) {
	if ( ! function_exists( 'wc_get_cart_url' ) ) {
		return '';
	}
	$count = bsc_cart_count();
	$class = $floating ? 'bsc-cart-link bsc-cart-link--floating' : 'bsc-cart-link';
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( wc_get_cart_url() ) . '" aria-label="' . esc_attr( sprintf( 'سبد خرید، %d کالا', $count ) ) . '"><span class="bsc-cart-link__icon" aria-hidden="true">' . bsc_icon_svg( 'cart' ) . '</span><span class="bsc-cart-link__label">سبد خرید</span><span class="bsc-cart-link__count" aria-live="polite">' . esc_html( $count ) . '</span></a>';
}

function bsc_account_destination() {
	if ( function_exists( 'bsc_is_store_staff' ) && bsc_is_store_staff() ) {
		return admin_url( 'admin.php?page=bsc-store-setup' );
	}
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
}

function bsc_account_label() {
	if ( function_exists( 'bsc_is_store_staff' ) && bsc_is_store_staff() ) {
		return 'مدیریت فروشگاه';
	}
	return is_user_logged_in() ? 'حساب من' : 'ورود / ثبت‌نام';
}

function bsc_account_link_markup( $compact = false ) {
	$class = $compact ? 'bsc-account-link bsc-account-link--compact' : 'bsc-account-link';
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( bsc_account_destination() ) . '"><span class="bsc-account-link__icon" aria-hidden="true">' . bsc_icon_svg( 'user' ) . '</span><span>' . esc_html( bsc_account_label() ) . '</span></a>';
}

function bsc_sanitize_navigation_markup( $markup ) {
	$allowed = array(
		'a' => array( 'class' => true, 'href' => true, 'aria-label' => true, 'aria-current' => true ),
		'nav' => array( 'class' => true, 'aria-label' => true ),
		'span' => array( 'class' => true, 'aria-hidden' => true, 'aria-live' => true ),
		'svg' => array( 'viewbox' => true, 'viewBox' => true, 'focusable' => true, 'aria-hidden' => true ),
		'path' => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ),
		'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
		'rect' => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
	);
	return wp_kses( $markup, $allowed );
}

function bsc_cart_link_shortcode() {
	bsc_frontend_assets();
	return bsc_sanitize_navigation_markup( bsc_cart_link_markup() );
}
add_shortcode( 'bsc_cart_link', 'bsc_cart_link_shortcode' );

function bsc_account_link_shortcode() {
	bsc_frontend_assets();
	return bsc_sanitize_navigation_markup( bsc_account_link_markup() );
}
add_shortcode( 'bsc_account_link', 'bsc_account_link_shortcode' );

function bsc_product_search_shortcode() {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return '';
	}
	bsc_frontend_assets();
	$query = get_search_query();
	return '<form role="search" method="get" class="bsc-product-search" action="' . esc_url( home_url( '/' ) ) . '"><label class="screen-reader-text" for="bsc-product-search-field">جست‌وجوی محصول</label><span aria-hidden="true">' . bsc_icon_svg( 'search' ) . '</span><input type="search" id="bsc-product-search-field" name="s" value="' . esc_attr( $query ) . '" placeholder="جست‌وجوی محصول…"><input type="hidden" name="post_type" value="product"><button type="submit">جست‌وجو</button></form>';
}
add_shortcode( 'bsc_product_search', 'bsc_product_search_shortcode' );

function bsc_cart_fragments( $fragments ) {
	$fragments['a.bsc-cart-link:not(.bsc-cart-link--floating)'] = bsc_cart_link_markup();
	$fragments['a.bsc-cart-link--floating'] = bsc_cart_link_markup( true );
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'bsc_cart_fragments' );

function bsc_mobile_dock() {
	if ( is_admin() || ! function_exists( 'wc_get_page_permalink' ) ) {
		return;
	}
	bsc_frontend_assets();
	$home_url    = home_url( '/' );
	$account_url = bsc_account_destination();
	$account_label = function_exists( 'bsc_is_store_staff' ) && bsc_is_store_staff() ? 'مدیریت' : 'حساب';
	$markup      = '<nav class="bsc-mobile-dock" aria-label="دسترسی سریع فروشگاه">';
	$markup     .= '<a href="' . esc_url( $home_url ) . '"><span class="bsc-mobile-dock__icon" aria-hidden="true">' . bsc_icon_svg( 'home' ) . '</span><span>خانه</span></a>';
	$markup     .= '<a href="' . esc_url( $home_url . '#categories' ) . '"><span class="bsc-mobile-dock__icon" aria-hidden="true">' . bsc_icon_svg( 'grid' ) . '</span><span>دسته‌ها</span></a>';
	$markup     .= '<a href="' . esc_url( $account_url ) . '"><span class="bsc-mobile-dock__icon" aria-hidden="true">' . bsc_icon_svg( 'user' ) . '</span><span>' . esc_html( $account_label ) . '</span></a>';
	$markup     .= bsc_cart_link_markup( true );
	$markup     .= '</nav>';
	echo bsc_sanitize_navigation_markup( $markup );
}
add_action( 'wp_footer', 'bsc_mobile_dock', 30 );
