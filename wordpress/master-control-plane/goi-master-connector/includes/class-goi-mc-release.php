<?php
defined('ABSPATH') || exit;
final class GOI_MC_Release {
 public static function fetch_and_install(WP_REST_Request $request):WP_REST_Response{
  $req=GOI_MC_Security::request_id();$p=$request->get_json_params();
  if(!is_array($p))return new WP_REST_Response(['ok'=>false,'error'=>'invalid_request','request_id'=>$req],400);
  $manifest_url=esc_url_raw((string)($p['manifest_url']??''));$archive_url=esc_url_raw((string)($p['archive_url']??''));$sig=trim((string)($p['signature']??''));$s=GOI_MC_Storage::settings();$public_key=(string)$s['release_public_key'];
  if(!$manifest_url||!$archive_url||!$sig||!$public_key||!wp_http_validate_url($manifest_url)||!wp_http_validate_url($archive_url)||!GOI_MC_Security::https_only($manifest_url)||!GOI_MC_Security::https_only($archive_url))return new WP_REST_Response(['ok'=>false,'error'=>'invalid_release_source'],400);
  $mr=wp_safe_remote_get($manifest_url,['timeout'=>30,'redirection'=>2,'limit_response_size'=>262144]);
  if(is_wp_error($mr))return new WP_REST_Response(['ok'=>false,'error'=>'manifest_fetch_failed'],502);
  $raw=(string)wp_remote_retrieve_body($mr);$manifest=json_decode($raw,true);
  if(!is_array($manifest)||!GOI_MC_Security::manifest($manifest))return new WP_REST_Response(['ok'=>false,'error'=>'invalid_manifest'],400);
  if(!GOI_MC_Security::verify_manifest_signature($raw,$sig,$public_key))return new WP_REST_Response(['ok'=>false,'error'=>'invalid_manifest_signature'],403);
  if(empty($manifest['archive_sha256'])||empty($manifest['plugin_sha256']))return new WP_REST_Response(['ok'=>false,'error'=>'manifest_missing_integrity_fields'],400);
  $release=(string)$manifest['release_id'];
  if(GOI_MC_Storage::deployment($release))return new WP_REST_Response(['ok'=>false,'error'=>'release_already_seen'],409);
  if(!GOI_MC_Storage::lock($release))return new WP_REST_Response(['ok'=>false,'error'=>'deployment_locked'],409);
  GOI_MC_Storage::save_deployment(['release_id'=>$release,'version'=>(string)$manifest['version'],'build_id'=>(string)$manifest['build_id'],'state'=>'created','request_id'=>$req]);
  GOI_MC_Audit::log($req,'release.verified',$release,'ok');
  $tmp=trailingslashit(WP_CONTENT_DIR).'goi-release-'.$release.'.zip';
  try{
   $r=wp_safe_remote_get($archive_url,['timeout'=>120,'redirection'=>2,'limit_response_size'=>$s['max_archive_bytes']]);
   if(is_wp_error($r))throw new Exception('archive_fetch_failed');
   $body=(string)wp_remote_retrieve_body($r);if(strlen($body)>$s['max_archive_bytes'])throw new Exception('archive_too_large');
   if(!file_put_contents($tmp,$body,LOCK_EX))throw new Exception('archive_write_failed');
   if(!hash_equals((string)$manifest['archive_sha256'],hash_file('sha256',$tmp)))throw new Exception('archive_hash_mismatch');
   GOI_MC_Storage::transition($release,'backed_up',['source'=>'github_release']);
   $result=self::install_zip($tmp,$manifest,$req);
   @unlink($tmp);GOI_MC_Storage::unlock();return new WP_REST_Response($result,200);
  }catch(Throwable $e){
   @unlink($tmp);GOI_MC_Storage::transition($release,'failed',['error'=>$e->getMessage()]);GOI_MC_Storage::unlock();GOI_MC_Audit::log($req,'release.install_failed',$release,'error',['error'=>$e->getMessage()]);
   return new WP_REST_Response(['ok'=>false,'error'=>'release_install_failed','request_id'=>$req],500);
  }
 }
 private static function install_zip(string $zip_path,array $manifest,string $req):array{
  if(!class_exists('ZipArchive'))throw new Exception('zip_extension_required');
  $release=(string)$manifest['release_id'];$plugin_dir=trailingslashit(WP_PLUGIN_DIR).'goi-core';$stage_root=trailingslashit(WP_CONTENT_DIR).'goi-release-stage/'.sanitize_file_name($release);$rollback_root=trailingslashit(WP_CONTENT_DIR).'goi-release-rollback/'.sanitize_file_name($release);
  self::remove_tree($stage_root);wp_mkdir_p($stage_root);wp_mkdir_p(dirname($rollback_root));
  $zip=new ZipArchive();if($zip->open($zip_path)!==true)throw new Exception('invalid_zip');
  self::verify_archive($zip,$manifest);$root=self::archive_root($zip);$zip->close();
  $zip=new ZipArchive();$zip->open($zip_path);if(!$zip->extractTo($stage_root)){ $zip->close();throw new Exception('archive_extract_failed');}$zip->close();
  $candidate=$root?trailingslashit($stage_root).trim($root,'/'):$stage_root;$plugin_file=trailingslashit($candidate).'goi-core.php';
  if(!is_file($plugin_file))throw new Exception('goi_core_plugin_missing');
  if(!hash_equals((string)$manifest['plugin_sha256'],hash_file('sha256',$plugin_file)))throw new Exception('plugin_hash_mismatch');
  if(!GOI_MC_Security::validate_plugin_header($plugin_file,(string)$manifest['version']))throw new Exception('plugin_header_invalid');
  GOI_MC_Storage::transition($release,'staged',['stage'=>$candidate]);
  $was_active=is_plugin_active('goi-core/goi-core.php');$previous_schema=(string)get_option('goi_core_schema_version','0');
  if($was_active)deactivate_plugins('goi-core/goi-core.php',true);
  if(is_dir($rollback_root))self::remove_tree($rollback_root);
  if(is_dir($plugin_dir)&&!rename($plugin_dir,$rollback_root))throw new Exception('active_directory_move_failed');
  if(!rename($candidate,$plugin_dir)){if(is_dir($rollback_root))rename($rollback_root,$plugin_dir);throw new Exception('staged_directory_move_failed');}
  GOI_MC_Storage::transition($release,'installed',['active'=>$plugin_dir,'previous_schema'=>$previous_schema,'was_active'=>$was_active]);
  if($was_active){require_once ABSPATH.'wp-admin/includes/plugin.php';$a=activate_plugin('goi-core/goi-core.php',true);if(is_wp_error($a)||!is_plugin_active('goi-core/goi-core.php'))throw new Exception('activation_failed');}
  $target=(string)($manifest['schema_version']??$previous_schema);$migration=GOI_MC_Migrations::run($target);GOI_MC_Storage::transition($release,'migrated',$migration);
  $health=GOI_MC_Core_Health::check();if(!$health['ok'])throw new Exception('health_check_failed');GOI_MC_Storage::transition($release,'health_checked',$health);
  $reg=GOI_MC_Core_Health::regression();if(!$reg['ok'])throw new Exception('regression_check_failed');GOI_MC_Storage::transition($release,'regression_checked',$reg);
  GOI_MC_Storage::transition($release,'completed');GOI_MC_Audit::log($req,'release.install_completed',$release,'ok',['was_active'=>$was_active,'previous_schema'=>$previous_schema,'target_schema'=>$target]);
  return ['ok'=>true,'release_id'=>$release,'version'=>$manifest['version'],'state'=>'completed','request_id'=>$req];
 }
 private static function verify_archive(ZipArchive $zip,array $manifest):void{
  $expected=[];foreach($manifest['files'] as $f)$expected[(string)$f['path']]=['sha256'=>$f['sha256'],'size'=>$f['size']];
  $root=self::archive_root($zip);$seen=[];
  for($i=0;$i<$zip->numFiles;$i++){
   $name=$zip->getNameIndex($i);if(!GOI_MC_Security::zip_entry($name))throw new Exception('unsafe_archive_path');if(str_ends_with($name,'/'))continue;
   $relative=$root&&str_starts_with($name,$root)?substr($name,strlen($root)):$name;
   if(!isset($expected[$relative]))throw new Exception('archive_manifest_mismatch');
   $data=$zip->getFromIndex($i);if($data===false||strlen($data)!==$expected[$relative]['size']||!hash_equals($expected[$relative]['sha256'],hash('sha256',$data)))throw new Exception('archive_file_hash_mismatch');$seen[$relative]=true;
  }
  if(count($seen)!==count($expected))throw new Exception('archive_manifest_incomplete');
 }
 private static function archive_root(ZipArchive $zip):string{
  $root='';$prefix=null;
  for($i=0;$i<$zip->numFiles;$i++){ $n=$zip->getNameIndex($i);if(str_ends_with($n,'/')){$parts=explode('/',trim($n,'/'));if(count($parts)===1&&$prefix===null)$prefix=$parts[0];} }
  if($prefix!==null)$root=$prefix.'/';return $root;
 }
 private static function remove_tree(string $dir):void{
  if(!is_dir($dir))return;$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
  foreach($it as $item){if($item->isDir())@rmdir($item->getPathname());else@unlink($item->getPathname());}@rmdir($dir);
 }
}
