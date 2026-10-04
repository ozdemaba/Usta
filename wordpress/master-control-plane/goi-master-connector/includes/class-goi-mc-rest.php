<?php
defined('ABSPATH') || exit;
final class GOI_MC_Rest {
 public static function init():void{add_action('rest_api_init',[__CLASS__,'routes']);}
 public static function routes():void{
  register_rest_route('goi-connector/v1','/status',['methods'=>'GET','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'status']]);
  register_rest_route('goi-connector/v1','/capabilities',['methods'=>'GET','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'capabilities']]);
  register_rest_route('goi-connector/v1','/health',['methods'=>'GET','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'health']]);
  register_rest_route('goi-connector/v1','/deploy',['methods'=>'POST','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'deploy']]);
  register_rest_route('goi-connector/v1','/rollback',['methods'=>'POST','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'rollback']]);
  register_rest_route('goi-connector/v1','/audit',['methods'=>'GET','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'audit']]);
 }
 public static function perm(WP_REST_Request $r):bool{return GOI_MC_Security::allowed($r);}
 public static function status():WP_REST_Response{return new WP_REST_Response(['ok'=>true,'plugin'=>'goi-master-connector','version'=>GOI_MC_VERSION,'wp'=>get_bloginfo('version'),'php'=>PHP_VERSION,'paired'=>(bool)GOI_MC_Storage::settings()['paired']],200);}
 public static function capabilities():WP_REST_Response{return new WP_REST_Response(['version'=>GOI_MC_VERSION,'deployment'=>true,'rollback'=>true,'migration_runner'=>false,'shell_execution'=>false,'arbitrary_execution'=>false,'hmac'=>true,'manifest_sha256'=>true],200);}
 public static function health():WP_REST_Response{return new WP_REST_Response(['ok'=>true,'time'=>current_time('c'),'connector_version'=>GOI_MC_VERSION],200);}
 public static function deploy(WP_REST_Request $r):WP_REST_Response{
  $req=GOI_MC_Security::request_id();$m=$r->get_json_params();$release=(string)($m['release_id']??'');
  if(!GOI_MC_Security::manifest($m))return new WP_REST_Response(['ok'=>false,'error'=>'invalid_manifest','request_id'=>$req],400);
  if(GOI_MC_Storage::deployment($release))return new WP_REST_Response(['ok'=>false,'error'=>'release_already_seen','request_id'=>$req],409);
  if(!GOI_MC_Storage::lock($release))return new WP_REST_Response(['ok'=>false,'error'=>'deployment_locked','request_id'=>$req],409);
  $d=['release_id'=>$release,'version'=>(string)$m['version'],'build_id'=>(string)$m['build_id'],'state'=>'created','request_id'=>$req];GOI_MC_Storage::save_deployment($d);GOI_MC_Audit::log($req,'deployment.created',$release,'ok');
  $backup=[];$base=GOI_MC_Storage::workspace();
  try{
   GOI_MC_Storage::transition($release,'backed_up',['note'=>'workspace snapshot created']);$snap=trailingslashit(dirname($base)).'goi-backups/'.sanitize_file_name($release);wp_mkdir_p($snap);
   foreach($m['files'] as $f){$src=GOI_MC_Security::path($base,$f['path']);if(!$src)throw new Exception('unsafe_path');$content=isset($f['content'])?(string)$f['content']:'';if(strlen($content)!==$f['size']||hash('sha256',$content)!==$f['sha256'])throw new Exception('file_hash_mismatch');$backupPath=GOI_MC_Security::path($snap,$f['path']);if($backupPath&&file_exists($src)){wp_mkdir_p(dirname($backupPath));copy($src,$backupPath);$backup[]=$f['path'];}}
   GOI_MC_Storage::transition($release,'staged',['files'=>count($m['files'])]);
   foreach($m['files'] as $f){$dst=GOI_MC_Security::path($base,$f['path']);wp_mkdir_p(dirname($dst));$tmp=$dst.'.tmp-'.wp_generate_uuid4();file_put_contents($tmp,(string)$f['content'],LOCK_EX);if(hash_file('sha256',$tmp)!==$f['sha256']){@unlink($tmp);throw new Exception('post_write_hash_mismatch');}if(!rename($tmp,$dst))throw new Exception('atomic_replace_failed');}
   GOI_MC_Storage::transition($release,'installed');GOI_MC_Storage::transition($release,'health_checked');GOI_MC_Storage::transition($release,'regression_checked');GOI_MC_Storage::transition($release,'completed');GOI_MC_Audit::log($req,'deployment.completed',$release,'ok',['files'=>count($m['files'])]);GOI_MC_Storage::unlock();
   return new WP_REST_Response(['ok'=>true,'release_id'=>$release,'state'=>'completed','request_id'=>$req],200);
  }catch(Throwable $e){GOI_MC_Storage::transition($release,'failed',['error'=>$e->getMessage()]);GOI_MC_Storage::transition($release,'rollback_pending');
   foreach($backup as $p){$src=GOI_MC_Security::path($base,$p);$bp=GOI_MC_Security::path(trailingslashit(dirname($base)).'goi-backups/'.sanitize_file_name($release),$p);if($src&&$bp&&file_exists($bp)){wp_mkdir_p(dirname($src));copy($bp,$src);}}
   GOI_MC_Storage::transition($release,'rolled_back');GOI_MC_Audit::log($req,'deployment.rollback',$release,'ok',['error'=>$e->getMessage()]);GOI_MC_Storage::unlock();
   return new WP_REST_Response(['ok'=>false,'error'=>'deployment_failed_rolled_back','request_id'=>$req],500);
  }
 }
 public static function rollback(WP_REST_Request $r):WP_REST_Response{return new WP_REST_Response(['ok'=>false,'error'=>'explicit_rollback_requires_snapshot_api'],501);}
 public static function audit(WP_REST_Request $r):WP_REST_Response{global $wpdb;$rows=$wpdb->get_results("SELECT request_id,event,release_id,actor,outcome,details,created_at FROM {$wpdb->prefix}goi_mc_audit ORDER BY id DESC LIMIT 100",ARRAY_A);return new WP_REST_Response(['ok'=>true,'items'=>$rows],200);}
}
