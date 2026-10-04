<?php
defined('ABSPATH') || exit;

final class GOI_MC_Release {
    public static function fetch_and_install(WP_REST_Request $request): WP_REST_Response {
        $req = GOI_MC_Security::request_id();
        $p = $request->get_json_params();
        if (!is_array($p)) return new WP_REST_Response(['ok'=>false,'error'=>'invalid_request','request_id'=>$req],400);
        $url = esc_url_raw((string)($p['manifest_url'] ?? ''));
        $sig = trim((string)($p['signature'] ?? ''));
        $archive_url = esc_url_raw((string)($p['archive_url'] ?? ''));
        $public_key = trim((string)($p['public_key'] ?? ''));
        if (!$url || !$archive_url || !$sig || !$public_key || !wp_http_validate_url($url) || !wp_http_validate_url($archive_url)) {
            return new WP_REST_Response(['ok'=>false,'error'=>'invalid_release_source','request_id'=>$req],400);
        }
        if (!GOI_MC_Security::https_only($url) || !GOI_MC_Security::https_only($archive_url)) {
            return new WP_REST_Response(['ok'=>false,'error'=>'https_required','request_id'=>$req],400);
        }
        $manifest_response = wp_safe_remote_get($url,['timeout'=>30,'redirection'=>2,'limit_response_size'=>262144]);
        if (is_wp_error($manifest_response)) return new WP_REST_Response(['ok'=>false,'error'=>'manifest_fetch_failed','request_id'=>$req],502);
        $manifest = json_decode((string)wp_remote_retrieve_body($manifest_response),true);
        if (!is_array($manifest) || !GOI_MC_Security::manifest($manifest)) return new WP_REST_Response(['ok'=>false,'error'=>'invalid_manifest','request_id'=>$req],400);
        if (!GOI_MC_Security::verify_manifest_signature(wp_json_encode($manifest),$sig,$public_key)) return new WP_REST_Response(['ok'=>false,'error'=>'invalid_manifest_signature','request_id'=>$req],403);
        $release = (string)$manifest['release_id'];
        if (GOI_MC_Storage::deployment($release)) return new WP_REST_Response(['ok'=>false,'error'=>'release_already_seen','request_id'=>$req],409);
        if (!GOI_MC_Storage::lock($release)) return new WP_REST_Response(['ok'=>false,'error'=>'deployment_locked','request_id'=>$req],409);
        $d=['release_id'=>$release,'version'=>(string)$manifest['version'],'build_id'=>(string)$manifest['build_id'],'state'=>'created','request_id'=>$req];
        GOI_MC_Storage::save_deployment($d);
        GOI_MC_Audit::log($req,'release.verified',$release,'ok',['manifest_url'=>$url]);
        $tmp = trailingslashit(WP_CONTENT_DIR).'goi-release-'.$release.'.zip';
        try {
            $r = wp_safe_remote_get($archive_url,['timeout'=>120,'redirection'=>2,'limit_response_size'=>GOI_MC_Storage::settings()['max_archive_bytes']]);
            if (is_wp_error($r)) throw new Exception('archive_fetch_failed');
            $body=(string)wp_remote_retrieve_body($r);
            if (strlen($body)>GOI_MC_Storage::settings()['max_archive_bytes']) throw new Exception('archive_too_large');
            if (!file_put_contents($tmp,$body,LOCK_EX)) throw new Exception('archive_write_failed');
            $expected=(string)($p['archive_sha256']??'');
            if (!preg_match('/^[a-f0-9]{64}$/',$expected) || !hash_equals($expected,hash_file('sha256',$tmp))) throw new Exception('archive_hash_mismatch');
            GOI_MC_Storage::transition($release,'backed_up',['source'=>'github_release']);
            $result=self::install_zip($tmp,$manifest,$req);
            @unlink($tmp);
            GOI_MC_Storage::unlock();
            return new WP_REST_Response($result,200);
        } catch (Throwable $e) {
            @unlink($tmp);
            GOI_MC_Storage::transition($release,'failed',['error'=>$e->getMessage()]);
            GOI_MC_Storage::unlock();
            GOI_MC_Audit::log($req,'release.install_failed',$release,'error',['error'=>$e->getMessage()]);
            return new WP_REST_Response(['ok'=>false,'error'=>'release_install_failed','request_id'=>$req],500);
        }
    }

