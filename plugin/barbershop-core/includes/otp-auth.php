<?php
/**
 * Passwordless customer entry. Registration asks only for an Iranian mobile.
 * Order recipient, shipping, billing and email details belong to WooCommerce checkout.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bsc_otp_valid_mobile( $value ) {
    $phone = bsc_normalize_iran_mobile( $value );
    return preg_match( '/^09[0-9]{9}$/', $phone ) ? $phone : '';
}

/** Random session token; no telephone or OTP is exposed in client storage. */
function bsc_otp_session_key( $token ) {
    return 'bsc_otp_session_' . hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
}
function bsc_otp_phone_key( $phone, $suffix ) {
    return 'bsc_otp_' . $suffix . '_' . hash_hmac( 'sha256', $phone, wp_salt( 'auth' ) );
}
function bsc_otp_cookie( $value, $expires ) {
    setcookie( 'bsc_otp_session', $value, array(
        'expires' => $expires, 'path' => '/', 'secure' => true,
        'httponly' => true, 'samesite' => 'Lax',
    ) );
}
function bsc_otp_challenge() {
    $token = isset( $_COOKIE['bsc_otp_session'] ) ? (string) wp_unslash( $_COOKIE['bsc_otp_session'] ) : '';
    if ( ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) { return false; }
    $challenge = get_transient( bsc_otp_session_key( $token ) );
    if ( ! is_array( $challenge ) || empty( $challenge['expires'] ) || time() > (int) $challenge['expires'] ) {
        return false;
    }
    return $challenge;
}
function bsc_otp_redirect( $state ) {
    $url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
    wp_safe_redirect( add_query_arg( 'bsc_otp', $state, $url ) );
    exit;
}
function bsc_otp_sms_ready() {
    return has_filter( 'bsc_otp_send_sms' )
        || ( defined( 'BSC_FARAZSMS_API_KEY' ) && BSC_FARAZSMS_API_KEY
            && defined( 'BSC_FARAZSMS_PATTERN' ) && BSC_FARAZSMS_PATTERN );
}

/**
 * The host's FarazSMS plugin can use the bsc_otp_send_sms filter to supply its
 * actual transport. A strict direct FarazSMS pattern API transport is available
 * where plugin hook support has not been verified. Never assume delivery.
 */
function bsc_otp_send_sms( $phone, $code ) {
    $handled = apply_filters( 'bsc_otp_send_sms', null, $phone, $code );
    if ( null !== $handled ) { return true === $handled; }

    if ( ! defined( 'BSC_FARAZSMS_API_KEY' ) || ! BSC_FARAZSMS_API_KEY
        || ! defined( 'BSC_FARAZSMS_PATTERN' ) || ! BSC_FARAZSMS_PATTERN ) {
        return false;
    }
    $variable = defined( 'BSC_FARAZSMS_CODE_VARIABLE' ) ? BSC_FARAZSMS_CODE_VARIABLE : 'code';
    if ( ! preg_match( '/^[a-zA-Z][a-zA-Z0-9_]*$/', $variable ) ) { return false; }
    $body = array(
        'code' => (string) BSC_FARAZSMS_PATTERN,
        'recipient' => $phone,
        'attributes' => array( $variable => $code ),
        'number_format' => 'english',
    );
    if ( defined( 'BSC_FARAZSMS_LINE' ) && BSC_FARAZSMS_LINE ) {
        $body['line_number'] = (string) BSC_FARAZSMS_LINE;
    }
    $response = wp_remote_post( 'https://api.iranpayamak.com/ws/v1/sms/pattern', array(
        'timeout' => 12,
        'redirection' => 0,
        'headers' => array(
            'Api-Key' => (string) BSC_FARAZSMS_API_KEY,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ),
        'body' => wp_json_encode( $body ),
    ) );
    if ( is_wp_error( $response ) || 201 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return false;
    }
    $result = json_decode( wp_remote_retrieve_body( $response ), true );
    return is_array( $result ) && isset( $result['status'] ) && 'success' === $result['status'];
}

