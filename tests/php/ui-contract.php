<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$required=[
 'wordpress/goi-core/includes/class-goi-core-ui.php',
 'wordpress/goi-core/assets/goi-app.css',
 'wordpress/goi-core/assets/goi-app.js',
];
foreach($required as $file){if(!is_file($root.'/'.$file)) throw new RuntimeException("Missing UI asset: ".$file);}
$plugin=file_get_contents($root.'/wordpress/goi-core/goi-core.php');
foreach(["class-goi-core-ui.php","GOI_Core_UI::init()","Version: 0.7.0","GOI_CORE_VERSION','0.6.0"] as $needle){
 if(strpos($plugin,$needle)===false) throw new RuntimeException("UI integration contract failed: ".$needle);
}
$ui=file_get_contents($root.'/wordpress/goi-core/includes/class-goi-core-ui.php');
foreach(["add_menu_page","add_shortcode","goi_intelligence","manage_options","wp_enqueue_style","wp_enqueue_script"] as $needle){
 if(strpos($ui,$needle)===false) throw new RuntimeException("UI contract failed: ".$needle);
}
echo "GOI application UI contract passed.\n";
