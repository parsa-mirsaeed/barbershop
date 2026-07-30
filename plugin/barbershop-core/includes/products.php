<?php
/** Product-editor guidance for a non-technical barber/shop manager. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bsc_product_quality_metabox() {
	add_meta_box(
		'bsc-product-quality',
		'چک‌لیست انتشار حرفه‌ای',
		'bsc_render_product_quality_metabox',
		'product',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes_product', 'bsc_product_quality_metabox' );

function bsc_render_product_quality_metabox( $post ) {
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post->ID ) : false;
	$checks  = array(
		'تصویر اصلی باکیفیت' => has_post_thumbnail( $post ),
		'نام واضح و فارسی'   => '' !== trim( get_the_title( $post ) ),
		'قیمت ثبت شده'       => $product && '' !== $product->get_regular_price(),
		'دسته‌بندی انتخاب شده' => has_term( '', 'product_cat', $post ),
		'توضیح کوتاه تکمیل شده' => '' !== trim( (string) $post->post_excerpt ),
		'وضعیت موجودی مشخص' => $product && in_array( $product->get_stock_status(), array( 'instock', 'outofstock', 'onbackorder' ), true ),
	);
	$complete = count( array_filter( $checks ) );
	$total    = count( $checks );
	?>
	<div class="bsc-product-quality" data-bsc-progress="<?php echo esc_attr( $complete ); ?>" data-bsc-total="<?php echo esc_attr( $total ); ?>">
		<p class="bsc-product-quality__score"><strong><?php echo esc_html( $complete . ' از ' . $total ); ?></strong> مورد کامل است.</p>
		<ul>
			<?php foreach ( $checks as $label => $done ) : ?>
				<li class="<?php echo $done ? 'is-done' : 'is-pending'; ?>"><span aria-hidden="true"><?php echo $done ? '✓' : '○'; ?></span><?php echo esc_html( $label ); ?></li>
			<?php endforeach; ?>
		</ul>
		<p class="description">محصول کامل‌تر، برای مشتری قابل اعتمادتر و برای جست‌وجو قابل فهم‌تر است.</p>
	</div>
	<?php
}

/** Add a direct Persian help tab on WooCommerce product screens. */
function bsc_product_help_tab() {
	$screen = get_current_screen();
	if ( ! $screen || 'product' !== $screen->post_type ) {
		return;
	}
	$screen->add_help_tab(
		array(
			'id'      => 'bsc-product-help',
			'title'   => 'راهنمای سریع محصول',
			'content' => '<p>برای هر محصول تصویر واضح، نام فارسی، قیمت، موجودی، دسته‌بندی و توضیح کوتاه ثبت کنید. اطلاعات درگاه پرداخت را داخل توضیحات محصول یا تنظیمات قالب قرار ندهید.</p>',
		)
	);
}
add_action( 'current_screen', 'bsc_product_help_tab' );
