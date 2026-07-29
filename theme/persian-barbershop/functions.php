<?php
/** Theme setup and asset registration. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pbs_setup() {
    load_theme_textdomain( 'persian-barbershop', get_template_directory() . '/languages' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'pbs_setup' );

function pbs_assets() {
    $theme = wp_get_theme();
    wp_enqueue_style( 'persian-barbershop', get_stylesheet_uri(), array(), $theme->get( 'Version' ) );
    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_style( 'persian-barbershop-woocommerce', get_theme_file_uri( 'assets/css/woocommerce.css' ), array( 'persian-barbershop' ), $theme->get( 'Version' ) );
    }
}
add_action( 'wp_enqueue_scripts', 'pbs_assets' );

function pbs_editor_assets() {
    wp_enqueue_style( 'persian-barbershop-editor', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
}
add_action( 'enqueue_block_editor_assets', 'pbs_editor_assets' );

function pbs_pattern_category() {
    register_block_pattern_category(
        'persian-barbershop',
        array( 'label' => esc_html_x( 'بخش‌های آرایشگاه', 'Block pattern category', 'persian-barbershop' ) )
    );
}
add_action( 'init', 'pbs_pattern_category' );
