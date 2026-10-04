<?php
if (!defined('ABSPATH')) { exit; }
final class GOI_Core_Geo {
 public static function register(): void { add_action('rest_api_init', [__CLASS__, 'routes']); }
 public static function routes(): void {
  register_rest_route('goi/v1','/geo/countries',['methods'=>'GET','permission_callback'=>'__return_true','callback'=>[__CLASS__,'countries']]);
  register_rest_route('goi/v1','/geo/regions',['methods'=>'GET','permission_callback'=>'__return_true','callback'=>[__CLASS__,'regions']]);
 }
 public static function countries(WP_REST_Request $request) {
  global $wpdb; $t=$wpdb->prefix.'goi_countries';
  $rows=$wpdb->get_results("SELECT iso2,iso3,name,latitude,longitude,status FROM {$t} WHERE status='active' ORDER BY name ASC",ARRAY_A);
  return new WP_REST_Response(['ok'=>true,'items'=>$rows,'count'=>count($rows)],200);
 }
 public static function regions(WP_REST_Request $request) {
  global $wpdb; $country=sanitize_text_field((string)$request->get_param('country')); $t=$wpdb->prefix.'goi_regions';
  if($country!=='' && !preg_match('/^[A-Z]{2}$/',$country)) return new WP_Error('invalid_country','Country must be ISO-2.',['status'=>400]);
  $sql="SELECT r.id,r.uuid,r.name,r.region_type,r.latitude,r.longitude,c.iso2 country_code FROM {$t} r INNER JOIN {$wpdb->prefix}goi_countries c ON c.id=r.country_id WHERE c.status='active'";
  if($country!=='') $sql.=$wpdb->prepare(' AND c.iso2=%s',$country); $sql.=' ORDER BY r.name ASC';
  $rows=$wpdb->get_results($sql,ARRAY_A); return new WP_REST_Response(['ok'=>true,'items'=>$rows,'count'=>count($rows)],200);
 }
}
