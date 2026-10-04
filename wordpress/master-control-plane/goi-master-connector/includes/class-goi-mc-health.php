<?php
defined('ABSPATH') || exit;

final class GOI_MC_Core_Health {
    public static function check(): array {
        global $wpdb;
        $checks=[
            'wordpress_db'=>(bool)$wpdb->get_var('SELECT 1'),
            'core_plugin_file'=>is_file(trailingslashit(WP_PLUGIN_DIR).'goi-core/goi-core.php'),
            'core_version'=>(bool)get_option('goi_core_version',false),
            'core_schema'=>(bool)get_option('goi_core_schema_version',false),
            'rest_registered'=>self::rest_registered(),
        ];
        return ['ok'=>!in_array(false,$checks,true),'checks'=>$checks];
    }
    public static function regression(): array {
        $health=self::check();
        $tests=[
            'plugin_directory'=>is_dir(trailingslashit(WP_PLUGIN_DIR).'goi-core'),
            'connector_loaded'=>defined('GOI_MC_VERSION'),
            'rest_api_available'=>function_exists('register_rest_route'),
        ];
        $ok=$health['ok'] && !in_array(false,$tests,true);
        return ['ok'=>$ok,'tests'=>$tests];
    }
    private static function rest_registered(): bool {
        if (!function_exists('rest_get_server')) return false;
        $routes=rest_get_server()->get_routes();
        return isset($routes['/goi/v1/status']);
    }
}
