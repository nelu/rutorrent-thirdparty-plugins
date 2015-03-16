<?php
use Flm\RemoteShell as Remote;
use Flm\Filesystem as Fs;
use Flm\Archive;
use Flm\Helper;

class FLM {

	public $hash = 'flm.dat';

	protected $xmlrpc;

	public $postlist = array('dir', 'action', 'file', 'fls', 'target', 'mode', 'to', 'format');

	public $workdir;
	public	$userdir;
	public $fman_path;

	protected $output = array('errcode' => 0);
	protected $temp = array();
	protected $filelist;

	protected $uisettings;
	
	protected $settings = array();

	public $shout = TRUE;
    
    
    public $config;


	public function __construct( $directory) {
		/*
		 * Construct function - initialises the objects properties 
		 * 
		 * $userdir - current user home directory (jail)
		 * $workdir - the directory where filemanager is working at the time of call
		 * $filelist - the current file-list sent for processing (archive,move,copy,delete, etc)
		 * $settings - array with filemager current configuration
		 * $fman_path - string flm.class.php directory location
		 * 
		 */
		global $topDirectory, $fm;
        
        
       // $this->config = $config;

		$this->userdir = addslash($topDirectory);
      
        $this->setWorkDir($directory);

        //new remote shell
		Remote::get();

		if( !\Flm\Helper::remote_test($this->workdir, 'd')) {
		    throw new Exception("Error Processing Request".$this->workdir, 2);
        }

        // instantiating filesystem
        Fs::get();
        $config = Flm\Helper::$config;
        
        if(!is_dir($config['tempdir'])) {
            var_dump(Flm\Helper::$config);
            throw new Exception("Error Processing Request", 17);
        }
	    
	    $this->settings = $config;

		$this->fman_path = dirname(__FILE__);

	}

    public function getFullPath($relative_path) {
            return fullpath(trim($relative_path, DIRECTORY_SEPARATOR), $this->workdir);
    }
    
    public function getDirPath($path) {
        
        return fullpath($path, $this->userdir);
    }
    
    public function getUserDir($relative_path) {
         return fullpath(trim($relative_path, DIRECTORY_SEPARATOR), $this->userdir);
    }

    public function setFilelist($filelist) {
        $this->filelist = $this->get_filelist($filelist);
    }
    
    public function setWorkDir($directory) {

        $dir = addslash($this->userdir. trim($directory, DIRECTORY_SEPARATOR)); 
        
        $path_check = explode($this->userdir, addslash(fullpath(  $dir, $this->userdir)));
        if( count($path_check)  < 2 )
        {
            $dir = $this->userdir;
        }
        
        $this->workdir = $dir;
        
        return $dir;
    }
	public function archive ($paths) {

        $archive_file = $this->getUserDir($paths->target);
      //  var_dump('arch path', $this->workdir.$paths['archive'], $archive_file);

              
       $options = $paths->mode;
       $options->workdir = $this->workdir;
       
       if(!isset($options->type) 
       || !isset(Helper::$config['archive']['types'][$options->type ]) ) 
       {
           throw new Exception("invalid type", 1);
           
       }

        $files = array_map(array($this, 'getFullPath'), (array)$paths->fls);
        
        $fs = Fs::get();  

        if($fs->isFile($archive_file)) {
           throw new Exception("dest is file", 16);      
        } 

       
       $archive = new Archive($archive_file);  
       
       $archive->setOptions((array)$options);
       

       return   $archive->create($files);
	}

	public function copy($paths) {
	    
        
        $files = array_map(array($this, 'getFullPath'), (array)$paths->fls);
        
        $to = $this->getUserDir($paths->to);
       // var_dump($paths, $to, $files);
        
        $fs =Fs::get(); 
		if(!$fs->isDir($to)) {
		        
                throw new Exception("Destination is not directory", 2);
         }

        $task_info = $fs->copy($files, $to);
        return $task_info;
	}


	static public function dir_sort($a, $b) {return strcmp($a['name'], $b['name']);}

	public function dirlist($paths) {


        $dirpath = $this->getFullPath($paths->dir);
        
        //var_dump($dirpath);
        
        $directory_contents = Fs::get()->listDir( $dirpath );

        usort($directory_contents, array($this, 'dir_sort'));

        return $directory_contents;
	}



	public function extractFile($paths) {
	    
        $archive_file = $this->getUserDir($paths['archive']);
      //  var_dump('arch path', $this->workdir.$paths['archive'], $archive_file);

        $to = $this->getUserDir($paths['to']);        

        $fs = Fs::get();  

       if (!$fs->isFile($archive_file) ) {
           throw new Exception("Error Processing Request", 6);  
       }else  if($fs->isFile($to)) {
           throw new Exception("dest is file", 16);      
        }  else if(!Remote::test(dirname($to), 'w') ) {
              throw new Exception("Not writable", 300);
        }
        
       $archive = new Archive($archive_file);  
                
       return   $archive->extract($to);
	}

	public function fext($file) {
		return (pathinfo($file, PATHINFO_EXTENSION));
	}

