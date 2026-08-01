<?php
/** Minimal, privacy-conscious WooCommerce customer profile. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert Persian and Arabic-Indic digits to ASCII and normalize spacing.
 *
 * @param string $phone Raw phone value.
 * @return string
 */
function bsc_normalize_phone( $phone ) {
	$phone = strtr(
		(string) $phone,
		array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
			'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
			'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		)
	);
	return trim( preg_replace( '/\s+/u', ' ', $phone ) );
}

/**
 * Normalize common Iranian mobile formats to 09xxxxxxxxx.
 *
 * @param string $phone Raw phone value.
 * @return string
 */
function bsc_normalize_iran_mobile( $phone ) {
	$digits = preg_replace( '/\D+/', '', bsc_normalize_phone( $phone ) );
	if ( 0 === strpos( $digits, '0098' ) ) {
		$digits = '0' . substr( $digits, 4 );
	} elseif ( 0 === strpos( $digits, '98' ) && 12 === strlen( $digits ) ) {
		$digits = '0' . substr( $digits, 2 );
	} elseif ( 10 === strlen( $digits ) && 0 === strpos( $digits, '9' ) ) {
		$digits = '0' . $digits;
	}
	return $digits;
}

/**
 * Split the single public name field into WooCommerce's internal name fields.
 *
 * @param string $full_name Full customer name.
 * @return array{0:string,1:string}
 */
function bsc_split_customer_name( $full_name ) {
	$full_name = trim( preg_replace( '/\s+/u', ' ', sanitize_text_field( $full_name ) ) );
	$parts     = preg_split( '/\s+/u', $full_name, -1, PREG_SPLIT_NO_EMPTY );
	if ( ! $parts ) {
		return array( '', '' );
	}
	if ( 1 === count( $parts ) ) {
		return array( $parts[0], '' );
	}
	$last_name  = array_pop( $parts );
	$first_name = implode( ' ', $parts );
	return array( $first_name, $last_name );
}

/**
 * Render a single, clear name field before WooCommerce's email/password fields.
 *
 * @return void
 */
function bsc_registration_identity_fields() {
	$full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	?>
	<div class="bsc-register-intro">
		<strong>ساخت حساب در کمتر از یک دقیقه</strong>
		<span>با نام، شماره موبایل، ایمیل و رمز عبور حساب خود را بسازید.</span>
	</div>
	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="reg_full_name">نام و نام خانوادگی&nbsp;<span class="required" aria-hidden="true">*</span></label>
		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="full_name" id="reg_full_name" autocomplete="name" value="<?php echo esc_attr( $full_name ); ?>" required aria-required="true" maxlength="120" placeholder="مثلاً پارسا میرسعید">
	</p>
	<?php
}
add_action( 'woocommerce_register_form_start', 'bsc_registration_identity_fields' );

/**
 * Render the required Iranian mobile field and explicit legal consent.
 *
 * @return void
 */
function bsc_registration_phone_field() {
	$phone       = isset( $_POST['billing_phone'] ) ? bsc_normalize_iran_mobile( sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) ) : '';
	$privacy_url = get_privacy_policy_url();
	$terms_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'terms' ) : '';
	?>
	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="reg_billing_phone">شماره موبایل&nbsp;<span class="required" aria-hidden="true">*</span></label>
		<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_phone" id="reg_billing_phone" autocomplete="tel" inputmode="numeric" value="<?php echo esc_attr( $phone ); ?>" maxlength="11" placeholder="۰۹۱۲۱۲۳۴۵۶۷" required aria-required="true">
	</p>
	<p class="bsc-register-note">پس از ساخت حساب، برای ورود از <strong>شماره موبایل یا ایمیل</strong> همراه با رمز عبور استفاده کنید؛ نام شما شناسه ورود نیست.</p>
	<p class="bsc-registration-consent">
		<input type="checkbox" name="bsc_legal_consent" id="bsc_legal_consent" value="yes" required aria-required="true" <?php checked( isset( $_POST['bsc_legal_consent'] ) ); ?>>
		<label for="bsc_legal_consent">
			با ساخت حساب،
			<?php if ( $terms_url ) : ?><a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener">شرایط استفاده و خرید</a><?php else : ?>شرایط استفاده و خرید<?php endif; ?>
			و
			<?php if ( $privacy_url ) : ?><a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener">سیاست حریم خصوصی</a><?php else : ?>سیاست حریم خصوصی<?php endif; ?>
			را می‌پذیرم.
		</label>
	</p>
	<?php
}
add_action( 'woocommerce_register_form', 'bsc_registration_phone_field', 20 );

/**
 * Remove WooCommerce's generic English privacy paragraph; the required Persian
 * consent block above provides clearer links and an explicit acceptance action.
 *
 * @param string $text Existing privacy text.
 * @return string
 */
function bsc_registration_privacy_text( $text ) {
	unset( $text );
	return '';
}
add_filter( 'woocommerce_registration_privacy_policy_text', 'bsc_registration_privacy_text', 100 );

