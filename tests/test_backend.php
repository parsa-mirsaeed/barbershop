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
require dirname( __DIR__ ) . '/plugin/barbershop-core/includes/hosting.php';
function wp_salt( $scheme = '' ) { return 'test-only-wordpress-salt-' . $scheme; }
function get_option( $key, $default = false ) { global $bsc_test_options; return isset( $bsc_test_options[ $key ] ) ? $bsc_test_options[ $key ] : $default; }
function is_front_page() { return true; }
function has_filter( $key ) { return false; }
function apply_filters( $key, $value ) { return $value; }
require dirname( __DIR__ ) . '/plugin/barbershop-core/includes/otp-auth.php';
require dirname( __DIR__ ) . '/plugin/barbershop-core/includes/visibility.php';

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

bsc_test_assert_same( 512 * 1024 * 1024, bsc_hosting_ini_bytes( '512M' ), 'Shared-host parser handles megabytes.' );
bsc_test_assert_same( 2 * 1024 * 1024 * 1024, bsc_hosting_ini_bytes( '2G' ), 'Shared-host parser handles gigabytes.' );
bsc_test_assert_same( -1, bsc_hosting_ini_bytes( '-1' ), 'Unlimited PHP memory is preserved.' );
$extensions = bsc_hosting_extension_requirements();
bsc_test_assert_same( true, $extensions['pdo_mysql']['required'], 'PDO MySQL is a critical shared-host requirement.' );
bsc_test_assert_same( true, $extensions['curl']['required'], 'cURL is a critical shared-host requirement.' );
bsc_test_assert_same( false, $extensions['intl']['required'], 'Intl is recommended rather than fatal.' );

bsc_test_assert_same( '09121234567', bsc_otp_valid_mobile( '+989121234567' ), 'OTP supports +98 Iranian phone.' );
bsc_test_assert_same( '', bsc_otp_valid_mobile( '123456' ), 'OTP rejects invalid phone numbers.' );
bsc_test_assert_same( true, 0 === strpos( bsc_otp_customer_login( '09121234567' ), 'bsc_u_' ), 'Opaque login prefix avoids exposing the phone.' );
bsc_test_assert_same( false, false !== strpos( bsc_otp_customer_login( '09121234567' ), '09121234567' ), 'Opaque logins contain no phone numbers.' );
bsc_test_assert_same( true, bsc_visibility_enabled( 'gallery' ), 'Gallery is visible by default.' );
bsc_test_assert_same( true, bsc_visibility_enabled( 'product_reviews' ), 'Product reviews are visible by default.' );
bsc_test_assert_same( false, bsc_otp_sms_ready(), 'OTP fails closed without SMS provider credentials.' );
$bsc_test_options['bsc_show_section_gallery'] = 'no';
bsc_test_assert_same( false, bsc_visibility_enabled( 'gallery' ), 'Merchant can disable before/after gallery.' );
bsc_test_assert_same( '', bsc_visibility_render_block( 'before/after', array( 'attrs' => array( 'anchor' => 'gallery' ) ) ), 'Hidden gallery block renders no markup.' );
bsc_test_assert_same( 'keep products', bsc_visibility_render_block( 'keep products', array( 'attrs' => array( 'anchor' => 'products' ) ) ), 'Other sections remain visible.' );
$bsc_test_options['bsc_show_product_reviews'] = 'no';
bsc_test_assert_same( false, bsc_visibility_enabled( 'product_reviews' ), 'Merchant can hide product comments.' );
bsc_test_assert_same( false, isset( bsc_visibility_product_tabs( array( 'reviews' => array( 'title' => 'Reviews' ) ) )['reviews'] ), 'Hidden review tab is removed.' );
echo "Backend unit tests passed.\\n";
