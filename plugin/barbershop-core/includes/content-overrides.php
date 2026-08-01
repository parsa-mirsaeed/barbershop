<?php
/** Complete dashboard-driven replacements and public/staff presentation separation. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Avoid writing role capabilities on every public request; sync only after upgrades. */
remove_action( 'init', 'bsc_sync_barber_role', 35 );
function bsc_maybe_sync_barber_role() {
	if ( BSC_VERSION !== get_option( 'bsc_barber_role_version' ) || ! get_role( 'barber' ) ) {
		bsc_sync_barber_role();
	}
}
add_action( 'init', 'bsc_maybe_sync_barber_role', 35 );

function bsc_render_extended_storefront_content( $block_content, $block ) {
	unset( $block );
	$content = bsc_get_store_content();
	$replacements = array(
		'[توضیح خدمت اول]' => $content['service_1_text'],
		'[توضیح خدمت دوم]' => $content['service_2_text'],
		'[توضیح خدمت سوم]' => $content['service_3_text'],
		'[نظر مشتری اول]' => $content['review_1_text'],
		'[نام مشتری اول]' => $content['review_1_name'],
		'[نظر مشتری دوم]' => $content['review_2_text'],
		'[نام مشتری دوم]' => $content['review_2_name'],
		'[نظر مشتری سوم]' => $content['review_3_text'],
		'[نام مشتری سوم]' => $content['review_3_name'],
	);
	$safe = array();
	foreach ( $replacements as $source => $replacement ) {
		$safe[ $source ] = esc_html( $replacement );
	}
	$block_content = strtr( $block_content, $safe );
	$phone_link = preg_replace( '/[^0-9+]/', '', bsc_normalize_phone( $content['contact_phone'] ) );
	if ( $phone_link ) {
		$block_content = str_replace( 'tel:+000000000000', 'tel:' . esc_attr( $phone_link ), $block_content );
	}
	return $block_content;
}
add_filter( 'render_block', 'bsc_render_extended_storefront_content', 45, 2 );

/** Keep the public storefront visually separate from wp-admin for every role. */
function bsc_hide_public_admin_toolbar( $show ) {
	return is_admin() ? $show : false;
}
add_filter( 'show_admin_bar', 'bsc_hide_public_admin_toolbar', 100 );

/** Cover account-template strings that may remain English when a language pack is incomplete. */
function bsc_translate_extended_account_text( $translated, $text, $domain ) {
	if ( 'woocommerce' !== $domain ) {
		return $translated;
	}
	$map = array(
		'Hello %1$s (not %1$s? Log out)' => 'سلام %1$s (شما نیستید؟ خروج امن)',
		'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)' => 'سلام %1$s (شما نیستید؟ <a href="%2$s">خروج امن</a>)',
		'From your account dashboard you can view your recent orders, manage your shipping and billing addresses, and edit your password and account details.' => 'از پیشخوان حساب می‌توانید سفارش‌های اخیر، رمز عبور و اطلاعات حساب خود را مدیریت کنید.',
		'This will be how your name will be displayed in the account section and in reviews' => 'این نام در بخش حساب و دیدگاه‌های شما نمایش داده می‌شود.',
		'Password change' => 'تغییر رمز عبور',
		'Current password' => 'رمز عبور فعلی',
		'New password' => 'رمز عبور جدید',
		'Confirm new password' => 'تکرار رمز عبور جدید',
		'No downloads available yet.' => 'هنوز دانلودی در دسترس نیست.',
		'Go shop' => 'رفتن به فروشگاه',
		'Addresses' => 'نشانی‌ها',
		'Order' => 'سفارش',
		'Date' => 'تاریخ',
		'Status' => 'وضعیت',
		'Actions' => 'عملیات',
	);
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}
add_filter( 'gettext', 'bsc_translate_extended_account_text', 101, 3 );
