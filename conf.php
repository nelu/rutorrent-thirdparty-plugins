<?php

$fm['tempdir'] = '/tmp';		// path were to store temporary data ; must be writable 
$fm['mkdperm'] = 755; 		// default permission to set to new created directories

$fm['rar'] = 'rar';
$fm['zip'] = 'zip';
$fm['unzip'] = 'unzip';

// archive mangling, see archiver man page before editing

$fm['archive']['types'] = array('rar', 'zip');

$fm['archive']['compress'][0] = range(0, 5);
$fm['archive']['compress'][1] = array('-0', '-1', '-9');






?>