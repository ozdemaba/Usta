<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$f=$root.'/wordpress/goi-core/includes/class-goi-core-ingestion-worker.php';
if(!is_file($f)) throw new RuntimeException('ingestion_worker_missing');
$c=file_get_contents($f);
foreach(['data_source_runs','checkpointed','adapter_id_required','GOI_Core_Source_Registry','records_seen','records_created','records_updated','records_rejected'] as $x) if(strpos($c,$x)===false) throw new RuntimeException('worker_contract_missing:'.$x);
echo "GOI ingestion worker contract passed.\n";