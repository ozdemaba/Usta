<?php
defined('ABSPATH') || exit;

final class GOI_Core_Procurement {
 public static function register(): void { add_action('rest_api_init',[self::class,'routes']); }

 public static function routes(): void {
  register_rest_route(GOI_Core_REST::NS,'/procurement',[
   'methods'=>WP_REST_Server::READABLE,'callback'=>[self::class,'list'],'permission_callback'=>static function(): bool{return current_user_can('read');},
   'args'=>[
    'country'=>['required'=>false,'sanitize_callback'=>[self::class,'country']],
    'status'=>['required'=>false,'sanitize_callback'=>'sanitize_key'],
    'limit'=>['required'=>false,'default'=>25,'sanitize_callback'=>'absint'],
   ],
  ]);
  register_rest_route(GOI_Core_REST::NS,'/procurement/(?P<id>\d+)',[
   'methods'=>WP_REST_Server::READABLE,'callback'=>[self::class,'get'],'permission_callback'=>static function(): bool{return current_user_can('read');},
  ]);
  register_rest_route(GOI_Core_REST::NS,'/procurement/ingest',[
   'methods'=>WP_REST_Server::CREATABLE,'callback'=>[self::class,'ingest'],'permission_callback'=>static function(): bool{return current_user_can('manage_options');},
  ]);
 }

 public static function list(WP_REST_Request $request): WP_REST_Response {
  global $wpdb; $t=GOI_Core_DB::tables(); $limit=min(100,max(1,(int)$request->get_param('limit')));
  $where=["o.opportunity_type='procurement'"]; $args=[];
  $country=self::country($request->get_param('country'));
  if($country){$where[]='o.country_code=%s';$args[]=$country;}
  $status=sanitize_key((string)$request->get_param('status'));
  if($status){$where[]='o.status=%s';$args[]=$status;}
  $sql="SELECT o.id,o.uuid,o.title,o.status,o.country_code,o.published_at,o.deadline_at,o.estimated_value,o.currency_code,o.url,
  p.ocid,p.procurement_method,p.main_procurement_category,p.tender_status,p.tender_period_start,p.tender_period_end,p.tender_value,p.tender_currency
  FROM {$t['opportunities']} o LEFT JOIN {$t['procurement_records']} p ON p.opportunity_id=o.id
  WHERE ".implode(' AND ',$where)." ORDER BY COALESCE(o.deadline_at,'9999-12-31 23:59:59') ASC,o.id DESC LIMIT %d";
  $args[]=$limit; $rows=$wpdb->get_results($wpdb->prepare($sql,$args),ARRAY_A);
  return new WP_REST_Response(['ok'=>true,'count'=>count($rows),'items'=>$rows],200);
 }

