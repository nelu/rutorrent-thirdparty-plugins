<?php
namespace Flm\Fileshare;
use \LFS;
use \Exception;

class FSHARE {

    public $settings;


	public function __construct() {
		global $topDirectory;
		$this->userdir = addslash($topDirectory);
        
        $this->setSettings();
	}

	public function islimited($limit_key, $cur) {

        $limits = $this->settings;
        
		return ($limits[$limit_key] != 0) 
		              ? (($cur <= $limits[$limit_key]) ? false : true)
                    : false;
	}


    
    public function setSettings() {
        
        
            $this->settings = require_once(dirname(__FILE__). '/../conf.php');
        
    }

	public function addFile($file, $duration, $password) {

           
           $torrentfile = explode('|', $file);
           
           if(count($torrentfile) > 1) {
               
               $file = array($torrentfile[1], $torrentfile[0]);
           }

        if(is_array($file )) {
            $file = \Flm\Helper::getTorrentHashFilepath($file[0], $file[1]);
        } else {
                          
            $file  = fullpath(trim($file, DIRECTORY_SEPARATOR), $this->userdir);
            
        }


        $this->validDuration($duration);


        $share = Storage::load();
        
        if($this->islimited('links', $share->size() ) ) {
            throw new Exception("File limit reached ".$share->size(), 2);
        }
        
  
        
		if( (($stat = LFS::stat($file)) === FALSE) ) {
		          throw new Exception("Invalid file ".$file, 2);
		
         }


		$data =  array(
					 'path' => $file,
					 'size' => $stat['size'],
					 'expire' => time()+(3600*$duration),
					 'password' => $password);
                     
		return $share->add($data);
	}


	public function deleteFiles($files) {

        
          return Storage::load()->remove($files);
	}

	public function editFile($item_id, $duration, $password) {

        
        $share = Storage::load();
        
                
        if(!$share) {
                throw new Exception("Invalid link share ".$item_id, 1);
                
        }
        
        $share->getData($item_id);
        
        $edit = array();
        
		if ($duration !== null)
        {
			if( ($duration < 1)
			     || $this->islimited('duration', $duration) ) {
			    
               throw new Exception("Expire time not permitted ".$item_id, 1);
                
		
            }
            $edit['expire'] = time()+(3600*$duration);
		} 
        
		$edit['password'] = $password;
        
        $share->set($item_id, $edit);
        
	}

	public  function getFiles() {
		
       // var_dump(Storage::load());
		
		$out = array();
		foreach (Storage::load()->getData() as $token => $value) {
			$value['file'] = str_replace($this->userdir, '/', $value['file']);
            $value['id'] = $token;
		      $out[] = $value;
        }
		
		return $out;
		
	}

    public function validDuration($duration) {
               
        if(($duration < 1) || $this->islimited('duration', $duration)) {
              throw new Exception("Expire time not permitted ".$duration, 1);
        }
        
    }
    
    public function DownloadFile() {
        
    }



}


?>