/** Opaque, deterministic login prevents disclosing phone via author slugs. */
function bsc_otp_customer_login( $phone ) {
    return 'bsc_u_' . substr( hash_hmac( 'sha256', $phone, wp_salt( 'auth' ) ), 0, 32 );
}

/**
 * Only WooCommerce customers may authenticate with SMS. Never OTP-log in
 * WordPress admins, editors or other privileged accounts.
 */
function bsc_otp_is_customer_account( $user ) {
    // Never grant OTP authentication to multi-role staff or privileged accounts.
    return $user instanceof WP_User
        && array_values( (array) $user->roles ) === array( 'customer' )
        && ! user_can( $user, 'manage_options' )
        && ! user_can( $user, 'manage_woocommerce' )
        && ! user_can( $user, 'edit_others_posts' );
}

function bsc_otp_customer_for_phone( $phone ) {
    // Only a separately SMS-verified identity may access existing order history.
    // WooCommerce billing_phone is shipping/contact data and can be edited in checkout.
    $matches = get_users( array(
        'meta_key' => '_bsc_verified_phone', 'meta_value' => $phone, 'number' => 3,
    ) );
    $opaque = get_user_by( 'login', bsc_otp_customer_login( $phone ) );
    if ( $opaque ) {
        $found = false;
        foreach ( $matches as $match ) {
            if ( (int) $match->ID === (int) $opaque->ID ) { $found = true; break; }
        }
        if ( ! $found ) { $matches[] = $opaque; }
    }
    if ( count( $matches ) > 1 ) { return new WP_Error( 'ambiguous_phone' ); }
    if ( $matches ) {
        return bsc_otp_is_customer_account( $matches[0] )
            ? $matches[0] : new WP_Error( 'not_customer' );
    }
    // Legacy password accounts must link their phone while authenticated.
    // Never assume an arbitrary billing/shipping phone verified its owner's identity.
    $legacy = get_users( array(
        'meta_key' => 'billing_phone', 'meta_value' => $phone, 'number' => 2,
    ) );
    return $legacy ? new WP_Error( 'legacy_phone' ) : null;
}

function bsc_otp_send_handler() {
    check_admin_referer( 'bsc_otp_send' );
    if ( ! is_ssl() || ! bsc_otp_sms_ready() || ! function_exists( 'wc_get_page_permalink' ) ) {
        bsc_otp_redirect( 'unavailable' );
    }
    $phone = isset( $_POST['phone'] ) ? bsc_otp_valid_mobile( sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ) : '';
    if ( ! $phone || empty( $_POST['bsc_legal_consent'] ) ) { bsc_otp_redirect( 'invalid' ); }
    $link_id = get_current_user_id();
    if ( $link_id && ! bsc_otp_is_customer_account( wp_get_current_user() ) ) {
        bsc_otp_redirect( 'unavailable' );
    }

    // Server-derived network identity: do not trust spoofable forwarding headers.
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
    $ip_key = bsc_otp_phone_key( $ip, 'ip' );
    $phone_key = bsc_otp_phone_key( $phone, 'daily' );
    if ( get_transient( bsc_otp_phone_key( $phone, 'cooldown' ) )
        || (int) get_transient( $ip_key ) >= 12
        || (int) get_transient( $phone_key ) >= 8 ) {
        bsc_otp_redirect( 'rate' );
    }

    set_transient( $ip_key, (int) get_transient( $ip_key ) + 1, HOUR_IN_SECONDS );
    set_transient( $phone_key, (int) get_transient( $phone_key ) + 1, DAY_IN_SECONDS );
    set_transient( bsc_otp_phone_key( $phone, 'cooldown' ), 1, MINUTE_IN_SECONDS );

    $code = (string) random_int( 100000, 999999 );
    if ( ! bsc_otp_send_sms( $phone, $code ) ) { bsc_otp_redirect( 'unavailable' ); }

    // Supersede the previous challenge when resending successfully.
    $prior = isset( $_COOKIE['bsc_otp_session'] ) ? (string) wp_unslash( $_COOKIE['bsc_otp_session'] ) : '';
    if ( preg_match( '/^[a-f0-9]{64}$/', $prior ) ) {
        delete_transient( bsc_otp_session_key( $prior ) );
    }
    $token = bin2hex( random_bytes( 32 ) );
    $challenge = array(
        'phone' => $phone, 'hash' => wp_hash_password( $code ),
        'attempts' => 0, 'expires' => time() + 300, 'consent' => true,
        'link_id' => $link_id,
    );
    set_transient( bsc_otp_session_key( $token ), $challenge, 300 );
    bsc_otp_cookie( $token, time() + 300 );
    bsc_otp_redirect( 'sent' );
}
add_action( 'admin_post_nopriv_bsc_otp_send', 'bsc_otp_send_handler' );
add_action( 'admin_post_bsc_otp_send', 'bsc_otp_send_handler' );

