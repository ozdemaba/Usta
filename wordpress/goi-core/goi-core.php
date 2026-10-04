<?php
/**
 * Plugin Name: GOI Core
 * Description: Global Opportunity Intelligence application core.
 * Version: 0.4.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;
define('GOI_CORE_VERSION','0.4.0');
define('GOI_CORE_FILE',__FILE__);
define('GOI_CORE_DIR',plugin_dir_path(__FILE__));
require_once GOI_CORE_DIR.'includes/class-goi-core-db.php';
require_once GOI_CORE_DIR.'includes/class-goi-core-install.php';
require_once GOI_CORE_DIR.'includes/class-goi-core-rest.php';
require_once GOI_CORE_DIR.'includes/class-goi-core-map.php';
require_once GOI_CORE_DIR.'includes/class-goi-core-map-data.php';
require_once GOI_CORE_DIR.'includes/class-goi-core-geo.php';
require_once GOI_CORE_DIR.'includes/class-goi-core-map-aggregation.php';
require_once GOI_CORE_DIR.'includes/class-goi-core-ui.php';
register_activation_hook(__FILE__,['GOI_Core_Install','activate']);
add_action('plugins_loaded',static function(): void { GOI_Core_REST::init(); GOI_Core_Map::register(); GOI_Core_Geo::register(); GOI_Core_UI::init(); });
