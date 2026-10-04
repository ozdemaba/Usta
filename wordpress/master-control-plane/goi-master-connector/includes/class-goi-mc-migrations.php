<?php
defined('ABSPATH') || exit;
final class GOI_MC_Migrations {
 public static function run(string $target=''):array{
  $current=(string)get_option('goi_core_schema_version','0');$target=$target?:'1.0.0';
  if(version_compare($current,$target,'>='))return ['ok'=>true,'from'=>$current,'to'=>$current,'applied'=>[]];
  $applied=[];
  foreach(self::available() as $version=>$callback){
   if(version_compare($version,$current,'>')){
    call_user_func($callback);update_option('goi_core_schema_version',$version,false);$applied[]=$version;
   }
   if(version_compare($version,$target,'>='))break;
  }
  $final=(string)get_option('goi_core_schema_version','0');
  if(version_compare($final,$target,'<'))throw new Exception('migration_target_unreachable');
  return ['ok'=>true,'from'=>$current,'to'=>$final,'applied'=>$applied];
 }
 private static function available():array{return ['1.0.0'=>static function():void{if(!get_option('goi_core_schema_version'))add_option('goi_core_schema_version','1.0.0',false);}];}
}
