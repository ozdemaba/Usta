<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$db=file_get_contents($root.'/wordpress/goi-core/includes/class-goi-core-db.php');
$core=file_get_contents($root.'/wordpress/goi-core/goi-core.php');
$install=file_get_contents($root.'/wordpress/goi-core/includes/class-goi-core-install.php');
$sources=file_get_contents($root.'/wordpress/goi-core/includes/class-goi-core-sources.php');
foreach(['data_source_runs','opportunity_observations','procurement_records'] as $x) if(strpos($db,"'$x'")===false) throw new RuntimeException("missing_table:$x");
foreach(['/sources','/sources/runs','/sources/run','run_uuid','records_rejected','error_count'] as $x) if(strpos($sources,$x)===false) throw new RuntimeException("missing_source_contract:$x");
if(strpos($core,"GOI_Core_Sources::register()")===false || strpos($core,"Version: 0.7.0")===false) throw new RuntimeException('core_0_6_0_missing');
if(strpos($install,'migrate_1_4_0')===false || strpos($install,"'1.4.0'")===false) throw new RuntimeException('migration_1_4_0_missing');
echo "GOI procurement source contract passed.\n";
