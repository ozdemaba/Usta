<?php
/**
 * GOI Core geographic data contract.
 */
defined( 'ABSPATH' ) || exit;

final class GOI_Core_Map_Data {
    public static function country_features(): array {
        return array(
            'type' => 'FeatureCollection',
            'features' => array(),
            'properties_contract' => array(
                'iso2' => 'string',
                'iso3' => 'string',
                'name' => 'string',
                'opportunity_count' => 'integer',
                'ending_24h_count' => 'integer',
                'ending_7d_count' => 'integer',
                'total_estimated_value' => 'number',
                'opportunity_score' => 'number',
            ),
        );
    }
}
