<?php
declare(strict_types=1);

$root=dirname(__DIR__,2);
$db=$root.'/wordpress/goi-core/includes/class-goi-core-db.php';
$install=$root.'/wordpress/goi-core/includes/class-goi-core-install.php';
$core=$root.'/wordpress/goi-core/goi-core.php';

foreach([$db,$install,$core] as $file){
 if(!is_file($file)) throw new RuntimeException("missing_file: ".$file);
}
$dbText=file_get_contents($db);
$installText=file_get_contents($install);
$coreText=file_get_contents($core);
$tables=['organisations','countries','regions','data_sources','opportunities','opportunity_sources'];
foreach($tables as $table){
 if(strpos($dbText,"'".$table."'")===false) throw new RuntimeException("missing_table_registry: ".$table);
}
if(strpos($dbText,'dbDelta($statement)')===false) throw new RuntimeException('dbdelta_missing');
if(strpos($installText,'migrate_1_1_0')===false) throw new RuntimeException('migration_missing');
if(strpos($installText,"migrate_1_2_0")===false) throw new RuntimeException('map_schema_migration_missing');
if(strpos($installText,"'1.2.0'")===false) throw new RuntimeException('schema_version_missing');
if(strpos($coreText,"Version: 0.5.0")===false) throw new RuntimeException('core_version_missing');
echo "GOI Core schema contract passed.\n";
