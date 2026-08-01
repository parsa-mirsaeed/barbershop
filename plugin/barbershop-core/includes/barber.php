<?php
/** Barber role, dedicated operations dashboard, editable storefront content, and staff/customer separation. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bsc_is_store_staff( $user = null ) {
	if ( null === $user ) {
		$user = wp_get_current_user();
	} elseif ( is_int( $user ) ) {
		$user = get_user_by( 'id', $user );
	}
	return $user instanceof WP_User && ( user_can( $user, 'manage_barbershop' ) || user_can( $user, 'manage_options' ) );
}

function bsc_sync_barber_role() {
	$source = get_role( 'shop_manager' );
	$caps   = $source ? $source->capabilities : array(
		'read' => true,
		'upload_files' => true,
		'manage_woocommerce' => true,
		'view_woocommerce_reports' => true,
		'edit_products' => true,
		'edit_others_products' => true,
		'publish_products' => true,
		'delete_products' => true,
		'manage_product_terms' => true,
		'edit_product_terms' => true,
		'delete_product_terms' => true,
		'assign_product_terms' => true,
	);
	$caps['manage_barbershop'] = true;
	foreach ( bsc_capabilities() as $capability ) {
		$caps[ $capability ] = true;
	}
	$role = get_role( 'barber' );
	if ( ! $role ) {
		add_role( 'barber', 'آرایشگر / مدیر فروشگاه', $caps );
		$role = get_role( 'barber' );
	}
	if ( $role ) {
		foreach ( $caps as $capability => $grant ) {
			if ( $grant ) {
				$role->add_cap( $capability );
			}
		}
	}
	foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) {
		$staff_role = get_role( $role_name );
		if ( $staff_role ) {
			$staff_role->add_cap( 'manage_barbershop' );
			foreach ( bsc_capabilities() as $capability ) {
				$staff_role->add_cap( $capability );
			}
		}
	}
	update_option( 'bsc_barber_role_version', BSC_VERSION, false );
}
add_action( 'init', 'bsc_sync_barber_role', 35 );
add_action( 'woocommerce_installed', 'bsc_sync_barber_role', 20 );

function bsc_localize_woocommerce_pages() {
	$pages = array(
		'woocommerce_shop_page_id' => 'فروشگاه',
		'woocommerce_cart_page_id' => 'سبد خرید',
		'woocommerce_checkout_page_id' => 'تسویه حساب',
		'woocommerce_myaccount_page_id' => 'حساب کاربری',
	);
	foreach ( $pages as $option => $title ) {
		$page_id = absint( get_option( $option ) );
		if ( ! $page_id ) {
			continue;
		}
		$post = get_post( $page_id );
		if ( $post && $title !== $post->post_title ) {
			wp_update_post( array( 'ID' => $page_id, 'post_title' => $title ) );
		}
	}
}

function bsc_remove_seed_content() {
	$sample = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $sample && 'Hello world!' === $sample->post_title ) {
		wp_delete_post( $sample->ID, true );
	}
}

function bsc_store_content_defaults() {
	return array(
		'brand_name' => get_bloginfo( 'name' ) && '[نام برند]' !== get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'نام برند',
		'brand_tagline' => 'مراقبت حرفه‌ای ریش، مو و پوست',
		'hero_title_prefix' => 'قدرت طبیعت، برای',
		'hero_title_emphasis' => 'استایل حرفه‌ای',
		'hero_description' => 'محصولات منتخب آرایشگاهی با دسته‌بندی روشن، اطلاعات دقیق و خرید روان؛ از انتخاب محصول تا پرداخت، بدون مسیرهای گیج‌کننده.',
		'hero_primary_label' => 'انتخاب دسته‌بندی',
		'hero_secondary_label' => 'مشاهده فروشگاه',
		'footer_description' => 'فروشگاه تخصصی محصولات مراقبت و استایل با خرید ساده و اطلاعات شفاف.',
		'contact_heading' => 'ارتباط مستقیم با مجموعه',
		'contact_phone' => '',
		'contact_hours' => '',
		'contact_address' => '',
		'services_heading' => 'جزئیات کوچک، تفاوت بزرگ',
		'service_1_title' => 'اصلاح و فرم‌دهی مو',
		'service_1_text' => 'توضیح خدمت، مدت تقریبی و محدوده قیمت را وارد کنید.',
		'service_2_title' => 'اصلاح و طراحی ریش',
		'service_2_text' => 'توضیح خدمت، مدت تقریبی و محدوده قیمت را وارد کنید.',
		'service_3_title' => 'مشاوره استایل',
		'service_3_text' => 'توضیح خدمت، مدت تقریبی و محدوده قیمت را وارد کنید.',
		'portfolio_heading' => 'قبل و بعد، با اجازه انتشار',
		'portfolio_text' => 'فقط تصاویر دارای اجازه مستند انتشار نمایش داده می‌شوند.',
		'articles_heading' => 'راهنمای نگهداری و استایل',
		'reviews_heading' => 'اعتماد، از تجربه واقعی می‌آید',
		'review_1_text' => 'نظر واقعی مشتری با اجازه انتشار',
		'review_1_name' => 'مشتری تأییدشده',
		'review_2_text' => 'نظر واقعی مشتری با اجازه انتشار',
		'review_2_name' => 'مشتری تأییدشده',
		'review_3_text' => 'نظر واقعی مشتری با اجازه انتشار',
		'review_3_name' => 'مشتری تأییدشده',
		'logo_id' => 0,
		'hero_image_id' => 0,
		'contact_image_id' => 0,
		'gallery_1_id' => 0,
		'gallery_2_id' => 0,
		'gallery_3_id' => 0,
	);
}

function bsc_get_store_content() {
	$saved = get_option( 'bsc_store_content', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), bsc_store_content_defaults() );
}

function bsc_store_content_value( $key ) {
	$content = bsc_get_store_content();
	return isset( $content[ $key ] ) ? $content[ $key ] : '';
}

function bsc_apply_barber_runtime_defaults() {
	if ( BSC_VERSION === get_option( 'bsc_barber_runtime_version' ) ) {
		return;
	}
	update_option( 'WPLANG', 'fa_IR' );
	update_option( 'fresh_site', 0 );
	update_option( 'woocommerce_coming_soon', 'no' );
	update_option( 'woocommerce_store_pages_only', 'no' );
	update_option( 'woocommerce_registration_generate_password', 'no' );
	update_option( 'woocommerce_registration_generate_username', 'yes' );
	update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
	bsc_sync_barber_role();
	bsc_localize_woocommerce_pages();
	bsc_remove_seed_content();
	update_option( 'bsc_barber_runtime_version', BSC_VERSION, false );
}
add_action( 'init', 'bsc_apply_barber_runtime_defaults', 80 );

function bsc_staff_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	unset( $requested_redirect_to );
	if ( $user instanceof WP_User && bsc_is_store_staff( $user ) ) {
		return admin_url( 'admin.php?page=bsc-store-setup' );
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'bsc_staff_login_redirect', 20, 3 );

function bsc_wc_staff_login_redirect( $redirect, $user ) {
	return $user instanceof WP_User && bsc_is_store_staff( $user ) ? admin_url( 'admin.php?page=bsc-store-setup' ) : $redirect;
}
add_filter( 'woocommerce_login_redirect', 'bsc_wc_staff_login_redirect', 20, 2 );

function bsc_redirect_staff_away_from_customer_account() {
	if ( ! is_admin() && function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in() && bsc_is_store_staff() ) {
		wp_safe_redirect( admin_url( 'admin.php?page=bsc-store-setup' ) );
		exit;
	}
}
add_action( 'template_redirect', 'bsc_redirect_staff_away_from_customer_account', 1 );

function bsc_customer_admin_bar( $show ) {
	if ( ! is_admin() && is_user_logged_in() && ! bsc_is_store_staff() ) {
		return false;
	}
	return $show;
}
add_filter( 'show_admin_bar', 'bsc_customer_admin_bar' );

function bsc_validate_customer_password( $errors, $username, $email ) {
	unset( $username, $email );
	$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
	$length = function_exists( 'mb_strlen' ) ? mb_strlen( $password, 'UTF-8' ) : strlen( $password );
	if ( $length < 12 ) {
		$errors->add( 'bsc_password_length', 'رمز عبور باید حداقل ۱۲ نویسه باشد. استفاده از یک عبارت عبور طولانی پیشنهاد می‌شود.' );
	}
	return $errors;
}
add_filter( 'woocommerce_registration_errors', 'bsc_validate_customer_password', 30, 3 );

function bsc_registration_password_help() {
	echo '<p class="bsc-password-help">رمز عبور را خودتان وارد کنید؛ حداقل ۱۲ نویسه و ترجیحاً یک عبارت عبور یکتا.</p>';
}
add_action( 'woocommerce_register_form', 'bsc_registration_password_help', 15 );

function bsc_translate_account_fallbacks( $translated, $text, $domain ) {
	if ( 'woocommerce' !== $domain ) {
		return $translated;
	}
	$map = array(
		'My account' => 'حساب کاربری', 'Cart' => 'سبد خرید', 'Orders' => 'سفارش‌ها',
		'Account details' => 'اطلاعات حساب', 'Dashboard' => 'پیشخوان حساب', 'Logout' => 'خروج امن',
		'First name' => 'نام', 'Last name' => 'نام خانوادگی', 'Display name' => 'نام نمایشی',
		'Email address' => 'نشانی ایمیل', 'Password change' => 'تغییر رمز عبور',
		'Current password (leave blank to leave unchanged)' => 'رمز عبور فعلی (برای عدم تغییر خالی بگذارید)',
		'New password (leave blank to leave unchanged)' => 'رمز عبور جدید (برای عدم تغییر خالی بگذارید)',
		'Confirm new password' => 'تکرار رمز عبور جدید', 'Save changes' => 'ذخیره تغییرات',
		'No order has been made yet.' => 'هنوز سفارشی ثبت نشده است.', 'Browse products' => 'مشاهده محصولات',
		'Your cart is currently empty.' => 'سبد خرید شما خالی است.', 'Return to shop' => 'بازگشت به فروشگاه',
		'New in store' => 'تازه‌های فروشگاه',
	);
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}
add_filter( 'gettext', 'bsc_translate_account_fallbacks', 99, 3 );

function bsc_render_known_shortcode_block( $block_content, $block ) {
	unset( $block );
	$plain = html_entity_decode( wp_strip_all_tags( $block_content ), ENT_QUOTES, 'UTF-8' );
	if ( false !== strpos( $plain, 'bsc_product_categories' ) && function_exists( 'bsc_product_categories_shortcode' ) ) {
		return bsc_product_categories_shortcode( array( 'limit' => 6 ) );
	}
	if ( false !== strpos( $plain, 'barbershop_before_after_gallery' ) && function_exists( 'bsc_gallery_shortcode' ) ) {
		return bsc_gallery_shortcode( array( 'limit' => 6 ) );
	}
	if ( preg_match( '/\bproducts\b/u', $plain ) && shortcode_exists( 'products' ) ) {
		return do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC" visibility="visible"]' );
	}
	return $block_content;
}
add_filter( 'render_block_core/shortcode', 'bsc_render_known_shortcode_block', 5, 2 );

function bsc_render_editable_storefront_content( $block_content, $block ) {
	unset( $block );
	$content = bsc_get_store_content();
	$replacements = array(
		'[نام برند]' => $content['brand_name'], '[نام کسب‌وکار]' => $content['brand_name'],
		'[شماره تماس]' => $content['contact_phone'], '[ساعت پاسخ‌گویی]' => $content['contact_hours'],
		'[نشانی]' => $content['contact_address'], '[توضیح کوتاه برند و مزیت فروشگاه]' => $content['footer_description'],
		'مراقبت حرفه‌ای ریش، مو و پوست' => $content['brand_tagline'], 'قدرت طبیعت، برای' => $content['hero_title_prefix'],
		'استایل حرفه‌ای' => $content['hero_title_emphasis'],
		'محصولات منتخب آرایشگاهی با دسته‌بندی روشن، اطلاعات دقیق و خرید روان؛ از انتخاب محصول تا پرداخت، بدون مسیرهای گیج‌کننده.' => $content['hero_description'],
		'انتخاب دسته‌بندی' => $content['hero_primary_label'], 'مشاهده فروشگاه' => $content['hero_secondary_label'],
		'جزئیات کوچک، تفاوت بزرگ' => $content['services_heading'], 'اصلاح و فرم‌دهی مو' => $content['service_1_title'],
		'اصلاح و طراحی ریش' => $content['service_2_title'], 'مشاوره استایل' => $content['service_3_title'],
		'قبل و بعد، با اجازه انتشار' => $content['portfolio_heading'],
		'تصاویر زیر جای‌نگهدار هستند. آن‌ها را فقط با عکس‌های مجاز جایگزین کنید.' => $content['portfolio_text'],
		'راهنمای نگهداری و استایل' => $content['articles_heading'], 'اعتماد، از تجربه واقعی می‌آید' => $content['reviews_heading'],
		'ارتباط مستقیم با [نام کسب‌وکار]' => $content['contact_heading'],
	);
	$safe = array();
	foreach ( $replacements as $source => $replacement ) {
		$safe[ $source ] = esc_html( $replacement );
	}
	$block_content = strtr( $block_content, $safe );
	$media_map = array(
		'assets/images/product-hero.svg' => 'hero_image_id', 'assets/images/gallery-1.svg' => 'gallery_1_id',
		'assets/images/gallery-2.svg' => 'gallery_2_id', 'assets/images/gallery-3.svg' => 'gallery_3_id',
		'assets/images/gallery-5.svg' => 'contact_image_id',
	);
	foreach ( $media_map as $theme_path => $option_key ) {
		$attachment_id = absint( $content[ $option_key ] );
		$replacement_url = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'full' ) : '';
		if ( $replacement_url ) {
			$block_content = str_replace( esc_url( get_theme_file_uri( $theme_path ) ), esc_url( $replacement_url ), $block_content );
		}
	}
	return $block_content;
}
add_filter( 'render_block', 'bsc_render_editable_storefront_content', 40, 2 );

function bsc_dashboard_text_fields() {
	return array(
		'brand_name' => 'نام برند', 'brand_tagline' => 'توضیح کوتاه برند',
		'hero_title_prefix' => 'بخش اول تیتر اصلی', 'hero_title_emphasis' => 'بخش برجسته تیتر اصلی',
		'hero_description' => 'توضیح بخش اصلی', 'hero_primary_label' => 'متن دکمه دسته‌بندی',
		'hero_secondary_label' => 'متن دکمه فروشگاه', 'footer_description' => 'توضیح پایین سایت',
		'contact_heading' => 'عنوان تماس', 'contact_phone' => 'شماره تماس', 'contact_hours' => 'ساعت پاسخ‌گویی',
		'contact_address' => 'نشانی', 'services_heading' => 'عنوان بخش خدمات',
		'service_1_title' => 'خدمت اول', 'service_1_text' => 'توضیح خدمت اول',
		'service_2_title' => 'خدمت دوم', 'service_2_text' => 'توضیح خدمت دوم',
		'service_3_title' => 'خدمت سوم', 'service_3_text' => 'توضیح خدمت سوم',
		'portfolio_heading' => 'عنوان قبل و بعد', 'portfolio_text' => 'توضیح قبل و بعد',
		'articles_heading' => 'عنوان مقاله‌ها', 'reviews_heading' => 'عنوان نظر مشتریان',
		'review_1_text' => 'نظر مشتری اول', 'review_1_name' => 'نام نمایشی مشتری اول',
		'review_2_text' => 'نظر مشتری دوم', 'review_2_name' => 'نام نمایشی مشتری دوم',
		'review_3_text' => 'نظر مشتری سوم', 'review_3_name' => 'نام نمایشی مشتری سوم',
	);
}

function bsc_dashboard_media_fields() {
	return array(
		'logo_id' => 'لوگوی برند', 'hero_image_id' => 'تصویر اصلی صفحه', 'contact_image_id' => 'تصویر بخش تماس',
		'gallery_1_id' => 'تصویر جای‌نگهدار نمونه‌کار ۱', 'gallery_2_id' => 'تصویر جای‌نگهدار نمونه‌کار ۲',
		'gallery_3_id' => 'تصویر جای‌نگهدار نمونه‌کار ۳',
	);
}

function bsc_save_barber_dashboard() {
	if ( empty( $_POST['bsc_barber_dashboard_action'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_barbershop' ) ) {
		wp_die( 'دسترسی کافی ندارید.' );
	}
	check_admin_referer( 'bsc_save_barber_dashboard', 'bsc_barber_dashboard_nonce' );
	$defaults = bsc_store_content_defaults();
	$stored = bsc_get_store_content();
	foreach ( bsc_dashboard_text_fields() as $key => $label ) {
		unset( $label );
		if ( isset( $_POST['bsc_content'][ $key ] ) ) {
			$value = sanitize_textarea_field( wp_unslash( $_POST['bsc_content'][ $key ] ) );
			$stored[ $key ] = '' === $value ? $defaults[ $key ] : $value;
		}
	}
	foreach ( bsc_dashboard_media_fields() as $key => $label ) {
		unset( $label );
		$stored[ $key ] = isset( $_POST['bsc_content'][ $key ] ) ? absint( $_POST['bsc_content'][ $key ] ) : 0;
	}
	update_option( 'bsc_store_content', $stored, false );
	update_option( 'blogname', sanitize_text_field( $stored['brand_name'] ) );
	update_option( 'blogdescription', sanitize_text_field( $stored['brand_tagline'] ) );
	if ( $stored['logo_id'] ) {
		set_theme_mod( 'custom_logo', absint( $stored['logo_id'] ) );
	} else {
		remove_theme_mod( 'custom_logo' );
	}
	if ( isset( $_POST['bsc_categories'] ) && is_array( $_POST['bsc_categories'] ) && current_user_can( 'manage_product_terms' ) ) {
		foreach ( wp_unslash( $_POST['bsc_categories'] ) as $term_id => $row ) {
			$term_id = absint( $term_id );
			if ( ! $term_id || ! is_array( $row ) ) { continue; }
			$name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
			$menu_order = isset( $row['menu_order'] ) ? absint( $row['menu_order'] ) : 0;
			$icon_id = isset( $row['icon_id'] ) ? absint( $row['icon_id'] ) : 0;
			if ( $name ) { wp_update_term( $term_id, 'product_cat', array( 'name' => $name ) ); }
			update_term_meta( $term_id, 'order', $menu_order );
			if ( $icon_id && 'attachment' === get_post_type( $icon_id ) ) {
				update_term_meta( $term_id, '_bsc_category_icon_id', $icon_id );
			} else {
				delete_term_meta( $term_id, '_bsc_category_icon_id' );
			}
		}
	}
	set_transient( 'bsc_dashboard_saved_' . get_current_user_id(), 1, 60 );
	wp_safe_redirect( admin_url( 'admin.php?page=bsc-store-setup' ) );
	exit;
}
add_action( 'admin_init', 'bsc_save_barber_dashboard' );

function bsc_replace_store_dashboard_menu() {
	remove_menu_page( 'bsc-store-setup' );
	add_menu_page( 'داشبورد آرایشگر', 'داشبورد آرایشگر', 'manage_barbershop', 'bsc-store-setup', 'bsc_render_barber_dashboard', 'dashicons-store', 3 );
}
add_action( 'admin_menu', 'bsc_replace_store_dashboard_menu', 99 );

function bsc_barber_menu_cleanup() {
	$user = wp_get_current_user();
	if ( ! in_array( 'barber', (array) $user->roles, true ) ) { return; }
	foreach ( array( 'index.php', 'edit.php', 'edit.php?post_type=page', 'edit-comments.php', 'themes.php', 'plugins.php', 'users.php', 'tools.php', 'options-general.php' ) as $slug ) {
		remove_menu_page( $slug );
	}
}
add_action( 'admin_menu', 'bsc_barber_menu_cleanup', 999 );

function bsc_block_barber_visual_editors() {
	$user = wp_get_current_user();
	if ( ! in_array( 'barber', (array) $user->roles, true ) ) { return; }
	global $pagenow;
	if ( in_array( $pagenow, array( 'site-editor.php', 'customize.php', 'themes.php', 'plugins.php', 'plugin-install.php' ), true ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=bsc-store-setup' ) );
		exit;
	}
}
add_action( 'admin_init', 'bsc_block_barber_visual_editors', 1 );

function bsc_render_media_control( $key, $label, $attachment_id ) {
	$field_id = 'bsc_' . sanitize_key( $key );
	$image = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
	?>
	<div class="bsc-dashboard-media-field"><label><strong><?php echo esc_html( $label ); ?></strong></label>
	<input type="hidden" id="<?php echo esc_attr( $field_id ); ?>" name="bsc_content[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $attachment_id ); ?>">
	<div class="bsc-dashboard-media-preview" data-bsc-preview-wrap="<?php echo esc_attr( $field_id ); ?>" aria-live="polite"><img data-bsc-preview="<?php echo esc_attr( $field_id ); ?>" src="<?php echo esc_url( $image ); ?>" alt="پیش‌نمایش <?php echo esc_attr( $label ); ?>" <?php hidden( ! $image ); ?>></div>
	<div class="bsc-media-actions"><button type="button" class="button button-primary" data-bsc-media-target="<?php echo esc_attr( $field_id ); ?>" data-bsc-media-title="<?php echo esc_attr( $label ); ?>">انتخاب یا تغییر</button><button type="button" class="button button-link-delete" data-bsc-media-remove="<?php echo esc_attr( $field_id ); ?>">حذف</button></div></div>
	<?php
}

function bsc_render_barber_dashboard() {
	if ( ! current_user_can( 'manage_barbershop' ) ) { return; }
	$content = bsc_get_store_content();
	$products = wp_count_posts( 'product' );
	$portfolio = wp_count_posts( BSC_POST_TYPE );
	$categories = taxonomy_exists( 'product_cat' ) ? get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) : array();
	?>
	<div class="wrap bsc-barber-dashboard" dir="rtl">
	<header class="bsc-admin-hero"><div><p class="bsc-admin-kicker">پنل اختصاصی آرایشگر</p><h1>مدیریت کامل فروشگاه، بدون ویرایشگر ظاهری</h1><p>برند، تصاویر، متن‌ها، دسته‌ها، محصولات، سفارش‌ها و نمونه‌های قبل و بعد از همین مسیر مدیریت می‌شوند.</p></div><a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>">افزودن محصول</a></header>
	<?php if ( get_transient( 'bsc_dashboard_saved_' . get_current_user_id() ) ) : delete_transient( 'bsc_dashboard_saved_' . get_current_user_id() ); ?><div class="notice notice-success is-dismissible"><p>تغییرات داشبورد ذخیره شد.</p></div><?php endif; ?>
	<div class="bsc-stat-grid"><div class="bsc-stat"><strong><?php echo esc_html( number_format_i18n( $products && isset( $products->publish ) ? $products->publish : 0 ) ); ?></strong><span>محصول منتشرشده</span></div><div class="bsc-stat"><strong><?php echo esc_html( number_format_i18n( is_wp_error( $categories ) ? 0 : count( $categories ) ) ); ?></strong><span>دسته اصلی</span></div><div class="bsc-stat"><strong><?php echo esc_html( number_format_i18n( $portfolio && isset( $portfolio->publish ) ? $portfolio->publish : 0 ) ); ?></strong><span>نمونه قبل و بعد</span></div><div class="bsc-stat"><strong>LIVE</strong><span>حالت Coming soon خاموش</span></div></div>
	<nav class="bsc-dashboard-actions" aria-label="دسترسی‌های مدیریت"><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>">محصولات و موجودی</a><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_order' ) ); ?>">سفارش‌ها</a><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . BSC_POST_TYPE ) ); ?>">قبل و بعد</a><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ); ?>">همه دسته‌ها و زیرگروه‌ها</a><?php if ( current_user_can( 'manage_options' ) ) : ?><a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>">ساخت حساب آرایشگر</a><?php endif; ?></nav>
	<form method="post" class="bsc-dashboard-form"><?php wp_nonce_field( 'bsc_save_barber_dashboard', 'bsc_barber_dashboard_nonce' ); ?><input type="hidden" name="bsc_barber_dashboard_action" value="save">
	<section class="bsc-admin-panel"><div class="bsc-panel-heading"><div><h2>هویت، متن‌ها و اطلاعات تماس</h2><p>تمام جای‌نگهدارهای اصلی سایت از این فرم جایگزین می‌شوند.</p></div></div><div class="bsc-dashboard-field-grid">
	<?php foreach ( bsc_dashboard_text_fields() as $key => $label ) : $is_long = false !== strpos( $key, 'description' ) || false !== strpos( $key, '_text' ); ?><label class="<?php echo $is_long ? 'is-wide' : ''; ?>"><span><?php echo esc_html( $label ); ?></span><?php if ( $is_long ) : ?><textarea name="bsc_content[<?php echo esc_attr( $key ); ?>]" rows="3"><?php echo esc_textarea( $content[ $key ] ); ?></textarea><?php else : ?><input type="text" name="bsc_content[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $content[ $key ] ); ?>"><?php endif; ?></label><?php endforeach; ?>
	</div></section>
	<section class="bsc-admin-panel"><div class="bsc-panel-heading"><div><h2>لوگو و تصاویر جای‌نگهدار</h2><p>فایل‌ها را مستقیم از کتابخانه رسانه انتخاب کنید؛ نیازی به ویرایشگر سایت نیست.</p></div></div><div class="bsc-dashboard-media-grid"><?php foreach ( bsc_dashboard_media_fields() as $key => $label ) { bsc_render_media_control( $key, $label, absint( $content[ $key ] ) ); } ?></div></section>
	<section class="bsc-admin-panel"><div class="bsc-panel-heading"><div><h2>دسته‌بندی و آیکون‌ها</h2><p>نام، ترتیب و آیکون بزرگ دسته‌های اصلی را همین‌جا تغییر دهید.</p></div></div><div class="bsc-dashboard-category-grid">
	<?php if ( ! is_wp_error( $categories ) ) : foreach ( $categories as $term ) : $icon_id = absint( get_term_meta( $term->term_id, '_bsc_category_icon_id', true ) ); $field_id = 'bsc_category_icon_' . $term->term_id; $image = $icon_id ? wp_get_attachment_image_url( $icon_id, 'thumbnail' ) : ''; ?>
	<article class="bsc-dashboard-category"><label><span>نام دسته</span><input type="text" name="bsc_categories[<?php echo esc_attr( $term->term_id ); ?>][name]" value="<?php echo esc_attr( $term->name ); ?>"></label><label><span>ترتیب</span><input type="number" min="0" max="999" name="bsc_categories[<?php echo esc_attr( $term->term_id ); ?>][menu_order]" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'order', true ) ); ?>"></label><input type="hidden" id="<?php echo esc_attr( $field_id ); ?>" name="bsc_categories[<?php echo esc_attr( $term->term_id ); ?>][icon_id]" value="<?php echo esc_attr( $icon_id ); ?>"><div class="bsc-dashboard-media-preview" data-bsc-preview-wrap="<?php echo esc_attr( $field_id ); ?>"><img data-bsc-preview="<?php echo esc_attr( $field_id ); ?>" src="<?php echo esc_url( $image ); ?>" alt="آیکون <?php echo esc_attr( $term->name ); ?>" <?php hidden( ! $image ); ?>></div><div class="bsc-media-actions"><button type="button" class="button" data-bsc-media-target="<?php echo esc_attr( $field_id ); ?>" data-bsc-media-title="آیکون <?php echo esc_attr( $term->name ); ?>">انتخاب آیکون</button><button type="button" class="button button-link-delete" data-bsc-media-remove="<?php echo esc_attr( $field_id ); ?>">حذف</button></div><a href="<?php echo esc_url( admin_url( 'term.php?taxonomy=product_cat&tag_ID=' . $term->term_id . '&post_type=product' ) ); ?>">ویرایش زیرگروه‌ها و توضیحات</a></article>
	<?php endforeach; endif; ?></div></section>
	<div class="bsc-dashboard-save"><button type="submit" class="button button-primary button-hero">ذخیره همه تغییرات</button><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">مشاهده سایت</a></div></form>
	<p class="bsc-admin-disclaimer">ورود مدیر و آرایشگر از wp-admin انجام می‌شود. ورود مشتریان در صفحه حساب فروشگاه است. وردپرس روی یک دامنه یک نشست احراز هویت مشترک دارد؛ برای آزمون تجربه مشتری از پنجره ناشناس یا مرورگر جدا استفاده کنید.</p></div>
	<?php
}

function bsc_barber_dashboard_assets( $hook ) {
	if ( 'toplevel_page_bsc-store-setup' !== $hook ) { return; }
	wp_enqueue_media();
	wp_enqueue_style( 'barbershop-core-admin', BSC_URL . 'assets/admin.css', array(), BSC_VERSION );
	wp_enqueue_style( 'barbershop-core-barber', BSC_URL . 'assets/barber-dashboard.css', array( 'barbershop-core-admin' ), BSC_VERSION );
	wp_enqueue_script( 'barbershop-core-admin', BSC_URL . 'assets/admin.js', array(), BSC_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'bsc_barber_dashboard_assets', 20 );

function bsc_account_experience_assets() {
	if ( function_exists( 'is_account_page' ) && ( is_account_page() || is_cart() || is_checkout() ) ) {
		wp_enqueue_style( 'barbershop-core-account', BSC_URL . 'assets/account.css', array( 'persian-barbershop-woocommerce' ), BSC_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'bsc_account_experience_assets', 30 );