/**
 * Validate the simplified registration form.
 *
 * @param WP_Error $errors Validation errors.
 * @param string   $username Generated username.
 * @param string   $email Customer email.
 * @return WP_Error
 */
function bsc_validate_registration( $errors, $username, $email ) {
	unset( $username, $email );
	$full_name = isset( $_POST['full_name'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) ) : '';
	$phone     = isset( $_POST['billing_phone'] ) ? bsc_normalize_iran_mobile( sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) ) : '';

	if ( '' === $full_name ) {
		$errors->add( 'full_name_required', 'لطفاً نام و نام خانوادگی را وارد کنید.' );
	}
	if ( '' === $phone ) {
		$errors->add( 'phone_required', 'لطفاً شماره موبایل را وارد کنید.' );
	} elseif ( ! preg_match( '/^09[0-9]{9}$/', $phone ) ) {
		$errors->add( 'phone_invalid', 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم داشته باشد.' );
	} else {
		$existing = get_users(
			array(
				'fields'     => 'ids',
				'meta_key'   => 'billing_phone',
				'meta_value' => $phone,
				'number'     => 1,
			)
		);
		if ( $existing ) {
			$errors->add( 'phone_exists', 'این شماره موبایل قبلاً ثبت شده است. از بخش ورود استفاده کنید.' );
		}
	}
	if ( empty( $_POST['bsc_legal_consent'] ) ) {
		$errors->add( 'legal_consent_required', 'برای ساخت حساب، پذیرش شرایط استفاده و سیاست حریم خصوصی لازم است.' );
	}
	return $errors;
}
add_filter( 'woocommerce_registration_errors', 'bsc_validate_registration', 10, 3 );

/**
 * Save the single visible name field into WordPress/WooCommerce profile fields.
 *
 * @param int $customer_id Customer user ID.
 * @return void
 */
function bsc_save_registration_fields( $customer_id ) {
	$full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	$phone     = isset( $_POST['billing_phone'] ) ? bsc_normalize_iran_mobile( sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) ) : '';
	list( $first_name, $last_name ) = bsc_split_customer_name( $full_name );

	wp_update_user(
		array(
			'ID'           => $customer_id,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'display_name' => $full_name,
		)
	);
	update_user_meta( $customer_id, 'billing_first_name', $first_name );
	update_user_meta( $customer_id, 'billing_last_name', $last_name );
	update_user_meta( $customer_id, 'billing_phone', $phone );
	update_user_meta( $customer_id, '_bsc_legal_consent_at', gmdate( 'c' ) );
}
add_action( 'woocommerce_created_customer', 'bsc_save_registration_fields' );

/**
 * Permit password login with a unique billing mobile number as well as email.
 *
 * @param WP_User|WP_Error|null $user Current authentication result.
 * @param string                $username Submitted identity.
 * @param string                $password Submitted password.
 * @return WP_User|WP_Error|null
 */
function bsc_authenticate_with_mobile( $user, $username, $password ) {
	if ( $user instanceof WP_User || '' === trim( (string) $username ) || '' === (string) $password ) {
		return $user;
	}
	$phone = bsc_normalize_iran_mobile( $username );
	if ( ! preg_match( '/^09[0-9]{9}$/', $phone ) ) {
		return $user;
	}
	$matches = get_users(
		array(
			'meta_key'   => 'billing_phone',
			'meta_value' => $phone,
			'number'     => 2,
		)
	);
	if ( 1 !== count( $matches ) ) {
		return $user;
	}
	return wp_authenticate_username_password( null, $matches[0]->user_login, $password );
}
add_filter( 'authenticate', 'bsc_authenticate_with_mobile', 35, 3 );

/**
 * Translate the login identity label and a few registration fallbacks.
 *
 * @param string $translated Existing translation.
 * @param string $text Source string.
 * @param string $domain Text domain.
 * @return string
 */
function bsc_translate_account_identity_text( $translated, $text, $domain ) {
	if ( 'woocommerce' !== $domain ) {
		return $translated;
	}
	$map = array(
		'Username or email address' => 'شماره موبایل یا ایمیل',
		'Username or email'         => 'شماره موبایل یا ایمیل',
		'Email address'             => 'نشانی ایمیل',
		'Password'                  => 'رمز عبور',
	);
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}
add_filter( 'gettext', 'bsc_translate_account_identity_text', 120, 3 );

/**
 * Keep the account navigation short and task-focused.
 *
 * @param array $items WooCommerce menu items.
 * @return array
 */
function bsc_account_menu_items( $items ) {
	unset( $items['edit-address'], $items['downloads'] );
	$order = array( 'dashboard', 'orders', 'edit-account', 'customer-logout' );
	$short = array();
	foreach ( $order as $key ) {
		if ( isset( $items[ $key ] ) ) {
			$short[ $key ] = $items[ $key ];
		}
	}
	return $short;
}
add_filter( 'woocommerce_account_menu_items', 'bsc_account_menu_items', 20 );

