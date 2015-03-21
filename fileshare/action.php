<?php
use Flm\Fileshare\WebController;
$pluginDir = dirname(__FILE__);

require_once( $pluginDir."/../../php/xmlrpc.php" );
require_once( $pluginDir."/../../php/cache.php" );
require_once ($pluginDir . '/../filemanager/src/Helper.php');
require_once ($pluginDir . '/src/Fileshare.php');
require_once ($pluginDir . '/src/Storage.php');
require_once ($pluginDir . '/src/WebController.php');


$x = new WebController();

$x->_run();
