<?php
namespace Flm;


class Filesystem {

   public static $instance;

    public static function get($args = null) {
                
            if(is_null(self::$instance)) {
                
                self::$instance = new self($args);
            }
        return self::$instance;
    }
    

    

    public function copy($files, $to) {
        
    
        $temp = Helper::getTempDir();
        
        
        $args = array('action' => 'recursiveCopy',
                        'params' => array('files' => $files,
                                            'to' => addslash($to)
                                            ),
                        'temp' => $temp );
                        
         $task = $temp['dir'].'task';    
            
        file_put_contents($task, json_encode($args));
        

      //  var_dump(Helper::getTaskCmd(), escapeshellarg($task));
        RemoteShell::get()->execBackground(Helper::getTaskCmd(), $task );
        
        return $temp;

    }
    
    

    public function listDir($directory_path) {

        $output = array();
        
       // setlocale(LC_CTYPE, "en_US.UTF-8");
   
     //   var_dump('before callname', $directory_path, $this->mb_escapeshellarg($directory_path));
        
        $find_args = array( Helper::mb_escapeshellarg($directory_path), '-mindepth', '1', '-maxdepth', '1', '-printf', escapeshellarg('%y\\t%f\\t%s\\t%C@\\t%#m\\n') );

         $i = 0;
        foreach (RemoteShell::get()->execOutput('find', $find_args) as $fileline) {
         
            if(empty($fileline)) {continue;}
            
            $f = array();

            list($fd, $f['name'], $f['size'], $f['time'], $f['perm']) = explode("\t", trim($fileline));

            $f['name'] = stripslashes($f['name']);
            $f['time'] = intval($f['time']);

            if($fd == 'd') {
                        $f['name'] .= DIRECTORY_SEPARATOR;
                        $f['size'] = ''; }

            $output[$i] = $f;
            $i++;

        }

        
        return $output;
    }
    

        
     public function move($files, $to) {
         
         
                       
        $temp = Helper::getTempDir();
        
        
        $args = array('action' => 'recursiveMove',
                        'params' => array(
                            'files' => $files,
                            'to' => addslash($to)),
                        'temp' => $temp );
                        
         $task = $temp['dir'].'task';    
            
        file_put_contents($task, json_encode($args));

            $task_opts = array  ( 'requester'=>'filemanager',
                            'name'=>'move', 
                        );
                        
             $rtask = new \rTask( $task_opts );
             $commands = array( Helper::getTaskCmd() ." ". escapeshellarg($task) );
                    $ret = $rtask->start($commands, 0);    
             
         //   var_dump($ret);
           
             return $temp;
       
    }
     
    public function remove($files) {
            
            
                
        $temp = Helper::getTempDir();
        
        
        $args = array('action' => 'recursiveRemove',
                        'params' => array('files' => $files),
                        'temp' => $temp );
                        
         $task = $temp['dir'].'task';    
            
        file_put_contents($task, json_encode($args));
/*
        

      //  var_dump(Helper::getTaskCmd(), escapeshellarg($task));
        RemoteShell::get()->execBackground(Helper::getTaskCmd(), $task );
        
        return $temp;*/

            
            $task_opts = array  ( 'requester'=>'filemanager',
                            'name'=>'remove', 
                        );
                        
             $rtask = new \rTask( $task_opts );
             $commands = array( Helper::getTaskCmd() ." ". escapeshellarg($task) );
                    $ret = $rtask->start($commands, 0);    
             
          //   var_dump($ret);
             return $temp;
  }
     
     
     public function rename($from, $to) {

        if (!RemoteShell::test($from,'e') 
              || RemoteShell::test($to,'e')) {
                      throw new Exception("Error Processing Request", 18);
                      
        }
              
       if(!RemoteShell::get()->execCmd('mv', array( '-f', $from, $to)) ) 
        {
            throw new Exception("Error Processing Request", 8);
            
        }
        
        return true;

    }
        
    public function mkdir($target, $recursive = false, $mode = null) {
        
            $target = Helper::mb_escapeshellarg($target);
          if ( $this->isDir($target,'d')) { throw new Exception("Directory already exists", 16); }
          
            $mode = !is_null($mode) ? $mode : Helper::$config['mkdperm'];
          
          $args = array( '--mode='.$mode, $target);
          
          if($recursive) {
              
              $args =  array_merge(array('-p'), $args); 
          }
          
        
              
       if(!RemoteShell::get()->execOutput('mkdir', $args ) ) 
        {
            throw new Exception("Error Processing Request", 4);
            
        }

        return true;

    }
    
    public function fileExists() {
      return  RemoteShell::test($file,'e');  
    }
    public function isFile($file) {
      return  RemoteShell::test($file,'f');
    }
    public function isDir($dir) {
      return  RemoteShell::test($dir,'d');       
    }
}
