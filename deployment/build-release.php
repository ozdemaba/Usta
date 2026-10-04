<?php
declare(strict_types=1);
$tag=getenv('RELEASE_ID')?:'dev-'.getenv('GITHUB_SHA');
$build=getenv('GITHUB_SHA')?:'unknown';
$core=__DIR__.'/../wordpress/goi-core';
$plugin=$core.'/goi-core.php';
$header=file_get_contents($plugin);
if(!preg_match('/^\\s*\\*?\\s*Version:\\s*([^\\s]+)\\s*$/mi',$header,$m))throw new RuntimeException('GOI Core version not found');
$version=$m[1];
$out=__DIR__.'/../dist';if(!is_dir($out))mkdir($out,0775,true);
$zipPath=$out.'/goi-core-'.$version.'-'.$tag.'.zip';$zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('zip_open_failed');
$files=[];$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($core,FilesystemIterator::SKIP_DOTS));
foreach($it as $file){if(!$file->isFile())continue;$rel=str_replace('\\\\','/',substr($file->getPathname(),strlen($core)+1));$data=file_get_contents($file->getPathname());$zip->addFromString('goi-core/'.$rel,$data);$files[]=['path'=>$rel,'sha256'=>hash('sha256',$data),'size'=>strlen($data)];}
$zip->close();
$manifest=['release_id'=>$tag,'version'=>$version,'build_id'=>$build,'schema_version'=>'1.0.0','archive_sha256'=>hash_file('sha256',$zipPath),'plugin_sha256'=>hash_file('sha256',$plugin),'files'=>$files,'tests'=>['php-lint','manifest-integrity'],'migrations'=>[],'minimum_php'=>'8.1','minimum_wordpress'=>'6.4','rollback_release'=>null];
$json=json_encode($manifest,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
file_put_contents($out.'/release-manifest.json',$json."\n");
$key=getenv('GOI_RELEASE_PRIVATE_KEY');
if(!$key)throw new RuntimeException('GOI_RELEASE_PRIVATE_KEY missing');
$private=base64_decode($key,true);if(!is_string($private)||strlen($private)!==SODIUM_CRYPTO_SIGN_SECRETKEYBYTES)throw new RuntimeException('invalid signing key');
$sig=sodium_crypto_sign_detached($json,$private);file_put_contents($out.'/release-manifest.sig',base64_encode($sig)."\n");