function bsc_otp_verify_handler() {
    check_admin_referer( 'bsc_otp_verify' );
    if ( ! is_ssl() ) { bsc_otp_redirect( 'unavailable' ); }
    $token = isset( $_COOKIE['bsc_otp_session'] ) ? (string) wp_unslash( $_COOKIE['bsc_otp_session'] ) : '';
    $challenge = bsc_otp_challenge();
    if ( ! $challenge ) { bsc_otp_redirect( 'expired' ); }
    $key = bsc_otp_session_key( $token );
    $code = isset( $_POST['code'] ) ? bsc_normalize_phone( sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) : '';
    if ( ! preg_match( '/^[0-9]{6}$/', $code ) || ! wp_check_password( $code, $challenge['hash'] ) ) {
        $challenge['attempts'] = (int) $challenge['attempts'] + 1;
        if ( $challenge['attempts'] >= 5 ) {
            delete_transient( $key );
            bsc_otp_cookie( '', time() - 3600 );
            bsc_otp_redirect( 'expired' );
        }
        set_transient( $key, $challenge, max( 1, (int) $challenge['expires'] - time() ) );
        bsc_otp_redirect( 'wrong' );
    }

    // Consume before account operations: an OTP can never be replayed.
    delete_transient( $key );
    bsc_otp_cookie( '', time() - 3600 );
    $phone = $challenge['phone'];
    $user = bsc_otp_customer_for_phone( $phone );
    if ( ! empty( $challenge['link_id'] ) ) {
        $link_id = (int) $challenge['link_id'];
        if ( get_current_user_id() !== $link_id ||
             ! bsc_otp_is_customer_account( wp_get_current_user() ) ||
             ( $user && ( is_wp_error( $user ) ? 'legacy_phone' !== $user->get_error_code() : (int) $user->ID !== $link_id ) ) ) {
            bsc_otp_redirect( 'unavailable' );
        }
        update_user_meta( $link_id, '_bsc_verified_phone', $phone );
        update_user_meta( $link_id, '_bsc_phone_verified_at', gmdate( 'c' ) );
        bsc_otp_redirect( 'success' );
    }
    if ( is_wp_error( $user ) ) {
        bsc_otp_redirect( 'legacy_phone' === $user->get_error_code() ? 'legacy' : 'unavailable' );
    }
    if ( null === $user ) {
        if ( ! get_role( 'customer' ) ) { bsc_otp_redirect( 'unavailable' ); }
        $id = wp_insert_user( array(
            'user_login' => bsc_otp_customer_login( $phone ),
            'user_pass' => wp_generate_password( 40, true, true ),
            'user_email' => '',
            'display_name' => 'مشتری مونتیکس',
            'role' => 'customer',
        ) );
        if ( is_wp_error( $id ) ) { bsc_otp_redirect( 'unavailable' ); }
        update_user_meta( $id, 'billing_phone', $phone );
        update_user_meta( $id, '_bsc_verified_phone', $phone );
        update_user_meta( $id, '_bsc_phone_verified_at', gmdate( 'c' ) );
        update_user_meta( $id, '_bsc_legal_consent_at', gmdate( 'c' ) );
        if ( function_exists( 'bsc_legal_document_version' ) ) {
            update_user_meta( $id, '_bsc_legal_consent_version', bsc_legal_document_version() );
        }
        $user = get_user_by( 'id', $id );
    }
    if ( ! $user || ! bsc_otp_is_customer_account( $user ) ) {
        bsc_otp_redirect( 'unavailable' );
    }
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, false, is_ssl() );
    do_action( 'wp_login', $user->user_login, $user );
    bsc_otp_redirect( 'success' );
}
add_action( 'admin_post_nopriv_bsc_otp_verify', 'bsc_otp_verify_handler' );
add_action( 'admin_post_bsc_otp_verify', 'bsc_otp_verify_handler' );

