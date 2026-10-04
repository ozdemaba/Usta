<?php
defined('ABSPATH') || exit;
final class GOI_Core_Sources {
 public static function register(): void { add_action('rest_api_init',[self::class,'routes']); }
 public static function routes(): void {
  register_rest_route(GOI_Core_REST::NS,'/sources',[
   'methods'=>WP_REST_Server::READABLE,'callback'=>[self::class,'list'],'permission_callback'=>static function(): bool{return current_user_can('manage_options');},
  ]);
  register_rest_route(GOI_Core_REST::NS,'/sources/runs',[
   'methods'=>WP_REST_Server::READABLE,'callback'=>[self::class,'runs'],'permission_callback'=>static function(): bool{return current_user_can('manage_options');},
  ]);
  register_rest_route(GOI_Core_REST::NS,'/sources/run',[
   'methods'=>WP_REST_Server::CREATABLE,'callback'=>[self::class,'start_run'],'permission_callback'=>static function(): bool{return current_user_can('manage_options');},
  ]);
 }
 public static function list(): WP_REST_Response {
  global $wpdb; $t=GOI_Core_DB::tables();
  $rows=$wpdb->get_results("SELECT id,uuid,name,owner_name,source_type,country_code,base_url,licence,reliability,status,last_sync_at,created_at,updated_at FROM {$t['data_sources']} ORDER BY name ASC",ARRAY_A);
  return new WP_REST_Response(['ok'=>true,'count'=>count($rows),'items'=>$rows],200);
 }
 public static function runs(WP_REST_Request $request): WP_REST_Response {
  global $wpdb; $t=GOI_Core_DB::tables(); $source=absint($request->get_param('source_id')); $limit=min(100,max(1,absint($request->get_param('limit')?:25)));
  $where=''; $args=[];
  if($source){$where=' WHERE source_id=%d';$args[]=$source;}
  $args[]=$limit;
  $rows=$wpdb->get_results($wpdb->prepare("SELECT id,source_id,run_uuid,started_at,finished_at,status,records_seen,records_created,records_updated,records_rejected,error_count,cursor,error_summary FROM {$t['data_source_runs']}{$where} ORDER BY id DESC LIMIT %d",$args),ARRAY_A);
  return new WP_REST_Response(['ok'=>true,'count'=>count($rows),'items'=>$rows],200);
 }
 public static function start_run(WP_REST_Request $request): WP_REST_Response {
  global $wpdb; $t=GOI_Core_DB::tables(); $source=absint($request->get_param('source_id'));
  if(!$source || !(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['data_sources']} WHERE id=%d AND status='active'",$source))) return new WP_REST_Response(['ok'=>false,'error'=>'active_source_required'],400);
  $now=current_time('mysql',true); $uuid=wp_generate_uuid4();
  $ok=$wpdb->insert($t['data_source_runs'],['source_id'=>$source,'run_uuid'=>$uuid,'started_at'=>$now,'status'=>'running','created_at'=>$now,'updated_at'=>$now],['%d','%s','%s','%s','%s','%s']);
  if(!$ok) return new WP_REST_Response(['ok'=>false,'error'=>'run_create_failed'],500);
  return new WP_REST_Response(['ok'=>true,'run_id'=>(int)$wpdb->insert_id,'run_uuid'=>$uuid,'status'=>'running'],201);
 }
}
