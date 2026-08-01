<?php
/** Persian WooCommerce labels and a streamlined, accessible purchase experience. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Translate high-frequency WooCommerce UI strings when a language pack leaves
 * an English fallback. Kept as a pure function for backend tests.
 *
 * @param string $translated Existing translated value.
 * @param string $text       Source English text.
 * @param string $domain     Text domain.
 * @return string
 */
function bsc_translate_woocommerce_text( $translated, $text, $domain ) {
	if ( 'woocommerce' !== $domain ) {
		return $translated;
	}
	$map = array(
		'Add to cart'                    => 'افزودن به سبد خرید',
		'Select options'                 => 'انتخاب گزینه‌ها',
		'Read more'                      => 'مشاهده جزئیات',
		'View cart'                      => 'مشاهده سبد خرید',
		'Proceed to checkout'            => 'ادامه و تسویه حساب',
		'Apply coupon'                   => 'اعمال کد تخفیف',
		'Coupon code'                    => 'کد تخفیف',
		'Update cart'                    => 'به‌روزرسانی سبد',
		'Cart totals'                    => 'خلاصه سبد خرید',
		'Place order'                    => 'ثبت سفارش و پرداخت',
		'Billing details'                => 'اطلاعات سفارش‌دهنده',
		'Additional information'         => 'توضیحات تکمیلی',
		'Your order'                     => 'خلاصه سفارش شما',
		'Product'                        => 'محصول',
		'Price'                          => 'قیمت',
		'Quantity'                       => 'تعداد',
		'Subtotal'                       => 'جمع جزء',
		'Total'                          => 'مبلغ نهایی',
		'Login'                          => 'ورود',
		'Register'                       => 'ساخت حساب کوتاه',
		'Lost your password?'            => 'رمز عبور را فراموش کرده‌اید؟',
		'Remember me'                    => 'مرا به خاطر بسپار',
		'No products were found matching your selection.' => 'محصولی مطابق انتخاب شما پیدا نشد.',
	);
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}
add_filter( 'gettext', 'bsc_translate_woocommerce_text', 20, 3 );

function bsc_loop_add_to_cart_text( $text, $product ) {
	if ( $product && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
		return 'افزودن به سبد';
	}
	return $text;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'bsc_loop_add_to_cart_text', 10, 2 );

function bsc_single_add_to_cart_text() {
	return 'افزودن به سبد خرید';
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'bsc_single_add_to_cart_text' );

function bsc_order_button_text() {
	return 'ثبت سفارش و پرداخت امن';
}
add_filter( 'woocommerce_order_button_text', 'bsc_order_button_text' );

function bsc_sale_flash( $html ) {
	unset( $html );
	return '<span class="onsale">تخفیف</span>';
}
add_filter( 'woocommerce_sale_flash', 'bsc_sale_flash' );

function bsc_catalog_orderby( $options ) {
	$labels = array(
		'menu_order' => 'پیشنهاد فروشگاه',
		'popularity' => 'محبوب‌ترین',
		'rating'     => 'بالاترین امتیاز',
		'date'       => 'جدیدترین',
		'price'      => 'قیمت: کم به زیاد',
		'price-desc' => 'قیمت: زیاد به کم',
	);
	foreach ( $labels as $key => $label ) {
		if ( isset( $options[ $key ] ) ) {
			$options[ $key ] = $label;
		}
	}
	return $options;
}
add_filter( 'woocommerce_catalog_orderby', 'bsc_catalog_orderby' );
add_filter( 'woocommerce_default_catalog_orderby_options', 'bsc_catalog_orderby' );

function bsc_product_tabs( $tabs ) {
	if ( isset( $tabs['description'] ) ) {
		$tabs['description']['title'] = 'معرفی محصول';
	}
	if ( isset( $tabs['additional_information'] ) ) {
		$tabs['additional_information']['title'] = 'مشخصات';
	}
	if ( isset( $tabs['reviews'] ) ) {
		$tabs['reviews']['title'] = 'دیدگاه خریداران';
	}
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'bsc_product_tabs' );

function bsc_default_address_fields( $fields ) {
	$labels = array(
		'first_name' => array( 'label' => 'نام', 'placeholder' => 'نام' ),
		'last_name'  => array( 'label' => 'نام خانوادگی', 'placeholder' => 'نام خانوادگی' ),
		'company'    => array( 'label' => 'نام شرکت', 'placeholder' => 'اختیاری' ),
		'country'    => array( 'label' => 'کشور / منطقه' ),
		'state'      => array( 'label' => 'استان' ),
		'city'       => array( 'label' => 'شهر' ),
		'address_1'  => array( 'label' => 'نشانی', 'placeholder' => 'خیابان، کوچه، پلاک و واحد' ),
		'address_2'  => array( 'label' => 'توضیح تکمیلی نشانی', 'placeholder' => 'اختیاری' ),
		'postcode'   => array( 'label' => 'کد پستی', 'placeholder' => 'کد پستی' ),
	);
	foreach ( $labels as $key => $values ) {
		if ( isset( $fields[ $key ] ) ) {
			$fields[ $key ] = array_merge( $fields[ $key ], $values );
		}
	}
	return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'bsc_default_address_fields' );

function bsc_checkout_fields( $fields ) {
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['label']       = 'شماره تماس';
		$fields['billing']['billing_phone']['placeholder'] = 'مثلاً ۰۹۱۲۱۲۳۴۵۶۷';
		$fields['billing']['billing_phone']['priority']    = 25;
	}
	if ( isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email']['label']       = 'ایمیل';
		$fields['billing']['billing_email']['placeholder'] = 'برای رسید و پیگیری سفارش';
		$fields['billing']['billing_email']['priority']    = 30;
	}
	if ( isset( $fields['billing']['billing_company'] ) ) {
		$fields['billing']['billing_company']['required'] = false;
		$fields['billing']['billing_company']['priority'] = 120;
	}
	if ( isset( $fields['billing']['billing_address_2'] ) ) {
		$fields['billing']['billing_address_2']['required'] = false;
	}
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['label']       = 'توضیحات سفارش';
		$fields['order']['order_comments']['placeholder'] = 'نکته لازم برای آماده‌سازی یا تحویل سفارش (اختیاری)';
	}
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'bsc_checkout_fields' );

function bsc_account_menu_labels( $items ) {
	$labels = array(
		'dashboard'       => 'پیشخوان حساب',
		'orders'          => 'سفارش‌های من',
		'downloads'       => 'دانلودها',
		'edit-address'    => 'نشانی‌ها',
		'edit-account'    => 'اطلاعات حساب',
		'customer-logout' => 'خروج امن',
	);
	foreach ( $labels as $key => $label ) {
		if ( isset( $items[ $key ] ) ) {
			$items[ $key ] = $label;
		}
	}
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'bsc_account_menu_labels', 5 );

function bsc_loop_add_to_cart_args( $args, $product ) {
	if ( $product ) {
		$args['attributes']['aria-label'] = sprintf( 'افزودن %s به سبد خرید', $product->get_name() );
	}
	return $args;
}
add_filter( 'woocommerce_loop_add_to_cart_args', 'bsc_loop_add_to_cart_args', 10, 2 );

function bsc_empty_cart_message() {
	echo '<div class="bsc-empty-cart"><span class="bsc-empty-cart__icon" aria-hidden="true">' . bsc_icon_svg( 'cart' ) . '</span><h2>سبد خرید شما خالی است</h2><p>از دسته‌بندی‌ها شروع کنید و محصول مناسب را به سبد اضافه کنید.</p></div>';
}
add_action( 'woocommerce_cart_is_empty', 'bsc_empty_cart_message', 5 );
