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
 public function fetch(array $cursor=[]): array { return ['records'=>[],'cursor'=>null,'source'=>$this->id(),'requires_query'=>true]; }
}
final class GOI_Core_SAM_Adapter extends GOI_Core_HTTP_Adapter {
 public function id(): string{return 'sam_gov';}
 public function manifest(): array{return ['name'=>'SAM.gov Contract Opportunities','owner'=>'U.S. General Services Administration','country_code'=>'US','type'=>'api','auth'=>'api_key','licence'=>'SAM.gov Terms of Use','endpoint'=>'https://sam.gov/','coverage'=>'US federal contract opportunities','status'=>'verified-source'];}
 public function fetch(array $cursor=[]): array { return ['records'=>[],'cursor'=>null,'source'=>$this->id(),'requires_credentials'=>true]; }
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