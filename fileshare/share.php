<?php

require_once( dirname(__FILE__)."/src/WebController.php" );

$x = new Flm\Fileshare\WebController();

$x->fileDownload();
