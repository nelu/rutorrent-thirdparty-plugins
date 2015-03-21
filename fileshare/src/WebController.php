<?php
namespace Flm\Fileshare;
use \Flm\Helper;


// web controller
class WebController {

    public $fs;

    public function __construct($value = '') {

  
    
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

        unset($call->action);

        if (method_exists($this, $method)) {
            call_user_func_array(array($this, $method), array($call));
        }
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

   
    public function deleteShare($params) {

        if (!isset($params->files) || (count($params->files) < 1)) {
            Helper::jsonError(22);
        }


        try {
            $temp = $this->fs->deleteFiles($params->files);

        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0));

    }

    public function sess() {
        $e->get_session();
    }

    public function createShare($params) {
        
          if (!isset($params->file) || empty($params->file)) {
                Helper::jsonError(2);
        }
          
          if(!isset($params->expire) ) {
                Helper::jsonError(22);  
          }

            if( !isset($params->password)) {
               
               $params->password=''; 
            }

        try {
            $temp = $this->fs->addFile($params->file, $params->expire, $params->password);

        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0));
        
        
    }
    
    public function editShare($params) {
                  if (!isset($params->file) || empty($params->file)) {
                Helper::jsonError(2);
        }
          
          if(!isset($params->expire) ) {
            $params->expire = null;
          }

            if( !isset($params->password)) {
               
               $params->password=''; 
            }

        try {
            $temp = $this->fs->editFile($params->file, $params->expire, $params->password);

        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
            return false;
        }

        Helper::jsonOut(array('error' => 0));
        
        
    }
    public function fileList($params) {


        $out = array( 'uh' => base64_encode( getUser()) );
        
        $out['list'] = $this->fs->getFiles();


        Helper::jsonOut($out);
    }
    
    public function fileDownload() {
        
        
        if(!isset($_GET['uh'])
            || !isset($_GET['s'])
            || !( $user = base64_decode($_GET['uh']) ) ) 
            {
          
                die('Invalid link');
            }

        $share_token = $_GET['s'];
                
        $_SERVER['REMOTE_USER'] = $user; // maybe encryption, maybe a user hash scan if users less than 100
        
        
        require_once( dirname(__FILE__)."/../../../php/cache.php" );
        require_once( dirname(__FILE__)."/Storage.php" );
      //  require_once( dirname(__FILE__)."/src/Storage.php" );
        

       $share = Storage::load();
         
         if( !($filedata = $share->getData($share_token)) 
            || ($filedata['expire'] < time() ) 
            ) {
                
                 die('Invalid link');
            }


        if ( !empty($filedata['password']) && ($_SERVER['PHP_AUTH_PW'] != $filedata['password']) ) {
            self::_doAuth();
        } else {
        
                
             if (!sendFile($filedata['file'])) {
                 echo "temp fail";
            }
        
        }
        
        
    }
    
 public static function _doAuth() {
    header('WWW-Authenticate: Basic realm="LEAVE USERNAME EMPTY!! Password only!"');
    header('HTTP/1.0 401 Unauthorized');
    echo "Not permitted\n";
    exit;
}

    public function _run() {


        if (!isset($_POST['action'])) {

            die();
        }

        try {
                    $this->fs = new FSHARE();
        } catch (\Exception $err) {
            var_dump($err);
            Helper::jsonError($err->getCode());
        }

        $action = $_POST['action'];

        $call = json_decode($action);

        if ($call) {

            $this->_processCall($call);

        } else {
          Helper::jsonError(3);
        }

        
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