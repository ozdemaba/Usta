<?php
defined('ABSPATH') || exit;
final class GOI_MC_Storage {
 public static function activate():void{
  global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$charset=$wpdb->get_charset_collate();
  dbDelta("CREATE TABLE {$wpdb->prefix}goi_mc_deployments (id bigint unsigned NOT NULL AUTO_INCREMENT, release_id varchar(190) NOT NULL, version varchar(64) NOT NULL, build_id varchar(190) NOT NULL, state varchar(32) NOT NULL, actor bigint unsigned NOT NULL DEFAULT 0, request_id varchar(64) NOT NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, details longtext NULL, PRIMARY KEY(id), UNIQUE KEY release_id(release_id), KEY state(state)) $charset;");
  dbDelta("CREATE TABLE {$wpdb->prefix}goi_mc_audit (id bigint unsigned NOT NULL AUTO_INCREMENT, request_id varchar(64) NOT NULL, event varchar(64) NOT NULL, release_id varchar(190) NULL, actor bigint unsigned NOT NULL DEFAULT 0, outcome varchar(32) NOT NULL, details longtext NULL, created_at datetime NOT NULL, PRIMARY KEY(id), KEY request_id(request_id), KEY release_id(release_id)) $charset;");
  if(!get_option('goi_mc_schema_version'))add_option('goi_mc_schema_version','1.2.0',false);
  if(!get_option('goi_mc_settings'))add_option('goi_mc_settings',['enabled'=>false,'paired'=>false,'secret_hash'=>'','workspace'=>'goi-core','max_write_bytes'=>1048576,'max_files'=>5000,'max_archive_bytes'=>52428800,'release_public_key'=>'','production_requires_approval'=>true],false);
 }
 public static function settings():array{return wp_parse_args((array)get_option('goi_mc_settings',[]),['enabled'=>false,'paired'=>false,'secret_hash'=>'','workspace'=>'goi-core','max_write_bytes'=>1048576,'max_files'=>5000,'max_archive_bytes'=>52428800,'production_requires_approval'=>true]);}
 public static function update_settings(array $v):bool{return update_option('goi_mc_settings',array_merge(self::settings(),$v),false);}
 public static function workspace():string{$s=self::settings();$base=trailingslashit(WP_CONTENT_DIR).'goi-workspace/';$name=sanitize_file_name($s['workspace']);if(!$name)$name='goi-core';wp_mkdir_p($base.$name);return wp_normalize_path($base.$name);}
 public static function lock(string $release):bool{
  $existing=get_option('goi_mc_deploy_lock',null);
  if(is_array($existing)&&isset($existing['time'])&&time()-(int)$existing['time']<900)return false;
  if($existing!==null)delete_option('goi_mc_deploy_lock');
  return add_option('goi_mc_deploy_lock',['release_id'=>$release,'time'=>time(),'request_id'=>GOI_MC_Security::request_id()],false);
 }
 public static function unlock():void{delete_option('goi_mc_deploy_lock');}
 public static function deployment(string $release):?array{global $wpdb;$t=$wpdb->prefix.'goi_mc_deployments';$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE release_id=%s",$release),ARRAY_A);return $r?:null;}
 public static function save_deployment(array $d):void{global $wpdb;$t=$wpdb->prefix.'goi_mc_deployments';$now=current_time('mysql',true);$wpdb->replace($t,['release_id'=>$d['release_id'],'version'=>$d['version'],'build_id'=>$d['build_id'],'state'=>$d['state'],'actor'=>get_current_user_id(),'request_id'=>$d['request_id'],'created_at'=>$d['created_at']??$now,'updated_at'=>$now,'details'=>wp_json_encode($d['details']??[])],['%s','%s','%s','%s','%d','%s','%s','%s']);}
 public static function transition(string $release,string $state,array $details=[],string $request_id=''):void{global $wpdb;$t=$wpdb->prefix.'goi_mc_deployments';$wpdb->update($t,['state'=>$state,'updated_at'=>current_time('mysql',true),'details'=>wp_json_encode($details)],['release_id'=>$release],['%s','%s','%s'],['%s']);}
 public static function audit(string $request,string $event,?string $release,string $outcome,array $details=[]):void{global $wpdb;$wpdb->insert($wpdb->prefix.'goi_mc_audit',['request_id'=>$request,'event'=>$event,'release_id'=>$release,'actor'=>get_current_user_id(),'outcome'=>$outcome,'details'=>wp_json_encode($details),'created_at'=>current_time('mysql',true)],['%s','%s','%s','%d','%s','%s','%s']);}
}
