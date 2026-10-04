<?php
defined('ABSPATH') || exit;
final class GOI_Core_UI {
 public static function init():void{
  add_action('admin_menu',[self::class,'admin_menu']);
  add_action('admin_enqueue_scripts',[self::class,'admin_assets']);
  add_action('wp_enqueue_scripts',[self::class,'front_assets']);
  add_shortcode('goi_intelligence',[self::class,'shortcode']);
 }
 public static function admin_menu():void{
  add_menu_page('GOI Command Centre','GOI Intelligence','manage_options','goi-command-centre',[self::class,'render_admin'],'dashicons-chart-area',3);
  $items=['goi-map'=>'World Map','goi-opportunities'=>'Opportunities','goi-countries'=>'Countries','goi-trade'=>'Trade Intelligence','goi-companies'=>'Companies','goi-agents'=>'AI Agents','goi-sources'=>'Data Sources','goi-settings'=>'Administration'];
  foreach($items as $slug=>$label) add_submenu_page('goi-command-centre',$label,$label,'manage_options',$slug,[self::class,'render_section']);
 }
 public static function admin_assets(string $hook):void{
  if(strpos($hook,'goi-')===false) return;
  wp_enqueue_style('goi-app',plugins_url('../assets/goi-app.css',__FILE__),[],GOI_CORE_VERSION);
  wp_enqueue_style('goi-map',plugins_url('../assets/goi-map.css',__FILE__),[],GOI_CORE_VERSION);
  wp_enqueue_script('goi-map',plugins_url('../assets/goi-map.js',__FILE__),[],GOI_CORE_VERSION,true);
  wp_enqueue_style('goi-map',plugins_url('../assets/goi-map.css',__FILE__),[],GOI_CORE_VERSION);
  wp_enqueue_script('goi-map',plugins_url('../assets/goi-map.js',__FILE__),[],GOI_CORE_VERSION,true);
  wp_enqueue_script('goi-app',plugins_url('../assets/goi-app.js',__FILE__),[],GOI_CORE_VERSION,true);
 }
 public static function front_assets():void{
  if(!is_singular()) return; global $post;
  if(!$post||!has_shortcode((string)$post->post_content,'goi_intelligence')) return;
  wp_enqueue_style('goi-app',plugins_url('../assets/goi-app.css',__FILE__),[],GOI_CORE_VERSION);
  wp_enqueue_script('goi-app',plugins_url('../assets/goi-app.js',__FILE__),[],GOI_CORE_VERSION,true);
 }
 public static function render_admin():void{self::shell('Command Centre','Live application shell. Data services and AI agents plug into these surfaces in subsequent phases.',true);}
 public static function render_section():void{
  $page=sanitize_key($_GET['page']??'goi-command-centre');
  $labels=['goi-map'=>'World Map','goi-opportunities'=>'Opportunities','goi-countries'=>'Countries','goi-trade'=>'Trade Intelligence','goi-companies'=>'Companies','goi-agents'=>'AI Agents','goi-sources'=>'Data Sources','goi-settings'=>'Administration'];
  self::shell($labels[$page]??'GOI Intelligence','Application module shell ready for its data and workflow services.',false);
 }
 private static function shell(string $title,string $subtitle,bool $dashboard):void{
  $schema=(string)get_option('goi_core_schema_version','0'); ?>
  <div class="goi-app"><aside class="goi-sidebar"><div class="goi-brand"><span class="goi-brand-mark">GO</span><div><strong>GOI</strong><small>Intelligence</small></div></div><nav>
  <?php foreach(['goi-command-centre'=>'⌂ Command Centre','goi-map'=>'◉ World Map','goi-opportunities'=>'▣ Opportunities','goi-countries'=>'◎ Countries','goi-trade'=>'↗ Trade Intelligence','goi-companies'=>'◆ Companies','goi-agents'=>'✦ AI Agents','goi-sources'=>'◌ Data Sources','goi-settings'=>'⚙ Administration'] as $slug=>$label): ?>
  <a class="<?php echo $slug===sanitize_key($_GET['page']??'goi-command-centre')?'active':'';?>" href="<?php echo esc_url(admin_url('admin.php?page='.$slug));?>"><?php echo esc_html($label);?></a>
  <?php endforeach;?></nav><div class="goi-sidebar-foot"><span class="goi-dot"></span> Core online<br><small>Schema <?php echo esc_html($schema);?></small></div></aside>
  <main class="goi-main"><header class="goi-topbar"><div><div class="goi-eyebrow">GLOBAL OPPORTUNITY INTELLIGENCE</div><h1><?php echo esc_html($title);?></h1><p><?php echo esc_html($subtitle);?></p></div><button class="goi-command">⌘ AI Command</button></header>
  <?php $dashboard?self::dashboard():self::module();?></main></div><?php
 }
 private static function dashboard():void{?>
  <section class="goi-grid goi-kpis"><div class="goi-card"><span>Tracked Opportunities</span><strong>—</strong><small>Awaiting ingestion</small></div><div class="goi-card"><span>Ending Soon</span><strong>—</strong><small>Deadline engine</small></div><div class="goi-card"><span>Countries</span><strong>—</strong><small>Country intelligence</small></div><div class="goi-card"><span>AI Agents</span><strong>—</strong><small>Agent registry</small></div></section>
  <section class="goi-grid goi-main-grid"><div class="goi-card goi-map-card"><div class="goi-card-head"><div><h2>Global Opportunity Map</h2><span>Map intelligence layer</span></div><span class="goi-badge">MAP ENGINE</span></div><div class="goi-map"><div class="goi-map__canvas" data-goi-map></div><div class="goi-map__empty">Approved map tile/style source required before live rendering.</div></div></div>
  <div class="goi-card"><div class="goi-card-head"><div><h2>AI Operations</h2><span>Master Agent status</span></div><span class="goi-live"><i></i> READY</span></div><?php foreach(['Master Intelligence Agent'=>'Mission orchestration surface','Evidence Engine'=>'Source-traceable intelligence','Data Quality Agent'=>'Validation and provenance'] as $a=>$d):?><div class="goi-agent"><span>✦</span><div><strong><?php echo esc_html($a);?></strong><small><?php echo esc_html($d);?></small></div><b>READY</b></div><?php endforeach;?></div></section>
  <section class="goi-card"><div class="goi-card-head"><div><h2>Opportunity Feed</h2><span>Live source feed will appear after ingestion is connected.</span></div><button class="goi-secondary">Configure Sources</button></div><div class="goi-empty">No opportunity records yet. Ingestion and source adapters will populate this workspace.</div></section><?php }
 private static function module():void{?><section class="goi-card goi-module"><div class="goi-card-head"><div><h2>Module workspace</h2><span>Interface foundation established</span></div><span class="goi-badge">PHASE 3</span></div><div class="goi-module-grid"><div><strong>Data</strong><small>Structured records and provenance</small></div><div><strong>AI</strong><small>Agent analysis and evidence</small></div><div><strong>Actions</strong><small>Workflows and approvals</small></div></div><div class="goi-empty">This surface is connected to the real application shell first. Functional data services will be added phase-by-phase.</div></section><?php }
 public static function shortcode():string{ob_start();?><div class="goi-app goi-front"><aside class="goi-sidebar"><div class="goi-brand"><span class="goi-brand-mark">GO</span><div><strong>GOI</strong><small>Intelligence</small></div></div><nav><a class="active" href="#">◉ Global Intelligence</a><a href="#opportunities">▣ Opportunities</a><a href="#countries">◎ Countries</a><a href="#trade">↗ Trade</a><a href="#companies">◆ Companies</a></nav><div class="goi-sidebar-foot"><span class="goi-dot"></span> Intelligence platform</div></aside><main class="goi-main"><header class="goi-topbar"><div><div class="goi-eyebrow">GLOBAL OPPORTUNITY INTELLIGENCE</div><h1>Global Intelligence</h1><p>Discover opportunities, markets, projects and companies across the world.</p></div><button class="goi-command">✦ Ask GOI AI</button></header><section class="goi-card goi-map-card goi-front-map"><div class="goi-card-head"><div><h2>World Opportunity Map</h2><span>Select a region to begin intelligence discovery</span></div><span class="goi-badge">GLOBAL</span></div><div class="goi-map"><div class="goi-map__canvas" data-goi-map></div><div class="goi-map__empty">Map engine ready. Configure an approved production tile/style source to render the world layer.</div></div></section><section class="goi-grid goi-kpis"><div class="goi-card"><span>Opportunities</span><strong>—</strong><small>Global index</small></div><div class="goi-card"><span>Ending Soon</span><strong>—</strong><small>Next deadlines</small></div><div class="goi-card"><span>Markets</span><strong>—</strong><small>Market intelligence</small></div><div class="goi-card"><span>Projects</span><strong>—</strong><small>Pipeline intelligence</small></div></section></main></div><?php return (string)ob_get_clean();}
}
