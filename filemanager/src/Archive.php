<?php
namespace Flm;
use \Exception;

class Archive {
    
    static $rar = array('compress' => '',
                        'extract' => '');
    public $file;
    public $options;
    
    public function __construct($archive_file) {
        $this->file=$archive_file;
    }
    
    public function setOptions($options) {
        
        $aopts = Helper::$config['archive'];

        $a['type'] = $aopts['types'][(int)$options['type']];
        $a['comp'] = $aopts['compress'][$options['type']][$options['compression']];
        $a['volume'] = (intval($options['vsize'])*1024);
        $a['multif'] = (($a['type'] == 'rar') && ($options['format'] == 'old')) ? '-vn' : '';
        
        $a['workdir'] = $options['workdir'];
        
          if(($options['password'] != '') 
                && ($a['type'] == 'rar'))
            { 
            $a['password'] = $options['password']; 
        }

        $this->options = $a;
        
        return $this;
    }
    public function create ($files) {

        if(is_null($this->options)) {
            
            throw new Exception("Please load setOptions first", 1);
            
        }


        switch($this->options['type']) {
                
                case 'gzip': 
                case 'bzip2': 
                    $bin = 'tar';
                    break;
                case 'rar':
                    $bin = 'rar';
                    break;
                case 'zip':
                    $bin = 'zip';
                    break;
                default: 
                    $bin = false;
        }
        
        if(!$bin) {
            throw new Exception("Unsuported archive format ".$this->options['type'], 16);
        }
        
                 
                       
        $temp = Helper::getTempDir();
        
        
        $args = array('action' => 'compressFiles',
                        'params' => array(
                            'files' => $files,
                            'archive' => $this->file,
                            'options' => $this->options,
                             'binary'=>getExternal($bin)
                            ),
                        'temp' => $temp );
                        
         $task = $temp['dir'].'task';    
            
        file_put_contents($task, json_encode($args));

            $task_opts = array  ( 'requester'=>'filemanager',
                            'name'=>'compress', 
                        );
                        
             $rtask = new \rTask( $task_opts );
             $commands = array( Helper::getTaskCmd() ." ". escapeshellarg($task) );
                    $ret = $rtask->start($commands, 0);    
             
           //var_dump($ret);
           
             return $temp;
    }

   
    public static function getFormatBinary($file) {
        
       switch(pathinfo($file, PATHINFO_EXTENSION)) {
            case 'rar':
                $bin = 'rar';
                break;
            case 'zip':
                $bin = 'unzip';
                break;
            case 'iso':
                $bin = 'unzip';
                break;
            case 'tar':
            case 'bz2':
            case 'gz':
                $bin = 'tar';
                break;
            default:
                $bin = false;  
        }
       
       return $bin;
        
    }

    public  function extract($to) {

             
        $formatBin = self::getFormatBinary($this->file);
        
        if(!$formatBin) {
            throw new Exception("Error Processing Request", 18);
        }
    
        $temp = Helper::getTempDir();
        
        
        $args = array('action' => 'extract',
                        'params' => array('file' => $this->file,
                                            'to' => $to,
                                            'binary'=>getExternal($formatBin)),
                        'temp' => $temp );
                        
         $task = $temp['dir'].'task';    
            
        file_put_contents($task, json_encode($args));
        


            $task_opts = array  ( 'requester'=>'filemanager',
                            'name'=>'unpack', 
                        );
                    
         $rtask = new \rTask( $task_opts );
         $commands = array( Helper::getTaskCmd() ." ". escapeshellarg($task) );
         $ret = $rtask->start($commands, 0);   



        return $temp;
    
    }

   
}