<?php
defined('ABSPATH') || exit;
final class GOI_Core_Install {
 public static function activate(): void { self::migrate_1_0_0(); self::migrate_1_1_0(); update_option('goi_core_version',GOI_CORE_VERSION,false); }
 public static function migrate_1_0_0(): void { $current=(string)get_option('goi_core_schema_version','0'); if(version_compare($current,'1.0.0','<')) update_option('goi_core_schema_version','1.0.0',false); }
 public static function migrate_1_1_0(): void { require_once GOI_CORE_DIR.'includes/class-goi-core-db.php'; GOI_Core_DB::install_schema(); update_option('goi_core_schema_version','1.1.0',false); }
}
