<?php
defined('ABSPATH') || exit;

final class GOI_Core_Source_Adapters {
 public static function register(): void {
  GOI_Core_Source_Registry::register(new GOI_Core_TED_Adapter());
  GOI_Core_Source_Registry::register(new GOI_Core_SAM_Adapter());
  GOI_Core_Source_Registry::register(new GOI_Core_AusTender_Adapter());
  GOI_Core_Source_Registry::register(new GOI_Core_UK_FindTender_Adapter());
 }
}

abstract class GOI_Core_HTTP_Adapter implements GOI_Core_Source_Adapter {
 protected function request(string $url,array $args=[]): array {
  $parts=wp_parse_url($url);
  if(!$parts || empty($parts['scheme']) || strtolower($parts['scheme'])!=='https' || empty($parts['host'])) throw new RuntimeException('adapter_https_required');
  $host=strtolower($parts['host']);
  if(filter_var($host,FILTER_VALIDATE_IP)) throw new RuntimeException('adapter_ip_host_rejected');
  $args=wp_parse_args($args,['timeout'=>30,'redirection'=>3,'reject_unsafe_urls'=>true]);
  $r=wp_safe_remote_request($url,$args);
  if(is_wp_error($r)) throw new RuntimeException('adapter_http_error:'.$r->get_error_code());
  $code=(int)wp_remote_retrieve_response_code($r);
  if($code<200||$code>=300) throw new RuntimeException('adapter_http_status:'.$code);
  return ['status'=>$code,'headers'=>wp_remote_retrieve_headers($r),'body'=>wp_remote_retrieve_body($r)];
 }
 protected function canonical(array $r,string $source): ?array {
  $title=isset($r['title'])?sanitize_text_field((string)$r['title']):'';
  $id=isset($r['id'])?sanitize_text_field((string)$r['id']):'';
  if($id===''||$title==='') return null;
  return ['source'=>$source,'source_record_id'=>$id,'title'=>$title,'description'=>isset($r['description'])?wp_kses_post((string)$r['description']):null,'country_code'=>isset($r['country_code'])?strtoupper(sanitize_text_field((string)$r['country_code'])):null,'currency_code'=>isset($r['currency_code'])?strtoupper(sanitize_text_field((string)$r['currency_code'])):null,'deadline_at'=>isset($r['deadline_at'])?sanitize_text_field((string)$r['deadline_at']):null,'url'=>isset($r['url'])?esc_url_raw((string)$r['url']):null,'raw'=>$r];
 }
 public function normalize(array $record): ?array { return $this->canonical($record,$this->id()); }
}

