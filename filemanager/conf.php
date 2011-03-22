<?php

$fm['tempdir'] = '/tmp';		// path were to store temporary data ; must be writable 
$fm['mkdperm'] = 755; 		// default permission to set to new created directories

$fm['rar'] = 'rar';
$fm['zip'] = 'zip';
$fm['unzip'] = 'unzip';

// path on domain where a symlink to view.php can be found
// change only if you use web AUTH
// example: http://mydomain.com/stream/view.php
$fm['stpath'] = 'plugins/filemanager/view.php'; 


// archive mangling, see archiver man page before editing

$fm['archive']['types'] = array('rar', 'zip');

$fm['archive']['compress'][0] = range(0, 5);
$fm['archive']['compress'][1] = array('-0', '-1', '-9');






?>