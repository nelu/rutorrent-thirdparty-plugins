<?php
use Flm\WebController;
$pluginDir = dirname(__FILE__);

require_once ($pluginDir . '/src/Helper.php');
require_once ($pluginDir . '/src/RemoteShell.php');
require_once ($pluginDir . '/src/Filesystem.php');
require_once ($pluginDir . '/src/Archive.php');
require_once ($pluginDir . '/src/WebController.php');
require_once (dirname(__FILE__) . '/../_task/task.php');

include ('flm.class.php');



$x = new WebController();
