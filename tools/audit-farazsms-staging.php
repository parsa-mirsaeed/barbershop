<?php
/** Read-only. Run with WP-CLI against an isolated WordPress staging clone. */
if(!defined('ABSPATH')){fwrite(STDERR,"Run with wp eval-file on staging\n");exit(2);}
if(!function_exists('get_plugins'))require_once ABSPATH.'wp-admin/includes/plugin.php';
$errors=0;
function montix_staging_check($ok,$label){global $errors;if(!$ok)$errors++;echo ($ok?'PASS ':'FAIL ').$label."\n";}
$plugins=get_plugins();$faraz='faraz-sms/faraz-sms.php';$core='barbershop-core/barbershop-core.php';
montix_staging_check(($plugins[$faraz]['Version']??'')==='3.22.273-montix.2','FarazSMS vendor fork exact version');
montix_staging_check(($plugins[$core]['Version']??'')==='3.5.1','Matching Montix Core version');
montix_staging_check(is_plugin_active($faraz)&&is_plugin_active($core),'Both plugins active');
montix_staging_check(defined('FARAZSMS_MONTIX_SECURITY_PATCH')&&FARAZSMS_MONTIX_SECURITY_PATCH==='2026-10-17.2','Security patch marker loaded');
montix_staging_check(defined('FARAZSMS_LOGIN_BUNDLED')&&FARAZSMS_LOGIN_BUNDLED,'Bundled login active');
montix_staging_check(defined('FARAZSMS_LOGIN_FRONTEND_ENABLED')&&FARAZSMS_LOGIN_FRONTEND_ENABLED,'Login frontend active');
montix_staging_check(shortcode_exists('farazsms_login_form'),'FarazSMS shortcode registered');
$page=get_page_by_path('login-register');
montix_staging_check($page&&$page->post_status==='publish'&&has_shortcode($page->post_content,'farazsms_login_form'),'/login-register page');
montix_staging_check(wp_parse_url(home_url('/'),PHP_URL_SCHEME)==='https','Public HTTPS URL; separately verify is_ssl in real HTTP requests');
montix_staging_check(get_option('woocommerce_enable_myaccount_registration')==='no','Direct account signup disabled');
montix_staging_check(get_option('woocommerce_enable_signup_and_login_from_checkout')==='no','Checkout account signup disabled');
montix_staging_check(get_option('woocommerce_enable_guest_checkout')==='yes','Guest checkout retained');
montix_staging_check(!is_plugin_active('farazsms-login/farazsms-login.php'),'No duplicate standalone FarazSMS login plugin');
global $wpdb;$table=$wpdb->prefix.'farazsms_verification';
$col=$wpdb->get_row("SHOW COLUMNS FROM $table LIKE 'code'");
montix_staging_check($col&&preg_match('/varchar\((\d+)\)/i',$col->Type,$m)&&(int)$m[1]>=64,'OTP hash column >=64 chars');
montix_staging_check(get_option('montix_faraz_otp_hmac_v1','')==='1','OTP hash schema migrated');
$phones=[];$collisions=0;$staff=0;$offset=0;
do {
 $users=get_users(['number'=>200,'offset'=>$offset,'orderby'=>'ID','order'=>'ASC']);
 foreach($users as $u) {
   foreach(['_farazsms_verified_phone','_bsc_verified_phone'] as $key) {
    $raw=get_user_meta($u->ID,$key,true);if(!$raw)continue;
    $number=farazsms_dedupe_normalize_mobile($raw);
    if(!$number){$collisions++;continue;}
    if(isset($phones[$number])&&$phones[$number]!==$u->ID)$collisions++;
    $phones[$number]=$u->ID;
    if(!farazsms_montix_is_customer($u))$staff++;
   }
 }
 $offset+=count($users);
}while(count($users)===200&&$offset<50000);
montix_staging_check($offset<50000||count($users)<200,'All user records audited (<50000)');
montix_staging_check($collisions===0,'No duplicate/invalid verified-phone ownership: '.$collisions);
montix_staging_check($staff===0,'No privileged accounts with verified customer phones: '.$staff);
echo "Read-only audit; $errors blocking failures. No SMS or personal data output.\n";
exit($errors?1:0);
