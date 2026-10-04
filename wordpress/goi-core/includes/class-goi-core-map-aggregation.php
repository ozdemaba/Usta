<?php
if (!defined('ABSPATH')) { exit; }
final class GOI_Core_Map_Aggregation {
 public static function by_country(): array {
  global $wpdb; $o=$wpdb->prefix.'goi_opportunities'; $c=$wpdb->prefix.'goi_countries';
  $sql="SELECT c.iso2,c.iso3,c.name,COUNT(o.id) opportunity_count,
   SUM(CASE WHEN o.deadline_at IS NOT NULL AND o.deadline_at <= UTC_TIMESTAMP()+INTERVAL 24 HOUR AND o.deadline_at >= UTC_TIMESTAMP() THEN 1 ELSE 0 END) ending_24h_count,
   SUM(CASE WHEN o.deadline_at IS NOT NULL AND o.deadline_at <= UTC_TIMESTAMP()+INTERVAL 7 DAY AND o.deadline_at >= UTC_TIMESTAMP() THEN 1 ELSE 0 END) ending_7d_count,
   COALESCE(SUM(o.estimated_value),0) total_estimated_value
   FROM {$c} c LEFT JOIN {$o} o ON o.country_code=c.iso2 AND o.status IN ('open','active','published')
   WHERE c.status='active' GROUP BY c.id ORDER BY c.name ASC";
  return $wpdb->get_results($sql,ARRAY_A) ?: [];
 }
}
