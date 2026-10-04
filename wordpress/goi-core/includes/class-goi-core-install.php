<?php
defined('ABSPATH') || exit;

final class GOI_Core_Install {
    public static function activate(): void {
        $current=(string)get_option('goi_core_schema_version','0');
        if(version_compare($current,'1.0.0','<')){
            update_option('goi_core_schema_version','1.0.0',false);
        }
        update_option('goi_core_version',GOI_CORE_VERSION,false);
    }
}
