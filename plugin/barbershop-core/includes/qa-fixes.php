<?php
/** Runtime fixes for payment gateways, Persian WooCommerce screens, and dashboard usability. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Late translation map for strings that are frequently left in English by
 * WooCommerce templates, endpoints, or incomplete language packs.
 *
 * @return array<string,string>
 */
function bsc_qa_translation_map() {
	return array(
		'Your cart is currently empty!' => 'سبد خرید شما خالی است.',
		'Your cart is currently empty.' => 'سبد خرید شما خالی است.',
		'New in store' => 'تازه‌های فروشگاه',
		'New in store!' => 'تازه‌های فروشگاه',
		'Cart' => 'سبد خرید',
		'Checkout' => 'تسویه حساب',
		'My account' => 'حساب کاربری',
		'Login' => 'ورود',
		'Log in' => 'ورود',
		'Register' => 'ساخت حساب',
		'Username or email address' => 'نام کاربری یا نشانی ایمیل',
		'Password' => 'رمز عبور',
		'Remember me' => 'مرا به خاطر بسپار',
		'Lost your password?' => 'رمز عبور را فراموش کرده‌اید؟',
		'Email address' => 'نشانی ایمیل',
		'A link to set a new password will be sent to your email address.' => 'پیوند تعیین رمز عبور جدید به ایمیل شما ارسال می‌شود.',
		'Orders' => 'سفارش‌ها',
		'Order' => 'سفارش',
		'Date' => 'تاریخ',
		'Status' => 'وضعیت',
		'Total' => 'مبلغ نهایی',
		'Actions' => 'عملیات',
		'View' => 'مشاهده',
		'Pay' => 'پرداخت',
		'Cancel' => 'لغو',
		'Order received' => 'سفارش دریافت شد',
		'Thank you. Your order has been received.' => 'سپاسگزاریم؛ سفارش شما با موفقیت دریافت شد.',
		'Order number:' => 'شماره سفارش:',
		'Date:' => 'تاریخ:',
		'Email:' => 'ایمیل:',
		'Total:' => 'مبلغ نهایی:',
		'Payment method:' => 'روش پرداخت:',
		'Payment methods' => 'روش‌های پرداخت',
		'Payment method' => 'روش پرداخت',
		'Billing address' => 'نشانی صورتحساب',
		'Shipping address' => 'نشانی تحویل',
		'Order details' => 'جزئیات سفارش',
		'Product' => 'محصول',
		'Quantity' => 'تعداد',
		'Price' => 'قیمت',
		'Subtotal' => 'جمع جزء',
		'Proceed to checkout' => 'ادامه و تسویه حساب',
		'Place order' => 'ثبت سفارش و پرداخت',
		'Apply coupon' => 'اعمال کد تخفیف',
		'Coupon code' => 'کد تخفیف',
		'Update cart' => 'به‌روزرسانی سبد',
		'Cart totals' => 'خلاصه سبد خرید',
		'Return to shop' => 'بازگشت به فروشگاه',
		'Browse products' => 'مشاهده محصولات',
		'No order has been made yet.' => 'هنوز سفارشی ثبت نشده است.',
		'No payment methods are available.' => 'در حال حاضر روش پرداختی در دسترس نیست.',
		'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.' => 'برای نشانی انتخاب‌شده روش پرداختی در دسترس نیست. لطفاً اطلاعات سفارش را بررسی کنید یا با فروشگاه تماس بگیرید.',
		'Have a coupon?' => 'کد تخفیف دارید؟',
		'Click here to enter your code' => 'برای واردکردن کد اینجا را انتخاب کنید',
		'Returning customer?' => 'قبلاً حساب ساخته‌اید؟',
		'Click here to login' => 'برای ورود اینجا را انتخاب کنید',
		'Additional information' => 'اطلاعات تکمیلی',
		'Notes about your order, e.g. special notes for delivery.' => 'توضیحات لازم درباره آماده‌سازی یا تحویل سفارش.',
		'N/A' => 'ثبت نشده',
	);
}

function bsc_qa_translate_woocommerce( $translated, $text, $domain ) {
	if ( ! in_array( $domain, array( 'woocommerce', 'woocommerce-blocks' ), true ) ) {
		return $translated;
	}
	$map = bsc_qa_translation_map();
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}
add_filter( 'gettext', 'bsc_qa_translate_woocommerce', 200, 3 );

function bsc_qa_translate_woocommerce_context( $translated, $text, $context, $domain ) {
	unset( $context );
	return bsc_qa_translate_woocommerce( $translated, $text, $domain );
}
add_filter( 'gettext_with_context', 'bsc_qa_translate_woocommerce_context', 200, 4 );

