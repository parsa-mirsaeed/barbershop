<?php
/** Shared-hosting compatibility audit and safety helpers. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Convert php.ini size strings to bytes. */
function bsc_hosting_ini_bytes( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value || '-1' === $value ) {
		return -1;
	}
	$unit   = strtolower( substr( $value, -1 ) );
	$number = (float) $value;
	if ( 'g' === $unit ) {
		$number *= 1024;
		$unit = 'm';
	}
	if ( 'm' === $unit ) {
		$number *= 1024;
		$unit = 'k';
	}
	if ( 'k' === $unit ) {
		$number *= 1024;
	}
	return (int) $number;
}

/** Requirements that are either mandatory or strongly recommended on shared hosting. */
function bsc_hosting_extension_requirements() {
	return array(
		'pdo_mysql' => array( 'label' => 'PDO MySQL', 'required' => true, 'note' => 'برای Gateland الزامی است.' ),
		'mysqli'    => array( 'label' => 'MySQLi', 'required' => true, 'note' => 'اتصال اصلی وردپرس به MySQL/MariaDB.' ),
		'curl'      => array( 'label' => 'cURL', 'required' => true, 'note' => 'درگاه پرداخت، پیامک و به‌روزرسانی‌ها به HTTPS خروجی نیاز دارند.' ),
		'mbstring'  => array( 'label' => 'mbstring', 'required' => true, 'note' => 'برای متن فارسی و افزونه‌ها.' ),
		'openssl'   => array( 'label' => 'OpenSSL', 'required' => true, 'note' => 'ارتباط HTTPS و امضای امن.' ),
		'fileinfo'  => array( 'label' => 'Fileinfo', 'required' => true, 'note' => 'اعتبارسنجی فایل‌های آپلودی.' ),
		'intl'      => array( 'label' => 'Intl', 'required' => false, 'note' => 'برای قالب‌بندی و سازگاری بهتر توصیه می‌شود.' ),
		'sodium'    => array( 'label' => 'Sodium', 'required' => false, 'note' => 'برای رمزنگاری مدرن توصیه می‌شود.' ),
		'zip'       => array( 'label' => 'ZIP', 'required' => false, 'note' => 'برای نصب و به‌روزرسانی بسته‌ها توصیه می‌شود.' ),
	);
}

function bsc_hosting_add_check( &$checks, $status, $label, $details, $remediation = '' ) {
	$checks[] = array(
		'status'      => $status,
		'label'       => $label,
		'details'     => $details,
		'remediation' => $remediation,
	);
}

