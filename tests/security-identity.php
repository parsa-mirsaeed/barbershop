<?php
/** Direct identity isolation and collision test for bundled FarazSMS. */
error_reporting(E_ALL);define('WPINC','wp-includes');
class WP_User{public $ID,$roles,$user_login;function __construct($id,$roles=['customer']){$this->ID=$id;$this->roles=$roles;$this->user_login='opaque'.$id;}}
$users=[1=>new WP_User(1),2=>new WP_User(2,['administrator']),3=>new WP_User(3,['shop_manager']),4=>new WP_User(4,['customer','editor'])];$meta=[];$logins=[];
function add_action($a,$b,$c=10,$d=1){}function add_filter($a,$b,$c=10,$d=1){}
function farazsms_identity_phone_meta_keys(){return ['billing_phone','mobile_number','digits_phone_no'];}
function user_can($u,$cap){return $u->roles!==['customer'];}
function get_users($args){global $users,$meta;$found=[];foreach($users as $id=>$u){if(isset($meta[$id][$args['meta_key']])&&in_array($meta[$id][$args['meta_key']],(array)$args['meta_value'],true))$found[]=($args['fields']??'')==='ID'?$id:$u;}return array_slice($found,0,$args['number']??999);}
function get_user_by($field,$v){global $logins;return $logins[$v]??false;}
function t($ok,$label){if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);}echo "PASS $label\n";}
$source=getenv('MONTIX_FARAZ_SOURCE_DIR') ?: __DIR__.'/../plugin/faraz-sms';
require $source.'/inc/farazsms-account-dedupe.php';
$meta[1]['_farazsms_verified_phone']='09120000001';
t(farazsms_montix_verified_customer_by_mobile('+989120000001')->ID===1,'Iran number normalized');
t(farazsms_montix_verified_customer_by_mobile('invalid')===null,'bad phone rejected');
$meta[2]['_farazsms_verified_phone']='09120000002';
$meta[3]['_bsc_verified_phone']='09120000003';
$meta[4]['_bsc_verified_phone']='09120000004';
t(farazsms_montix_verified_customer_by_mobile('09120000002')===null,'administrator blocked');
t(farazsms_montix_verified_customer_by_mobile('09120000003')===null,'manager blocked');
t(farazsms_montix_verified_customer_by_mobile('09120000004')===null,'multi-role blocked');
$users[5]=new WP_User(5);$meta[5]['billing_phone']='09120000005';$meta[5]['mobile_number']='09120000006';
$logins['09120000007']=$users[5];
t(farazsms_montix_verified_customer_by_mobile('09120000005')===null,'billing_phone untrusted');
t(farazsms_montix_verified_customer_by_mobile('09120000006')===null,'mobile_number untrusted');
t(farazsms_montix_verified_customer_by_mobile('09120000007')===null,'username untrusted');
$meta[5]['_bsc_verified_phone']='09120000001';
t(farazsms_montix_verified_customer_by_mobile('09120000001')===null,'verified collision fails closed');
t(farazsms_montix_phone_claimable('09120000001',6)===false,'verified phone cannot be stolen');
t(farazsms_montix_phone_claimable('09120000005',6)===false,'legacy billing contact cannot be silently claimed');
t(farazsms_montix_phone_claimable('09120000008',6)===true,'unused phone is claimable');
echo "Identity suite passed.\n";
