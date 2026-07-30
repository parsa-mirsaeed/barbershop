<?php
/** Additional dashboard fields for repeated gallery captions. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bsc_gallery_caption_defaults() {
	return array(
		'gallery_1_title' => 'عنوان نمونه‌کار اول',
		'gallery_2_title' => 'عنوان نمونه‌کار دوم',
		'gallery_3_title' => 'عنوان نمونه‌کار سوم',
	);
}

function bsc_get_gallery_captions() {
	$value = get_option( 'bsc_gallery_captions', array() );
	return wp_parse_args( is_array( $value ) ? $value : array(), bsc_gallery_caption_defaults() );
}

function bsc_save_gallery_captions() {
	if ( empty( $_POST['bsc_gallery_caption_action'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_barbershop' ) ) {
		wp_die( 'دسترسی کافی ندارید.' );
	}
	check_admin_referer( 'bsc_save_gallery_captions', 'bsc_gallery_caption_nonce' );
	$defaults = bsc_gallery_caption_defaults();
	$captions = array();
	foreach ( $defaults as $key => $default ) {
		$value = isset( $_POST['bsc_gallery_caption'][ $key ] ) ? sanitize_text_field( wp_unslash( $_POST['bsc_gallery_caption'][ $key ] ) ) : '';
		$captions[ $key ] = '' !== $value ? $value : $default;
	}
	update_option( 'bsc_gallery_captions', $captions, false );
	set_transient( 'bsc_gallery_captions_saved_' . get_current_user_id(), 1, 60 );
	wp_safe_redirect( admin_url( 'admin.php?page=bsc-store-setup#bsc-gallery-captions' ) );
	exit;
}
add_action( 'admin_init', 'bsc_save_gallery_captions' );

function bsc_render_gallery_caption_replacements( $block_content, $block ) {
	unset( $block );
	$captions = bsc_get_gallery_captions();
	return strtr(
		$block_content,
		array(
			'[عنوان نمونه‌کار اول]' => esc_html( $captions['gallery_1_title'] ),
			'[عنوان نمونه‌کار دوم]' => esc_html( $captions['gallery_2_title'] ),
			'[عنوان نمونه‌کار سوم]' => esc_html( $captions['gallery_3_title'] ),
		)
	);
}
add_filter( 'render_block', 'bsc_render_gallery_caption_replacements', 46, 2 );

function bsc_render_gallery_caption_panel() {
	$captions = bsc_get_gallery_captions();
	?>
	<div class="wrap bsc-barber-dashboard" dir="rtl" id="bsc-gallery-captions">
		<section class="bsc-admin-panel">
			<div class="bsc-panel-heading"><div><h2>عنوان تصاویر جای‌نگهدار نمونه‌کار</h2><p>عنوان هر یک از سه تصویر نمونه را مستقل وارد کنید. نمونه‌های واقعی قبل و بعد از بخش اختصاصی «قبل و بعد» مدیریت می‌شوند.</p></div></div>
			<form method="post" class="bsc-dashboard-form">
				<?php wp_nonce_field( 'bsc_save_gallery_captions', 'bsc_gallery_caption_nonce' ); ?>
				<input type="hidden" name="bsc_gallery_caption_action" value="save">
				<div class="bsc-dashboard-field-grid">
					<?php foreach ( $captions as $key => $value ) : ?>
						<label><span><?php echo esc_html( str_replace( array( 'gallery_', '_title' ), array( 'نمونه‌کار ', '' ), $key ) ); ?></span><input type="text" name="bsc_gallery_caption[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>"></label>
					<?php endforeach; ?>
				</div>
				<p><button type="submit" class="button button-primary">ذخیره عنوان نمونه‌کارها</button></p>
			</form>
			<?php if ( get_transient( 'bsc_gallery_captions_saved_' . get_current_user_id() ) ) : delete_transient( 'bsc_gallery_captions_saved_' . get_current_user_id() ); ?><div class="notice notice-success inline"><p>عنوان نمونه‌کارها ذخیره شد.</p></div><?php endif; ?>
		</section>
	</div>
	<?php
}

function bsc_render_complete_barber_dashboard() {
	bsc_render_barber_dashboard();
	bsc_render_gallery_caption_panel();
}

function bsc_complete_barber_dashboard_menu() {
	remove_menu_page( 'bsc-store-setup' );
	add_menu_page( 'داشبورد آرایشگر', 'داشبورد آرایشگر', 'manage_barbershop', 'bsc-store-setup', 'bsc_render_complete_barber_dashboard', 'dashicons-store', 3 );
}
add_action( 'admin_menu', 'bsc_complete_barber_dashboard_menu', 1000 );
