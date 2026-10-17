<?php
/** Direct OTP storage and verification regression on the patched vendor class. */
error_reporting(E_ALL);define('ABSPATH',__DIR__.'/wp/');define('MINUTE_IN_SECONDS',60);
$transients=[];$options=[];
function farazsms_tr_num($v){return (string)$v;}function wp_salt($s){return 'test-only-secret';}
function get_option($k,$default=''){global $options;return $options[$k]??$default;}
function update_option($k,$v,$autoload=false){global $options;$options[$k]=$v;return true;}
function delete_transient($k){global $transients;unset($transients[$k]);}
function get_transient($k){global $transients;return $transients[$k]??false;}
function set_transient($k,$v,$ttl){global $transients;$transients[$k]=$v;return true;}
class FakeDB {
 public $prefix='wp_',$row=null,$columnType='varchar(10)',$last_error='',$simulateConsumed=false;
 function get_var($sql){return 'wp_farazsms_verification';}
 function get_charset_collate(){return 'DEFAULT CHARACTER SET utf8mb4';}
 function get_row($sql){return strpos($sql,'SHOW COLUMNS')===0?(object)['Type'=>$this->columnType]:$this->row;}
 function query($sql){if(strpos($sql,'DELETE')===0){$this->row=null;return 1;}return 0;}
 function prepare($sql,...$args){return str_replace('%s',"'".($args[0]??'')."'",$sql);}
 function replace($table,$data,$fmt){$this->row=(object)['code'=>$data['code'],'expire_date'=>$data['expire_date'],'id'=>1,'verification'=>$data['verification']];return 1;}
 function delete($table,$where,$fmt){if($this->simulateConsumed)return 0;$this->row=null;return 1;}
}
$wpdb=new FakeDB();
$source=getenv('MONTIX_FARAZ_SOURCE_DIR') ?: __DIR__.'/../plugin/faraz-sms';
require $source.'/modules/farazsms-login/includes/class-otp.php';
function t($ok,$label){if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}echo "PASS: $label\n";}
$otp=new FarazSMS\OTP();
t($otp->send_verification_code('09120000001','314159')===true,'hash stored');
t($wpdb->columnType==='varchar(100)'&&get_option('montix_faraz_otp_hmac_v1')==='1','schema migration succeeds');
t(strlen($wpdb->row->code)===64&&$wpdb->row->code!=='314159','no plaintext OTP');
t($otp->verify_verification_code('09120000001','000000')===false,'wrong OTP rejected');
t($otp->verify_verification_code('09120000001','314159')===true,'correct OTP accepted once');
t($otp->verify_verification_code('09120000001','314159')===false,'OTP replay denied');
t($otp->send_verification_code('09120000001','987654')===true,'second challenge');
$wpdb->row->code='987654';
t($otp->verify_verification_code('09120000001','987654')===false,'plaintext row refused');
t($otp->send_verification_code('09120000001','555555')===true,'concurrent challenge');
$wpdb->simulateConsumed=true;
t($otp->verify_verification_code('09120000001','555555')===false,'concurrent already-consumed challenge refused');
$wpdb->simulateConsumed=false;
t($otp->send_verification_code('09120000001','123456')===true,'resend');
$wpdb->row->expire_date='2000-01-01 00:00:00';
t($otp->verify_verification_code('09120000001','123456')===false,'expired OTP refused');
echo "OTP suite passed.\n";
