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

function bsc_registration_identity_fields() {
	$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	?>
	<div class="bsc-register-intro"><strong>ساخت حساب در کمتر از یک دقیقه</strong><span>فقط اطلاعات لازم برای ورود و پیگیری سفارش دریافت می‌شود.</span></div>
	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="reg_first_name">نام&nbsp;<span class="required" aria-hidden="true">*</span></label>
		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="first_name" id="reg_first_name" autocomplete="given-name" value="<?php echo esc_attr( $first_name ); ?>" required aria-required="true" maxlength="60">
	</p>
	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="reg_last_name">نام خانوادگی&nbsp;<span class="required" aria-hidden="true">*</span></label>
		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="last_name" id="reg_last_name" autocomplete="family-name" value="<?php echo esc_attr( $last_name ); ?>" required aria-required="true" maxlength="80">
	</p>
	<p class="bsc-registration-trap" aria-hidden="true">
		<label for="reg_website">وب‌سایت</label>
		<input type="text" name="website" id="reg_website" value="" tabindex="-1" autocomplete="off">
	</p>
	<?php
}
add_action( 'woocommerce_register_form_start', 'bsc_registration_identity_fields' );

function bsc_registration_phone_field() {
	$phone = isset( $_POST['billing_phone'] ) ? bsc_normalize_phone( sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) ) : '';
	?>
	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="reg_billing_phone">شماره تماس <span class="optional">(اختیاری)</span></label>
		<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_phone" id="reg_billing_phone" autocomplete="tel" inputmode="tel" value="<?php echo esc_attr( $phone ); ?>" maxlength="24" placeholder="مثلاً ۰۹۱۲۱۲۳۴۵۶۷">
	</p>
	<p class="bsc-register-note">ایمیل فقط برای ورود، بازیابی حساب، رسید و اطلاع‌رسانی سفارش استفاده می‌شود. نشانی هنگام ثبت‌نام درخواست نمی‌شود.</p>
	<?php
}
add_action( 'woocommerce_register_form', 'bsc_registration_phone_field', 20 );

function bsc_validate_registration( $errors, $username, $email ) {
	unset( $username, $email );
	$first_name = isset( $_POST['first_name'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) ) : '';
	$last_name  = isset( $_POST['last_name'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) ) : '';
	$phone      = isset( $_POST['billing_phone'] ) ? bsc_normalize_phone( sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) ) : '';
	$website    = isset( $_POST['website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['website'] ) ) ) : '';
	if ( '' !== $website ) {
		$errors->add( 'registration_failed', 'ثبت‌نام انجام نشد. دوباره تلاش کنید.' );
	}
	if ( '' === $first_name ) {
		$errors->add( 'first_name_required', 'لطفاً نام را وارد کنید.' );
	}
	if ( '' === $last_name ) {
		$errors->add( 'last_name_required', 'لطفاً نام خانوادگی را وارد کنید.' );
	}
	if ( $phone && ! preg_match( '/^\+?[0-9()\-\s]{7,24}$/', $phone ) ) {
		$errors->add( 'phone_invalid', 'شماره تماس واردشده معتبر نیست.' );
	}
	return $errors;
}
add_filter( 'woocommerce_registration_errors', 'bsc_validate_registration', 10, 3 );

function bsc_save_registration_fields( $customer_id ) {
	$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	$phone      = isset( $_POST['billing_phone'] ) ? bsc_normalize_phone( sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) ) : '';
	wp_update_user( array( 'ID' => $customer_id, 'first_name' => $first_name, 'last_name' => $last_name, 'display_name' => trim( $first_name . ' ' . $last_name ) ) );
	update_user_meta( $customer_id, 'billing_first_name', $first_name );
	update_user_meta( $customer_id, 'billing_last_name', $last_name );
	if ( $phone ) {
		update_user_meta( $customer_id, 'billing_phone', $phone );
	}
}
add_action( 'woocommerce_created_customer', 'bsc_save_registration_fields' );

function bsc_account_menu_items( $items ) {
	unset( $items['edit-address'] );
	if ( isset( $items['downloads'] ) ) {
		unset( $items['downloads'] );
	}
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'bsc_account_menu_items', 20 );

function bsc_account_query_vars( $vars ) {
	unset( $vars['edit-address'] );
	return $vars;
}
add_filter( 'woocommerce_get_query_vars', 'bsc_account_query_vars', 20 );

function bsc_account_phone_field() {
	$user_id = get_current_user_id();
	?>
	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="account_billing_phone">شماره تماس <span class="optional">(اختیاری)</span></label>
		<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="account_billing_phone" id="account_billing_phone" autocomplete="tel" inputmode="tel" maxlength="24" value="<?php echo esc_attr( get_user_meta( $user_id, 'billing_phone', true ) ); ?>">
	</p>
	<?php
}
add_action( 'woocommerce_edit_account_form', 'bsc_account_phone_field' );

function bsc_validate_account_phone( $errors ) {
	if ( ! isset( $_POST['account_billing_phone'] ) ) {
		return;
	}
	$phone = bsc_normalize_phone( sanitize_text_field( wp_unslash( $_POST['account_billing_phone'] ) ) );
	if ( $phone && ! preg_match( '/^\+?[0-9()\-\s]{7,24}$/', $phone ) ) {
		$errors->add( 'phone_invalid', 'شماره تماس واردشده معتبر نیست.' );
	}
}
add_action( 'woocommerce_save_account_details_errors', 'bsc_validate_account_phone' );

function bsc_save_account_phone( $user_id ) {
	if ( isset( $_POST['account_billing_phone'] ) ) {
		update_user_meta( $user_id, 'billing_phone', bsc_normalize_phone( sanitize_text_field( wp_unslash( $_POST['account_billing_phone'] ) ) ) );
	}
}
add_action( 'woocommerce_save_account_details', 'bsc_save_account_phone' );
