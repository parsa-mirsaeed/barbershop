<?php
/** Store setup, font provisioning, and reusable defaults. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bsc_declare_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', BSC_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', BSC_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'bsc_declare_compatibility' );

function bsc_capabilities() {
	return array(
		'edit_barbershop_result', 'read_barbershop_result', 'delete_barbershop_result',
		'edit_barbershop_results', 'edit_others_barbershop_results', 'publish_barbershop_results',
		'read_private_barbershop_results', 'delete_barbershop_results',
		'delete_private_barbershop_results', 'delete_published_barbershop_results',
		'delete_others_barbershop_results', 'edit_private_barbershop_results',
		'edit_published_barbershop_results', 'upload_files',
	);
}

function bsc_grant_caps() {
	foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) {
		$role = get_role( $role_name );
		if ( ! $role ) {
			continue;
		}
		foreach ( bsc_capabilities() as $capability ) {
			$role->add_cap( $capability );
		}
	}
}
add_action( 'woocommerce_installed', 'bsc_grant_caps' );

function bsc_category_blueprint() {
	return array(
		array(
			'name' => 'حالت‌دهنده ریش و مو',
			'slug' => 'hair-beard-styling',
			'icon' => 'styling',
			'children' => array(
				array( 'name' => 'واکس و پماد مو', 'slug' => 'hair-wax-pomade' ),
				array( 'name' => 'تافت و اسپری مو', 'slug' => 'hair-spray' ),
				array( 'name' => 'ژل و کرم حالت‌دهنده', 'slug' => 'hair-gel-cream' ),
				array( 'name' => 'روغن و بالم ریش', 'slug' => 'beard-oil-balm' ),
			),
		),
		array(
			'name' => 'مراقبت مو',
			'slug' => 'hair-care',
			'icon' => 'hair-care',
			'children' => array(
				array( 'name' => 'شامپو', 'slug' => 'shampoo' ),
				array( 'name' => 'نرم‌کننده', 'slug' => 'conditioner' ),
				array( 'name' => 'ماسک و ویتامینه مو', 'slug' => 'hair-mask-vitamins' ),
				array( 'name' => 'سرم و روغن مو', 'slug' => 'hair-serum-oil' ),
			),
		),
		array(
			'name' => 'مراقبت پوست',
			'slug' => 'skin-care',
			'icon' => 'skin-care',
			'children' => array(
				array( 'name' => 'اسکراب', 'slug' => 'scrub' ),
				array( 'name' => 'شوینده', 'slug' => 'cleanser' ),
				array( 'name' => 'تونر', 'slug' => 'toner' ),
				array( 'name' => 'مرطوب‌کننده', 'slug' => 'moisturizer' ),
			),
		),
		array(
			'name' => 'اصلاح و ابزار حرفه‌ای',
			'slug' => 'professional-tools',
			'icon' => 'tools',
			'children' => array(
				array( 'name' => 'ماشین اصلاح', 'slug' => 'clippers' ),
				array( 'name' => 'شانه و برس', 'slug' => 'combs-brushes' ),
				array( 'name' => 'تیغ و لوازم اصلاح', 'slug' => 'shaving-tools' ),
			),
		),
	);
}

function bsc_ensure_term( $name, $slug, $parent = 0 ) {
	$existing = term_exists( $slug, 'product_cat' );
	if ( is_array( $existing ) ) {
		return absint( $existing['term_id'] );
	}
	if ( is_int( $existing ) ) {
		return $existing;
	}
	$result = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug, 'parent' => $parent ) );
	if ( is_wp_error( $result ) ) {
		return 0;
	}
	return absint( $result['term_id'] );
}

function bsc_seed_product_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return false;
	}
	foreach ( bsc_category_blueprint() as $category ) {
		$parent_id = bsc_ensure_term( $category['name'], $category['slug'] );
		if ( ! $parent_id ) {
			continue;
		}
		update_term_meta( $parent_id, '_bsc_default_icon', sanitize_key( $category['icon'] ) );
		foreach ( $category['children'] as $child ) {
			bsc_ensure_term( $child['name'], $child['slug'], $parent_id );
		}
	}
	update_option( 'bsc_seeded_categories_version', BSC_VERSION, false );
	return true;
}

/** @return string */
function bsc_font_file_path() {
	$uploads = wp_upload_dir();
	return trailingslashit( $uploads['basedir'] ) . 'barbershop-fonts/Vazirmatn-Variable.woff2';
}

