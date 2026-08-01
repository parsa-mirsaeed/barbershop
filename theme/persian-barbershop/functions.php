<?php
/** Theme setup, typography, and performance-focused asset registration. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return a cache-safe asset version.
 *
 * @param string $relative_path Theme-relative file path.
 * @return string
 */
function pbs_asset_version( $relative_path ) {
	$path = get_theme_file_path( $relative_path );
	return is_file( $path ) ? (string) filemtime( $path ) : (string) wp_get_theme()->get( 'Version' );
}

/**
 * Return the locally installed Vazirmatn font URL.
 *
 * The one-command installer downloads the OFL-licensed font into uploads so the
 * public storefront does not depend on a third-party font request.
 *
 * @return string
 */
function pbs_vazirmatn_font_url() {
	$uploads = wp_upload_dir();
	$path    = trailingslashit( $uploads['basedir'] ) . 'barbershop-fonts/Vazirmatn-Variable.woff2';
	if ( ! is_file( $path ) ) {
		return '';
	}
	return trailingslashit( $uploads['baseurl'] ) . 'barbershop-fonts/Vazirmatn-Variable.woff2';
}

function pbs_setup() {
	load_theme_textdomain( 'persian-barbershop', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'custom-logo', array( 'height' => 160, 'width' => 160, 'flex-height' => true, 'flex-width' => true, 'unlink-homepage-logo' => true ) );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'pbs_setup' );

/**
 * Add the self-hosted font face to a registered stylesheet.
 *
 * @param string $handle Registered stylesheet handle.
 * @return void
 */
function pbs_add_font_face( $handle ) {
	$font_url = pbs_vazirmatn_font_url();
	if ( ! $font_url ) {
		return;
	}
	$css = sprintf(
		'@font-face{font-family:"Vazirmatn";src:url("%s") format("woff2-variations");font-style:normal;font-weight:100 900;font-display:swap;}',
		esc_url_raw( $font_url )
	);
	wp_add_inline_style( $handle, $css );
}

function pbs_assets() {
	wp_enqueue_style( 'persian-barbershop', get_stylesheet_uri(), array(), pbs_asset_version( 'style.css' ) );
	pbs_add_font_face( 'persian-barbershop' );

	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style(
			'persian-barbershop-woocommerce',
			get_theme_file_uri( 'assets/css/woocommerce.css' ),
			array( 'persian-barbershop' ),
			pbs_asset_version( 'assets/css/woocommerce.css' )
		);
	}

	wp_enqueue_script(
		'persian-barbershop-site',
		get_theme_file_uri( 'assets/js/site.js' ),
		array(),
		pbs_asset_version( 'assets/js/site.js' ),
		true
	);
	wp_script_add_data( 'persian-barbershop-site', 'strategy', 'defer' );
}
add_action( 'wp_enqueue_scripts', 'pbs_assets' );

function pbs_editor_assets() {
	wp_enqueue_style( 'persian-barbershop-editor', get_stylesheet_uri(), array(), pbs_asset_version( 'style.css' ) );
	pbs_add_font_face( 'persian-barbershop-editor' );
}
add_action( 'enqueue_block_editor_assets', 'pbs_editor_assets' );

/** Preload only the locally hosted primary font. */
function pbs_preload_font() {
	$font_url = pbs_vazirmatn_font_url();
	if ( $font_url ) {
		echo '<link rel="preload" href="' . esc_url( $font_url ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
}
add_action( 'wp_head', 'pbs_preload_font', 1 );

function pbs_pattern_category() {
	register_block_pattern_category( 'persian-barbershop', array( 'label' => 'بخش‌های فروشگاه آرایشگاه' ) );
}
add_action( 'init', 'pbs_pattern_category' );

function pbs_body_classes( $classes ) {
	$classes[] = 'pbs-brand-storefront';
	if ( pbs_vazirmatn_font_url() ) {
		$classes[] = 'pbs-vazirmatn-ready';
	}
	return $classes;
}
add_filter( 'body_class', 'pbs_body_classes' );

/**
 * Display a neutral bundled mark until the owner uploads a custom logo.
 * The normal Site Logo block takes over immediately after customization.
 *
 * @param string $block_content Rendered Site Logo block.
 * @return string
 */
function pbs_default_site_logo( $block_content ) {
	if ( has_custom_logo() || trim( $block_content ) ) {
		return $block_content;
	}
	$site_name = get_bloginfo( 'name' );
	if ( ! $site_name ) {
		$site_name = '[نام برند]';
	}
	return sprintf(
		'<div class="wp-block-site-logo pbs-default-logo"><a href="%1$s" class="custom-logo-link" rel="home"><img src="%2$s" class="custom-logo" width="112" height="112" alt="%3$s" decoding="async"></a></div>',
		esc_url( home_url( '/' ) ),
		esc_url( get_theme_file_uri( 'assets/images/brand-mark.svg' ) ),
		esc_attr( $site_name )
	);
}
add_filter( 'render_block_core/site-logo', 'pbs_default_site_logo' );

/** Remove legacy emoji assets to reduce blocking requests on a Persian storefront. */
function pbs_remove_emoji_assets() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'pbs_remove_emoji_assets' );
