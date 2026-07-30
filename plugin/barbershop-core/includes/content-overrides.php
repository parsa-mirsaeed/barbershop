<?php
/** Complete the dashboard-driven replacement of independently editable storefront placeholders. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