	public function get_session() {
		$sid = session_id();
		
		if(empty($sid)) {
			session_start();
			$_SESSION['uname'] = getUser();
			$sid = session_id();
		}

		$this->output['sess'] = $sid;
	}

	public function kill($token) {

		if($token === FALSE) {$this->sdie('No token');}

		$k['tmp'] = addslash($this->settings['tempdir']).'.rutorrent/.fman/'.$token;
		$k['pid'] = $k['tmp'].'/pid';
		
		if(!is_file($k['pid'])) {$this->output['errcode'] = 19; return false;};

		$pid = file($k['pid']);
		$pid = trim($pid[0]);


		Remote::get()->addCommand(new rXMLRPCCommand( "execute", array('sh', '-c', 'kill -15 '.$pid.' `pgrep -P '.$pid.'`')));
		Remote::get()->addCommand(new rXMLRPCCommand( "execute", array("rm", "-rf", $k['tmp'])));
	
		if(!Remote::get()->success()) {$this->output['errcode'] = 20;}
	}




	public function mediainfo ($file) {

		eval(getPluginConf('mediainfo'));

		if(($file === FALSE) || !LFS::is_file($this->workdir.$file))  {$this->output['errcode'] = 6; return false; }

		Remote::get()->addCommand( new rXMLRPCCommand('execute_capture', 
					array(getExternal("mediainfo"), $this->workdir.$file)));

		if(!Remote::get()->success()) {$this->output['errcode'] = 14; return false;}


		$this->output['minfo'] = Remote::get()->val[0];

	}

	public function move($paths) {
	    
        
        $files = array_map(array($this, 'getFullPath'), (array)$paths->fls);
        
        // destination dir requires ending /
        $to = addslash($this->getUserDir($paths->to));
      //  var_dump($paths,  $files);
        
        $fs =Fs::get(); 
        if(!$fs->isDir($to)) {
                
                throw new Exception("Destination is not directory", 2);
         }

        $task_info = $fs->move($files, $to);
        return $task_info;

	}


	public function mkdir($dirpath) {
        
        return Fs::get()->mkdir($this->getFullPath($dirpath), true );

	}

	public function nfo_get($nfofile, $dos = TRUE) {

        $nfofile = $this->getFullPath($nfofile);
     //   var_dump($nfofile);
        
		if (!is_file($nfofile)) 	{
		    throw new Exception("no file", 6);
			
		 }
		elseif ((Helper::getExt($nfofile) != 'nfo') 
		      || (filesize($nfofile) > 50000)) 
		 {
		     throw new Exception("Invalid file", 18);
         }

        require_once dirname(__FILE__).'/src/NfoView.php';
        
        $nfo =new Flm\NfoView($nfofile);
        
        return $nfo->get($dos);

	}


	public function read_file($file, $array = TRUE) {
		
		return $array ? file($this->workdir.$file, FILE_IGNORE_NEW_LINES) : file_get_contents($this->workdir.$file); 

	}


	public function readTaskLogFromPos($token, $lpos) {
	    
        
        $tmp = \Flm\Helper::getTempDir($token);
        
        
        $file = $tmp['dir'].'log';
        
        if(!is_file($file)) {
            throw new \Exception("Logfile not found!", 23);
            return false;
        }
        
        $log = \Flm\Helper::readTaskLog($file, $lpos);
        // relative paths
        $log['lines'] = str_replace($this->userdir, '/', $log['lines']);
        
        return $log;
        
	}


	public function rename($paths) {

		$from = $this->workdir.$paths['from'];
		$to = $this->workdir.$paths['to'];

        
        return Fs::get()->rename($from, $to );

	}


	public function remove($paths) {
	    
        $files = array_map(array($this, 'getFullPath'), (array)$paths->fls);
       // var_dump($paths, $to, $files);
        
        $fs =Fs::get(); 

        $task_info = $fs->remove($files);
        return $task_info;
    }


	public function video_info($video_file) {

		Remote::get()->addCommand( new rXMLRPCCommand('execute_capture', 
					array(getExternal("ffprobe"), '-v', 0, '-show_format', '-show_streams', '-print_format', 'json' ,'-i', $video_file)));
		//Remote::get()->success();


		if(!Remote::get()->success()) {$this->sdie('Current ffmpeg/ffprobe not supported. Please compile a newer version.'); }

		$vinfo = json_decode(stripslashes(Remote::get()->val[0]), true);

		$video_stream = false;
		$video['stream_id'] = 0;

		foreach($vinfo['streams'] as $sk => $stream) {

			if(array_search('video', $stream, true) !== false) {
				$video['stream_id'] = $sk;
				$video_stream = $stream;
			}
		}

		if($video_stream === false) {$this->sdie('Invalid video!');}

		$video['duration'] = floor(isset($vinfo['format']['duration']) ? $vinfo['format']['duration'] : (isset($video_stream['duration']) ? $video_stream['duration'] : 0));
		$video['frame_rate'] = floor(isset($video_stream['r_frame_rate']) ? eval("return (".$video_stream['r_frame_rate'].");") : 0);
		$video['total_frames'] = $video['duration']*$video['frame_rate'];

		if($video['total_frames'] < 1) {

			Remote::get()->addCommand( new rXMLRPCCommand('execute_capture', 
					array(getExternal("ffprobe"), '-v', 0, '-show_streams', '-print_format', 'json', '-count_frames', '-i', $video_file)));

			$vinfo = json_decode(stripslashes(Remote::get()->val[0]), true);
			$video['total_frames'] = $vinfo['streams'][$video['stream_id']]['nb_read_frames'];

		}

		return $video; 

	}




