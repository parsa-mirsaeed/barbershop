<?php
/** Persian starter privacy and purchase terms for the storefront. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create one managed legal page without overwriting merchant edits.
 *
 * A fresh WordPress installation may already contain an unpublished English
 * "Privacy Policy" draft at the requested slug. That untouched core draft is
 * safe to localize; published or renamed merchant pages are always preserved.
 *
 * @param string $slug Page slug.
 * @param string $title Page title.
 * @param string $content Starter HTML.
 * @return int
 */
function bsc_ensure_legal_page( $slug, $title, $content ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $page instanceof WP_Post ) {
		$is_untouched_core_privacy_draft = (
			'privacy-policy' === $slug
			&& 'Privacy Policy' === trim( (string) $page->post_title )
			&& in_array( $page->post_status, array( 'draft', 'auto-draft' ), true )
		);

		if ( $is_untouched_core_privacy_draft ) {
			$updated = wp_update_post(
				array(
					'ID'           => (int) $page->ID,
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_content' => wp_kses_post( $content ),
				),
				true
			);
			if ( ! is_wp_error( $updated ) ) {
				update_post_meta( $page->ID, '_bsc_managed_legal_page', 'yes' );
			}
		}

		return (int) $page->ID;
	}

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $title,
			'post_content' => wp_kses_post( $content ),
		),
		true
	);
	if ( is_wp_error( $page_id ) ) {
		return 0;
	}
	update_post_meta( $page_id, '_bsc_managed_legal_page', 'yes' );
	return (int) $page_id;
}

/**
 * Build and select Persian privacy and terms pages once per legal schema.
 * Existing published pages are retained exactly as edited by the merchant.
 *
 * @return void
 */
