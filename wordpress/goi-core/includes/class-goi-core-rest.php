<?php
defined('ABSPATH') || exit;
final class GOI_Core_REST {
 public const NS='goi/v1';
 public static function init(): void { add_action('rest_api_init',[self::class,'register']); }
 public static function register(): void {
  register_rest_route(self::NS,'/status',['methods'=>WP_REST_Server::READABLE,'callback'=>[self::class,'status'],'permission_callback'=>static function(): bool{return current_user_can('read');}]);
  register_rest_route(self::NS,'/schema',['methods'=>WP_REST_Server::READABLE,'callback'=>[self::class,'schema'],'permission_callback'=>static function(): bool{return current_user_can('manage_options');}]);
 }
 public static function status(WP_REST_Request $request): WP_REST_Response { return new WP_REST_Response(['ok'=>true,'product'=>'global-opportunity-intelligence','core_version'=>GOI_CORE_VERSION,'schema_version'=>(string)get_option('goi_core_schema_version','0'),'request_id'=>wp_generate_uuid4()],200); }
 public static function schema(WP_REST_Request $request): WP_REST_Response { return new WP_REST_Response(['ok'=>true,'schema_version'=>(string)get_option('goi_core_schema_version','0'),'tables'=>GOI_Core_DB::tables()],200); }
}
