<?php
// limits
// 0 = unlimited

$fs['duration'] = 1; 	// link expire after hours
$fs['links'] = 0; 	//max links per user

// path on domain where a symlink to share.php can be found
// this file must not be protected by basic AUTH since the plugin uses this functionality
// example: http://mydomain.com/share.php
$fs['downloadpath'] = 'https://domain.tld/noauthdir/share.php'; 

return $fs;