/** Collect local checks without printing secrets, paths, or database credentials. */
function bsc_hosting_collect_checks() {
	$checks = array();

	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		bsc_hosting_add_check( $checks, 'fail', 'نسخه PHP', 'PHP ' . PHP_VERSION . ' پشتیبانی نمی‌شود.', 'حداقل PHP 7.4 و برای تولید PHP 8.2 یا جدیدتر استفاده کنید.' );
	} elseif ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
		bsc_hosting_add_check( $checks, 'warning', 'نسخه PHP', 'PHP ' . PHP_VERSION . ' اجرا می‌شود اما برای تولید قدیمی است.', 'در پنل هاست PHP 8.2 یا جدیدتر را انتخاب کنید.' );
	} else {
		bsc_hosting_add_check( $checks, 'pass', 'نسخه PHP', 'PHP ' . PHP_VERSION . ' مناسب است.' );
	}

	$memory = bsc_hosting_ini_bytes( ini_get( 'memory_limit' ) );
	if ( -1 !== $memory && $memory < 256 * 1024 * 1024 ) {
		bsc_hosting_add_check( $checks, 'fail', 'PHP memory_limit', ini_get( 'memory_limit' ) . ' برای WooCommerce/Wordfence کم است.', 'حداقل 256M و ترجیحاً 512M تنظیم کنید.' );
	} elseif ( -1 !== $memory && $memory < 512 * 1024 * 1024 ) {
		bsc_hosting_add_check( $checks, 'warning', 'PHP memory_limit', ini_get( 'memory_limit' ) . ' قابل استفاده است.', 'برای اسکن Wordfence، تصویر و به‌روزرسانی‌ها 512M بهتر است.' );
	} else {
		bsc_hosting_add_check( $checks, 'pass', 'PHP memory_limit', '-1' === trim( (string) ini_get( 'memory_limit' ) ) ? 'نامحدود توسط PHP.' : ini_get( 'memory_limit' ) );
	}

	foreach ( bsc_hosting_extension_requirements() as $extension => $meta ) {
		if ( extension_loaded( $extension ) ) {
			bsc_hosting_add_check( $checks, 'pass', $meta['label'], 'فعال است.' );
		} else {
			bsc_hosting_add_check( $checks, $meta['required'] ? 'fail' : 'warning', $meta['label'], 'فعال نیست. ' . $meta['note'], 'از پشتیبانی هاست بخواهید افزونه PHP را فعال کند.' );
		}
	}

	if ( extension_loaded( 'gd' ) || extension_loaded( 'imagick' ) ) {
		bsc_hosting_add_check( $checks, 'pass', 'پردازش تصویر', extension_loaded( 'imagick' ) ? 'Imagick فعال است.' : 'GD فعال است.' );
	} else {
		bsc_hosting_add_check( $checks, 'warning', 'پردازش تصویر', 'GD و Imagick فعال نیستند.', 'برای ساخت thumbnail و بهینه‌سازی تصاویر یکی از آن‌ها را فعال کنید.' );
	}

	$home_scheme = wp_parse_url( home_url( '/' ), PHP_URL_SCHEME );
	bsc_hosting_add_check(
		$checks,
		'https' === $home_scheme ? 'pass' : 'fail',
		'HTTPS سایت',
		'https' === $home_scheme ? 'آدرس اصلی سایت HTTPS است.' : 'آدرس اصلی سایت HTTPS نیست.',
		'https' === $home_scheme ? '' : 'SSL رایگان هاست را فعال و WordPress Address/Site Address را به https تغییر دهید.'
	);

	bsc_hosting_add_check(
		$checks,
		defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'pass' : 'warning',
		'FORCE_SSL_ADMIN',
		defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'مدیریت فقط روی HTTPS اجرا می‌شود.' : 'در wp-config.php فعال نشده است.',
		defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? '' : "define( 'FORCE_SSL_ADMIN', true );"
	);

	bsc_hosting_add_check(
		$checks,
		defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ? 'pass' : 'warning',
		'ویرایش فایل از داشبورد',
		defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ? 'ویرایشگر فایل قالب/افزونه غیرفعال است.' : 'ویرایشگر فایل وردپرس هنوز فعال است.',
		defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ? '' : "define( 'DISALLOW_FILE_EDIT', true );"
	);

	$debug_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;
	$debug_display = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY;
	bsc_hosting_add_check(
		$checks,
		( ! $debug_enabled && ! $debug_display ) ? 'pass' : 'fail',
		'حالت Debug',
		( ! $debug_enabled && ! $debug_display ) ? 'خروجی خطا برای مشتری غیرفعال است.' : 'WP_DEBUG یا WP_DEBUG_DISPLAY در تولید فعال است.',
		( ! $debug_enabled && ! $debug_display ) ? '' : 'در تولید WP_DEBUG و WP_DEBUG_DISPLAY را false کنید و خطاها را فقط در لاگ خصوصی ثبت کنید.'
	);

	$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : '';
	bsc_hosting_add_check(
		$checks,
		'production' === $environment ? 'pass' : 'warning',
		'محیط وردپرس',
		$environment ? 'WP_ENVIRONMENT_TYPE=' . $environment : 'نوع محیط مشخص نیست.',
		'production' === $environment ? '' : "define( 'WP_ENVIRONMENT_TYPE', 'production' );"
	);

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) || ! is_writable( $uploads['basedir'] ) ) {
		bsc_hosting_add_check( $checks, 'fail', 'پوشه uploads', 'وردپرس امکان نوشتن امن در uploads را ندارد.', 'مالکیت/مجوز پوشه را در cPanel/DirectAdmin اصلاح کنید؛ از 777 استفاده نکنید.' );
	} else {
		bsc_hosting_add_check( $checks, 'pass', 'پوشه uploads', 'قابل نوشتن است.' );
		$free = @disk_free_space( $uploads['basedir'] );
		if ( false !== $free ) {
			$gb = $free / 1073741824;
			if ( $gb < 2 ) {
				bsc_hosting_add_check( $checks, 'fail', 'فضای آزاد فایل‌سیستم', number_format_i18n( $gb, 1 ) . ' GB گزارش شده است.', 'فضا را فوراً افزایش دهید یا فایل/بکاپ محلی را پاک‌سازی کنید.' );
			} elseif ( $gb < 5 ) {
				bsc_hosting_add_check( $checks, 'warning', 'فضای آزاد فایل‌سیستم', number_format_i18n( $gb, 1 ) . ' GB گزارش شده است.', 'برای فروشگاه حداقل چند گیگابایت فضای آزاد نگه دارید.' );
			} else {
				bsc_hosting_add_check( $checks, 'pass', 'فضای آزاد فایل‌سیستم', number_format_i18n( $gb, 1 ) . ' GB گزارش شده است.' );
			}
		}
	}

	if ( defined( 'AUTH_KEY' ) && strlen( (string) AUTH_KEY ) >= 40 && false === strpos( (string) AUTH_KEY, 'put your unique phrase here' ) ) {
		bsc_hosting_add_check( $checks, 'pass', 'کلیدهای امنیتی وردپرس', 'کلیدهای تصادفی تنظیم شده‌اند.' );
	} else {
		bsc_hosting_add_check( $checks, 'fail', 'کلیدهای امنیتی وردپرس', 'کلیدهای امنیتی قابل تأیید نیستند.', 'کلیدهای SALT استاندارد وردپرس را در wp-config.php قرار دهید.' );
	}

	if ( function_exists( 'wp_next_scheduled' ) && wp_next_scheduled( 'wp_version_check' ) ) {
		bsc_hosting_add_check( $checks, 'pass', 'WP-Cron', 'رویداد زمان‌بندی‌شده وجود دارد.' );
	} else {
		bsc_hosting_add_check( $checks, 'warning', 'WP-Cron', 'رویداد استاندارد بعدی قابل مشاهده نیست.', 'برای WooCommerce یک Cron واقعی هر 5 دقیقه تنظیم و سپس فقط در صورت فعال بودن آن DISABLE_WP_CRON را true کنید.' );
	}

	if ( ! extension_loaded( 'pdo_mysql' ) && function_exists( 'is_plugin_active' ) && is_plugin_active( 'gateland/gateland.php' ) ) {
		bsc_hosting_add_check( $checks, 'fail', 'Gateland', 'Gateland فعال است ولی pdo_mysql وجود ندارد؛ این حالت می‌تواند Fatal Error ایجاد کند.', 'Gateland را غیرفعال کنید تا pdo_mysql توسط هاست فعال شود.' );
	}

	if ( defined( 'WP_CONTENT_DIR' ) ) {
		$debug_log = trailingslashit( WP_CONTENT_DIR ) . 'debug.log';
		if ( is_file( $debug_log ) && filesize( $debug_log ) > 0 && ! $debug_enabled ) {
			bsc_hosting_add_check( $checks, 'warning', 'debug.log', 'فایل debug.log باقی مانده است.', 'پس از بررسی خطاها آن را خارج از web root منتقل یا حذف کنید.' );
		}
	}

	return $checks;
}