    private static function install_zip(string $zip_path,array $manifest,string $req): array {
        if (!class_exists('ZipArchive')) throw new Exception('zip_extension_required');
        $release=(string)$manifest['release_id'];
        $plugin_dir=trailingslashit(WP_PLUGIN_DIR).'goi-core';
        $stage_root=trailingslashit(WP_CONTENT_DIR).'goi-release-stage/'.sanitize_file_name($release);
        $rollback_root=trailingslashit(WP_CONTENT_DIR).'goi-release-rollback/'.sanitize_file_name($release);
        wp_mkdir_p($stage_root); wp_mkdir_p(dirname($rollback_root));
        $zip=new ZipArchive();
        if ($zip->open($zip_path)!==true) throw new Exception('invalid_zip');
        $root='';
        for($i=0;$i<$zip->numFiles;$i++){
            $name=$zip->getNameIndex($i);
            if (!GOI_MC_Security::zip_entry($name)) { $zip->close(); throw new Exception('unsafe_archive_path'); }
            if ($i===0 && str_ends_with($name,'/')) $root=trim($name,'/').'/';
        }
        $zip->extractTo($stage_root); $zip->close();
        $candidate=$root ? trailingslashit($stage_root).trim($root,'/') : $stage_root;
        $plugin_file=trailingslashit($candidate).'goi-core.php';
        if (!is_file($plugin_file)) throw new Exception('goi_core_plugin_missing');
        $declared=hash('sha256',(string)file_get_contents($plugin_file));
        if (!isset($manifest['plugin_sha256']) || !hash_equals((string)$manifest['plugin_sha256'],$declared)) throw new Exception('plugin_hash_mismatch');
        if (!GOI_MC_Security::validate_plugin_header($plugin_file,(string)$manifest['version'])) throw new Exception('plugin_header_invalid');
        GOI_MC_Storage::transition($release,'staged',['stage'=>$candidate]);
        $was_active=is_plugin_active('goi-core/goi-core.php');
        if ($was_active) deactivate_plugins('goi-core/goi-core.php',true);
        if (is_dir($plugin_dir)) {
            if (!rename($plugin_dir,$rollback_root)) throw new Exception('active_directory_move_failed');
        }
        if (!rename($candidate,$plugin_dir)) {
            if (is_dir($rollback_root)) rename($rollback_root,$plugin_dir);
            throw new Exception('staged_directory_move_failed');
        }
        GOI_MC_Storage::transition($release,'installed',['active'=>$plugin_dir]);
        if ($was_active) {
            activate_plugin('goi-core/goi-core.php',true);
            if (!is_plugin_active('goi-core/goi-core.php')) throw new Exception('activation_failed');
        }
        GOI_MC_Storage::transition($release,'migrated');
        $health=GOI_MC_Core_Health::check();
        if (!$health['ok']) throw new Exception('health_check_failed');
        GOI_MC_Storage::transition($release,'health_checked',$health);
        $reg=GOI_MC_Core_Health::regression();
        if (!$reg['ok']) throw new Exception('regression_check_failed');
        GOI_MC_Storage::transition($release,'regression_checked',$reg);
        GOI_MC_Storage::transition($release,'completed');
        GOI_MC_Audit::log($req,'release.install_completed',$release,'ok',['was_active'=>$was_active]);
        return ['ok'=>true,'release_id'=>$release,'version'=>$manifest['version'],'state'=>'completed','request_id'=>$req];
    }
}