function bsc_otp_cancel_handler() {
    check_admin_referer( 'bsc_otp_cancel' );
    $token = isset( $_COOKIE['bsc_otp_session'] ) ? (string) wp_unslash( $_COOKIE['bsc_otp_session'] ) : '';
    if ( preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
        delete_transient( bsc_otp_session_key( $token ) );
    }
    bsc_otp_cookie( '', time() - 3600 );
    bsc_otp_redirect( 'cancelled' );
}
add_action( 'admin_post_nopriv_bsc_otp_cancel', 'bsc_otp_cancel_handler' );
add_action( 'admin_post_bsc_otp_cancel', 'bsc_otp_cancel_handler' );

function bsc_otp_form() {
    $challenge = bsc_otp_challenge();
    $messages = array(
        'sent' => 'کد یک‌بارمصرف به شماره شما ارسال شد.',
        'wrong' => 'کد صحیح نیست. دوباره تلاش کنید.',
        'expired' => 'کد منقضی شده یا تعداد تلاش‌ها به پایان رسیده است.',
        'rate' => 'درخواست‌های زیادی ثبت شده است. لطفاً بعداً تلاش کنید.',
        'invalid' => 'شماره موبایل معتبر و تأیید قوانین الزامی است.',
        'unavailable' => 'ارسال یا ورود پیامکی در دسترس نیست. لطفاً بعداً تلاش کنید.',
        'legacy' => 'این شماره مربوط به حساب قدیمی است. یک‌بار با رمز عبور قبلی وارد شوید و شماره را از صفحه حساب تأیید کنید.',
        'cancelled' => 'می‌توانید یک شماره دیگر وارد کنید.',
    );
    $state = isset( $_GET['bsc_otp'] ) ? sanitize_key( wp_unslash( $_GET['bsc_otp'] ) ) : '';
    ob_start(); ?>
    <section class="bsc-otp" dir="rtl" aria-label="ورود یا ثبت نام با موبایل">
        <h2>ورود و ثبت‌نام با شماره موبایل</h2>
        <p>بدون ایمیل، رمز عبور یا فرم اطلاعات شخصی؛ مشخصات تحویل فقط هنگام خرید در ووکامرس پرسیده می‌شود.</p>
        <?php if ( isset( $messages[ $state ] ) ) : ?>
            <p class="bsc-otp-message" role="status"><?php echo esc_html( $messages[ $state ] ); ?></p>
        <?php endif; ?>
        <?php if ( $challenge ) : ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <p>کد ۶ رقمی ارسال‌شده به شماره <?php echo esc_html( substr( $challenge['phone'], 0, 4 ) . '***' . substr( $challenge['phone'], -4 ) ); ?> را وارد کنید.</p>
                <label for="bsc_otp_code">کد تأیید</label>
                <input id="bsc_otp_code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9۰-۹٠-٩]{6}" maxlength="6" required>
                <input type="hidden" name="action" value="bsc_otp_verify">
                <?php wp_nonce_field( 'bsc_otp_verify' ); ?>
                <button type="submit">تأیید و ورود</button>
            </form>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="bsc_otp_send">
                <input type="hidden" name="phone" value="<?php echo esc_attr( $challenge['phone'] ); ?>">
                <input type="hidden" name="bsc_legal_consent" value="yes">
                <?php wp_nonce_field( 'bsc_otp_send' ); ?>
                <button type="submit">ارسال دوباره کد (پس از ۶۰ ثانیه)</button>
            </form>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="bsc_otp_cancel">
                <?php wp_nonce_field( 'bsc_otp_cancel' ); ?>
                <button type="submit">تغییر شماره موبایل</button>
            </form>
        <?php else : ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <label for="bsc_otp_phone">شماره موبایل</label>
                <input id="bsc_otp_phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="۰۹۱۲۱۲۳۴۵۶۷" maxlength="16" required>
                <input type="hidden" name="action" value="bsc_otp_send">
                <?php wp_nonce_field( 'bsc_otp_send' ); ?>
                <label class="bsc-otp-consent"><input type="checkbox" name="bsc_legal_consent" value="yes" required> شرایط استفاده و سیاست حریم خصوصی سایت را می‌پذیرم.</label>
                <button type="submit">ارسال کد تأیید</button>
                <?php if ( 'legacy' === $state ) : ?>
                    <a href="<?php echo esc_url( wp_login_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' ) ) ); ?>">ورود با رمز حساب قبلی برای تأیید موبایل</a>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </section>
    <?php return ob_get_clean();
}


