<?php
defined('ABSPATH') || exit;
final class GOI_MC_Security {
 public static function request_id():string{$h=isset($_SERVER['HTTP_X_REQUEST_ID'])?sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REQUEST_ID'])):'';return preg_match('/^[A-Za-z0-9._-]{8,64}$/',$h)?$h:wp_generate_uuid4();}
 public static function browser_allowed(WP_REST_Request $r):bool{return current_user_can('manage_options') && wp_verify_nonce((string)$r->get_header('X-WP-Nonce'),'wp_rest');}
 public static function machine_allowed(WP_REST_Request $r):bool{
  $s=GOI_MC_Storage::settings();if(!$s['enabled']||!$s['paired']||!$s['secret_hash'])return false;
  $secret=(string)$r->get_header('X-GOI-Secret');$ts=(string)$r->get_header('X-GOI-Timestamp');$sig=(string)$r->get_header('X-GOI-Signature');
  if(!$secret||!preg_match('/^[0-9]{10}$/',$ts)||abs(time()-(int)$ts)>300||!preg_match('/^[a-f0-9]{64}$/',$sig))return false;
  if(!hash_equals($s['secret_hash'],hash('sha256',$secret)))return false;
  $body=(string)$r->get_body();$canonical=$r->get_method()."\n".$r->get_route()."\n".$ts."\n".hash('sha256',$body);
  return hash_equals(hash_hmac('sha256',$canonical,$secret),$sig);
 }
 public static function allowed(WP_REST_Request $r):bool{return self::browser_allowed($r)||self::machine_allowed($r);}
 public static function https_only(string $url):bool{return strtolower((string)wp_parse_url($url,PHP_URL_SCHEME))==='https';}
 public static function verify_manifest_signature(string $message,string $signature,string $public_key):bool{
  if(!function_exists('sodium_crypto_sign_verify_detached'))return false;
  $sig=base64_decode($signature,true);$key=base64_decode($public_key,true);
  return is_string($sig)&&is_string($key)&&strlen($sig)===SODIUM_CRYPTO_SIGN_BYTES&&strlen($key)===SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES&&sodium_crypto_sign_verify_detached($sig,$message,$key);
 }
 public static function path(string $base,string $relative):?string{
  $relative=ltrim(str_replace('\\','/',$relative),'/');if($relative===''||str_contains($relative,'..')||preg_match('#(^|/)\.(?:/|$)#',$relative)||str_contains($relative,"\0"))return null;
  if(!preg_match('#^[A-Za-z0-9._/-]+$#',$relative))return null;
  $candidate=wp_normalize_path(trailingslashit($base).$relative);$realBase=wp_normalize_path(trailingslashit($base));return str_starts_with($candidate,$realBase)?$candidate:null;
 }
 public static function zip_entry(string $name):bool{
  if($name===''||str_starts_with($name,'/')||str_contains($name,"\0"))return false;
  $name=str_replace('\\','/',$name);
  if(str_contains($name,'..')||preg_match('#(^|/)\.(?:/|$)#',$name))return false;
  return (bool)preg_match('#^[A-Za-z0-9._/-]+/?$#',$name);
 }
 public static function validate_plugin_header(string $file,string $version):bool{
  if(!is_file($file)||filesize($file)>262144)return false;
  $data=(string)file_get_contents($file);
  if(!preg_match('/Plugin Name:\s*GOI Core/i',$data))return false;
  if(!preg_match('/Version:\s*'.preg_quote($version,'/').'\b/i',$data))return false;
  return true;
 }
 public static function manifest(array $m):bool{
  if(!isset($m['release_id'],$m['version'],$m['build_id'],$m['files'])||!is_array($m['files'])||count($m['files'])<1)return false;
  if(!preg_match('/^[A-Za-z0-9._-]{1,190}$/',(string)$m['release_id']))return false;
  $s=GOI_MC_Storage::settings();
  if(isset($m['archive_sha256'])&&!preg_match('/^[a-f0-9]{64}$/',(string)$m['archive_sha256']))return false;
  if(isset($m['plugin_sha256'])&&!preg_match('/^[a-f0-9]{64}$/',(string)$m['plugin_sha256']))return false;
  foreach($m['files'] as $f){
   if(!is_array($f)||!isset($f['path'],$f['sha256'],$f['size'])||!is_int($f['size'])||$f['size']<0||$f['size']>$s['max_write_bytes']||!preg_match('/^[a-f0-9]{64}$/',$f['sha256']))return false;
   if(self::path(GOI_MC_Storage::workspace(),(string)$f['path'])===null)return false;
  }
  return count($m['files'])<=$s['max_files'];
 }
}
