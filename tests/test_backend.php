<?php
/** Lightweight backend unit tests for pure plugin behavior. */
define( 'ABSPATH', __DIR__ . '/fixtures/wordpress/' );
define( 'BSC_FILE', __FILE__ );
define( 'BSC_VERSION', 'test' );
function add_action() {}
function add_filter() {}
function is_admin() { return false; }

require dirname( __DIR__ ) . '/plugin/barbershop-core/includes/account.php';
require dirname( __DIR__ ) . '/plugin/barbershop-core/includes/woocommerce.php';
require dirname( __DIR__ ) . '/plugin/barbershop-core/includes/setup.php';

function bsc_test_assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, "FAIL: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

bsc_test_assert_same( '0912 123 4567', bsc_normalize_phone( ' ۰۹۱۲ ۱۲۳ ۴۵۶۷ ' ), 'Persian phone digits are normalized.' );
bsc_test_assert_same( '+989121234567', bsc_normalize_phone( '+٩٨٩١٢١٢٣٤٥٦٧' ), 'Arabic-Indic phone digits are normalized.' );
bsc_test_assert_same( '09121234567', bsc_normalize_iran_mobile( '+989121234567' ), 'International Iranian mobile numbers are normalized.' );
bsc_test_assert_same( '09121234567', bsc_normalize_iran_mobile( '0098 912 123 4567' ), '0098 Iranian mobile numbers are normalized.' );
bsc_test_assert_same( '09121234567', bsc_normalize_iran_mobile( '۹۱۲۱۲۳۴۵۶۷' ), 'Ten-digit Persian mobile numbers receive the domestic zero.' );
bsc_test_assert_same( 'افزودن به سبد خرید', bsc_translate_woocommerce_text( 'Add to cart', 'Add to cart', 'woocommerce' ), 'WooCommerce purchase text is translated.' );
bsc_test_assert_same( 'Other', bsc_translate_woocommerce_text( 'Other', 'Other', 'default' ), 'Other text domains are untouched.' );
$menu = bsc_account_menu_items( array( 'dashboard' => 'Dashboard', 'orders' => 'Orders', 'edit-address' => 'Addresses', 'downloads' => 'Downloads' ) );
bsc_test_assert_same( false, isset( $menu['edit-address'] ), 'Address management is removed from the minimal profile.' );
bsc_test_assert_same( false, isset( $menu['downloads'] ), 'Unused downloads item is removed.' );
$blueprint = bsc_category_blueprint();
bsc_test_assert_same( 4, count( $blueprint ), 'Four editable primary product categories are seeded.' );
bsc_test_assert_same( 'حالت‌دهنده ریش و مو', $blueprint[0]['name'], 'Styling category is present.' );
bsc_test_assert_same( 'اصلاح و ابزار حرفه‌ای', $blueprint[3]['name'], 'Professional tools category is present.' );
echo "Backend unit tests passed.\n";
