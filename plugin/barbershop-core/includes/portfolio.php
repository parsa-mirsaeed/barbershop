<?php
/** Privacy-aware before/after portfolio. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bsc_register_post_type() {
	register_post_type( BSC_POST_TYPE, array(
		'labels' => array(
			'name' => 'قبل و بعد',
			'singular_name' => 'نمونه قبل و بعد',
			'add_new_item' => 'افزودن نمونه',
			'edit_item' => 'ویرایش نمونه',
		),
		'public' => true,
		'publicly_queryable' => false,
		'exclude_from_search' => true,
		'show_in_rest' => true,
		'menu_icon' => 'dashicons-images-alt2',
		'supports' => array( 'title', 'editor', 'revisions' ),
		'capability_type' => array( 'barbershop_result', 'barbershop_results' ),
		'map_meta_cap' => true,
	) );
}
add_action( 'init', 'bsc_register_post_type' );

function bsc_register_meta() {
	$auth = static function () { return current_user_can( 'edit_barbershop_results' ); };
	foreach ( array( '_bsc_before_image_id', '_bsc_after_image_id' ) as $key ) {
		register_post_meta( BSC_POST_TYPE, $key, array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'absint', 'auth_callback' => $auth ) );
	}
	register_post_meta( BSC_POST_TYPE, '_bsc_service_label', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => $auth ) );
	register_post_meta( BSC_POST_TYPE, '_bsc_consent_confirmed', array( 'type' => 'boolean', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'rest_sanitize_boolean', 'auth_callback' => $auth ) );
}
add_action( 'init', 'bsc_register_meta' );

function bsc_add_meta_box() {
	add_meta_box( 'bsc-images', 'تصاویر قبل و بعد و اجازه انتشار', 'bsc_render_meta_box', BSC_POST_TYPE, 'normal', 'high' );
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
	<p><strong>نام، شماره تماس، شناسه شبکه اجتماعی، جزئیات نوبت یا اطلاعات خصوصی مشتری را در این نوشته وارد نکنید.</strong></p>
	<div class="bsc-media-grid">
	<?php foreach ( $values as $side => $image_id ) : ?>
		<div><label><strong><?php echo esc_html( 'before' === $side ? 'تصویر قبل' : 'تصویر بعد' ); ?></strong></label>
		<input type="hidden" id="bsc_<?php echo esc_attr( $side ); ?>_image_id" name="bsc_<?php echo esc_attr( $side ); ?>_image_id" value="<?php echo esc_attr( $image_id ); ?>">
		<p><img data-bsc-preview="bsc_<?php echo esc_attr( $side ); ?>_image_id" src="<?php echo esc_url( $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '' ); ?>" alt="" <?php hidden( ! $image_id ); ?>></p>
		<button type="button" class="button" data-bsc-media-target="bsc_<?php echo esc_attr( $side ); ?>_image_id">انتخاب تصویر</button></div>
	<?php endforeach; ?>
	</div>
	<p><label for="bsc_service_label"><strong>عنوان خدمت</strong></label><br><input class="widefat" type="text" id="bsc_service_label" name="bsc_service_label" maxlength="80" value="<?php echo esc_attr( $service ); ?>"></p>
	<p><label><input type="checkbox" name="bsc_consent_confirmed" value="1" <?php checked( $consent ); ?>> <strong>تأیید می‌کنم اجازه مستند انتشار هر دو تصویر دریافت شده است.</strong></label></p>
	<?php
}

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
	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( 'نمونه در حالت پیش‌نویس ماند. هر دو تصویر و تأیید اجازه انتشار لازم است.' ) );
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
	if ( ! $query->have_posts() ) { return '<p class="has-text-align-center">نمونه‌های تأییدشده پس از انتشار در این بخش نمایش داده می‌شوند.</p>'; }
	wp_enqueue_style( 'barbershop-core-frontend', BSC_URL . 'assets/frontend.css', array(), BSC_VERSION );
	wp_enqueue_script( 'barbershop-core-frontend', BSC_URL . 'assets/frontend.js', array(), BSC_VERSION, true );
	ob_start(); echo '<div class="bsc-ba-grid">';
	while ( $query->have_posts() ) { $query->the_post();
		$before = absint( get_post_meta( get_the_ID(), '_bsc_before_image_id', true ) );
		$after = absint( get_post_meta( get_the_ID(), '_bsc_after_image_id', true ) );
		$service = (string) get_post_meta( get_the_ID(), '_bsc_service_label', true );
		if ( ! $before || ! $after ) { continue; }
		echo '<article class="bsc-ba" data-bsc-before-after><div class="bsc-ba__media">';
		echo wp_get_attachment_image( $before, 'large', false, array( 'class' => 'bsc-ba__before', 'loading' => 'lazy' ) );
		echo wp_get_attachment_image( $after, 'large', false, array( 'class' => 'bsc-ba__after', 'loading' => 'lazy' ) );
		echo '<div class="bsc-ba__labels"><span>قبل</span><span>بعد</span></div><input class="bsc-ba__range" type="range" min="0" max="100" value="50" aria-label="مقایسه تصاویر قبل و بعد">';
		echo '</div><div class="bsc-ba__body"><h3>' . esc_html( get_the_title() ) . '</h3>';
		if ( $service ) { echo '<p>' . esc_html( $service ) . '</p>'; }
		echo '</div></article>';
	}
	echo '</div>'; wp_reset_postdata(); return (string) ob_get_clean();
}
add_shortcode( 'barbershop_before_after_gallery', 'bsc_gallery_shortcode' );
