<?php
defined('ABSPATH') || exit;
final class GOI_MC_Admin {
 public static function init():void{add_action('admin_menu',[__CLASS__,'menu']);add_action('admin_post_goi_mc_save_settings',[__CLASS__,'save']);}
 public static function menu():void{add_menu_page('GOI Master Connector','GOI Connector','manage_options','goi-master-connector',[__CLASS__,'page'],'dashicons-controls-repeat',58);}
 public static function page():void{
  if(!current_user_can('manage_options'))wp_die('Forbidden');$s=GOI_MC_Storage::settings();
  echo '<div class="wrap"><h1>GOI Master Connector</h1><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
  wp_nonce_field('goi_mc_settings');echo '<input type="hidden" name="action" value="goi_mc_save_settings">';
  echo '<table class="form-table"><tr><th>Enabled</th><td><label><input type="checkbox" name="enabled" value="1" '.checked($s['enabled'],true,false).'> Enable machine control</label></td></tr>';
  echo '<tr><th>Production approval</th><td><label><input type="checkbox" name="production_requires_approval" value="1" '.checked($s['production_requires_approval'],true,false).'> Require administrator approval</label></td></tr>';
  echo '<tr><th>Release signing public key</th><td><input class="regular-text code" name="release_public_key" value="'.esc_attr($s['release_public_key']).'" autocomplete="off"><p class="description">Ed25519 public key, base64 encoded. This key is trusted for release manifest signatures.</p></td></tr></table>';
  submit_button('Save connector settings');echo '</form><h2>Runtime</h2><p>Version: <code>'.esc_html(GOI_MC_VERSION).'</code> &nbsp; Workspace: <code>'.esc_html($s['workspace']).'</code> &nbsp; Signed releases: <strong>'.(function_exists('sodium_crypto_sign_verify_detached')?'Available':'Unavailable').'</strong></p></div>';
 }
 public static function save():void{
  if(!current_user_can('manage_options'))wp_die('Forbidden');check_admin_referer('goi_mc_settings');
  $key=preg_replace('/\s+/','',(string)($_POST['release_public_key']??''));
  if($key!==''&&(!function_exists('sodium_crypto_sign_verify_detached')||!defined('SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES')||!is_string(base64_decode($key,true))||strlen(base64_decode($key,true))!==SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES))wp_die('Invalid Ed25519 public key');
  GOI_MC_Storage::update_settings(['enabled'=>isset($_POST['enabled']),'production_requires_approval'=>isset($_POST['production_requires_approval']),'release_public_key'=>$key]);
  wp_safe_redirect(admin_url('admin.php?page=goi-master-connector&updated=1'));exit;
 }
}