 public static function get(WP_REST_Request $request): WP_REST_Response {
  global $wpdb; $t=GOI_Core_DB::tables(); $id=absint($request['id']);
  $sql=$wpdb->prepare("SELECT o.*,p.ocid,p.procurement_method,p.procurement_method_details,p.main_procurement_category,p.additional_procurement_categories,p.planning_status,p.tender_status,p.tender_title,p.tender_description,p.tender_date,p.tender_period_start,p.tender_period_end,p.tender_value,p.tender_currency,p.number_of_tenderers,p.award_status,p.contract_status,p.ocds_version FROM {$t['opportunities']} o LEFT JOIN {$t['procurement_records']} p ON p.opportunity_id=o.id WHERE o.id=%d LIMIT 1",$id);
  $row=$wpdb->get_row($sql,ARRAY_A);
  if(!$row) return new WP_REST_Response(['ok'=>false,'error'=>'not_found'],404);
  $awards=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['procurement_awards']} WHERE procurement_record_id=(SELECT id FROM {$t['procurement_records']} WHERE opportunity_id=%d) ORDER BY id ASC",$id),ARRAY_A);
  $contracts=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['procurement_contracts']} WHERE procurement_record_id=(SELECT id FROM {$t['procurement_records']} WHERE opportunity_id=%d) ORDER BY id ASC",$id),ARRAY_A);
  $documents=$wpdb->get_results($wpdb->prepare("SELECT id,document_id,url,title,document_type,format,language_code,date_published,content_hash,access_status FROM {$t['procurement_documents']} WHERE procurement_record_id=(SELECT id FROM {$t['procurement_records']} WHERE opportunity_id=%d) ORDER BY id ASC",$id),ARRAY_A);
  return new WP_REST_Response(['ok'=>true,'item'=>$row,'awards'=>$awards,'contracts'=>$contracts,'documents'=>$documents],200);
 }

 public static function ingest(WP_REST_Request $request): WP_REST_Response {
  $data=$request->get_json_params(); if(!is_array($data)) return new WP_REST_Response(['ok'=>false,'error'=>'invalid_json'],400);
  $source_id=absint($data['source_id']??0); $source_record_id=sanitize_text_field((string)($data['source_record_id']??'')); $title=sanitize_text_field((string)($data['title']??''));
  if(!$source_id || $source_record_id==='' || $title==='') return new WP_REST_Response(['ok'=>false,'error'=>'source_id_source_record_id_title_required'],400);
  global $wpdb; $t=GOI_Core_DB::tables(); $now=current_time('mysql',true); $country=self::country($data['country_code']??'');
  $canonical=hash('sha256',$source_id.'|'.$source_record_id);
  $existing=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['opportunities']} WHERE canonical_key=%s LIMIT 1",$canonical));
  $payload=wp_json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); $hash=hash('sha256',(string)$payload);
  $op=[
   'external_id'=>$source_record_id,'canonical_key'=>$canonical,'title'=>$title,'description'=>sanitize_textarea_field((string)($data['description']??'')),
   'opportunity_type'=>'procurement','status'=>sanitize_key((string)($data['status']??'open')),'country_code'=>$country?:null,
   'published_at'=>self::utc_datetime($data['published_at']??null),'deadline_at'=>self::utc_datetime($data['deadline_at']??null),
   'estimated_value'=>isset($data['estimated_value'])?(float)$data['estimated_value']:null,'currency_code'=>self::currency($data['currency_code']??''),
   'url'=>esc_url_raw((string)($data['url']??'')),'latitude'=>isset($data['latitude'])?(float)$data['latitude']:null,'longitude'=>isset($data['longitude'])?(float)$data['longitude']:null,
   'source_updated_at'=>self::utc_datetime($data['source_updated_at']??null),'first_seen_at'=>$now,'last_seen_at'=>$now,'content_hash'=>$hash,'metadata'=>$payload,'updated_at'=>$now,
  ];
  if($existing){
   $wpdb->update($t['opportunities'],$op,['id'=>$existing],null,['%d']); $opportunity_id=$existing;
  } else {
   $op['uuid']=wp_generate_uuid4();$op['created_at']=$now;
   $formats=['%s','%s','%s','%s','%s','%s','%s','%s','%s','%f','%s','%s','%f','%f','%s','%s','%s','%s','%s','%s','%s','%s','%s'];
   $wpdb->insert($t['opportunities'],$op,$formats); $opportunity_id=(int)$wpdb->insert_id;
  }
  if(!$opportunity_id) return new WP_REST_Response(['ok'=>false,'error'=>'opportunity_write_failed'],500);
  $source_row=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['opportunity_sources']} WHERE opportunity_id=%d AND source_id=%d LIMIT 1",$opportunity_id,$source_id));
  if($source_row){$wpdb->update($t['opportunity_sources'],['source_record_id'=>$source_record_id,'source_url'=>esc_url_raw((string)($data['url']??'')),'observed_at'=>$now,'raw_hash'=>$hash],['id'=>$source_row]);}
  else{$wpdb->insert($t['opportunity_sources'],['opportunity_id'=>$opportunity_id,'source_id'=>$source_id,'source_record_id'=>$source_record_id,'source_url'=>esc_url_raw((string)($data['url']??'')),'observed_at'=>$now,'raw_hash'=>$hash],['%d','%d','%s','%s','%s','%s']);}
  $wpdb->insert($t['opportunity_observations'],['opportunity_id'=>$opportunity_id,'source_id'=>$source_id,'source_record_id'=>$source_record_id,'observed_at'=>$now,'content_hash'=>$hash,'payload'=>$payload,'change_type'=>$existing?'updated':'created'],['%d','%d','%s','%s','%s','%s','%s']);
  $record_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['procurement_records']} WHERE opportunity_id=%d",$opportunity_id));
  $proc=[
   'opportunity_id'=>$opportunity_id,'ocid'=>sanitize_text_field((string)($data['ocid']??''))?:null,'procurement_method'=>sanitize_text_field((string)($data['procurement_method']??''))?:null,
   'procurement_method_details'=>sanitize_textarea_field((string)($data['procurement_method_details']??''))?:null,'main_procurement_category'=>sanitize_text_field((string)($data['main_procurement_category']??''))?:null,
   'additional_procurement_categories'=>wp_json_encode(array_values(array_filter((array)($data['additional_procurement_categories']??[]),'is_scalar'))),
   'planning_status'=>sanitize_key((string)($data['planning_status']??''))?:null,'tender_status'=>sanitize_key((string)($data['tender_status']??''))?:null,
   'tender_title'=>sanitize_text_field((string)($data['tender_title']??$title)),'tender_description'=>sanitize_textarea_field((string)($data['tender_description']??$data['description']??'')),
   'tender_date'=>self::utc_datetime($data['tender_date']??null),'tender_period_start'=>self::utc_datetime($data['tender_period_start']??null),'tender_period_end'=>self::utc_datetime($data['tender_period_end']??$data['deadline_at']??null),
   'tender_value'=>isset($data['tender_value'])?(float)$data['tender_value']:null,'tender_currency'=>self::currency($data['tender_currency']??$data['currency_code']??''),
   'number_of_tenderers'=>isset($data['number_of_tenderers'])?absint($data['number_of_tenderers']):null,'award_status'=>sanitize_key((string)($data['award_status']??''))?:null,
   'contract_status'=>sanitize_key((string)($data['contract_status']??''))?:null,'ocds_version'=>sanitize_text_field((string)($data['ocds_version']??'1.1')),'raw_payload_hash'=>$hash,'updated_at'=>$now
  ];
  if($record_id){$wpdb->update($t['procurement_records'],$proc,['id'=>$record_id]);}else{$proc['created_at']=$now;$wpdb->insert($t['procurement_records'],$proc);}
  return new WP_REST_Response(['ok'=>true,'opportunity_id'=>$opportunity_id,'created'=>$existing?false:true,'canonical_key'=>$canonical],$existing?200:201);
 }

 private static function country($value): string { $v=strtoupper(sanitize_text_field((string)$value)); return preg_match('/^[A-Z]{2}$/',$v)?$v:''; }
 private static function currency($value): ?string { $v=strtoupper(sanitize_text_field((string)$value)); return preg_match('/^[A-Z]{3}$/',$v)?$v:null; }
 private static function utc_datetime($value): ?string {
  if($value===null || $value==='') return null; $ts=strtotime((string)$value); return $ts===false?null:gmdate('Y-m-d H:i:s',$ts);
 }
}
