<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$f=$root.'/wordpress/goi-core/includes/class-goi-core-db.php';
if(!is_file($f)) throw new RuntimeException('db_missing');
$c=file_get_contents($f);
foreach(['country_metrics','trade_flows','official_name','income_group','metric_code','metric_year','reporter_country_id','partner_country_id','trade_flow','hs_version','hs_code','value_usd'] as $x) if(strpos($c,$x)===false) throw new RuntimeException('country_trade_schema_missing:'.$x);
$i=file_get_contents($root.'/wordpress/goi-core/includes/class-goi-core-install.php');
if(strpos($i,'migrate_1_6_0')===false) throw new RuntimeException('migration_missing');
echo "Country/trade schema contract passed.\n";
