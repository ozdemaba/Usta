<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$f=$root.'/wordpress/goi-core/includes/class-goi-core-source-adapters.php';
if(!is_file($f)) throw new RuntimeException('ted_adapter_missing');
$c=file_get_contents($f);
foreach(['api.ted.europa.eu/v3/notices/search','paginationMode','iterationNextToken','publication-number','notice-title','place-of-performance-country','estimated-value','map_notice'] as $x) if(strpos($c,$x)===false) throw new RuntimeException('ted_contract_missing:'.$x);
echo "TED adapter contract passed.\n";