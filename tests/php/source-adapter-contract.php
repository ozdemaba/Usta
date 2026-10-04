<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
foreach(['class-goi-core-source-adapter.php','class-goi-core-source-adapters.php'] as $f){
 if(!is_file($root.'/wordpress/goi-core/includes/'.$f)) throw new RuntimeException('missing_adapter_file:'.$f);
}
$a=file_get_contents($root.'/wordpress/goi-core/includes/class-goi-core-source-adapter.php');
$b=file_get_contents($root.'/wordpress/goi-core/includes/class-goi-core-source-adapters.php');
foreach(['GOI_Core_Source_Adapter','manifest','fetch','normalize','GOI_Core_Source_Registry'] as $x) if(strpos($a,$x)===false) throw new RuntimeException('missing_adapter_contract:'.$x);
foreach(['ted_eu','sam_gov','austender','uk_find_tender','wp_safe_remote_request','https_required'] as $x) if(strpos($b,$x)===false) throw new RuntimeException('missing_source_guard:'.$x);
echo "GOI source adapter contract passed.\n";