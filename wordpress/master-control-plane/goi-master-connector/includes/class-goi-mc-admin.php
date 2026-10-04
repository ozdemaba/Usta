<?php
defined('ABSPATH') || exit;
final class GOI_MC_Admin {
 public static function init():void{add_action('admin_menu',[__CLASS__,'menu']);}
 public static function menu():void{add_management_page('GOI Connector','GOI Connector','manage_options','goi-connector',[__CLASS__,'page']);}
 public static function page():void{$s=GOI_MC_Storage::settings();echo '<div class="wrap"><h1>GOI Master Connector '.esc_html(GOI_MC_VERSION).'</h1><p>Secure control plane for staged GOI releases.</p><table class="widefat"><tr><th>Enabled</th><td>'.esc_html($s['enabled']?'Yes':'No').'</td></tr><tr><th>Paired</th><td>'.esc_html($s['paired']?'Yes':'No').'</td></tr><tr><th>Workspace</th><td>'.esc_html($s['workspace']).'</td></tr><tr><th>Production approval</th><td>'.esc_html($s['production_requires_approval']?'Required':'Not required').'</td></tr></table></div>';}
}
