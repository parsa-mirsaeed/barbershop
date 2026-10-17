<?php
/** Montix 3.5 — FarazSMS owns all guest login, registration, OTP and notifications. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** A stock/unpatched vendor plugin must never own the Montix public login page. */
function bsc_faraz_ready() {
    return is_ssl() && defined( 'FARAZSMS_MONTIX_SECURITY_PATCH' )
        && FARAZSMS_MONTIX_SECURITY_PATCH === '2026-10-17.2'
        // Only the reviewed bundled login code may handle OTP, not a standalone unpatched login plugin.
        && defined( 'FARAZSMS_LOGIN_BUNDLED' ) && FARAZSMS_LOGIN_BUNDLED
        && defined( 'FARAZSMS_LOGIN_FRONTEND_ENABLED' ) && FARAZSMS_LOGIN_FRONTEND_ENABLED
        && function_exists( 'farazsms_montix_verified_customer_by_mobile' )
        && class_exists( '\FarazSMS\OTP' )
        && class_exists( '\FarazSMS\Send_SMS' );
}

// The legacy Woo registration/auth hooks must not compete with FarazSMS.
remove_action( 'woocommerce_register_form_start', 'bsc_registration_identity_fields' );
remove_action( 'woocommerce_register_form', 'bsc_registration_phone_field', 20 );
remove_filter( 'woocommerce_registration_errors', 'bsc_validate_registration', 10 );
remove_action( 'woocommerce_created_customer', 'bsc_save_registration_fields' );
remove_filter( 'authenticate', 'bsc_authenticate_with_mobile', 35 );
remove_action( 'woocommerce_edit_account_form', 'bsc_account_phone_field' );
remove_action( 'woocommerce_save_account_details_errors', 'bsc_validate_account_phone' );
remove_action( 'woocommerce_save_account_details', 'bsc_save_account_phone' );

// Even a crafted WooCommerce registration POST must not bypass verified SMS signup.
add_filter( 'woocommerce_registration_errors', function( $errors ) {
    $errors->add( 'bsc_faraz_only', 'برای ثبت‌نام، شماره موبایل خود را با پیامک تأیید کنید.' );
    return $errors;
}, 999 );
add_filter( 'option_woocommerce_enable_myaccount_registration', function() { return 'no'; } );
add_filter( 'option_woocommerce_enable_signup_and_login_from_checkout', function() { return 'no'; } );

function bsc_faraz_login_url() {
    return home_url( '/login-register/' );
}

/** Render one CTA rather than Montix's previously duplicated OTP/registration form. */
function bsc_faraz_guest_my_account( $return, $tag ) {
    if ( 'woocommerce_my_account' !== $tag || is_user_logged_in() || is_admin() ) { return $return; }
    if ( ! bsc_faraz_ready() ) {
        return '<p role="alert">ورود پیامکی موقتاً در دسترس نیست. لطفاً با پشتیبانی تماس بگیرید.</p>';
    }
    return '<div class="bsc-faraz-login"><p>برای ورود یا ثبت‌نام با شماره موبایل، از فرم امن ورود استفاده کنید.</p><a class="button" href="' . esc_url( bsc_faraz_login_url() ) . '">ورود و ثبت‌نام پیامکی</a></div>';
}
add_filter( 'pre_do_shortcode_tag', 'bsc_faraz_guest_my_account', 10, 2 );

// Fail closed if an unpatched FarazSMS ZIP is installed. Block its public AJAX handlers.
add_action( 'init', function() {
    if ( bsc_faraz_ready() ) { return; }
    foreach ( array( 'identifier', 'login', 'login_password', 'register', 'forget_mobile_password', 'reset_password', 'forget_code', 'edit_identifier' ) as $part ) {
        remove_all_actions( 'wp_ajax_nopriv_farazsms_' . $part . '_ajax_handler' );
        remove_all_actions( 'wp_ajax_farazsms_' . $part . '_ajax_handler' );
    }
}, 999 );
add_filter( 'pre_do_shortcode_tag', function( $result, $tag ) {
    if ( 'farazsms_login_form' === $tag && ! bsc_faraz_ready() ) {
        return '<p role="alert">فرم ورود تا نصب نسخه ایمن فراز اس‌ام‌اس غیرفعال است.</p>';
    }
    return $result;
}, 5, 2 );

