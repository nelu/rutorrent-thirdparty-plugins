<?php
include('conf.php');

header('Content-Type: text/javascript; charset=UTF-8');

echo 'theWebUI.FS.maxlinks = "',$limits['links'], "\";\n";
echo 'theWebUI.FS.downlink = "',$downloadpath, "\";\n";
echo 'theWebUI.FS.maxdur = "',$limits['duration'], "\";\n";
echo 'theWebUI.FS.nolimit = "',$limits['nolimit'], "\";\n";