final class GOI_Core_TED_Adapter extends GOI_Core_HTTP_Adapter {
 public function id(): string{return 'ted_eu';}
 public function manifest(): array{return ['name'=>'TED EU Search API','owner'=>'European Union Publications Office','country_code'=>null,'type'=>'api','auth'=>'public_search','licence'=>'TED data reuse terms','endpoint'=>'https://api.ted.europa.eu/','coverage'=>'EU procurement notices','status'=>'verified-source'];}
 public function fetch(array $cursor=[]): array {
  $query=isset($cursor['query'])?(string)$cursor['query']:'';
  if($query==='') return ['records'=>[],'cursor'=>null,'source'=>$this->id(),'requires_query'=>true];
  $mode=isset($cursor['paginationMode'])&&$cursor['paginationMode']==='PAGE_NUMBER'?'PAGE_NUMBER':'ITERATION';
  $body=['query'=>$query,'fields'=>['publication-number','notice-title','buyer-name','form-type','place-of-performance-country','deadline-date-lot','estimated-value','estimated-value-cur','dispatch-date'],'limit'=>min(250,max(1,(int)($cursor['limit']??250))),'paginationMode'=>$mode];
  if($mode==='PAGE_NUMBER') $body['page']=max(1,(int)($cursor['page']??1));
  elseif(!empty($cursor['iterationNextToken'])) $body['iterationNextToken']=(string)$cursor['iterationNextToken'];
  $r=$this->request('https://api.ted.europa.eu/v3/notices/search',['method'=>'POST','headers'=>['Accept'=>'application/json','Content-Type'=>'application/json'],'body'=>wp_json_encode($body),'timeout'=>60]);
  $decoded=json_decode($r['body'],true);
  if(!is_array($decoded)) throw new RuntimeException('ted_invalid_json');
  $items=$decoded['notices']??$decoded['results']??$decoded['data']??[];
  if(!is_array($items)) $items=[];
  $records=[]; foreach($items as $item){ if(is_array($item)){$n=$this->map_notice($item);if($n!==null)$records[]=$n;} }
  $next=$decoded['iterationNextToken']??$decoded['nextIterationToken']??null;
  return ['records'=>$records,'cursor'=>$next?['query'=>$query,'paginationMode'=>'ITERATION','iterationNextToken'=>(string)$next]:null,'source'=>$this->id(),'raw_count'=>count($items)];
 }
 public function normalize(array $record): ?array { return parent::normalize($record); }
 private function map_notice(array $n): ?array {
  $id=$this->pick($n,['publication-number','publicationNumber','publication_number','id']);
  $title=$this->pick($n,['notice-title','noticeTitle','title']);
  if($id===''||$title==='') return null;
  $country=$this->pick($n,['place-of-performance-country','placeOfPerformanceCountry','countryCode']);
  if(is_array($country)) $country=(string)($country[0]??'');
  $deadline=$this->pick($n,['deadline-date-lot','deadlineDateLot','deadlineDate','tenderPeriodEnd']);
  if(is_array($deadline)) $deadline=(string)($deadline[0]??'');
  $value=$this->pick($n,['estimated-value','estimatedValue','tenderValue']);
  if(is_array($value)) $value=$value[0]??null;
  $currency=$this->pick($n,['estimated-value-cur','estimatedValueCur','currency']);
  if(is_array($currency)) $currency=(string)($currency[0]??'');
  return ['id'=>(string)$id,'title'=>(string)$title,'description'=>(string)($this->pick($n,['description','noticeDescription'])??''),'country_code'=>(string)$country,'currency_code'=>(string)$currency,'deadline_at'=>(string)$deadline,'url'=>(string)($this->pick($n,['notice-url','noticeUrl','url'])??'')];
 }
 private function pick(array $row,array $keys) {
  foreach($keys as $key) if(array_key_exists($key,$row)&&$row[$key]!==null&&$row[$key]!=='') return $row[$key];
  return '';
 }
}
final class GOI_Core_SAM_Adapter extends GOI_Core_HTTP_Adapter {
 public function id(): string{return 'sam_gov';}
 public function manifest(): array{return ['name'=>'SAM.gov Contract Opportunities','owner'=>'U.S. General Services Administration','country_code'=>'US','type'=>'api','auth'=>'api_key','licence'=>'SAM.gov Terms of Use','endpoint'=>'https://api.sam.gov/opportunities/v2/search','coverage'=>'US federal contract opportunities','status'=>'verified-source'];}
 public function fetch(array $cursor=[]): array {
  $key=defined('GOI_SAM_GOV_API_KEY')?(string)GOI_SAM_GOV_API_KEY:(string)getenv('GOI_SAM_GOV_API_KEY');
  if($key==='') return ['records'=>[],'cursor'=>null,'source'=>$this->id(),'requires_credentials'=>true];
  $from=$this->date($cursor['postedFrom']??gmdate('m/d/Y',time()-6*86400));
  $to=$this->date($cursor['postedTo']??gmdate('m/d/Y'));
  $from_ts=DateTimeImmutable::createFromFormat('!m/d/Y',$from,new DateTimeZone('UTC')); $to_ts=DateTimeImmutable::createFromFormat('!m/d/Y',$to,new DateTimeZone('UTC'));
  if(!$from_ts||!$to_ts||$from_ts>$to_ts||($to_ts->getTimestamp()-$from_ts->getTimestamp())>365*86400) throw new RuntimeException('sam_invalid_date_window');
  $limit=min(1000,max(1,(int)($cursor['limit']??1000))); $offset=max(0,(int)($cursor['offset']??0));
  $params=['api_key'=>$key,'postedFrom'=>$from,'postedTo'=>$to,'limit'=>$limit,'offset'=>$offset];
  foreach(['ptype','solnum','noticeid','title','state','zip','organizationCode','organizationName','typeOfSetAside','typeOfSetAsideDescription','ncode','ccode','rdlfrom','rdlto','active'] as $k) if(isset($cursor[$k])&&$cursor[$k]!=='') $params[$k]=sanitize_text_field((string)$cursor[$k]);
  $url='https://api.sam.gov/opportunities/v2/search?'.http_build_query($params,'','&',PHP_QUERY_RFC3986);
  $r=$this->request($url,['method'=>'GET','headers'=>['Accept'=>'application/json'],'timeout'=>60]);
  $decoded=json_decode($r['body'],true); if(!is_array($decoded)) throw new RuntimeException('sam_invalid_json');
  $items=$decoded['opportunitiesData']??[]; if(!is_array($items)) $items=[];
  $records=[]; foreach($items as $item){if(is_array($item)){ $mapped=$this->map_notice($item); if($mapped!==null)$records[]=$mapped; }}
  $total=max(0,(int)($decoded['totalRecords']??0)); $next=$offset+$limit<$total?array_merge($cursor,['postedFrom'=>$from,'postedTo'=>$to,'limit'=>$limit,'offset'=>$offset+$limit]):null;
  return ['records'=>$records,'cursor'=>$next,'source'=>$this->id(),'raw_count'=>count($items),'total_records'=>$total];
 }
 private function date($value): string { $v=sanitize_text_field((string)$value); return preg_match('/^\\d{2}\\/\\d{2}\\/\\d{4}$/',$v)?$v:gmdate('m/d/Y'); }
 private function map_notice(array $n): ?array {
  $id=sanitize_text_field((string)($n['noticeId']??'')); $title=sanitize_text_field((string)($n['title']??'')); if($id===''||$title==='')return null;
  $country=$n['placeOfPerformance']['country']['code']??'US'; if(is_array($country))$country=$country['code']??'US'; $country=strtoupper((string)$country); if($country==='USA')$country='US';
  $deadline=(string)($n['responseDeadLine']??''); $url=(string)($n['uiLink']??''); if($url==='') {foreach((array)($n['links']??[]) as $link) if(is_array($link)&&!empty($link['href'])){$url=(string)$link['href'];break;}}
  return ['id'=>$id,'title'=>$title,'description'=>(string)($n['description']??''),'country_code'=>$country,'currency_code'=>'USD','deadline_at'=>$deadline,'url'=>$url];
 } 
}
final class GOI_Core_AusTender_Adapter extends GOI_Core_HTTP_Adapter {
 public function id(): string{return 'austender';}
 public function manifest(): array{return ['name'=>'AusTender','owner'=>'Australian Government','country_code'=>'AU','type'=>'dataset/api-adapter','auth'=>'public-data','licence'=>'Australian Government data reuse terms','endpoint'=>'https://www.tenders.gov.au/','coverage'=>'Australian Government procurement','status'=>'verified-source'];}
 public function fetch(array $cursor=[]): array { return ['records'=>[],'cursor'=>null,'source'=>$this->id(),'requires_feed_configuration'=>true]; }
}
final class GOI_Core_UK_FindTender_Adapter extends GOI_Core_HTTP_Adapter {
 public function id(): string{return 'uk_find_tender';}
 public function manifest(): array{return ['name'=>'Find a Tender','owner'=>'UK Government','country_code'=>'GB','type'=>'api','auth'=>'public/search-api','licence'=>'UK Government data reuse terms','endpoint'=>'https://www.find-tender.service.gov.uk/','coverage'=>'UK procurement notices','status'=>'verified-source'];}
 public function fetch(array $cursor=[]): array { return ['records'=>[],'cursor'=>null,'source'=>$this->id(),'requires_query'=>true]; }
}