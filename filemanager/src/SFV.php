<?php
namespace Flm;
use \Exception;

class SFV implements \Iterator  {
    
    protected $file;
    
    protected $files;
    protected $sfvfile;
    
    protected $position = 0;
    
    public function __construct($file = null ) {
        
          $this->position = 0;
          
          
        if(is_string($file)) {
            $this->loadFiles($file);
            
        }
        elseif(is_array($file) || is_object($file)) {
            $this->files = (array)$file;
        }

    }

    public function loadFiles($sfvfile) {
        

        if(!is_file($sfvfile)) {
            throw new Exception("sfv file not found: ".$this->sfvfile, 2);
        }
  
        
        $fr = file($sfvfile);

        $filelines = array();
        foreach($fr as $fl) {
            if (substr(trim($fl), 0, 1) == ';') {
                continue;
            } 
            $filelines[] = $fl;
        }
        
        $t = count($filelines);
       
        $this->sfvfile = $sfvfile;
        $this->files = $filelines;
        
        return $filelines;
    }
    
    public function getCurFile() {
        
        return $this->file;
    }
    public static function getFileHash($file) {
        
        if(!is_file($file)) {
                throw new \Exception(' FAILED: no such file '.$file, 1);
        }

        return hash_file('crc32b', $file);
    }
    
    public function checkFileHash($against = null) {
        
        
        $parts = explode(' ', trim($this->file));
        
        if(count($parts) < 2) {
            throw new Exception("Invalid line " .implode(' ', $this->file), 1);
        }
        
        $file = array_shift($parts);
        $hash = array_pop($parts);

        return (self::getFileHash($file) == $hash);

    }
    
    public function length() {
        
        return count($this->files);
    }


    function rewind() {

      $this->position = 0;
      $this->file = null;
    }

    function current() {

        
        $this->file = $this->files[$this->position]; 
        
        
      return $this;

    }

    function key() {

        return $this->position;
    }

    function next() {

        ++$this->position;
    }

    function valid() {
        return isset($this->files[$this->position]);
    }
}
