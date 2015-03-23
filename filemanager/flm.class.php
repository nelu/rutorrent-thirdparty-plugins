<?php
use Flm\RemoteShell as Remote;
use Flm\Filesystem as Fs;
use Flm\Archive;
use Flm\Helper;
use Flm\mediainfoSettings;

class FLM {


	public $workdir;
	public	$userdir;


	protected $temp = array();

	protected $uisettings;
	
	protected $settings = array();
    
    public $config;

	public function __construct( $directory) {
		/*
		 * Construct function - initialises the objects properties 
		 * 
		 * $userdir - current user home directory (jail)
		 * $workdir - the directory where filemanager is working at the time of call
		 * $settings - array with filemager current configuration
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

    
    public function getWorkDir($relative_path) {
        return fullpath(trim($relative_path, DIRECTORY_SEPARATOR), $this->workdir);
    }
    
    public function getDirPath($path) {
        
        return fullpath($path, $this->userdir);
    }

    public function getJailPath($path) {
        
        $f = explode($this->userdir, $path);
        
        var_dump('got to find', $f, $path);
        
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


        $options = is_null($paths->mode) ? stdClass () : $paths->mode ;
         
       if(!isset($options->type) 
       || !isset(Helper::$config['archive']['types'][$options->type ]) ) 
       {
           throw new Exception("invalid type", 1);
           
       }

        //$files = array_map(array($this, 'getJailPath'), (array)$paths->fls);
        $files = (array)$paths->fls;
        
        //var_dump($paths->fls, $file);

        $fs = Fs::get();  

        if($fs->isFile($archive_file)) {
           throw new Exception("dest is file", 16);      
        } 

              
     
       $options->workdir = $this->workdir;
       
       $archive = new Archive($archive_file);  
       
       $archive->setOptions((array)$options);
       

       return   $archive->create($files);
	}

	public function copy($paths) {
	    
        
        $files = array_map(array($this, 'getWorkDir'), (array)$paths->fls);
        
        $to = $this->getUserDir($paths->to);
       // var_dump($paths, $to, $files);
        
        $fs =Fs::get(); 
		if(!$fs->isDir($to)) {
		        
                throw new Exception("Destination is not directory", 2);
         }

        $task_info = $fs->copy($files, $to);
        return $task_info;
	}


	static public function dir_sort($a, $b) {
	       $a_isdir = ($a['type'] =='d');
        
        $b_isdir = ($b['type'] =='d');
    
        if( $a_isdir && $b_isdir) {strcmp($a['name'], $b['name']);}
        elseif ( $a_isdir ) { return -1; }
        elseif ($b_isdir ) {  return 1; }
	    
	   return strcmp($a['name'], $b['name']);
	    }

	public function dirlist($paths) {


        $dirpath = $this->getWorkDir($paths->dir);
        
        $directory_contents = Fs::get()->listDir( $dirpath );

        usort($directory_contents, array($this, 'dir_sort'));

            foreach ($directory_contents as $key => $value) {
                unset($directory_contents[$key]['type']);
            }
            
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




	public function mediainfo ($paths) {
        
        $filename = $this->getWorkDir($paths->target);

		if(!Fs::get()->isFile($filename))  {
		    throw new Exception("Error Processing Request", 6);
        }
        
        
        $commands = array();
        $flags = '';
        $st = mediainfoSettings::load();
        $task = new rTask( array
        ( 
            'arg'=>call_user_func('end',explode('/',$filename)),                    
            'requester'=>'mediainfo',
            'name'=>'mediainfo', 
           // 'hash'=>$_REQUEST['hash'], 
            'no'=> 0 
        ) );                    
        if($st && !empty($st->data["mediainfousetemplate"]))
        {
            $randName = $task->makeDirectory()."/opts";
            file_put_contents( $randName, $st->data["mediainfotemplate"] );
            $flags = "--Inform=file://".escapeshellarg($randName);
        }
        $commands[] = getExternal("mediainfo")." ".$flags." ".Helper::mb_escapeshellarg($filename);
        $ret = $task->start($commands, rTask::FLG_WAIT);


        return $ret;

	}

	public function move($paths) {
	    
        
        $files = array_map(array($this, 'getWorkDir'), (array)$paths->fls);
        
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
        
        return Fs::get()->mkdir($this->getWorkDir($dirpath), true );

	}

	public function nfo_get($nfofile, $dos = TRUE) {

        $nfofile = $this->getWorkDir($nfofile);
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
	    
        $files = array_map(array($this, 'getWorkDir'), (array)$paths->fls);
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
	}

	public function sfv_check ($paths) {

        $sfvfile =  $this->getWorkDir($paths->target);  

        if (Helper::getExt($sfvfile) != 'sfv')    { throw new Exception("Error Processing Request", 18);}

       if (!Fs::get()->isFile($sfvfile) ) {
           throw new Exception("File does not exists", 6);
           
       }
       
                            
        $temp = Helper::getTempDir();
        
        
        $args = array('action' => 'sfvCheck',
                        'params' => array(
                            'target' => $sfvfile,
                            'workdir' => $this->workdir

                            ),
                        'temp' => $temp );
                        
         $task = $temp['dir'].'task';    
            
        file_put_contents($task, json_encode($args));

            $task_opts = array  ( 'requester'=>'filemanager',
                            'name'=>'SFV check', 
                        );
                        
             $rtask = new \rTask( $task_opts );
             $commands = array( Helper::getTaskCmd() ." ". escapeshellarg($task) );
                    $ret = $rtask->start($commands, 0);    
             
           //var_dump($ret);
           
             return $temp;
	}



	public function sfvCreate ($paths) {
          
        $sfvfile =  $this->getUserDir($paths->target);  
        $files = array_map(array($this, 'getWorkDir'), (array)$paths->fls);

       if (Fs::get()->isFile($sfvfile) ) {
           throw new Exception("File already exists", 16);
           
       }
       
                            
        $temp = Helper::getTempDir();
        
        
        $args = array('action' => 'sfvCreate',
                        'params' => array(
                            'files' => $files,
                            'target' => $sfvfile,

                            ),
                        'temp' => $temp );
                        
         $task = $temp['dir'].'task';    
            
        file_put_contents($task, json_encode($args));

            $task_opts = array  ( 'requester'=>'filemanager',
                            'name'=>'SFV create', 
                        );
                        
             $rtask = new \rTask( $task_opts );
             $commands = array( Helper::getTaskCmd() ." ". escapeshellarg($task) );
                    $ret = $rtask->start($commands, 0);    
             
           //var_dump($ret);
           
             return $temp;
	}


}


?>