// Last-write protection even when a vendor update filter registers after us.
// Vendor fork also enforces this independently when Barbershop Core is disabled.
function bsc_faraz_auto_update_guard( $update, $item ) {
    return is_object( $item ) && isset( $item->plugin ) && 'faraz-sms/faraz-sms.php' === $item->plugin ? false : $update;
}
add_filter( 'auto_update_plugin', 'bsc_faraz_auto_update_guard', PHP_INT_MAX, 2 );
add_action( 'admin_notices', function() {
    if ( ! current_user_can( 'manage_options' ) || bsc_faraz_ready() ) { return; }
    echo '<div class="notice notice-error"><p>مونتیکس: نسخه امن FarazSMS 3.22.273-montix.2 یا ماژول ورود داخلی آن فعال نیست. ورود پیامکی برای جلوگیری از خطر تصاحب حساب مسدود است.</p></div>';
} );

/** Create the canonical login page once on admin traffic, preserving any existing page. */
add_action( 'admin_init', function() {
    if ( ! current_user_can( 'manage_options' ) || ! bsc_faraz_ready() ) { return; }
    if ( false === get_option( 'farazsms_login_ask_name', false ) ) {
        update_option( 'farazsms_login_ask_name', 'no', false );
    }
    if ( get_page_by_path( 'login-register' ) ) { return; }
    wp_insert_post( array(
        'post_title' => 'ورود و ثبت‌نام', 'post_name' => 'login-register',
        'post_status' => 'publish', 'post_type' => 'page',
        'post_content' => '<!-- wp:shortcode -->[farazsms_login_form]<!-- /wp:shortcode -->',
    ) );
} );

/** WP login session + FarazSMS's SMS sender + FarazSMS's OTP verifier for legacy account enrollment. */
function bsc_faraz_link_key() {
    $session = function_exists( 'wp_get_session_token' ) ? wp_get_session_token() : '';
    return 'bsc_fr_link_' . hash_hmac( 'sha256', get_current_user_id() . '|' . $session, wp_salt( 'auth' ) );
}
function bsc_faraz_legacy_customer() {
    $user = wp_get_current_user();
    return bsc_faraz_ready() && is_ssl() && farazsms_montix_is_customer( $user ) ? $user : null;
}
function bsc_faraz_link_redirect( $status ) {
    $url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
    wp_safe_redirect( add_query_arg( 'bsc_faraz', $status, $url ) );
    exit;
}
function bsc_faraz_link_send() {
    check_admin_referer( 'bsc_faraz_link_send' );
    $user = bsc_faraz_legacy_customer();
    if ( ! $user ) { bsc_faraz_link_redirect( 'unavailable' ); }
    $phone = isset( $_POST['phone'] ) ? farazsms_dedupe_normalize_mobile( sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ) : '';
    if ( ! $phone || ! farazsms_montix_phone_claimable( $phone, $user->ID ) ) {
        bsc_faraz_link_redirect( 'claimed' );
    }
    if ( ! function_exists( 'farazsms_login_otp_guard' ) ) { bsc_faraz_link_redirect( 'unavailable' ); }
    $guard = farazsms_login_otp_guard( $phone, 'forget' );
    if ( empty( $guard['allowed'] ) ) { bsc_faraz_link_redirect( 'rate' ); }
    $sms = new \FarazSMS\Send_SMS();
    $otp = new \FarazSMS\OTP();
    $code = $sms->code();
    $otp->delete_user_verification_data( $phone );
    if ( ! $otp->send_verification_code( $phone, $code ) ) {
        bsc_faraz_link_redirect( 'unavailable' );
    }
    $sent = $sms->send( $phone, $code );
    $result = (string) $sms->last_send_result;
    if ( ! $sent && 0 !== strpos( $result, 'curl_error:' ) ) {
        $otp->delete_user_verification_data( $phone );
        bsc_faraz_link_redirect( 'unavailable' );
    }
    set_transient( bsc_faraz_link_key(), array( 'phone' => $phone, 'id' => $user->ID ), 5 * MINUTE_IN_SECONDS );
    bsc_faraz_link_redirect( 'sent' );
}
add_action( 'admin_post_bsc_faraz_link_send', 'bsc_faraz_link_send' );

