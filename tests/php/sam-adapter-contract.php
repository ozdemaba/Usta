<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$f=$root.'/wordpress/goi-core/includes/class-goi-core-source-adapters.php';
if(!is_file($f)) throw new RuntimeException('sam_adapter_missing');
$c=file_get_contents($f);
foreach(['class GOI_Core_SAM_Adapter','api.sam.gov/opportunities/v2/search','GOI_SAM_GOV_API_KEY','postedFrom','postedTo','opportunitiesData','totalRecords','noticeId','responseDeadLine','placeOfPerformance','currency_code'] as $x) if(strpos($c,$x)===false) throw new RuntimeException('sam_contract_missing:'.$x);
echo "GOI SAM adapter contract passed.\n";