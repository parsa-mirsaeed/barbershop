<?php
/** Admin onboarding, order notifications, operational status, and privacy text. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bsc_add_settings_section( $sections ) {
	$sections['barbershop'] = 'تنظیمات آرایشگاه';
	return $sections;
}
add_filter( 'woocommerce_get_sections_general', 'bsc_add_settings_section' );

function bsc_general_settings( $settings, $section ) {
	if ( 'barbershop' !== $section ) { return $settings; }
	return array(
		array( 'title' => 'اطلاع‌رسانی و امنیت', 'type' => 'title', 'id' => 'bsc_settings', 'desc' => 'تنظیمات ضروری فروشگاه را در یک صفحه مدیریت کنید.' ),
		array( 'title' => 'ایمیل دریافت سفارش', 'desc' => 'اعلان سفارش جدید به این ایمیل ارسال می‌شود.', 'id' => 'bsc_order_email', 'type' => 'email', 'default' => get_option( 'admin_email' ), 'desc_tip' => true ),
		array( 'title' => 'غیرفعال‌کردن XML-RPC', 'desc' => 'برای فروشگاهی که به XML-RPC نیاز ندارد پیشنهاد می‌شود. در صورت استفاده از اپلیکیشن یا سرویس وابسته، این گزینه را خاموش کنید.', 'id' => 'bsc_disable_xmlrpc', 'type' => 'checkbox', 'default' => 'yes' ),
		array( 'type' => 'sectionend', 'id' => 'bsc_settings' ),
	);
}
add_filter( 'woocommerce_get_settings_general', 'bsc_general_settings', 10, 2 );

function bsc_new_order_recipient( $recipient ) {
	$email = sanitize_email( get_option( 'bsc_order_email', '' ) );
	return is_email( $email ) ? $email : $recipient;
}
add_filter( 'woocommerce_email_recipient_new_order', 'bsc_new_order_recipient' );

function bsc_admin_menu() {
	add_menu_page( 'مرکز مدیریت فروشگاه', 'مدیریت فروشگاه', 'manage_woocommerce', 'bsc-store-setup', 'bsc_render_setup_page', 'dashicons-store', 56 );
}
add_action( 'admin_menu', 'bsc_admin_menu' );

function bsc_handle_setup_action() {
	if ( ! isset( $_POST['bsc_setup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_setup_nonce'] ) ), 'bsc_run_setup' ) ) { return; }
	if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
	bsc_apply_woocommerce_defaults();
	$font_result = bsc_install_vazirmatn_font();
	$message = is_wp_error( $font_result ) ? 'تنظیمات و دسته‌بندی‌ها اعمال شد؛ دریافت فونت انجام نشد و باید دوباره تلاش شود.' : 'تنظیمات فروشگاه، دسته‌بندی‌ها و فونت وزیرمتن با موفقیت آماده شد.';
	add_settings_error( 'bsc_setup', 'bsc_setup_done', $message, is_wp_error( $font_result ) ? 'warning' : 'success' );
}
add_action( 'admin_init', 'bsc_handle_setup_action' );

function bsc_is_plugin_active( $plugin ) {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	return function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin );
}

function bsc_status_item( $label, $value, $ok = true ) {
	$class = $ok ? 'is-good' : 'needs-attention';
	$icon  = $ok ? 'yes-alt' : 'warning';
	return '<div class="bsc-status-item ' . esc_attr( $class ) . '"><span class="dashicons dashicons-' . esc_attr( $icon ) . '" aria-hidden="true"></span><span><strong>' . esc_html( $label ) . '</strong><small>' . esc_html( $value ) . '</small></span></div>';
}

function bsc_render_setup_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
	settings_errors( 'bsc_setup' );
	$product_count  = wp_count_posts( 'product' );
	$order_count    = wp_count_posts( 'shop_order' );
	$category_count = taxonomy_exists( 'product_cat' ) ? wp_count_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) : 0;
	$product_total  = $product_count && isset( $product_count->publish ) ? (int) $product_count->publish : 0;
	$order_total    = $order_count && isset( $order_count->wc_processing ) ? (int) $order_count->wc_processing : 0;
	$font_ready     = is_readable( bsc_font_file_path() );
	$wordfence      = bsc_is_plugin_active( 'wordfence/wordfence.php' );
	$woocommerce    = bsc_is_plugin_active( 'woocommerce/woocommerce.php' );
	$category_url   = admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' );
	$product_url    = admin_url( 'edit.php?post_type=product' );
	$order_url      = admin_url( 'edit.php?post_type=shop_order' );
	$customize_url  = admin_url( 'site-editor.php' );
	$security_url   = admin_url( 'admin.php?page=Wordfence' );
	$settings_url   = admin_url( 'admin.php?page=wc-settings&tab=general&section=barbershop' );
	?>
	<div class="wrap bsc-setup-page" dir="rtl">
		<section class="bsc-admin-hero">
			<div>
				<p class="bsc-admin-kicker">مرکز کنترل فروشگاه</p>
				<h1>فروشگاه حرفه‌ای، مدیریت ساده</h1>
				<p>محصول، دسته‌بندی، سفارش، ظاهر و وضعیت امنیت را بدون ویرایش کد مدیریت کنید.</p>
			</div>
			<a class="button button-primary button-hero" href="<?php echo esc_url( $product_url ); ?>">افزودن محصول جدید</a>
		</section>

		<div class="bsc-stat-grid" aria-label="آمار فروشگاه">
			<div class="bsc-stat"><strong><?php echo esc_html( number_format_i18n( $product_total ) ); ?></strong><span>محصول منتشرشده</span></div>
			<div class="bsc-stat"><strong><?php echo esc_html( number_format_i18n( is_wp_error( $category_count ) ? 0 : (int) $category_count ) ); ?></strong><span>دسته و زیر‌دسته</span></div>
			<div class="bsc-stat"><strong><?php echo esc_html( number_format_i18n( $order_total ) ); ?></strong><span>سفارش در حال پردازش</span></div>
			<div class="bsc-stat"><strong>RTL</strong><span>رابط فارسی و موبایل‌محور</span></div>
		</div>

		<section class="bsc-admin-panel">
			<div class="bsc-panel-heading"><div><h2>وضعیت آماده‌بودن فروشگاه</h2><p>این موارد را پیش از اتصال درگاه و انتشار عمومی بررسی کنید.</p></div><a href="<?php echo esc_url( $settings_url ); ?>">تنظیمات پیشرفته</a></div>
			<div class="bsc-status-grid">
				<?php echo wp_kses_post( bsc_status_item( 'ووکامرس', $woocommerce ? 'فعال و آماده' : 'نیازمند فعال‌سازی', $woocommerce ) ); ?>
				<?php echo wp_kses_post( bsc_status_item( 'Wordfence', $wordfence ? 'فعال؛ تکمیل پیکربندی و ۲FA را بررسی کنید' : 'نیازمند نصب یا فعال‌سازی', $wordfence ) ); ?>
				<?php echo wp_kses_post( bsc_status_item( 'فونت وزیرمتن', $font_ready ? 'نسخه محلی و بدون وابستگی زمان اجرا' : 'نیازمند دریافت دوباره', $font_ready ) ); ?>
				<?php echo wp_kses_post( bsc_status_item( 'HTTPS و پشتیبان‌گیری', 'در محیط میزبانی باید جداگانه کنترل شود', false ) ); ?>
			</div>
			<?php if ( $wordfence ) : ?><p class="bsc-security-note"><strong>اقدام مهم:</strong> در Wordfence، فایروال را بهینه کنید، احراز هویت دومرحله‌ای مدیران را فعال کنید و ایمیل هشدار را تنظیم کنید.</p><?php endif; ?>
		</section>

		<div class="bsc-setup-grid">
			<article class="bsc-setup-card"><span class="dashicons dashicons-format-image" aria-hidden="true"></span><h2>لوگو و ظاهر سایت</h2><p>لوگو، نام، صفحه اصلی، منو و متن‌ها را در ویرایشگر سایت تغییر دهید.</p><a class="button button-primary" href="<?php echo esc_url( $customize_url ); ?>">ویرایش ظاهر سایت</a></article>
			<article class="bsc-setup-card"><span class="dashicons dashicons-category" aria-hidden="true"></span><h2>دسته‌بندی و آیکون‌ها</h2><p>نام، ترتیب، زیرگروه و آیکون بزرگ هر دسته را ویرایش کنید.</p><a class="button button-primary" href="<?php echo esc_url( $category_url ); ?>">مدیریت دسته‌بندی‌ها</a></article>
			<article class="bsc-setup-card"><span class="dashicons dashicons-products" aria-hidden="true"></span><h2>محصولات و موجودی</h2><p>تصویر، قیمت، موجودی، توضیح و دسته‌بندی محصول را مدیریت کنید.</p><a class="button button-primary" href="<?php echo esc_url( $product_url ); ?>">مدیریت محصولات</a></article>
			<article class="bsc-setup-card"><span class="dashicons dashicons-clipboard" aria-hidden="true"></span><h2>سفارش‌ها</h2><p>سفارش‌های جدید، وضعیت پرداخت و آماده‌سازی را در یک فهرست ببینید.</p><a class="button button-primary" href="<?php echo esc_url( $order_url ); ?>">مشاهده سفارش‌ها</a></article>
			<article class="bsc-setup-card"><span class="dashicons dashicons-shield" aria-hidden="true"></span><h2>امنیت Wordfence</h2><p>فایروال، اسکن، محدودسازی ورود و ۲FA مدیران را تکمیل کنید.</p><a class="button button-primary" href="<?php echo esc_url( $security_url ); ?>">بازکردن Wordfence</a></article>
			<article class="bsc-setup-card"><span class="dashicons dashicons-admin-settings" aria-hidden="true"></span><h2>تنظیمات سفارش</h2><p>ایمیل سفارش و تنظیمات سخت‌سازی سازگار با فروشگاه را مدیریت کنید.</p><a class="button button-primary" href="<?php echo esc_url( $settings_url ); ?>">بازکردن تنظیمات</a></article>
		</div>

		<form method="post" class="bsc-repair-form">
			<?php wp_nonce_field( 'bsc_run_setup', 'bsc_setup_nonce' ); ?>
			<div><strong>ترمیم تنظیمات پایه</strong><p>دسته‌بندی‌های پیشنهادی، ثبت‌نام کوتاه و فونت محلی را بدون حذف محصولات فعلی بازسازی می‌کند.</p></div>
			<button type="submit" class="button">اجرای ترمیم امن</button>
		</form>
		<p class="bsc-admin-disclaimer">هیچ افزونه‌ای به‌تنهایی امنیت مطلق ایجاد نمی‌کند. به‌روزرسانی، HTTPS، پشتیبان رمزگذاری‌شده، کمینه‌سازی دسترسی‌ها و نظارت میزبان همچنان ضروری است.</p>
	</div>
	<?php
}

function bsc_admin_assets( $hook ) {
	$screen = get_current_screen();
	$needs_media = $screen && ( BSC_POST_TYPE === $screen->post_type || ( 'product_cat' === $screen->taxonomy && in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) );
	$needs_style = 'toplevel_page_bsc-store-setup' === $hook || $needs_media || ( $screen && in_array( $screen->post_type, array( 'product', 'shop_order' ), true ) );
	if ( $needs_media ) {
		wp_enqueue_media();
		wp_enqueue_script( 'barbershop-core-admin', BSC_URL . 'assets/admin.js', array(), BSC_VERSION, true );
	}
	if ( $needs_style ) {
		wp_enqueue_style( 'barbershop-core-admin', BSC_URL . 'assets/admin.css', array(), BSC_VERSION );
		if ( is_readable( bsc_font_file_path() ) ) {
			wp_add_inline_style( 'barbershop-core-admin', '@font-face{font-family:Vazirmatn;src:url("' . esc_url_raw( bsc_font_file_url() ) . '") format("woff2");font-display:swap;font-weight:100 900} .bsc-setup-page,.bsc-setup-page .button,.term-bsc-icon-wrap{font-family:Vazirmatn,Tahoma,sans-serif}' );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'bsc_admin_assets' );

function bsc_privacy_policy() {
	if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
		wp_add_privacy_policy_content( 'فروشگاه آرایشگاه', wp_kses_post( '<p>ووکامرس اطلاعات لازم برای حساب، سفارش و تحویل را مطابق تنظیمات نگهداری می‌کند. داده‌های غیرضروری ثبت‌نام جمع‌آوری نمی‌شود. اطلاعات کارت باید فقط در صفحه امن ارائه‌دهنده پرداخت وارد شود و این سایت نباید شماره کارت، CVV یا رمز را ذخیره کند. انتشار تصاویر قبل و بعد نیازمند اجازه مستند و قابل لغو است.</p>' ) );
	}
}
add_action( 'admin_init', 'bsc_privacy_policy' );
