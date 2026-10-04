<?php
/**
 * Plugin Name: GOI Master Connector
 * Description: Secure deployment/control plane for Global Opportunity Intelligence.
 * Version: 1.2.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 */
defined('ABSPATH') || exit;
define('GOI_MC_VERSION','1.2.0');
define('GOI_MC_FILE',__FILE__);define('GOI_MC_DIR',plugin_dir_path(__FILE__));
foreach(['class-goi-mc-storage.php','class-goi-mc-security.php','class-goi-mc-audit.php','class-goi-mc-health.php','class-goi-mc-migrations.php','class-goi-mc-release.php','class-goi-mc-rest.php','class-goi-mc-admin.php'] as $file)require_once GOI_MC_DIR.'includes/'.$file;
register_activation_hook(__FILE__,['GOI_MC_Storage','activate']);
add_action('plugins_loaded',static function(){GOI_MC_Rest::init();GOI_MC_Admin::init();});
