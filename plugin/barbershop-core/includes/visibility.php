<?php
/** Merchant-owned storefront visibility options. All sections are visible by default. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bsc_visibility_sections() {
    return array(
        'home' => 'بخش اصلی',
        'categories' => 'دسته‌بندی‌ها',
        'products' => 'محصولات',
        'services' => 'خدمات',
        'gallery' => 'قبل و بعد / نمونه‌کارها',
        'articles' => 'مقاله‌ها',
        'reviews' => 'نظر مشتریان',
        'contact' => 'تماس با ما',
    );
}

function bsc_visibility_enabled( $section ) {
    if ( 'product_reviews' === $section ) {
        return 'no' !== get_option( 'bsc_show_product_reviews', 'yes' );
    }
    if ( ! isset( bsc_visibility_sections()[ $section ] ) ) { return true; }
    return 'no' !== get_option( 'bsc_show_section_' . $section, 'yes' );
}

function bsc_visibility_settings( $settings, $section ) {
    if ( 'barbershop' !== $section ) { return $settings; }
    $settings[] = array( 'title' => 'نمایش بخش‌های سایت', 'type' => 'title', 'id' => 'bsc_visibility', 'desc' => 'خاموش کردن فقط نمایش را تغییر می‌دهد؛ عکس‌ها، دیدگاه‌ها و محتوای ذخیره‌شده حذف نمی‌شوند.' );
    foreach ( bsc_visibility_sections() as $id => $name ) {
        $settings[] = array(
            'title' => $name, 'id' => 'bsc_show_section_' . $id,
            'type' => 'checkbox', 'default' => 'yes', 'desc' => 'نمایش',
        );
    }
    $settings[] = array( 'title' => 'دیدگاه محصولات', 'id' => 'bsc_show_product_reviews', 'type' => 'checkbox', 'default' => 'yes', 'desc' => 'نمایش دیدگاه‌ها در صفحه محصول' );
    $settings[] = array( 'type' => 'sectionend', 'id' => 'bsc_visibility' );
    return $settings;
}
add_filter( 'woocommerce_get_settings_general', 'bsc_visibility_settings', 20, 2 );

/** Only affect named top-level storefront sections, not merchant content elsewhere. */
function bsc_visibility_render_block( $html, $block ) {
    if ( is_admin() || ! is_front_page() || empty( $block['attrs']['anchor'] ) ) { return $html; }
    $anchor = $block['attrs']['anchor'];
    if ( isset( bsc_visibility_sections()[ $anchor ] ) && ! bsc_visibility_enabled( $anchor ) ) {
        return '';
    }
    return $html;
}
add_filter( 'render_block', 'bsc_visibility_render_block', 100, 2 );

function bsc_visibility_navigation_link( $html, $block ) {
    if ( ! is_front_page() || empty( $block['attrs']['url'] ) ) { return $html; }
    $fragment = wp_parse_url( $block['attrs']['url'], PHP_URL_FRAGMENT );
    if ( $fragment && isset( bsc_visibility_sections()[ $fragment ] ) && ! bsc_visibility_enabled( $fragment ) ) {
        return '';
    }
    return $html;
}
add_filter( 'render_block_core/navigation-link', 'bsc_visibility_navigation_link', 100, 2 );

function bsc_visibility_product_tabs( $tabs ) {
    if ( ! bsc_visibility_enabled( 'product_reviews' ) ) { unset( $tabs['reviews'] ); }
    return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'bsc_visibility_product_tabs', 110 );

function bsc_visibility_product_comments( $open, $post_id ) {
    if ( 'product' === get_post_type( $post_id ) && ! bsc_visibility_enabled( 'product_reviews' ) ) {
        return false;
    }
    return $open;
}
add_filter( 'comments_open', 'bsc_visibility_product_comments', 100, 2 );
