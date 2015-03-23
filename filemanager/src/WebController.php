<?php
namespace Flm;
use Flm\Helper;


// web controller
class WebController {

    public $flm;

    public function __construct($value = '') {

        Helper::getConfig();

        if (!isset($_POST['action'])) {

            die();
        }
        
        

        $current_directory = isset($_POST['dir']) ? $_POST['dir'] : '';

        try {
            $this->flm = new \FLM($current_directory);
        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
        }

        $action = $_POST['action'];

        $call = json_decode($action);

        if ($call) {

            $this->_processCall($call);

        } else if (method_exists($this, $action)) {
            call_user_func(array($this, $action));
        }

        die();
    }

    public function _getPostData($post_keys, $json = true) {
        $ret = array();
        foreach ($post_keys as $key => $err_code) {

            if (!isset($_POST[$key]) || ($json && !($files = json_decode($_POST[$key], true)))) {

                Helper::jsonError($err_code);
                return false;

            }

            $ret[$key] = $_POST[$key];
        }

        return $ret;

    }

  
    public function _processCall($call) {

        $method = $call->method;

        if ((substr($method, 0, 1) == '_')) {
            die('invalid');
        }

        unset($call->method);

        if (method_exists($this, $method)) {
            call_user_func_array(array($this, $method), array($call));
        }
    }

  public function getConfig() {
      global $topDirectory;
      
      $archive = Helper::$config['archive'];
      $archive_bins = array_merge($archive['types'], array('unzip')); 
      
      // unzip should be the latest key index
      foreach($archive_bins as $kid => $external) {
            if( (findEXE($external) === false) 
                && isset($archive['types'][$kid]) 
                ) 
             { 
                $archive['types'][$kid] = false; 
                echo 'log("FILE MANAGER: ',$external,' "+theUILang.fErrMsg[24]);',"\n";
                die();
            }
        
        }


    $settings['homedir'] = rtrim($topDirectory, '/');
    $settings['mkdefmask'] = Helper::$config['mkdperm'];
    $settings['archives'] = $archive;

    Helper::jsonOut($settings);
      
  }
  
    public function taskLog($params) {

        try {
            $output = $this->flm->readTaskLogFromPos($params->target, $params->to);
        } catch (\Exception $err) {
            Helper::jsonError($err->getCode());
            return false;
        }

        $output['error'] = 0;

        Helper::jsonOut($output);
    }

    public function kill() {
        $e->kill($e->postlist['target']);
    }

    public function newDirectory($params) {

        if (!isset($params->target)) {
            Helper::jsonError(16);
        }
        try {

            $this->flm->mkdir($params->target);

        } catch (\Exception $err) {
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0));

    }

    public function fileDownload() {
        
        $data = $this->_getPostData(array( 'target' => 16), false);
        
        $sf = $this->flm->getWorkDir($data['target']);
        
        if (!sendFile($sf)) {
            cachedEcho('log(theUILang.fErrMsg[6]+" - ' . $sf . ' / "+theUILang.fErrMsg[3]);', "text/html");
        }
    }

    public function fileExtract($params) {


        if (!isset($params->to)) {
            Helper::jsonError(2);
        }

        if (!isset($params->target)) {
            Helper::jsonError(18);
        }

        try {
            $temp = $this->flm->extractFile(array('archive' => $params->target, 'to' => $params->to));
        } catch (\Exception $err) {
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0, 'tmpdir' => $temp['tok']));

    }

    public function fileMediaInfo() {

        $data = $this->_getPostData(array( 'target' => 16), false);

        try {
            $temp = $this->flm->mediainfo((object)$data);

        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut($temp);

    }

    public function fileRename($params) {

        
        if (!isset($params->to)) {
            Helper::jsonError(2);
        }

        if (!isset($params->target)) {
            Helper::jsonError(18);
        }

        try {
            $result = $this->flm->rename(array('from' => $params->target, 'to' => $params->to ));
        } catch (\Exception $err) {
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0));

    }

    public function fileScreenSheet($params) {
        

        
        if (!isset($params->to)) {
            Helper::jsonError(2);
        }

        if (!isset($params->target)) {
            Helper::jsonError(2);
        }

        try {
                    

        
            $temp = $this->flm->videoScreenshots($params->target, $params->to);

        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0, 'tmpdir' => $temp['tok']));
        
        
        $e->screenshots($e->postlist['target'], $e->postlist['to']);
    }

    public function filesCompress($params) {

        if (!isset($params->fls) || (count($params->fls) < 1)) {
            Helper::jsonError(22);
        }

        if (!isset($params->target)) {
            Helper::jsonError(16);
        }

        if (!isset($params->mode)) {
            Helper::jsonError(300);
        }

        try {

            $temp = $this->flm->archive($params);

        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0, 'tmpdir' => $temp['tok']));
    }

    public function filesCopy($params) {

        if (!isset($params->fls) || (count($params->fls) < 1)) {
            Helper::jsonError(22);
        }

        if (!isset($params->to)) {
            Helper::jsonError(2);
        }

        try {

            $temp = $this->flm->copy($params);
        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0, 'tmpdir' => $temp['tok']));
    }

    public function filesMove($params) {

        if (!isset($params->fls) || (count($params->fls) < 1)) {
            Helper::jsonError(22);
        }

        if (!isset($params->to)) {
            Helper::jsonError(2);
        }

        try {

            $temp = $this->flm->move($params);
        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0, 'tmpdir' => $temp['tok']));

    }

    public function filesRemove($params) {

        if (!isset($params->fls) || (count($params->fls) < 1)) {
            Helper::jsonError(22);
        }

        try {

            $temp = $this->flm->remove($params);
        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0, 'tmpdir' => $temp['tok']));

    }

    public function checkPostTargetAndDestination() {

        return $this->_getPostData(array('target' => 18, 'to' => 18), false);

    }

    public function checkPostSourcesAndDestination() {

        return $this->_getPostData(array('fls' => 22, 'to' => 2), false);

    }

    public function svfCheck($params) {

        if (!isset($params->target)) {
            Helper::jsonError(2);
        }

        try {
            $temp = $this->flm->sfv_check($params);

        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0, 'tmpdir' => $temp['tok']));

    }

    public function sfvCreate($params) {

        if (!isset($params->fls) || (count($params->fls) < 1)) {
            Helper::jsonError(22);
        }
        if (!isset($params->target)) {
            Helper::jsonError(2);
        }

        try {
            $temp = $this->flm->sfvCreate($params);

        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0, 'tmpdir' => $temp['tok']));

    }

    public function sess() {
        $e->get_session();
    }

    public function listDirectory($params) {

        try {
            $contents = $this->flm->dirlist($params);

        } catch (\Exception $err) {
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('listing' => $contents));
    }

    public function viewNfo($params) {

        if (!isset($params->mode)) {
            $params->mode = 0;
        }

        if (!isset($params->target)) {
            Helper::jsonError(2);
        }

        try {
            $contents = $this->flm->nfo_get($params->target, $params->mode);

        } catch (\Exception $err) {
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0, 'nfo' => $contents));

    }

}