/** @return string */
function bsc_font_file_url() {
	$uploads = wp_upload_dir();
	return trailingslashit( $uploads['baseurl'] ) . 'barbershop-fonts/Vazirmatn-Variable.woff2';
}

/**
 * Download and self-host Vazirmatn in the WordPress uploads directory.
 *
 * @return true|WP_Error
 */
function bsc_install_vazirmatn_font() {
	$target = bsc_font_file_path();
	if ( is_file( $target ) && filesize( $target ) > 10000 ) {
		update_option( 'bsc_vazirmatn_version', BSC_VAZIRMATN_VERSION, false );
		return true;
	}

	if ( ! function_exists( 'download_url' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	$directory = dirname( $target );
	if ( ! wp_mkdir_p( $directory ) ) {
		return new WP_Error( 'bsc_font_directory', 'ساخت پوشه فونت امکان‌پذیر نبود.' );
	}

	$temp = download_url( BSC_VAZIRMATN_URL, 30 );
	if ( is_wp_error( $temp ) ) {
		return $temp;
	}
	$size = filesize( $temp );
	if ( ! $size || $size < 10000 ) {
		@unlink( $temp );
		return new WP_Error( 'bsc_font_invalid', 'فایل فونت دریافت‌شده معتبر نیست.' );
	}
	if ( ! @rename( $temp, $target ) ) {
		if ( ! @copy( $temp, $target ) ) {
			@unlink( $temp );
			return new WP_Error( 'bsc_font_copy', 'ذخیره فونت در وردپرس انجام نشد.' );
		}
		@unlink( $temp );
	}
	@chmod( $target, 0644 );
	update_option( 'bsc_vazirmatn_version', BSC_VAZIRMATN_VERSION, false );
	return true;
}

function bsc_apply_woocommerce_defaults() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}
	update_option( 'users_can_register', 0 );
	update_option( 'default_role', 'customer' );
	update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
	update_option( 'woocommerce_registration_generate_username', 'yes' );
	update_option( 'woocommerce_registration_generate_password', 'yes' );
	update_option( 'woocommerce_enable_checkout_login_reminder', 'yes' );
	update_option( 'woocommerce_cart_redirect_after_add', 'yes' );
	update_option( 'woocommerce_checkout_highlight_required_fields', 'yes' );
	update_option( 'woocommerce_allow_tracking', 'no' );
	update_option( 'bsc_disable_xmlrpc', get_option( 'bsc_disable_xmlrpc', 'yes' ) );
	if ( class_exists( 'WC_Install' ) ) {
		WC_Install::create_pages();
	}
	bsc_seed_product_categories();
	bsc_install_vazirmatn_font();
	update_option( 'bsc_setup_version', BSC_VERSION, false );
	return true;
}

function bsc_maybe_apply_setup() {
	if ( class_exists( 'WooCommerce' ) && BSC_VERSION !== get_option( 'bsc_setup_version' ) ) {
		bsc_apply_woocommerce_defaults();
	}
}
add_action( 'init', 'bsc_maybe_apply_setup', 40 );

function bsc_activate() {
	bsc_grant_caps();
	bsc_apply_woocommerce_defaults();
	flush_rewrite_rules();
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/** Configure WooCommerce defaults and seed editable product categories. */
	function bsc_cli_setup() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			WP_CLI::error( 'WooCommerce must be installed and active first.' );
		}
		bsc_grant_caps();
		bsc_apply_woocommerce_defaults();
		WP_CLI::success( 'Barbershop storefront setup completed.' );
	}
	WP_CLI::add_command( 'bsc setup', 'bsc_cli_setup' );

	/** Download the self-hosted Vazirmatn font. */
	function bsc_cli_font_install() {
		$result = bsc_install_vazirmatn_font();
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::success( 'Vazirmatn font installed locally.' );
	}
	WP_CLI::add_command( 'bsc font install', 'bsc_cli_font_install' );
}
