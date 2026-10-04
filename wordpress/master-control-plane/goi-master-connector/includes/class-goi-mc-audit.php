<?php
defined('ABSPATH') || exit;
final class GOI_MC_Audit {public static function log(string $req,string $event,?string $release,string $outcome,array $details=[]):void{GOI_MC_Storage::audit($req,$event,$release,$outcome,$details);}}
