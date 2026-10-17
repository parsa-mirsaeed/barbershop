<?php
/** Accessible product-only search without a separate search index. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bsc_product_search_form() {
    $query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
    ob_start(); ?>
    <form class="bsc-product-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <label class="screen-reader-text" for="bsc-product-search-input">جست‌وجوی محصولات</label>
        <input id="bsc-product-search-input" type="search" name="s" value="<?php echo esc_attr( $query ); ?>" placeholder="جست‌وجوی محصول..." autocomplete="off" maxlength="100" required>
        <input type="hidden" name="post_type" value="product">
        <button type="submit" aria-label="جست‌وجوی محصولات">جست‌وجو</button>
    </form>
    <?php return ob_get_clean();
}
add_shortcode( 'bsc_product_search', 'bsc_product_search_form' );

function bsc_is_product_search() {
    return is_search() && 'product' === get_query_var( 'post_type' );
}

function bsc_scope_product_search( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) { return; }
    if ( 'product' === $query->get( 'post_type' ) ) {
        $query->set( 'post_type', 'product' );
        $query->set( 'post_status', 'publish' );
    }
}
add_action( 'pre_get_posts', 'bsc_scope_product_search' );

function bsc_product_search_results() {
    if ( ! bsc_is_product_search() ) {
        return '<p>برای یافتن محصولات از جست‌وجوی فروشگاه استفاده کنید.</p>' . bsc_product_search_form();
    }
    if ( ! function_exists( 'wc_get_template_part' ) ) {
        return '<p>فروشگاه در حال حاضر در دسترس نیست.</p>';
    }
    global $wp_query;
    ob_start();
    echo bsc_product_search_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in form renderer.
    echo '<h1 class="bsc-product-search-title">نتایج جست‌وجوی محصولات</h1>';
    if ( ! $wp_query->have_posts() ) {
        echo '<p class="woocommerce-info">محصولی مطابق جست‌وجو پیدا نشد.</p>';
    } else {
        woocommerce_product_loop_start();
        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();
            wc_get_template_part( 'content', 'product' );
        }
        woocommerce_product_loop_end();
        wp_reset_postdata();
        $pagination = paginate_links( array(
            'total' => (int) $wp_query->max_num_pages,
            'current' => max( 1, get_query_var( 'paged' ) ),
            'type' => 'list',
            'add_args' => array( 'post_type' => 'product', 's' => get_search_query( false ) ),
        ) );
        if ( $pagination ) {
            echo '<nav class="bsc-product-search-pagination" aria-label="صفحات نتایج جست‌وجو">' . wp_kses_post( $pagination ) . '</nav>';
        }
    }
    return ob_get_clean();
}
add_shortcode( 'bsc_product_search_results', 'bsc_product_search_results' );
