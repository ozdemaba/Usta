<?php
defined('ABSPATH') || exit;

final class GOI_Core_REST {
    public const NS='goi/v1';

    public static function init(): void {
        add_action('rest_api_init',[self::class,'register']);
    }

    public static function register(): void {
        register_rest_route(self::NS,'/status',[
            'methods'=>WP_REST_Server::READABLE,
            'callback'=>[self::class,'status'],
            'permission_callback'=>static function(): bool {
                return current_user_can('read');
            },
        ]);
    }

    public static function status(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response([
            'ok'=>true,
            'product'=>'global-opportunity-intelligence',
            'core_version'=>GOI_CORE_VERSION,
            'schema_version'=>(string)get_option('goi_core_schema_version','0'),
            'request_id'=>wp_generate_uuid4(),
        ],200);
    }
}