/** Use classic commerce shortcodes when a generated WooCommerce block page is present. */
function bsc_qa_ensure_classic_commerce_pages() {
	if ( ! class_exists( 'WooCommerce' ) || BSC_VERSION === get_option( 'bsc_classic_commerce_pages_version' ) ) {
		return;
	}
	$pages = array(
		'woocommerce_cart_page_id' => array( 'woocommerce/cart', '[woocommerce_cart]' ),
		'woocommerce_checkout_page_id' => array( 'woocommerce/checkout', '[woocommerce_checkout]' ),
		'woocommerce_myaccount_page_id' => array( 'woocommerce/my-account', '[woocommerce_my_account]' ),
	);
	foreach ( $pages as $option => $config ) {
		$page_id = absint( get_option( $option ) );
		$post = $page_id ? get_post( $page_id ) : null;
		if ( ! $post ) {
			continue;
		}
		$content = (string) $post->post_content;
		$is_generated_block = has_block( $config[0], $post ) || false !== strpos( $content, '<!-- wp:woocommerce/' );
		if ( '' === trim( $content ) || $is_generated_block ) {
			wp_update_post( array( 'ID' => $page_id, 'post_content' => $config[1] ) );
		}
	}
	update_option( 'bsc_classic_commerce_pages_version', BSC_VERSION, false );
}
add_action( 'init', 'bsc_qa_ensure_classic_commerce_pages', 95 );

/** Remove WooCommerce's duplicate English empty-cart sentence; the Persian card remains. */
function bsc_qa_remove_default_empty_cart_message() {
	remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
}
add_action( 'wp_loaded', 'bsc_qa_remove_default_empty_cart_message', 20 );

/** @return string[] */
function bsc_qa_enabled_gateway_labels() {
	$labels = array();
	if ( ! function_exists( 'WC' ) || ! WC() || ! method_exists( WC(), 'payment_gateways' ) ) {
		return $labels;
	}
	$manager = WC()->payment_gateways();
	if ( ! $manager || ! method_exists( $manager, 'payment_gateways' ) ) {
		return $labels;
	}
	foreach ( (array) $manager->payment_gateways() as $gateway ) {
		if ( ! is_object( $gateway ) || ! isset( $gateway->enabled ) || 'yes' !== $gateway->enabled ) {
			continue;
		}
		$title = method_exists( $gateway, 'get_title' ) ? wp_strip_all_tags( $gateway->get_title() ) : '';
		if ( $title ) {
			$labels[] = $title;
		}
	}
	return array_values( array_unique( array_filter( $labels ) ) );
}

/** Show enabled gateways in the cart while retaining final selection at checkout. */
function bsc_qa_cart_gateway_summary() {
	$labels = bsc_qa_enabled_gateway_labels();
	?>
	<section class="bsc-cart-payment-methods" aria-labelledby="bsc-cart-payment-title">
		<h3 id="bsc-cart-payment-title">روش‌های پرداخت فعال</h3>
		<?php if ( $labels ) : ?>
			<ul><?php foreach ( $labels as $label ) : ?><li><?php echo esc_html( $label ); ?></li><?php endforeach; ?></ul>
			<p>روش نهایی را در صفحه تسویه حساب انتخاب می‌کنید.</p>
		<?php else : ?>
			<p>روش‌های قابل استفاده پس از تکمیل اطلاعات سفارش در صفحه تسویه حساب نمایش داده می‌شوند.</p>
		<?php endif; ?>
	</section>
	<?php
}
add_action( 'woocommerce_after_cart_totals', 'bsc_qa_cart_gateway_summary', 20 );

function bsc_qa_frontend_assets() {
	$is_commerce = function_exists( 'is_woocommerce' ) && is_woocommerce();
	$is_account = function_exists( 'is_account_page' ) && is_account_page();
	$is_cart_page = function_exists( 'is_cart' ) && is_cart();
	$is_checkout_page = function_exists( 'is_checkout' ) && is_checkout();
	if ( ! $is_commerce && ! $is_account && ! $is_cart_page && ! $is_checkout_page ) {
		return;
	}
	wp_enqueue_style( 'barbershop-core-qa-woocommerce', BSC_URL . 'assets/qa-woocommerce.css', array( 'persian-barbershop-woocommerce' ), BSC_VERSION );
}
add_action( 'wp_enqueue_scripts', 'bsc_qa_frontend_assets', 80 );

function bsc_qa_admin_assets( $hook ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$is_barber = 'toplevel_page_bsc-store-setup' === $hook;
	$is_woocommerce = $screen && ( false !== strpos( (string) $screen->id, 'woocommerce' ) || in_array( $screen->post_type, array( 'shop_order', 'product', BSC_POST_TYPE ), true ) );
	if ( ! $is_barber && ! $is_woocommerce ) {
		return;
	}
	wp_enqueue_style( 'barbershop-core-qa-admin', BSC_URL . 'assets/qa-admin.css', array(), BSC_VERSION );
	wp_enqueue_script( 'barbershop-core-qa-admin', BSC_URL . 'assets/qa-admin.js', array(), BSC_VERSION, true );
	if ( function_exists( 'bsc_font_file_url' ) && bsc_font_file_url() ) {
		wp_add_inline_style( 'barbershop-core-qa-admin', '@font-face{font-family:"Vazirmatn";src:url("' . esc_url_raw( bsc_font_file_url() ) . '") format("woff2-variations");font-style:normal;font-weight:100 900;font-display:swap;}' );
	}
}
add_action( 'admin_enqueue_scripts', 'bsc_qa_admin_assets', 90 );
