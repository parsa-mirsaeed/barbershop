<?php
/**
 * Plugin Name: Barbershop Core
 * Description: Privacy-aware before/after portfolio controls and configurable WooCommerce order alerts.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: Project Contributors
 * License: GPL-2.0-or-later
 * Text Domain: barbershop-core
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const BSC_VERSION = '1.0.0';
const BSC_POST_TYPE = 'barbershop_result';

function bsc_declare_compatibility() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
}
add_action( 'before_woocommerce_init', 'bsc_declare_compatibility' );

function bsc_capabilities() {
    return array(
        'edit_barbershop_result', 'read_barbershop_result', 'delete_barbershop_result',
        'edit_barbershop_results', 'edit_others_barbershop_results', 'publish_barbershop_results',
        'read_private_barbershop_results', 'delete_barbershop_results',
        'delete_private_barbershop_results', 'delete_published_barbershop_results',
        'delete_others_barbershop_results', 'edit_private_barbershop_results',
        'edit_published_barbershop_results', 'upload_files',
    );
}

function bsc_register_post_type() {
    register_post_type(
        BSC_POST_TYPE,
        array(
            'labels' => array(
                'name' => __( 'Before & After', 'barbershop-core' ),
                'singular_name' => __( 'Before & After result', 'barbershop-core' ),
                'add_new_item' => __( 'Add result', 'barbershop-core' ),
                'edit_item' => __( 'Edit result', 'barbershop-core' ),
            ),
            'public' => true,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-images-alt2',
            'supports' => array( 'title', 'editor', 'revisions' ),
            'capability_type' => array( 'barbershop_result', 'barbershop_results' ),
            'map_meta_cap' => true,
        )
    );
}
add_action( 'init', 'bsc_register_post_type' );

function bsc_grant_caps() {
    foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) {
        $role = get_role( $role_name );
        if ( ! $role ) { continue; }
        foreach ( bsc_capabilities() as $capability ) { $role->add_cap( $capability ); }
    }
}
register_activation_hook( __FILE__, 'bsc_grant_caps' );
add_action( 'woocommerce_installed', 'bsc_grant_caps' );

function bsc_register_meta() {
    $auth = static function () { return current_user_can( 'edit_barbershop_results' ); };
    foreach ( array( '_bsc_before_image_id', '_bsc_after_image_id' ) as $key ) {
        register_post_meta( BSC_POST_TYPE, $key, array( 'type'=>'integer', 'single'=>true, 'show_in_rest'=>true, 'sanitize_callback'=>'absint', 'auth_callback'=>$auth ) );
    }
    register_post_meta( BSC_POST_TYPE, '_bsc_service_label', array( 'type'=>'string', 'single'=>true, 'show_in_rest'=>true, 'sanitize_callback'=>'sanitize_text_field', 'auth_callback'=>$auth ) );
    register_post_meta( BSC_POST_TYPE, '_bsc_consent_confirmed', array( 'type'=>'boolean', 'single'=>true, 'show_in_rest'=>true, 'sanitize_callback'=>'rest_sanitize_boolean', 'auth_callback'=>$auth ) );
}
add_action( 'init', 'bsc_register_meta' );

function bsc_add_meta_box() {
    add_meta_box( 'bsc-images', __( 'Before/after images and publication consent', 'barbershop-core' ), 'bsc_render_meta_box', BSC_POST_TYPE, 'normal', 'high' );
}
add_action( 'add_meta_boxes_' . BSC_POST_TYPE, 'bsc_add_meta_box' );

function bsc_render_meta_box( $post ) {
    wp_nonce_field( 'bsc_save_result', 'bsc_result_nonce' );
    $values = array(
        'before' => absint( get_post_meta( $post->ID, '_bsc_before_image_id', true ) ),
        'after' => absint( get_post_meta( $post->ID, '_bsc_after_image_id', true ) ),
    );
    $service = (string) get_post_meta( $post->ID, '_bsc_service_label', true );
    $consent = (bool) get_post_meta( $post->ID, '_bsc_consent_confirmed', true );
    ?>
    <p><strong><?php esc_html_e( 'Do not enter customer names, phone numbers, social handles, appointment details, or other private information in this post.', 'barbershop-core' ); ?></strong></p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px">
    <?php foreach ( $values as $side => $image_id ) : ?>
        <div><label><strong><?php echo esc_html( 'before' === $side ? __( 'Before image', 'barbershop-core' ) : __( 'After image', 'barbershop-core' ) ); ?></strong></label>
        <input type="hidden" id="bsc_<?php echo esc_attr( $side ); ?>_image_id" name="bsc_<?php echo esc_attr( $side ); ?>_image_id" value="<?php echo esc_attr( $image_id ); ?>">
        <p><img data-bsc-preview="bsc_<?php echo esc_attr( $side ); ?>_image_id" src="<?php echo esc_url( $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '' ); ?>" alt="" style="max-width:100%;height:auto" <?php hidden( ! $image_id ); ?>></p>
        <button type="button" class="button" data-bsc-media-target="bsc_<?php echo esc_attr( $side ); ?>_image_id"><?php esc_html_e( 'Choose image', 'barbershop-core' ); ?></button></div>
    <?php endforeach; ?>
    </div>
    <p><label for="bsc_service_label"><strong><?php esc_html_e( 'Service label', 'barbershop-core' ); ?></strong></label><br><input class="widefat" type="text" id="bsc_service_label" name="bsc_service_label" maxlength="80" value="<?php echo esc_attr( $service ); ?>"></p>
    <p><label><input type="checkbox" name="bsc_consent_confirmed" value="1" <?php checked( $consent ); ?>> <strong><?php esc_html_e( 'I confirm that documented publication consent has been obtained for both images.', 'barbershop-core' ); ?></strong></label></p>
    <?php
}

function bsc_admin_assets( $hook ) {
    $screen = get_current_screen();
    if ( ! $screen || BSC_POST_TYPE !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) { return; }
    wp_enqueue_media();
    wp_enqueue_script( 'barbershop-core-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.js', array(), BSC_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'bsc_admin_assets' );

function bsc_save_result( $post_id, $post ) {
    if ( BSC_POST_TYPE !== $post->post_type || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) { return; }
    if ( ! isset( $_POST['bsc_result_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_result_nonce'] ) ), 'bsc_save_result' ) ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

    $before = isset( $_POST['bsc_before_image_id'] ) ? absint( $_POST['bsc_before_image_id'] ) : 0;
    $after = isset( $_POST['bsc_after_image_id'] ) ? absint( $_POST['bsc_after_image_id'] ) : 0;
    $label = isset( $_POST['bsc_service_label'] ) ? sanitize_text_field( wp_unslash( $_POST['bsc_service_label'] ) ) : '';
    $consent = isset( $_POST['bsc_consent_confirmed'] ) ? 1 : 0;

    update_post_meta( $post_id, '_bsc_before_image_id', $before );
    update_post_meta( $post_id, '_bsc_after_image_id', $after );
    update_post_meta( $post_id, '_bsc_service_label', $label );
    update_post_meta( $post_id, '_bsc_consent_confirmed', $consent );

    if ( 'publish' === $post->post_status && ( ! $before || ! $after || ! $consent ) ) {
        remove_action( 'save_post_' . BSC_POST_TYPE, 'bsc_save_result', 10 );
        wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
        add_action( 'save_post_' . BSC_POST_TYPE, 'bsc_save_result', 10, 2 );
        set_transient( 'bsc_publish_blocked_' . get_current_user_id(), true, 60 );
    }
}
add_action( 'save_post_' . BSC_POST_TYPE, 'bsc_save_result', 10, 2 );

function bsc_admin_notice() {
    $key = 'bsc_publish_blocked_' . get_current_user_id();
    if ( ! get_transient( $key ) ) { return; }
    delete_transient( $key );
    printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html__( 'The result stayed in Draft. Add both images and confirm publication consent before publishing.', 'barbershop-core' ) );
}
add_action( 'admin_notices', 'bsc_admin_notice' );

function bsc_gallery_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'limit' => 6 ), $atts, 'barbershop_before_after_gallery' );
    $query = new WP_Query( array(
        'post_type' => BSC_POST_TYPE, 'post_status' => 'publish',
        'posts_per_page' => min( 24, max( 1, absint( $atts['limit'] ) ) ),
        'no_found_rows' => true,
        'meta_query' => array( array( 'key' => '_bsc_consent_confirmed', 'value' => '1' ) ),
    ) );
    if ( ! $query->have_posts() ) { return '<p class="has-text-align-center">' . esc_html__( 'Portfolio results will appear here after approved entries are published.', 'barbershop-core' ) . '</p>'; }
    wp_enqueue_style( 'barbershop-core-frontend', plugin_dir_url( __FILE__ ) . 'assets/frontend.css', array(), BSC_VERSION );
    wp_enqueue_script( 'barbershop-core-frontend', plugin_dir_url( __FILE__ ) . 'assets/frontend.js', array(), BSC_VERSION, true );
    ob_start(); echo '<div class="bsc-ba-grid">';
    while ( $query->have_posts() ) { $query->the_post();
        $before = absint( get_post_meta( get_the_ID(), '_bsc_before_image_id', true ) );
        $after = absint( get_post_meta( get_the_ID(), '_bsc_after_image_id', true ) );
        $service = (string) get_post_meta( get_the_ID(), '_bsc_service_label', true );
        if ( ! $before || ! $after ) { continue; }
        echo '<article class="bsc-ba" data-bsc-before-after><div class="bsc-ba__media">';
        echo wp_get_attachment_image( $before, 'large', false, array( 'class'=>'bsc-ba__before', 'loading'=>'lazy' ) );
        echo wp_get_attachment_image( $after, 'large', false, array( 'class'=>'bsc-ba__after', 'loading'=>'lazy' ) );
        echo '<div class="bsc-ba__labels"><span>' . esc_html__( 'Before', 'barbershop-core' ) . '</span><span>' . esc_html__( 'After', 'barbershop-core' ) . '</span></div>';
        echo '<input class="bsc-ba__range" type="range" min="0" max="100" value="50" aria-label="' . esc_attr__( 'Compare before and after images', 'barbershop-core' ) . '">';
        echo '</div><div class="bsc-ba__body"><h3>' . esc_html( get_the_title() ) . '</h3>';
        if ( $service ) { echo '<p>' . esc_html( $service ) . '</p>'; }
        echo '</div></article>';
    }
    echo '</div>'; wp_reset_postdata(); return (string) ob_get_clean();
}
add_shortcode( 'barbershop_before_after_gallery', 'bsc_gallery_shortcode' );

function bsc_add_settings_section( $sections ) { $sections['barbershop'] = __( 'Barbershop settings', 'barbershop-core' ); return $sections; }
add_filter( 'woocommerce_get_sections_general', 'bsc_add_settings_section' );

function bsc_general_settings( $settings, $section ) {
    if ( 'barbershop' !== $section ) { return $settings; }
    return array(
        array( 'title'=>__( 'Order notifications', 'barbershop-core' ), 'type'=>'title', 'id'=>'bsc_settings' ),
        array( 'title'=>__( 'Order alert email', 'barbershop-core' ), 'desc'=>__( 'New-order alerts are sent here.', 'barbershop-core' ), 'id'=>'bsc_order_email', 'type'=>'email', 'default'=>get_option( 'admin_email' ), 'desc_tip'=>true ),
        array( 'type'=>'sectionend', 'id'=>'bsc_settings' ),
    );
}
add_filter( 'woocommerce_get_settings_general', 'bsc_general_settings', 10, 2 );

function bsc_new_order_recipient( $recipient ) {
    $email = sanitize_email( get_option( 'bsc_order_email', '' ) );
    return is_email( $email ) ? $email : $recipient;
}
add_filter( 'woocommerce_email_recipient_new_order', 'bsc_new_order_recipient' );

function bsc_privacy_policy() {
    if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
        wp_add_privacy_policy_content( __( 'Barbershop store', 'barbershop-core' ), wp_kses_post( '<p>' . __( 'WooCommerce stores order and fulfilment information. Card details should be entered on the payment provider page and are not stored by this plugin. Published before/after images require documented permission.', 'barbershop-core' ) . '</p>' ) );
    }
}
add_action( 'admin_init', 'bsc_privacy_policy' );
