<?php
defined('ABSPATH') || exit;

final class GOI_Core_Ingestion_Worker {
 public static function register(): void { add_action('rest_api_init',[self::class,'routes']); }
 public static function routes(): void {
  register_rest_route(GOI_Core_REST::NS,'/sources/run/(?P<id>\d+)/execute',[
   'methods'=>WP_REST_Server::CREATABLE,'callback'=>[self::class,'execute'],'permission_callback'=>static function(): bool{return current_user_can('manage_options');},
  ]);
 }
 public static function execute(WP_REST_Request $request): WP_REST_Response {
  global $wpdb; $t=GOI_Core_DB::tables(); $run_id=absint($request['id']);
  $run=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['data_source_runs']} WHERE id=%d LIMIT 1",$run_id),ARRAY_A);
  if(!$run) return new WP_REST_Response(['ok'=>false,'error'=>'run_not_found'],404);
  if($run['status']!=='running') return new WP_REST_Response(['ok'=>false,'error'=>'run_not_running'],409);
  $adapter=GOI_Core_Source_Registry::get((string)$wpdb->get_var($wpdb->prepare("SELECT name FROM {$t['data_sources']} WHERE id=%d",$run['source_id'])));
  if(!$adapter){
   $source_name=(string)$wpdb->get_var($wpdb->prepare("SELECT uuid FROM {$t['data_sources']} WHERE id=%d",$run['source_id']));
   $adapter=GOI_Core_Source_Registry::get($source_name);
  }
  if(!$adapter) return self::fail($run_id,'adapter_not_registered');
  $input=$request->get_json_params(); if(!is_array($input)) $input=[];
  $cursor=[]; if(!empty($run['cursor'])){$decoded=json_decode($run['cursor'],true);if(is_array($decoded))$cursor=$decoded;}
  $cursor=array_merge($cursor,$input);
  $max_pages=min(10,max(1,absint($input['max_pages']??1)));
  $seen=$created=$updated=$rejected=$errors=0; $next=$cursor;
  try {
   for($page=0;$page<$max_pages;$page++){
    $result=$adapter->fetch($next);
    $records=is_array($result['records']??null)?$result['records']:[];
    $seen+=count($records);
    foreach($records as $record){
     $normalized=$adapter->normalize($record);
     if(!$normalized){$rejected++;continue;}
     $payload=$normalized; $payload['source_id']=(int)$run['source_id']; $payload['source_record_id']=$normalized['source_record_id'];
     $req=new WP_REST_Request('POST','/goi/v1/procurement/ingest'); $req->set_body(wp_json_encode($payload));
     $response=GOI_Core_Procurement::ingest($req); $code=$response->get_status();
     if($code>=200&&$code<300){$body=$response->get_data();if(!empty($body['created']))$created++;else $updated++;}else{$rejected++;}
    }
    $next=is_array($result['cursor']??null)?$result['cursor']:[];
    self::update($run_id,$seen,$created,$updated,$rejected,$errors,$next);
    if(empty($result['cursor'])) break;
   }
   $status=empty($next)?'completed':'checkpointed';
   $now=current_time('mysql',true);
   $wpdb->update($t['data_source_runs'],['finished_at'=>$status==='completed'?$now:null,'status'=>$status,'updated_at'=>$now],['id'=>$run_id],['%s','%s','%s'],['%d']);
   return new WP_REST_Response(['ok'=>true,'run_id'=>$run_id,'status'=>$status,'records_seen'=>$seen,'records_created'=>$created,'records_updated'=>$updated,'records_rejected'=>$rejected,'cursor'=>$next],200);
  } catch(Throwable $e) {
   $errors++; self::update($run_id,$seen,$created,$updated,$rejected,$errors,$next,$e->getMessage());
   return self::fail($run_id,'ingestion_failed');
  }
 }
 private static function update(int $id,int $seen,int $created,int $updated,int $rejected,int $errors,array $cursor=[],string $summary=''): void {
  global $wpdb; $t=GOI_Core_DB::tables(); $wpdb->update($t['data_source_runs'],['records_seen'=>$seen,'records_created'=>$created,'records_updated'=>$updated,'records_rejected'=>$rejected,'error_count'=>$errors,'cursor'=>wp_json_encode($cursor),'error_summary'=>$summary,'updated_at'=>current_time('mysql',true)],['id'=>$id]);
 }
 private static function fail(int $id,string $error): WP_REST_Response {
  global $wpdb; $t=GOI_Core_DB::tables(); $wpdb->update($t['data_source_runs'],['status'=>'failed','finished_at'=>current_time('mysql',true),'error_summary'=>$error,'updated_at'=>current_time('mysql',true)],['id'=>$id]);
  return new WP_REST_Response(['ok'=>false,'error'=>$error,'run_id'=>$id],500);
 }
}