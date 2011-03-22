<?php
require_once( '../../php/util.php' );
include('conf.php');


echo 'theWebUI.fManager.homedir = "', rtrim($topDirectory, '/'), '";',"\n";
echo 'theWebUI.fManager.mkdefmask = "', $fm['mkdperm'], '";',"\n";
echo 'theWebUI.fManager.archives = '.json_encode($fm['archive']).';',"\n";
echo 'theWebUI.fManager.streamer = '.json_encode($fm['stpath']).';',"\n";


$dis = array();

foreach(array('zip', 'rar', 'unzip') as $value) {if(empty($fm[$value])) {$dis[$value] = '';}}

echo 'theWebUI.fManager.disabledcmds=',json_encode($dis),";\n";
?>