/** Existing customers may explicitly bind their password account to a verified mobile. */
function bsc_otp_legacy_link_form() {
    $user = wp_get_current_user();
    if ( ! $user || ! bsc_otp_is_customer_account( $user )
        || get_user_meta( $user->ID, '_bsc_verified_phone', true ) ) { return; }
    $phone = bsc_otp_valid_mobile( get_user_meta( $user->ID, 'billing_phone', true ) );
    if ( ! $phone ) { return; }
    if ( bsc_otp_challenge() ) {
        echo bsc_otp_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by renderer.
        return;
    }
    echo '<section class="bsc-otp" dir="rtl"><h2>تأیید موبایل حساب قدیمی</h2><p>برای ورود بعدی با پیامک، شماره ثبت‌شده در حساب قبلی را تأیید کنید.</p>';
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    echo '<input type="hidden" name="action" value="bsc_otp_send"><input type="hidden" name="phone" value="' . esc_attr( $phone ) . '">';
    wp_nonce_field( 'bsc_otp_send' );
    echo '<label><input type="checkbox" name="bsc_legal_consent" value="yes" required> شرایط استفاده و حریم خصوصی را می‌پذیرم.</label>';
    echo '<button type="submit">ارسال کد برای تأیید موبایل</button></form></section>';
}
add_action( 'woocommerce_account_dashboard', 'bsc_otp_legacy_link_form', 20 );

/**
 * Prevent WooCommerce's legacy direct registration handler from creating
 * unverified accounts even when someone manually POSTs its old form.
 * Staff-created users and REST administration are not affected.
 */
function bsc_otp_require_verified_registration( $errors ) {
    if ( ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        $errors->add( 'bsc_otp_only', 'برای ساخت حساب، از کد تأیید پیامکی استفاده کنید.' );
    }
    return $errors;
}
add_filter( 'woocommerce_registration_errors', 'bsc_otp_require_verified_registration', 999 );

/** Replace WooCommerce's old guest signup/login UI, leaving customer endpoints intact. */
function bsc_otp_guest_account( $return, $tag ) {
    if ( 'woocommerce_my_account' === $tag && ! is_user_logged_in() && ! is_admin() ) {
        return bsc_otp_form();
    }
    return $return;
}
add_filter( 'pre_do_shortcode_tag', 'bsc_otp_guest_account', 10, 2 );
