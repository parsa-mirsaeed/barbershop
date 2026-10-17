<?php
/** Direct runtime regression on patched vendor reset + account identity functions. */
error_reporting(E_ALL); ob_start();
define('ABSPATH','/tmp/');define('WPINC','wp-includes');define('MINUTE_IN_SECONDS',60);
class WP_User {public $ID;public $roles;function __construct($id,$roles){$this->ID=$id;$this->roles=$roles;}}
$users=[1=>new WP_User(1,['customer']),2=>new WP_User(2,['administrator'])];
$meta=[1=>['_farazsms_verified_phone'=>'09120000001'],2=>['_farazsms_verified_phone'=>'09120000002']];
$transients=[];$options=[];
function user_can($u,$cap){return in_array('administrator',$u->roles,true);}
function wp_salt($scope){return 'only-test-salt';}
function get_users($a){global $users,$meta;$out=[];foreach($meta as $id=>$props){if(isset($props[$a['meta_key']])&&in_array($props[$a['meta_key']],(array)$a['meta_value'],true)){$out[]=($a['fields']??'')==='ID'?$id:$users[$id];}}return array_slice($out,0,$a['number']??500);}
function get_user_meta($id,$k,$single){global $meta;return $meta[$id][$k]??'';}
function get_user_by($f,$v){return false;} function add_action($a,$b,$c=10,$d=1){}
function did_action($x){return 0;} function get_option($k,$default=null){global $options;return $options[$k]??$default;}
function set_transient($k,$v,$ttl){global $transients;$transients[$k]=$v;return true;}
function get_transient($k){global $transients;return $transients[$k]??false;}
function delete_transient($k){global $transients;unset($transients[$k]);}
function add_option($k,$v,$unused='',$autoload=false){global $options;if(array_key_exists($k,$options))return false;$options[$k]=$v;return true;}
function delete_option($k){global $options;unset($options[$k]);}
function wp_schedule_single_event($t,$h,$p){} function wp_unslash($x){return $x;} function is_ssl(){return true;}
function t($good,$desc){if(!$good){fwrite(STDERR,"FAIL $desc\n");exit(1);}echo "PASS $desc\n";}
class_alias('stdClass','FarazSMS\Helper');
$source=getenv('MONTIX_FARAZ_SOURCE_DIR') ?: __DIR__.'/../plugin/faraz-sms';
require $source.'/inc/farazsms-account-dedupe.php';
require $source.'/modules/farazsms-login/includes/class-main.php';
t(farazsms_montix_verified_customer_by_mobile('09120000001')->ID===1,'verified customer lookup');
t(farazsms_montix_verified_customer_by_mobile('09120000002')===null,'no admin OTP account');
$ref=new ReflectionClass('FarazSMS\Main_Settings');$instance=$ref->newInstanceWithoutConstructor();
function invoke($instance,$method,...$args){$m=new ReflectionMethod($instance,$method);$m->setAccessible(true);return $m->invoke($instance,...$args);}
t(invoke($instance,'issue_password_reset_ticket','09120000001')===true,'reset ticket issued');
$cookie=$_COOKIE['montix_faraz_reset'];
t(invoke($instance,'password_reset_is_authorized','09120000001')===true,'browser A authorized');
t(invoke($instance,'password_reset_is_authorized','09120000002')===false,'another account refused');
unset($_COOKIE['montix_faraz_reset']);
t(invoke($instance,'password_reset_is_authorized','09120000001')===false,'browser B refused');
$_COOKIE['montix_faraz_reset']=$cookie;
t(invoke($instance,'consume_password_reset_ticket')===true,'one-time reset ticket used');
$_COOKIE['montix_faraz_reset']=$cookie;
t(invoke($instance,'password_reset_is_authorized','09120000001')===false,'used ticket no longer valid');
t(invoke($instance,'consume_password_reset_ticket')===false,'replay blocked');
t(invoke($instance,'issue_password_reset_ticket','09120000002')===false,'no admin reset ticket');
$users[3]=new WP_User(3,['customer']);$meta[3]=['billing_phone'=>'09120000003'];
t(farazsms_montix_verified_customer_by_mobile('09120000003')===null,'unverified billing phone rejected');
echo "Reset suite passed.\n";
