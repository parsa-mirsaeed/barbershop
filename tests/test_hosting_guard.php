<?php
/** Behavioral test for the shared-hosting MU guard without requiring WordPress. */
define( 'ABSPATH', __DIR__ . '/fixtures/wordpress/' );
function add_filter() {}
function add_action() {}
function current_user_can() { return true; }
function bsc_hosting_guard_has_pdo() { return false; }

require dirname( __DIR__ ) . '/hosting/mu-plugins/barbershop-hosting-guard.php';

function guard_assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, "FAIL: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

$plugins = array(
	'woocommerce/woocommerce.php',
	'gateland/gateland.php',
	'wordfence/wordfence.php',
);
$filtered = bsc_hosting_guard_active_plugins( $plugins );
guard_assert_same(
	array( 'woocommerce/woocommerce.php', 'wordfence/wordfence.php' ),
	$filtered,
	'Gateland is removed from the runtime active-plugin list when PDO MySQL is unavailable.'
);
guard_assert_same( true, ! empty( $GLOBALS['bsc_hosting_guard_blocked_gateland'] ), 'The guard records that Gateland was blocked.' );

$sitewide = array(
	'gateland/gateland.php' => time(),
	'woocommerce/woocommerce.php' => time(),
);
$sitewide = bsc_hosting_guard_sitewide_plugins( $sitewide );
guard_assert_same( false, isset( $sitewide['gateland/gateland.php'] ), 'The multisite Gateland entry is also removed.' );
guard_assert_same( true, isset( $sitewide['woocommerce/woocommerce.php'] ), 'Unrelated plugins remain untouched.' );

echo "Shared-hosting MU guard tests passed.\n";