function bsc_account_query_vars( $vars ) {
	unset( $vars['edit-address'] );
	return $vars;
}
add_filter( 'woocommerce_get_query_vars', 'bsc_account_query_vars', 20 );

/**
 * Add a mobile field to the account details screen.
 *
 * @return void
 */
function bsc_account_phone_field() {
	$user_id = get_current_user_id();
	?>
	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="account_billing_phone">شماره موبایل&nbsp;<span class="required" aria-hidden="true">*</span></label>
		<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="account_billing_phone" id="account_billing_phone" autocomplete="tel" inputmode="numeric" maxlength="11" required aria-required="true" value="<?php echo esc_attr( get_user_meta( $user_id, 'billing_phone', true ) ); ?>">
	</p>
	<?php
}
add_action( 'woocommerce_edit_account_form', 'bsc_account_phone_field' );

/**
 * Validate mobile changes and prevent one number being assigned to two users.
 *
 * @param WP_Error $errors Validation errors.
 * @return void
 */
function bsc_validate_account_phone( $errors ) {
	if ( ! isset( $_POST['account_billing_phone'] ) ) {
		return;
	}
	$phone = bsc_normalize_iran_mobile( sanitize_text_field( wp_unslash( $_POST['account_billing_phone'] ) ) );
	if ( ! preg_match( '/^09[0-9]{9}$/', $phone ) ) {
		$errors->add( 'phone_invalid', 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم داشته باشد.' );
		return;
	}
	$matches = get_users(
		array(
			'fields'     => 'ids',
			'meta_key'   => 'billing_phone',
			'meta_value' => $phone,
			'number'     => 2,
		)
	);
	foreach ( $matches as $user_id ) {
		if ( get_current_user_id() !== (int) $user_id ) {
			$errors->add( 'phone_exists', 'این شماره موبایل به حساب دیگری متصل است.' );
			break;
		}
	}
}
add_action( 'woocommerce_save_account_details_errors', 'bsc_validate_account_phone' );

function bsc_save_account_phone( $user_id ) {
	if ( isset( $_POST['account_billing_phone'] ) ) {
		update_user_meta( $user_id, 'billing_phone', bsc_normalize_iran_mobile( sanitize_text_field( wp_unslash( $_POST['account_billing_phone'] ) ) ) );
	}
}
add_action( 'woocommerce_save_account_details', 'bsc_save_account_phone' );

/**
 * Mark the account home endpoint so the generic WooCommerce paragraphs can be
 * replaced visually by the concise overview below.
 *
 * @param array $classes Body classes.
 * @return array
 */
function bsc_account_body_classes( $classes ) {
	if ( function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in() && function_exists( 'is_wc_endpoint_url' ) && ! is_wc_endpoint_url() ) {
		$classes[] = 'bsc-account-dashboard';
	}
	return $classes;
}
add_filter( 'body_class', 'bsc_account_body_classes' );

/**
 * Render a compact, modern account overview without adding new workflows.
 *
 * @return void
 */
function bsc_render_account_overview() {
	$user = wp_get_current_user();
	if ( ! $user || ! $user->exists() ) {
		return;
	}
	$name        = $user->display_name ? $user->display_name : $user->user_email;
	$phone       = get_user_meta( $user->ID, 'billing_phone', true );
	$order_count = function_exists( 'wc_get_customer_order_count' ) ? wc_get_customer_order_count( $user->ID ) : 0;
	$initial     = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1, 'UTF-8' ) : substr( $name, 0, 1 );
	?>
	<section class="bsc-account-overview" aria-label="خلاصه حساب کاربری">
		<div class="bsc-account-welcome">
			<span class="bsc-account-avatar" aria-hidden="true"><?php echo esc_html( $initial ); ?></span>
			<div>
				<h2><?php echo esc_html( sprintf( 'سلام %s', $name ) ); ?></h2>
				<p>سفارش‌ها، اطلاعات تماس و رمز عبور خود را سریع و امن مدیریت کنید.</p>
			</div>
			<div class="bsc-account-identifiers">
				<span class="bsc-account-chip"><?php echo esc_html( $user->user_email ); ?></span>
				<?php if ( $phone ) : ?><span class="bsc-account-chip"><?php echo esc_html( $phone ); ?></span><?php endif; ?>
			</div>
		</div>
		<div class="bsc-account-metrics">
			<div class="bsc-account-metric"><strong><?php echo esc_html( number_format_i18n( $order_count ) ); ?></strong><span>سفارش ثبت‌شده</span></div>
			<div class="bsc-account-metric"><strong>۲</strong><span>روش ورود: موبایل یا ایمیل</span></div>
		</div>
		<nav class="bsc-account-actions" aria-label="دسترسی سریع حساب">
			<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">مشاهده سفارش‌ها</a>
			<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>">ویرایش اطلاعات</a>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">رفتن به فروشگاه</a>
		</nav>
	</section>
	<?php
}
add_action( 'woocommerce_account_dashboard', 'bsc_render_account_overview', 5 );
