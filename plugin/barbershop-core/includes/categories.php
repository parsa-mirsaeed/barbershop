<?php
/** Editable product-category icons and storefront category grid. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bsc_category_icon_field_add() {
	wp_nonce_field( 'bsc_save_category_icon', 'bsc_category_icon_nonce' );
	?>
	<div class="form-field term-bsc-icon-wrap">
		<label for="bsc_category_icon_id">آیکون دسته‌بندی</label>
		<input type="hidden" id="bsc_category_icon_id" name="bsc_category_icon_id" value="">
		<div class="bsc-term-icon-preview" data-bsc-preview-wrap="bsc_category_icon_id" aria-live="polite"></div>
		<div class="bsc-media-actions">
			<button type="button" class="button button-primary" data-bsc-media-target="bsc_category_icon_id">انتخاب آیکون</button>
			<button type="button" class="button button-link-delete" data-bsc-media-remove="bsc_category_icon_id">حذف آیکون</button>
		</div>
		<p>تصویر مربع و ساده با پس‌زمینه شفاف انتخاب کنید؛ حداقل اندازه پیشنهادی ۵۱۲×۵۱۲ پیکسل است.</p>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'bsc_category_icon_field_add' );

function bsc_category_icon_field_edit( $term ) {
	$icon_id = absint( get_term_meta( $term->term_id, '_bsc_category_icon_id', true ) );
	$image   = $icon_id ? wp_get_attachment_image_url( $icon_id, 'medium' ) : '';
	wp_nonce_field( 'bsc_save_category_icon', 'bsc_category_icon_nonce' );
	?>
	<tr class="form-field term-bsc-icon-wrap">
		<th scope="row"><label for="bsc_category_icon_id">آیکون دسته‌بندی</label></th>
		<td>
			<input type="hidden" id="bsc_category_icon_id" name="bsc_category_icon_id" value="<?php echo esc_attr( $icon_id ); ?>">
			<div class="bsc-term-icon-preview" data-bsc-preview-wrap="bsc_category_icon_id" aria-live="polite">
				<img data-bsc-preview="bsc_category_icon_id" src="<?php echo esc_url( $image ); ?>" alt="پیش‌نمایش آیکون دسته‌بندی" <?php hidden( ! $image ); ?>>
			</div>
			<div class="bsc-media-actions">
				<button type="button" class="button button-primary" data-bsc-media-target="bsc_category_icon_id">انتخاب یا تغییر آیکون</button>
				<button type="button" class="button button-link-delete" data-bsc-media-remove="bsc_category_icon_id">حذف آیکون</button>
			</div>
			<p class="description">تصویر مربع و ساده با پس‌زمینه شفاف انتخاب کنید؛ حداقل اندازه پیشنهادی ۵۱۲×۵۱۲ پیکسل است.</p>
		</td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'bsc_category_icon_field_edit' );

function bsc_save_category_icon( $term_id ) {
	if ( ! isset( $_POST['bsc_category_icon_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_category_icon_nonce'] ) ), 'bsc_save_category_icon' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_product_terms' ) ) {
		return;
	}
	$icon_id = isset( $_POST['bsc_category_icon_id'] ) ? absint( $_POST['bsc_category_icon_id'] ) : 0;
	if ( $icon_id && 'attachment' === get_post_type( $icon_id ) ) {
		update_term_meta( $term_id, '_bsc_category_icon_id', $icon_id );
	} else {
		delete_term_meta( $term_id, '_bsc_category_icon_id' );
	}
}
add_action( 'created_product_cat', 'bsc_save_category_icon' );
add_action( 'edited_product_cat', 'bsc_save_category_icon' );

function bsc_allowed_icon_html() {
	return array(
		'span' => array( 'class' => true ),
		'img' => array( 'class' => true, 'src' => true, 'alt' => true, 'loading' => true, 'width' => true, 'height' => true, 'srcset' => true, 'sizes' => true, 'decoding' => true ),
		'svg' => array( 'viewbox' => true, 'viewBox' => true, 'role' => true, 'aria-hidden' => true, 'focusable' => true ),
		'path' => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linejoin' => true, 'stroke-linecap' => true ),
		'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
		'line' => array( 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true ),
		'rect' => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
	);
}

function bsc_sanitize_icon_markup( $markup ) {
	return wp_kses( $markup, bsc_allowed_icon_html() );
}

function bsc_default_icon_svg( $icon_key ) {
	$common = 'viewBox="0 0 96 96" role="img" aria-hidden="true" focusable="false"';
	if ( 'hair-care' === $icon_key ) {
		return '<svg ' . $common . '><path d="M27 24h42l-4 51H31z" fill="none" stroke="currentColor" stroke-width="6" stroke-linejoin="round"/><path d="M34 24V15h28v9M39 44h18M39 56h18" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"/></svg>';
	}
	if ( 'skin-care' === $icon_key ) {
		return '<svg ' . $common . '><circle cx="48" cy="47" r="28" fill="none" stroke="currentColor" stroke-width="6"/><path d="M33 48c7 9 23 9 30 0M37 38h1M58 38h1M48 19c-2 9-8 14-17 16" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"/></svg>';
	}
	if ( 'tools' === $icon_key ) {
		return '<svg ' . $common . '><path d="M23 73l26-26M55 41l18-18M65 17l14 14M17 61l18 18" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"/><circle cx="31" cy="65" r="11" fill="none" stroke="currentColor" stroke-width="6"/></svg>';
	}
	return '<svg ' . $common . '><path d="M28 72V35c0-8 6-14 14-14h12c8 0 14 6 14 14v37z" fill="none" stroke="currentColor" stroke-width="6" stroke-linejoin="round"/><path d="M40 21V12h16v9M37 47h22M42 58h12" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"/></svg>';
}

function bsc_term_icon_markup( $term ) {
	$icon_id = absint( get_term_meta( $term->term_id, '_bsc_category_icon_id', true ) );
	if ( $icon_id ) {
		$image = wp_get_attachment_image( $icon_id, 'medium', false, array( 'class' => 'bsc-category-card__image', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) );
		if ( $image ) { return $image; }
	}
	$icon_key = sanitize_key( get_term_meta( $term->term_id, '_bsc_default_icon', true ) );
	return '<span class="bsc-category-card__fallback">' . bsc_default_icon_svg( $icon_key ) . '</span>';
}

function bsc_product_categories_shortcode( $atts ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return '<p class="bsc-store-notice">برای نمایش دسته‌بندی‌ها ابتدا ووکامرس را فعال کنید.</p>';
	}
	$atts = shortcode_atts( array( 'limit' => 8 ), $atts, 'bsc_product_categories' );
	$default_category = absint( get_option( 'default_product_cat', 0 ) );
	$terms = get_terms( array(
		'taxonomy' => 'product_cat',
		'parent' => 0,
		'hide_empty' => false,
		'exclude' => $default_category ? array( $default_category ) : array(),
		'number' => min( 12, max( 1, absint( $atts['limit'] ) ) ),
		'orderby' => 'menu_order',
		'order' => 'ASC',
	) );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '<p class="bsc-store-notice">دسته‌بندی محصولات هنوز ساخته نشده است.</p>';
	}
	wp_enqueue_style( 'barbershop-core-frontend', BSC_URL . 'assets/frontend.css', array(), BSC_VERSION );
	ob_start();
	echo '<div class="bsc-category-grid" role="list">';
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) { continue; }
		$children = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $term->term_id, 'hide_empty' => false, 'number' => 6, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
		$count_text = sprintf( '%s محصول', number_format_i18n( max( 0, (int) $term->count ) ) );
		echo '<article class="bsc-category-card" role="listitem"><a href="' . esc_url( $link ) . '" aria-label="' . esc_attr( 'مشاهده دسته ' . $term->name ) . '"><span class="bsc-category-card__top"><span class="bsc-category-card__icon">' . bsc_sanitize_icon_markup( bsc_term_icon_markup( $term ) ) . '</span><span class="bsc-category-card__count">' . esc_html( $count_text ) . '</span></span><span class="bsc-category-card__title">' . esc_html( $term->name ) . '</span>';
		if ( ! is_wp_error( $children ) && $children ) {
			echo '<span class="bsc-category-card__children">';
			$names = wp_list_pluck( $children, 'name' );
			echo esc_html( implode( '، ', array_slice( $names, 0, 4 ) ) );
			echo '</span>';
		}
		echo '<span class="bsc-category-card__action">مشاهده محصولات <span aria-hidden="true">←</span></span></a></article>';
	}
	echo '</div>';
	return (string) ob_get_clean();
}
add_shortcode( 'bsc_product_categories', 'bsc_product_categories_shortcode' );

function bsc_product_cat_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'name' === $key ) { $new['bsc_icon'] = 'آیکون فروشگاه'; }
	}
	return $new;
}
add_filter( 'manage_edit-product_cat_columns', 'bsc_product_cat_columns' );

function bsc_product_cat_column_content( $content, $column, $term_id ) {
	if ( 'bsc_icon' !== $column ) { return $content; }
	$term = get_term( $term_id, 'product_cat' );
	if ( ! $term || is_wp_error( $term ) ) { return '&mdash;'; }
	return '<span class="bsc-admin-category-icon">' . bsc_sanitize_icon_markup( bsc_term_icon_markup( $term ) ) . '</span>';
}
add_filter( 'manage_product_cat_custom_column', 'bsc_product_cat_column_content', 10, 3 );
