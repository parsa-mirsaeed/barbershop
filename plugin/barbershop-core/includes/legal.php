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
	$legal_schema_version = '2';
	if ( $legal_schema_version === get_option( 'bsc_legal_pages_version' ) ) {
		return;
	}

	$site_name   = wp_strip_all_tags( get_bloginfo( 'name' ) );
	$admin_email = sanitize_email( get_option( 'admin_email' ) );
	$privacy     = sprintf(
		'<div class="bsc-legal-page"><p class="bsc-legal-lead">در %1$s، اطلاعات شخصی فقط به اندازه لازم برای ساخت حساب، پردازش سفارش، پرداخت، ارسال، پشتیبانی و امنیت فروشگاه استفاده می‌شود.</p>
<h2>چه اطلاعاتی دریافت می‌شود؟</h2>
<ul><li>نام، شماره موبایل، ایمیل و رمز عبور رمزنگاری‌شده حساب؛</li><li>جزئیات سفارش، نشانی تحویل و اطلاعات لازم برای صدور رسید؛</li><li>اطلاعات فنی ضروری مانند نشانی IP، مرورگر و گزارش‌های امنیتی؛</li><li>سوابق پیام‌ها و رضایت‌هایی که هنگام ثبت‌نام یا خرید اعلام می‌کنید.</li></ul>
<h2>چرا از این اطلاعات استفاده می‌کنیم؟</h2>
<p>برای ورود و بازیابی حساب، تکمیل خرید، اطلاع‌رسانی وضعیت سفارش، جلوگیری از تقلب، پاسخ‌گویی به درخواست‌ها، انجام تعهدات مالی و نگهداری سوابق ضروری فروشگاه.</p>
<h2>اشتراک‌گذاری محدود اطلاعات</h2>
<p>اطلاعات فقط در حد لازم با ارائه‌دهندگان خدمت مرتبط مانند درگاه پرداخت، شرکت یا سامانه حمل‌ونقل، سرویس پیامک، میزبانی و پشتیبانی فنی به اشتراک گذاشته می‌شود. این خدمات ممکن است سیاست حریم خصوصی مستقل داشته باشند.</p>
<h2>نگهداری و امنیت</h2>
<p>اطلاعات تا زمانی نگهداری می‌شود که برای ارائه خدمت، پشتیبانی، حسابداری، رسیدگی به اختلاف یا تکلیف قانونی لازم باشد. دسترسی مدیریتی محدود می‌شود و از روش‌های متعارف امنیتی، نسخه پشتیبان و ارتباط رمزگذاری‌شده استفاده می‌کنیم؛ با این حال هیچ سامانه اینترنتی مصونیت مطلق ندارد.</p>
<h2>حقوق و انتخاب‌های شما</h2>
<p>می‌توانید اطلاعات حساب خود را ویرایش کنید و برای دریافت نسخه‌ای از داده‌ها، اصلاح یا حذف اطلاعاتی که نگهداری آن الزام قانونی یا قراردادی ندارد، درخواست بفرستید.</p>
<h2>کوکی‌ها و خدمات فنی</h2>
<p>وردپرس و ووکامرس برای نگهداری سبد خرید، نشست ورود، امنیت و تنظیمات ضروری از کوکی استفاده می‌کنند. هر ابزار تحلیلی یا تبلیغاتی که بعداً افزوده شود باید جداگانه در این صفحه اعلام شود.</p>
<h2>تماس درباره حریم خصوصی</h2>
<p>درخواست‌های مرتبط با حریم خصوصی را به %2$s یا راه ارتباطی درج‌شده در صفحه تماس ارسال کنید.</p>
<div class="bsc-legal-admin-note"><strong>یادداشت مدیر فروشگاه:</strong> این متن یک الگوی فنی اولیه است. اطلاعات واقعی شرکت، روش‌های ارسال، ارائه‌دهندگان پیامک/پرداخت، مدت نگهداری و الزامات حقوقی کسب‌وکار خود را بررسی و تکمیل کنید.</div></div>',
		esc_html( $site_name ),
		esc_html( antispambot( $admin_email ) )
	);
	$terms      = sprintf(
		'<div class="bsc-legal-page"><p class="bsc-legal-lead">استفاده از %1$s و ثبت سفارش به معنی مطالعه و پذیرش شرایط زیر است. اطلاعات محصول، قیمت، هزینه ارسال و وضعیت موجودی که پیش از پرداخت نمایش داده می‌شود، مبنای سفارش خواهد بود.</p>
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
<div class="bsc-legal-admin-note"><strong>یادداشت مدیر فروشگاه:</strong> نام حقوقی فروشنده، نشانی، شناسه‌های لازم، قواعد دقیق ارسال و مرجوعی، ضمانت کالا و مرجع رسیدگی مرتبط با فعالیت خود را پیش از انتشار عمومی بازبینی کنید.</div></div>',
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
