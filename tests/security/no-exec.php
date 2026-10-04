<?php
declare(strict_types=1);
$root=$argv[1]??'wordpress/master-control-plane/goi-master-connector';
$forbidden=['shell_exec','exec','system','passthru','proc_open','popen'];$failed=false;
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
foreach($it as $file){
 if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;
 $tokens=token_get_all((string)file_get_contents($file->getPathname()));
 for($i=0,$n=count($tokens);$i<$n;$i++){
  if(!is_array($tokens[$i])||$tokens[$i][0]!==T_STRING)continue;$name=strtolower($tokens[$i][1]);if(!in_array($name,$forbidden,true))continue;
  for($j=$i+1;$j<$n;$j++){if(is_array($tokens[$j])&&in_array($tokens[$j][0],[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true))continue;if($tokens[$j]==='('){fwrite(STDERR,$file->getPathname().':'.$tokens[$i][2].' forbidden call '.$name."\n");$failed=true;}break;}
 }
}
exit($failed?1:0);