function bsc_ensure_legal_pages() {
	$legal_schema_version = '3';
	if ( $legal_schema_version === get_option( 'bsc_legal_pages_version' ) ) {
		return;
	}

	$site_name        = wp_strip_all_tags( get_bloginfo( 'name' ) );
	$admin_email      = sanitize_email( get_option( 'admin_email' ) );
	$supplier_name    = sanitize_text_field( get_option( 'bsc_legal_supplier_name', '' ) );
	$supplier_address = sanitize_textarea_field( get_option( 'bsc_legal_supplier_address', '' ) );
	$supplier_phone   = sanitize_text_field( get_option( 'bsc_legal_supplier_phone', '' ) );
	$supplier_email   = sanitize_email( get_option( 'bsc_legal_supplier_email', $admin_email ) );
	$supplier_name    = $supplier_name ? $supplier_name : $site_name;
	$supplier_address = $supplier_address ? $supplier_address : 'نشانی درج‌شده در بخش تماس فروشگاه';
	$supplier_phone   = $supplier_phone ? $supplier_phone : 'شماره درج‌شده در بخش تماس فروشگاه';
	$privacy     = sprintf(
		'<div class="bsc-legal-page"><p class="bsc-legal-lead">در %1$s، اصل بر جمع‌آوری حداقلی اطلاعات است؛ فقط داده‌های لازم برای ساخت و مدیریت حساب، انجام سفارش، پرداخت، ارسال، پشتیبانی و امنیت فروشگاه پردازش می‌شود.</p>
<h2>چه اطلاعاتی دریافت می‌شود؟</h2>
<ul><li>نام، شماره موبایل، ایمیل و اطلاعات حساب؛ رمز عبور به شکل هش نگهداری می‌شود و متن اصلی آن برای فروشگاه قابل مشاهده نیست؛</li><li>جزئیات سفارش، نشانی تحویل، وضعیت پرداخت و اطلاعات لازم برای رسید، پشتیبانی و بازپرداخت؛</li><li>اطلاعات فنی ضروری مانند IP، مرورگر، کوکی‌های سبد خرید و ورود و گزارش‌های امنیتی؛</li><li>سوابق پیام‌ها، درخواست‌های پشتیبانی و رضایت‌هایی که هنگام ثبت‌نام یا خرید اعلام می‌کنید.</li></ul>
<h2>چرا از این اطلاعات استفاده می‌کنیم؟</h2>
<p>برای ورود و بازیابی حساب، اجرای سفارش، اطلاع‌رسانی وضعیت سفارش، جلوگیری از تقلب، پاسخ‌گویی، حسابداری و انجام تکالیف قانونی. اطلاعات برای هدفی نامرتبط با هدف اعلام‌شده استفاده نمی‌شود مگر با رضایت لازم یا الزام قانونی.</p>
<h2>اشتراک‌گذاری محدود اطلاعات</h2>
<p>اطلاعات فروخته نمی‌شود. فقط در حد لازم با ارائه‌دهندگان مرتبط مانند درگاه پرداخت، شرکت یا سامانه حمل‌ونقل، سرویس پیامک، میزبانی، CDN و خدمات امنیتی یا پشتیبانی فنی به اشتراک گذاشته می‌شود، یا در مواردی که قانون یا دستور مرجع صالح ایجاب کند.</p>
<h2>پرداخت</h2>
<p>فروشگاه فقط شناسه و وضعیت لازم تراکنش را دریافت می‌کند. اطلاعات محرمانه کارت بانکی در محیط امن ارائه‌دهنده پرداخت وارد می‌شود و در فروشگاه ذخیره نمی‌شود.</p>
<h2>نگهداری و امنیت</h2>
<p>اطلاعات تا زمانی نگهداری می‌شود که برای ارائه خدمت، پشتیبانی، حسابداری، رسیدگی به اختلاف یا تکلیف قانونی لازم باشد. دسترسی مدیریتی محدود می‌شود و از روش‌های متعارف امنیتی، نسخه پشتیبان و ارتباط رمزگذاری‌شده استفاده می‌کنیم؛ با این حال هیچ سامانه اینترنتی مصونیت مطلق ندارد.</p>
<h2>حقوق و انتخاب‌های شما</h2>
<p>می‌توانید اطلاعات حساب خود را ویرایش کنید و برای دسترسی، اصلاح یا حذف اطلاعات شخصی مربوط به خود درخواست بفرستید. حذف اطلاعاتی که نگهداری آن برای اجرای سفارش، سوابق مالی، رسیدگی به اختلاف یا تکلیف قانونی لازم است ممکن است تا پایان مدت لازم محدود شود.</p>
<h2>کوکی‌ها و خدمات فنی</h2>
<p>وردپرس و ووکامرس برای سبد خرید، نشست ورود، امنیت و عملکرد ضروری سایت از کوکی استفاده می‌کنند. اگر ابزار تحلیلی یا تبلیغاتی غیراجباری افزوده شود، استفاده مربوط باید شفاف اعلام و انتخاب‌های لازم در اختیار کاربر قرار گیرد.</p>
<h2>پیام‌های سفارش و تبلیغات</h2>
<p>پیام‌های مربوط به حساب، پرداخت، سفارش و ارسال، پیام‌های عملیاتی خدمت هستند. پیام تبلیغاتی جدا از این پیام‌ها مدیریت می‌شود و کاربر باید امکان انتخاب درباره دریافت تبلیغات داشته باشد.</p>
<h2>تماس درباره حریم خصوصی</h2>
<p>درخواست‌های مرتبط با حریم خصوصی را به %2$s یا راه ارتباطی درج‌شده در صفحه تماس ارسال کنید.</p>
</div>',
		esc_html( $site_name ),
		esc_html( antispambot( $supplier_email ? $supplier_email : $admin_email ) )
	);
	$terms      = sprintf(
		'<div class="bsc-legal-page"><p class="bsc-legal-lead">ثبت سفارش در %1$s به معنی مطالعه و پذیرش شرایطی است که پیش از خرید در دسترس شما قرار گرفته است. این شرایط در کنار حقوق الزامی مصرف‌کننده طبق قوانین جاری جمهوری اسلامی ایران اجرا می‌شود.</p>
<h2>هویت فروشنده و راه‌های تماس</h2>
<ul><li><strong>نام تجاری:</strong> %1$s</li><li><strong>تأمین‌کننده قانونی:</strong> %2$s</li><li><strong>نشانی محل کسب:</strong> %3$s</li><li><strong>شماره تماس:</strong> %4$s</li><li><strong>ایمیل:</strong> %5$s</li></ul>
<h2>حساب کاربری</h2>
<p>کاربر مسئول درست‌بودن شماره موبایل، ایمیل و اطلاعات تحویل و همچنین حفاظت از رمز عبور خود است. ورود با شماره موبایل یا ایمیل و رمز عبور انجام می‌شود.</p>
<h2>ثبت و پذیرش سفارش</h2>
<p>ثبت پرداخت به‌تنهایی تضمین نهایی موجودی نیست. سفارش پس از بررسی پرداخت، موجودی و امکان ارسال تأیید می‌شود. در صورت خطای قیمت، نبود موجودی یا ناممکن‌بودن انجام سفارش، فروشگاه موضوع را اطلاع می‌دهد و مبلغ دریافت‌شده را از مسیر مناسب بازمی‌گرداند.</p>
<h2>قیمت و پرداخت</h2>
<p>مبلغ نهایی شامل قیمت کالا، تخفیف، مالیات قانونی در صورت اعمال و هزینه ارسال است و پیش از ثبت سفارش نمایش داده می‌شود. پرداخت فقط از روش‌های فعال و رسمی فروشگاه انجام می‌شود.</p>
<h2>ارسال و تحویل</h2>
<p>زمان‌های اعلام‌شده تقریبی هستند و ممکن است تحت تأثیر مقصد، شرکت حمل، تعطیلات یا شرایط خارج از اختیار فروشگاه تغییر کنند. مسئولیت واردکردن نشانی و شماره تماس صحیح با خریدار است.</p>
<h2>لغو، انصراف و مرجوعی</h2>
<p>درخواست لغو یا بازگشت مطابق قوانین قابل‌اعمال، ماهیت کالا، وضعیت پلمب و بهداشت، استفاده‌نشدن کالا و شرایط اعلام‌شده برای همان محصول بررسی می‌شود. کالاهای شخصی‌سازی‌شده، مصرف‌شده یا دارای محدودیت بهداشتی ممکن است مشمول استثنا باشند.</p>
<h2>سلامت و اصالت کالا</h2>
<p>توضیحات و تصاویر برای معرفی دقیق ارائه می‌شوند، اما تفاوت جزئی نمایش رنگ یا بسته‌بندی ممکن است رخ دهد. دستور مصرف، هشدار تولیدکننده و محدودیت‌های فردی باید رعایت شود.</p>
<h2>ارتباطات سفارش</h2>
<p>فروشگاه می‌تواند پیام‌های ضروری مربوط به حساب، پرداخت، وضعیت سفارش و ارسال را از طریق ایمیل یا پیامک ارسال کند. پیام تبلیغاتی باید جدا از پیام ضروری و بر اساس انتخاب کاربر مدیریت شود.</p>
<h2>رسیدگی به اختلاف</h2>
<p>ابتدا از راه‌های تماس فروشگاه درخواست خود را همراه شماره سفارش مطرح کنید تا موضوع با گفت‌وگو و مستندات سفارش بررسی شود. حقوق قانونی مصرف‌کننده با این شرایط محدود نمی‌شود.</p>
</div>',
		esc_html( $site_name )
	);

	$privacy_id = bsc_ensure_legal_page( 'privacy-policy', 'سیاست حریم خصوصی', $privacy );
	$terms_id   = bsc_ensure_legal_page( 'terms-and-conditions', 'شرایط استفاده و خرید', $terms );
	if ( $privacy_id ) {
		update_option( 'wp_page_for_privacy_policy', $privacy_id );
	}
	if ( $terms_id ) {
		update_option( 'woocommerce_terms_page_id', $terms_id );
	}
	update_option( 'bsc_legal_pages_version', $legal_schema_version, false );
}
add_action( 'init', 'bsc_ensure_legal_pages', 45 );
add_action( 'woocommerce_installed', 'bsc_ensure_legal_pages', 30 );

/**
 * Style the generated legal pages using the public refinement stylesheet.
 *
 * @param array $classes Body classes.
 * @return array
 */
function bsc_legal_body_class( $classes ) {
	if ( is_page( array( 'privacy-policy', 'terms-and-conditions' ) ) ) {
		$classes[] = 'bsc-legal-document';
	}
	return $classes;
}
add_filter( 'body_class', 'bsc_legal_body_class' );
