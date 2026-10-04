<?php
declare(strict_types=1);

$root=dirname(__DIR__,2);
$files=[
 $root.'/wordpress/goi-core/includes/class-goi-core-procurement.php',
 $root.'/wordpress/goi-core/includes/class-goi-core-db.php',
 $root.'/wordpress/goi-core/includes/class-goi-core-install.php',
 $root.'/wordpress/goi-core/goi-core.php',
];
foreach($files as $file){if(!is_file($file)) throw new RuntimeException('missing_file: '.$file);}
$db=file_get_contents($files[1]); $proc=file_get_contents($files[0]); $install=file_get_contents($files[2]); $core=file_get_contents($files[3]);
foreach(['opportunity_observations','procurement_records','procurement_awards','procurement_contracts','procurement_documents'] as $table){ if(strpos($db,"'".$table."'")===false) throw new RuntimeException('missing_table: '.$table); }
foreach(['canonical_key','ocid','tender_period_end','award_status','contract_status','raw_payload_hash'] as $token){ if(strpos($db,$token)===false) throw new RuntimeException('missing_schema_token: '.$token); }
foreach(['/procurement','/procurement/(?P<id>\\d+)','/procurement/ingest','source_id_source_record_id_title_required','canonical_key'] as $token){ if(strpos($proc,$token)===false) throw new RuntimeException('missing_procurement_contract: '.$token); }
if(strpos($install,'migrate_1_3_0')===false || strpos($install,"'1.3.0'")===false) throw new RuntimeException('migration_1_3_0_missing');
if(strpos($core,"Version: 0.6.0")===false || strpos($core,"GOI_Core_Procurement::register()")===false) throw new RuntimeException('core_0_6_0_integration_missing');
echo "GOI procurement contract passed.\n";
