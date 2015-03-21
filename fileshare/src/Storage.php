<?php
namespace Flm\Fileshare;
use \rCache;

class Storage {

    public $hash = 'fileshare.dat';
    
    public $data = array();

    
    static public function load()
    {
        $cache = new \rCache();
        $rt = new self();
        
        $loaded = $cache->get($rt);
        
        if($loaded) {
            
         
        }   
             
        return $rt; 
    }

    public function cleanup($older_than) {
        
    }
    
    public function getData($id = null) {
        
        return is_null($id) ? $this->data : $this->data[$id];
    }
    
    public function add($filedata) {

        do {$token = self::getToken();} while (isset($this->data[$token]));
        


        $this->data[$token] = array(
                     'file' => $filedata['path'],
                     'size' => $filedata['size'],
                     'expire' => $filedata['expire'],
                     'password' => $filedata['password']
                     );
                     
        $this->save();
        
        return $token;
    }
    
    public function save() {
            $cache = new rCache();
            
            return $cache->set($this);    
    }
    
    public function size() {
        
        
        return count($this->data);
        
    }
    
    public function set($token, $data) {
        
        
       if(!isset($this->data[$token])) {
           
           throw new Exception("storage not found ". $token, 11);
           
       }
       
       
       $new = array_merge($this->data[$token], $data);
       
       $this->data[$token] = $new;
       
       return $this->save();
       
    }
    
     public function remove($tokens) {

        foreach($tokens as $id) {
            if(isset($this->data[$id])) {unset($this->data[$id]);}
        }
        
        $this->save();
    }
     
         
    public static function getToken($length = "32") {

        $rnd = '';

        for ($i=0; $i<$length; $i++) {
                $lists[1] = rand(48,57);
                $lists[2] = rand(65,90);
                $lists[3] = rand(97,122);

            $randchar = $lists[rand(1,3)];

                $rnd .= chr($randchar);
        } 

        return $rnd;
    }
     
}
    