function bsc_hosting_summary( $checks ) {
	$summary = array( 'pass' => 0, 'warning' => 0, 'fail' => 0 );
	foreach ( $checks as $check ) {
		if ( isset( $summary[ $check['status'] ] ) ) {
			$summary[ $check['status'] ]++;
		}
	}
	return $summary;
}

/** Admin-only shared-hosting health page. */
function bsc_hosting_admin_menu() {
	add_management_page( 'سلامت میزبانی فروشگاه', 'سلامت میزبانی', 'manage_options', 'bsc-hosting-health', 'bsc_render_hosting_health_page' );
}
add_action( 'admin_menu', 'bsc_hosting_admin_menu' );

function bsc_render_hosting_health_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'دسترسی کافی ندارید.' );
	}
	$checks  = bsc_hosting_collect_checks();
	$summary = bsc_hosting_summary( $checks );
	$status_labels = array( 'pass' => 'مناسب', 'warning' => 'نیاز به بررسی', 'fail' => 'بحرانی' );
	?>
	<div class="wrap" dir="rtl" style="max-width:1100px">
		<h1>سلامت میزبانی فروشگاه</h1>
		<p>این صفحه هیچ رمز دیتابیس، کلید پرداخت، مسیر حساس یا اطلاعات مشتری را نمایش نمی‌دهد. موارد قرمز باید پیش از پذیرش سفارش واقعی برطرف شوند.</p>
		<div style="display:flex;gap:12px;flex-wrap:wrap;margin:18px 0">
			<strong style="padding:10px 14px;background:#e7f7ed;border-radius:8px">مناسب: <?php echo esc_html( $summary['pass'] ); ?></strong>
			<strong style="padding:10px 14px;background:#fff4d8;border-radius:8px">بررسی: <?php echo esc_html( $summary['warning'] ); ?></strong>
			<strong style="padding:10px 14px;background:#fde8e8;border-radius:8px">بحرانی: <?php echo esc_html( $summary['fail'] ); ?></strong>
		</div>
		<table class="widefat striped" style="table-layout:fixed">
			<thead><tr><th style="width:110px">وضعیت</th><th style="width:220px">بررسی</th><th>نتیجه و اقدام</th></tr></thead>
			<tbody>
			<?php foreach ( $checks as $check ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $status_labels[ $check['status'] ] ); ?></strong></td>
					<td><?php echo esc_html( $check['label'] ); ?></td>
					<td><?php echo esc_html( $check['details'] ); ?><?php if ( $check['remediation'] ) : ?><br><small><?php echo esc_html( $check['remediation'] ); ?></small><?php endif; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<h2 style="margin-top:28px">چک‌لیست دستی قبل از فروش واقعی</h2>
		<ul style="list-style:disc;padding-right:24px;line-height:2">
			<li>Wordfence Firewall را Optimize کنید و 2FA را برای Administrator و Barber فعال کنید.</li>
			<li>بکاپ روزانه دیتابیس + بکاپ فایل‌ها را خارج از همین هاست نگهداری و Restore واقعی را آزمایش کنید.</li>
			<li>درگاه را در حالت تست بررسی کنید: موفق، ناموفق، لغو، callback تکراری و مبلغ اشتباه.</li>
			<li>کش را برای cart، checkout، my-account، wc-ajax، wc-api و callbackهای پرداخت غیرفعال کنید.</li>
			<li>Cron واقعی هر 5 دقیقه برای wp-cron.php/Action Scheduler تنظیم کنید.</li>
			<li>اعلان کمبود فضا، خطاهای PHP و سفارش‌های ناموفق را در پنل هاست/WordPress فعال کنید.</li>
		</ul>
	</div>
	<?php
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	function bsc_cli_hosting_audit( $args, $assoc_args ) {
		unset( $args );
		$checks  = bsc_hosting_collect_checks();
		$summary = bsc_hosting_summary( $checks );
		if ( isset( $assoc_args['format'] ) && 'json' === $assoc_args['format'] ) {
			WP_CLI::line( wp_json_encode( array( 'summary' => $summary, 'checks' => $checks ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		} else {
			foreach ( $checks as $check ) {
				WP_CLI::line( strtoupper( $check['status'] ) . "\t" . $check['label'] . "\t" . $check['details'] );
			}
			WP_CLI::line( sprintf( 'pass=%d warning=%d fail=%d', $summary['pass'], $summary['warning'], $summary['fail'] ) );
		}
		if ( $summary['fail'] > 0 ) {
			WP_CLI::halt( 1 );
		}
	}
	WP_CLI::add_command( 'bsc hosting audit', 'bsc_cli_hosting_audit' );
}