function bsc_faraz_link_verify() {
    check_admin_referer( 'bsc_faraz_link_verify' );
    $user = bsc_faraz_legacy_customer();
    if ( ! $user ) { bsc_faraz_link_redirect( 'unavailable' ); }
    $key = bsc_faraz_link_key();
    $challenge = get_transient( $key );
    if ( ! is_array( $challenge ) || (int) $challenge['id'] !== (int) $user->ID ) {
        bsc_faraz_link_redirect( 'expired' );
    }
    $phone = $challenge['phone'];
    if ( ! farazsms_montix_phone_claimable( $phone, $user->ID ) ) { bsc_faraz_link_redirect( 'claimed' ); }
    $code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
    $otp = new \FarazSMS\OTP();
    if ( ! $otp->verify_verification_code( $phone, $code ) ) { bsc_faraz_link_redirect( 'wrong' ); }
    delete_transient( $key );
    update_user_meta( $user->ID, '_farazsms_verified_phone', $phone );
    delete_user_meta( $user->ID, '_bsc_verified_phone' );
    update_user_meta( $user->ID, '_bsc_phone_verified_at', gmdate( 'c' ) );
    bsc_faraz_link_redirect( 'linked' );
}
add_action( 'admin_post_bsc_faraz_link_verify', 'bsc_faraz_link_verify' );

/** Existing customers can link a phone, but must already be signed in with their old password. */
function bsc_faraz_link_panel() {
    $user = bsc_faraz_legacy_customer();
    if ( ! $user ) { return; }
    $phone = get_user_meta( $user->ID, '_farazsms_verified_phone', true );
    if ( ! $phone ) { $phone = get_user_meta( $user->ID, '_bsc_verified_phone', true ); }
    $state = isset( $_GET['bsc_faraz'] ) ? sanitize_key( wp_unslash( $_GET['bsc_faraz'] ) ) : '';
    $messages = array( 'sent' => 'کد به شماره شما ارسال شد.', 'linked' => 'شماره موبایل شما تأیید شد.',
        'wrong' => 'کد اشتباه است یا قبلاً مصرف شده.', 'expired' => 'کد منقضی شده است.',
        'claimed' => 'این شماره به حساب دیگری مرتبط است؛ با پشتیبانی تماس بگیرید.',
        'rate' => 'تعداد درخواست‌ها زیاد است؛ بعداً دوباره تلاش کنید.',
        'unavailable' => 'ارسال/تأیید پیامک در دسترس نیست.' );
    if ( $state && isset( $messages[ $state ] ) ) {
        echo '<p role="status">' . esc_html( $messages[ $state ] ) . '</p>';
    }
    if ( $phone ) { return; }
    $challenge = get_transient( bsc_faraz_link_key() );
    echo '<section class="bsc-faraz-link" dir="rtl"><h2>تأیید موبایل حساب قدیمی</h2>';
    echo '<p>برای حفظ سفارش‌ها، ابتدا با رمز قبلی وارد شوید و شماره موبایل را با پیامک تأیید کنید.</p>';
    if ( is_array( $challenge ) && ! empty( $challenge['phone'] ) ) {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="bsc_faraz_link_verify">';
        echo '<label>کد تأیید <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required></label>';
        wp_nonce_field( 'bsc_faraz_link_verify' );
        echo '<button type="submit">تأیید موبایل</button></form>';
    } else {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="bsc_faraz_link_send">';
        echo '<label>شماره موبایل <input type="tel" name="phone" required inputmode="tel" value="' . esc_attr( get_user_meta( $user->ID, 'billing_phone', true ) ) . '"></label>';
        wp_nonce_field( 'bsc_faraz_link_send' );
        echo '<button type="submit">ارسال کد تأیید</button></form>';
    }
    echo '</section>';
}
add_action( 'woocommerce_account_dashboard', 'bsc_faraz_link_panel', 20 );

// Legacy/password customers must authenticate normally before linking their phone.
add_filter( 'the_content', function( $content ) {
    if ( ! is_user_logged_in() && is_page( 'login-register' ) && bsc_faraz_ready() ) {
        $back = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
        $content .= '<p class="bsc-legacy-login">حساب قبلی دارید و هنوز شماره خود را تأیید نکرده‌اید؟ <a href="'
            . esc_url( wp_login_url( $back ) ) . '">ورود با رمز حساب قبلی</a> و تأیید موبایل در بخش حساب.</p>';
    }
    return $content;
}, 20 );
