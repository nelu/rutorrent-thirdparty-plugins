<?php

$rtp = realpath(dirname(__FILE__).'/../../php/xmlrpc.php');

require_once($rtp);
include('xmlfix.php');

include('conf.php');
include('flm.class.php');


$e = new FLM();

switch($e->postlist['action']) {

	case 'archive':
		$e->archive();
		break;
	case 'cp':
		$e->copy($e->userdir.$e->postlist['to']);
		break;
	case 'dl':
		$e->send_file($e->postlist['target']);
		break;
	case 'extract':
		$e->extract($e->postlist['target'], $e->postlist['to']);
		break;
	case 'getlog':
		$e->readlog($e->postlist['target'], $e->postlist['to']);
		break;
	case 'list':
		$e->dirlist();
		break;
	case 'kill':
		$e->kill($e->postlist['target']);
		break;
	case 'sfvch':
		$e->sfv_check($e->postlist['target']);
		break;
	case 'minfo':
		$e->mediainfo($e->postlist['target']);
		break;
	case 'mkd':
		$e->mkdir();
		break;
	case 'mv':
		$e->move($e->userdir.$e->postlist['to']);
		break;
	case 'nfo':
		$e->nfo_get($e->postlist['target'], $e->postlist['mode']);
		break;
	case 'rm':
		$e->remove();
		break;
	case 'rename':
		$e->rename();
		break;
	case 'sfvch':
		$e->sfv_check($e->postlist['target']);
		break;
	case 'sfvcr':
		$e->sfv_create($e->postlist['target']);
		break;
	case 'sess':
		$e->get_session();
		break;
	case 'view':
		$e->stream($e->postlist['target']);
		break;
	default:	
		$e->sdie('Invalid action');
}

?>