<?php
$root=dirname(__DIR__,2).'/wordpress/goi-core';
foreach(['includes/class-goi-core-geo.php','includes/class-goi-core-map-aggregation.php'] as $f) if(!is_file($root.'/'.$f)) throw new RuntimeException('missing_'.$f);
$geo=file_get_contents($root.'/includes/class-goi-core-geo.php'); $agg=file_get_contents($root.'/includes/class-goi-core-map-aggregation.php');
foreach(['goi/v1','/geo/countries','/geo/regions','iso2','iso3'] as $x) if(strpos($geo,$x)===false) throw new RuntimeException('geo_'.$x);
foreach(['by_country','ending_24h_count','ending_7d_count','estimated_value'] as $x) if(strpos($agg,$x)===false) throw new RuntimeException('agg_'.$x);
echo "GOI geographic contract passed.
";
