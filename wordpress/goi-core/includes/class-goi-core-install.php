<?php
defined('ABSPATH') || exit;
final class GOI_Core_Install {
 public static function activate(): void {
  self::migrate_1_0_0(); self::migrate_1_1_0(); self::migrate_1_2_0(); self::migrate_1_3_0(); self::migrate_1_4_0(); self::migrate_1_5_0(); self::migrate_1_6_0();
  update_option('goi_core_version',GOI_CORE_VERSION,false); self::ensure_front_page();
 }
 public static function ensure_front_page(): void {
  $existing=(int)get_option('goi_intelligence_page_id',0);
  if($existing && get_post_status($existing)) return;
  $page=wp_insert_post(['post_title'=>'GOI Intelligence','post_name'=>'goi-intelligence','post_status'=>'publish','post_type'=>'page','post_content'=>'[goi_intelligence]'],true);
  if(!is_wp_error($page)) update_option('goi_intelligence_page_id',(int)$page,false);
 }
 public static function migrate_1_0_0(): void { $current=(string)get_option('goi_core_schema_version','0'); if(version_compare($current,'1.0.0','<')) update_option('goi_core_schema_version','1.0.0',false); }
 public static function migrate_1_1_0(): void { require_once GOI_CORE_DIR.'includes/class-goi-core-db.php'; GOI_Core_DB::install_schema(); update_option('goi_core_schema_version','1.1.0',false); }
 public static function migrate_1_2_0(): void { if(version_compare((string)get_option('goi_core_schema_version','0'),'1.2.0','>=')) return; require_once GOI_CORE_DIR.'includes/class-goi-core-db.php'; GOI_Core_DB::install_schema(); update_option('goi_core_schema_version','1.2.0',false); }
 public static function migrate_1_4_0(): void { if(version_compare((string)get_option('goi_core_schema_version','0'),'1.4.0','>=')) return; require_once GOI_CORE_DIR.'includes/class-goi-core-db.php'; GOI_Core_DB::install_schema(); update_option('goi_core_schema_version','1.4.0',false); }
 public static function migrate_1_5_0(): void { if(version_compare((string)get_option('goi_core_schema_version','0'),'1.5.0','>=')) return; require_once GOI_CORE_DIR.'includes/class-goi-core-db.php'; GOI_Core_DB::install_schema(); update_option('goi_core_schema_version','1.5.0',false); }
 public static function migrate_1_3_0(): void {
  if(version_compare((string)get_option('goi_core_schema_version','0'),'1.3.0','>=')) return;
  global $wpdb; require_once GOI_CORE_DIR.'includes/class-goi-core-db.php'; GOI_Core_DB::install_schema();
  $t=GOI_Core_DB::tables();
  $indexes=$wpdb->get_results($wpdb->prepare("SHOW INDEX FROM {$t['opportunities']} WHERE Key_name=%s",'source_external'));
  if($indexes) { $wpdb->query("ALTER TABLE {$t['opportunities']} DROP INDEX source_external"); }
  update_option('goi_core_schema_version','1.3.0',false);
 }
 public static function migrate_1_6_0(): void { if(version_compare((string)get_option('goi_core_schema_version','0'),'1.6.0','>=')) return; require_once GOI_CORE_DIR.'includes/class-goi-core-db.php'; GOI_Core_DB::install_schema(); update_option('goi_core_schema_version','1.6.0',false); }
}
