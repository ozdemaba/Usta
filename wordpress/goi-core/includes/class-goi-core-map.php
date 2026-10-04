<?php
/**
 * GOI Core map service.
 */
defined( 'ABSPATH' ) || exit;

final class GOI_Core_Map {
    public static function register(): void {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes(): void {
        register_rest_route(
            'goi/v1',
            '/map/config',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'config' ),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'goi/v1',
            '/map/viewport',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'viewport' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'west'  => array( 'required' => true, 'validate_callback' => array( __CLASS__, 'valid_lon' ) ),
                    'south' => array( 'required' => true, 'validate_callback' => array( __CLASS__, 'valid_lat' ) ),
                    'east'  => array( 'required' => true, 'validate_callback' => array( __CLASS__, 'valid_lon' ) ),
                    'north' => array( 'required' => true, 'validate_callback' => array( __CLASS__, 'valid_lat' ) ),
                    'limit' => array( 'default' => 500, 'sanitize_callback' => 'absint', 'validate_callback' => array( __CLASS__, 'valid_limit' ) ),
                ),
            )
        );
    }

    public static function config(): WP_REST_Response {
        return new WP_REST_Response(
            array(
                'ok'      => true,
                'provider' => 'maplibre',
                'projection' => 'web-mercator',
                'tiles'   => array(
                    'mode' => 'adapter',
                    'production_source_required' => true,
                    'uncontrolled_public_osm_dependency' => false,
                ),
                'layers'  => array( 'opportunities', 'value', 'industry', 'economic', 'trade', 'infrastructure', 'risk' ),
                'deadline_status' => array(
                    'ending_24h' => 'red',
                    'ending_1_3d' => 'orange',
                    'ending_4_7d' => 'yellow',
                    'active' => 'green',
                    'future' => 'blue',
                    'high_value' => 'purple',
                    'ai_recommended' => 'white',
                ),
            )
        );
    }

    public static function viewport( WP_REST_Request $request ): WP_REST_Response {
        $west = (float) $request->get_param( 'west' );
        $south = (float) $request->get_param( 'south' );
        $east = (float) $request->get_param( 'east' );
        $north = (float) $request->get_param( 'north' );
        $limit = min( 500, max( 1, (int) $request->get_param( 'limit' ) ) );

        global $wpdb;
        $table = GOI_Core_DB::table( 'opportunities' );
        $where = '1=1';
        $params = array();

        if ( $south <= $north ) {
            $where .= ' AND latitude BETWEEN %f AND %f';
            $params[] = $south;
            $params[] = $north;
        }
        if ( $west <= $east ) {
            $where .= ' AND longitude BETWEEN %f AND %f';
            $params[] = $west;
            $params[] = $east;
        }

        $sql = "SELECT id, uuid, title, opportunity_type, status, country_code, latitude, longitude, deadline_at, estimated_value, currency_code
                FROM {$table} WHERE {$where} ORDER BY deadline_at ASC LIMIT %d";
        $params[] = $limit;
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

        return new WP_REST_Response(
            array(
                'ok' => true,
                'bounds' => array( 'west' => $west, 'south' => $south, 'east' => $east, 'north' => $north ),
                'items' => array_map( array( __CLASS__, 'marker' ), $rows ?: array() ),
                'count' => count( $rows ?: array() ),
                'aggregation' => 'row-level-fallback',
            )
        );
    }

    private static function marker( array $row ): array {
        return array(
            'id' => (int) $row['id'],
            'uuid' => $row['uuid'],
            'title' => $row['title'],
            'type' => $row['opportunity_type'],
            'status' => $row['status'],
            'country_code' => $row['country_code'],
            'longitude' => (float) $row['longitude'],
            'latitude' => (float) $row['latitude'],
            'deadline_at' => $row['deadline_at'],
            'estimated_value' => $row['estimated_value'],
            'currency_code' => $row['currency_code'],
            'deadline_class' => self::deadline_class( $row['deadline_at'] ),
        );
    }

    private static function deadline_class( ?string $deadline ): string {
        if ( empty( $deadline ) ) {
            return 'active';
        }
        $seconds = strtotime( $deadline ) - time();
        if ( $seconds <= 86400 ) return 'ending_24h';
        if ( $seconds <= 259200 ) return 'ending_1_3d';
        if ( $seconds <= 604800 ) return 'ending_4_7d';
        if ( $seconds > 604800 ) return 'active';
        return 'active';
    }

    public static function valid_lat( $value ): bool { return is_numeric( $value ) && (float) $value >= -90 && (float) $value <= 90; }
    public static function valid_lon( $value ): bool { return is_numeric( $value ) && (float) $value >= -180 && (float) $value <= 180; }
    public static function valid_limit( $value ): bool { return is_numeric( $value ) && (int) $value >= 1 && (int) $value <= 500; }
}
