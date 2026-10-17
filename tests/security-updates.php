<?php
/** Exercise actual vendor update functions with WordPress-like mocks. */
error_reporting(E_ALL);
$root=getenv('MONTIX_FARAZ_SOURCE_DIR') ?: __DIR__.'/../plugin/faraz-sms';
$code=file_get_contents($root.'/inc/farazsms-self-update.php');
$bridge=file_get_contents(__DIR__.'/../plugin/barbershop-core/includes/farazsms-bridge.php');
function extract_function($src,$name){
 $at=strpos($src,'function '.$name.'(');if($at===false)throw new RuntimeException("Missing $name");
 $op=strpos($src,'{',$at);$depth=0;
 for($i=$op;$i<strlen($src);$i++){if($src[$i]==='{')$depth++;if($src[$i]==='}'&&--$depth===0){return substr($src,$at,$i-$at+1);}}
 throw new RuntimeException("Unclosed $name");
}
class WP_Error{public $code;function __construct($code,$message){$this->code=$code;}}
define('FARAZSMS_MONTIX_SECURITY_PATCH','2026-10-17.2');
function get_option($name,$default=null){return '1';}
function t($v,$label){if(!$v){fwrite(STDERR,"FAIL $label\n");exit(1);}echo "PASS $label\n";}
foreach(['farazsms_self_update_auto_decision','farazsms_montix_pinned_auto_update','farazsms_montix_pinned_upgrade'] as $fn){eval(extract_function($code,$fn));}
eval(extract_function($bridge,'bsc_faraz_auto_update_guard'));
$vendor=(object)['plugin'=>'faraz-sms/faraz-sms.php'];$other=(object)['plugin'=>'woocommerce/woocommerce.php'];
t(farazsms_self_update_auto_decision(true,$vendor)===false,'vendor self-update toggle cannot override protection');
t(farazsms_montix_pinned_auto_update(true,$vendor)===false,'vendor pinned guard works');
t(bsc_faraz_auto_update_guard(true,$vendor)===false,'Montix pinned guard works');
t(farazsms_montix_pinned_auto_update(true,$other)===true,'other plugin updates allowed');
t(farazsms_montix_pinned_upgrade(true,['plugin'=>'faraz-sms/faraz-sms.php']) instanceof WP_Error,'WordPress updater requires review');
t(farazsms_montix_pinned_upgrade(true,['plugin'=>'woocommerce/woocommerce.php'])===true,'WooCommerce upgrades unchanged');
t(strpos($code,"if ( defined( 'FARAZSMS_MONTIX_SECURITY_PATCH' ) ) { return \$transient; }")!==false,'upstream update offer suppressed');
t(strpos($code,"'auto_update_plugin', 'farazsms_montix_pinned_auto_update', PHP_INT_MAX")!==false,'vendor last priority registered');
t(strpos($bridge,"'auto_update_plugin', 'bsc_faraz_auto_update_guard', PHP_INT_MAX")!==false,'core last priority registered');
echo "Update guard suite passed.\n";