	public function videoScreenshots($file, $output) {

        $fs = Fs::get();  

        $video_file = $this->getUserDir($file);
        $screens_file = $this->getUserDir($output);

       if (!$fs->isFile($video_file) ) {
           throw new Exception("Error Processing Request", 6);  
       }else  if($fs->isFile($screens_file)) {
           throw new Exception("dest is file", 16);      
        } 

		$defaults = array('scrows' => '12', 'sccols' => 4, 'scwidth' => 300 );

		$uisettings = json_decode(file_get_contents(getSettingsPath().'/uisettings.json'), true);
		$settings = array();

		foreach($defaults as $k => $value) {
			$settings[$k] = (isset($uisettings['webui.fManager.'.$k]) && ($uisettings['webui.fManager.'.$k] > 1)) ? $uisettings['webui.fManager.'.$k] : $value;
		}

		$vinfo = $this->video_info($video_file);

		$frame_step = floor($vinfo['total_frames'] / ($settings['scrows'] * $settings['sccols']));	

        $settings['frame_step'] = $frame_step;
                       
        $temp = Helper::getTempDir();
        
        
        $args = array('action' => 'makeScreensheet',
                        'params' => array(
                            'imgfile' => $screens_file,
                            'file' => $video_file,
                            'options' => $settings,
                             'binary'=> getExternal('ffmpeg')
                            ),
                        'temp' => $temp );
                        
         $task = $temp['dir'].'task';    
            
        file_put_contents($task, json_encode($args));

            $task_opts = array  ( 'requester'=>'filemanager',
                            'name'=>'screensheet', 
                        );
                        
             $rtask = new \rTask( $task_opts );
             $commands = array( Helper::getTaskCmd() ." ". escapeshellarg($task) );
                    $ret = $rtask->start($commands, 0);    
             
        //   var_dump($ret);
           
             return $temp;




return false;

		$this->batch_exec(array("sh", "-c", escapeshellarg($this->fman_path.'/scripts/screens')." ".
		                  escapeshellarg(getExternal('ffmpeg'))." ". //1
							escapeshellarg($this->temp['dir'])." ".  // 2
							escapeshellarg($file)." ".       //3
							escapeshellarg($output)." ".     //4
							$frame_step." ".                 //5
							$settings['scwidth']." ".        //6
							$settings['scrows']." ".         //7
							$settings['sccols']));           //8




	}

	public function send_file($file) {

		$fpath = $this->workdir.$file;
		$this->shout = FALSE;

		if(($file === FALSE) || (($finfo = LFS::stat($fpath)) === FALSE)) {cachedEcho('log(theUILang.fErrMsg[6]+" - '.$fpath.'");',"text/html");}

		$etag = sprintf('"%x-%x-%x"', $finfo['ino'], $finfo['size'], $finfo['mtime'] * 1000000);

		if( 	(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) ||
                        	(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $finfo['mtime'])) {

			header('HTTP/1.0 304 Not Modified');
		} else {
			header('Etag: '.$etag);
			header('Last-Modified: ' . date('r', $finfo['mtime']));
		}

		header('Cache-Control: ');
		header('Expires: ');
		header('Pragma: ');
		header('Content-Type: application/octet-stream');

		header('Content-Disposition: attachment; filename="'.end(explode('/',$file)).'"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Description: File Transfer');

		$this->get_file($fpath, ($finfo['size'] >= 2147483647));


	}

	
	public function sfv_check ($file) {

		if ($this->fext($file) != 'sfv') 	{ $this->output['errcode'] = 18; return false;}
		elseif (!is_file($this->userdir.$file)) 	{ $this->output['errcode'] = 6; return false;}

		$this->batch_exec(array("sh", "-c", escapeshellarg(getPHP())." ".escapeshellarg($this->fman_path.'/scripts/sfvcheck.php')." ".
							escapeshellarg($this->temp['dir'])." ".escapeshellarg($this->userdir.$file)));
	}



	public function sfv_create ($file) {

		if (empty($this->filelist)) {$this->output['errcode'] = 22; return false;}
		if(LFS::test($this->userdir.$file,'e')) {$this->output['errcode'] = 16; return false;}

		$this->batch_exec(array("sh", "-c", escapeshellarg(getPHP())." ".escapeshellarg($this->fman_path.'/scripts/sfvcreate.php')." ".
							escapeshellarg($this->temp['dir'])." ".escapeshellarg($this->userdir.$file)));
	}


	public function sdie($args = '') {

		$this->shout = FALSE;
		die($args);
	}


}


?>