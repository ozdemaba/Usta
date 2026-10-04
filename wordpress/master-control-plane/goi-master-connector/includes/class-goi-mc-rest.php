<?php
defined('ABSPATH') || exit;
final class GOI_MC_Rest {
 public static function init():void{add_action('rest_api_init',[__CLASS__,'routes']);}
 public static function routes():void{
  register_rest_route('goi-connector/v1','/status',['methods'=>'GET','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'status']]);
  register_rest_route('goi-connector/v1','/capabilities',['methods'=>'GET','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'capabilities']]);
  register_rest_route('goi-connector/v1','/health',['methods'=>'GET','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'health']]);
  register_rest_route('goi-connector/v1','/release/install',['methods'=>'POST','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'release_install']]);
  register_rest_route('goi-connector/v1','/deploy',['methods'=>'POST','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'legacy_deploy']]);
  register_rest_route('goi-connector/v1','/rollback',['methods'=>'POST','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'rollback']]);
  register_rest_route('goi-connector/v1','/audit',['methods'=>'GET','permission_callback'=>[__CLASS__,'perm'],'callback'=>[__CLASS__,'audit']]);
 }
 public static function perm(WP_REST_Request $r):bool{return GOI_MC_Security::allowed($r);}
 public static function status():WP_REST_Response{return new WP_REST_Response(['ok'=>true,'plugin'=>'goi-master-connector','version'=>GOI_MC_VERSION,'wp'=>get_bloginfo('version'),'php'=>PHP_VERSION,'paired'=>(bool)GOI_MC_Storage::settings()['paired']],200);}
 public static function capabilities():WP_REST_Response{return new WP_REST_Response(['version'=>GOI_MC_VERSION,'deployment'=>true,'release_install'=>true,'rollback'=>true,'migration_runner'=>true,'signed_manifests'=>function_exists('sodium_crypto_sign_verify_detached'),'shell_execution'=>false,'arbitrary_execution'=>false,'hmac'=>true,'manifest_sha256'=>true],200);}
 public static function health():WP_REST_Response{return new WP_REST_Response(array_merge(['connector_version'=>GOI_MC_VERSION],GOI_MC_Core_Health::check()),200);}
 public static function release_install(WP_REST_Request $r):WP_REST_Response{
  $s=GOI_MC_Storage::settings();
  if($s['production_requires_approval'] && !current_user_can('manage_options') && !self::approved($r))return new WP_REST_Response(['ok'=>false,'error'=>'production_approval_required'],403);
  return GOI_MC_Release::fetch_and_install($r);
 }
 public static function legacy_deploy(WP_REST_Request $r):WP_REST_Response{return new WP_REST_Response(['ok'=>false,'error'=>'legacy_file_deploy_disabled','use'=>'/release/install'],410);}
 public static function rollback(WP_REST_Request $r):WP_REST_Response{
  $p=$r->get_json_params();$release=is_array($p)?sanitize_text_field((string)($p['release_id']??'')):'';
  if(!$release||!preg_match('/^[A-Za-z0-9._-]{1,190}$/',$release))return new WP_REST_Response(['ok'=>false,'error'=>'invalid_release_id'],400);
  $d=GOI_MC_Storage::deployment($release);if(!$d)return new WP_REST_Response(['ok'=>false,'error'=>'release_not_found'],404);
  $root=trailingslashit(WP_CONTENT_DIR).'goi-release-rollback/'.sanitize_file_name($release);$active=trailingslashit(WP_PLUGIN_DIR).'goi-core';$hold=trailingslashit(WP_CONTENT_DIR).'goi-release-rollback/'.sanitize_file_name($release).'-active-'.wp_generate_uuid4();
  if(!is_dir($root))return new WP_REST_Response(['ok'=>false,'error'=>'rollback_snapshot_missing'],409);
  $req=GOI_MC_Security::request_id();
  if(!GOI_MC_Storage::lock('rollback-'.$release))return new WP_REST_Response(['ok'=>false,'error'=>'deployment_locked'],409);
  try{
   $details=json_decode((string)$d['details'],true);$previous_schema=(string)($details['previous_schema']??get_option('goi_core_schema_version','0'));
   $current_schema=(string)get_option('goi_core_schema_version','0');
   if(version_compare($current_schema,$previous_schema,'>'))throw new Exception('database_rollback_requires_reversible_migrations');
   if(is_dir($active)&&!rename($active,$hold))throw new Exception('active_hold_move_failed');
   if(!rename($root,$active)){if(is_dir($hold))rename($hold,$active);throw new Exception('rollback_restore_failed');}
   $was_active=!empty($details['was_active']);
   if($was_active){activate_plugin('goi-core/goi-core.php',true);if(!is_plugin_active('goi-core/goi-core.php'))throw new Exception('rollback_activation_failed');}
   GOI_MC_Storage::transition($release,'rolled_back',['request_id'=>$req,'rollback'=>'explicit']);
   GOI_MC_Audit::log($req,'deployment.explicit_rollback',$release,'ok');
   GOI_MC_Storage::unlock();
   return new WP_REST_Response(['ok'=>true,'release_id'=>$release,'state'=>'rolled_back','request_id'=>$req],200);
  }catch(Throwable $e){if(is_dir($hold)&&!is_dir($active))rename($hold,$active);GOI_MC_Storage::unlock();GOI_MC_Audit::log($req,'deployment.explicit_rollback',$release,'error',['error'=>$e->getMessage()]);return new WP_REST_Response(['ok'=>false,'error'=>'rollback_failed','request_id'=>$req],500);}
 }
 private static function approved(WP_REST_Request $r):bool{return (bool)$r->get_header('X-GOI-Approval');}
 public static function audit(WP_REST_Request $r):WP_REST_Response{global $wpdb;$rows=$wpdb->get_results("SELECT request_id,event,release_id,actor,outcome,details,created_at FROM {$wpdb->prefix}goi_mc_audit ORDER BY id DESC LIMIT 100",ARRAY_A);return new WP_REST_Response(['ok'=>true,'items'=>$rows],200);}
}
