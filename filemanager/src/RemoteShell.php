<?php
namespace Flm;
use \rXMLRPCCommand;
use \Exception;

require_once(realpath(dirname(__FILE__).'/../../../php/xmlrpc.php'));

class RemoteShell extends \rXMLRPCRequest {
    
   public static $instance;
    
    public static function get() {
                
            if(is_null(self::$instance)) {
                
                self::$instance = new self();
            }
        return self::$instance;
    }
    
    
/*
	public function run()
	{
	        $ret = false;
		$this->i8s = array();
		$this->strings = array();
		$this->val = array();
		if($this->makeCall())
		{
			$answer = self::send($this->content);

			
			if(!empty($answer))
			{
				if($this->parseByTypes)
				{
					if((preg_match_all("|<value><string>(.*)</string></value>|Us",$answer,$this->strings)!==false) &&
						count($this->strings)>1 &&
						(preg_match_all("|<value><i.>(.*)</i.></value>|Us",$answer,$this->i8s)!==false) &&
						count($this->i8s)>1) {
						$this->strings = str_replace("\\","\\\\",$this->strings[1]);
						$this->strings = str_replace("\"","\\\"",$this->strings);
						foreach($this->strings as &$string) 
							$string = html_entity_decode($string,ENT_COMPAT,"UTF-8");
						$this->i8s = $this->i8s[1];
						$ret = true;
	
					}
				} else {

					if((preg_match_all("/<value>(<string>|<i.>)(.*)(<\/string>|<\/i.>)<\/value>/s",$answer,$this->val)!==false) &&
						count($this->val)>2)
					{

						$this->val = str_replace("\\","\\\\",$this->val[2]);
						$this->val = str_replace("\"","\\\"",$this->val);

						foreach($this->val as &$string) 
							$string = html_entity_decode($string,ENT_COMPAT,"UTF-8");
						$ret = true;
					}
				}



				if($ret) {
					if(strstr($answer,"faultCode")!==false)
					{
						
						$this->fault = true;	
						if(LOG_RPC_FAULTS && $this->important)
						{
							toLog($this->content);
							toLog($answer);
						}
					}
				}
			}
		}
		$this->content = "";
		$this->commands = array();
		return($ret);
	}
*/

    public static function merge_cmd_args($shell_cmd, $args) {
        
        return array_merge(array($shell_cmd),  $args); 
    }
    public function execOutput($shell_cmd, $args) {
        
        $args = $args;
          
        $cmd  = self::merge_cmd_args($shell_cmd, $args);
        
        $cmd[] = ' 2>&1';
        
        $sucmd = dirname(__FILE__).'/../scripts/sucmd.sh';
        
 
        
        $ncmd = array($sucmd, implode(" ",$cmd));
        
      // var_dump($ncmd);
        
        $this->addCommand( new \rXMLRPCCommand('execute_capture',  $ncmd));

        if(!$this->success()
            || ($this->getExitCode($this->val[0])->exitcode > 0) ) {
            //   var_dump($cmd, $this->val);
             throw new Exception("Error ".$this->val[0], 10);
                
        }


   //   var_dump($this->val);
        
        return explode("\n", trim($this->val[0]));
        
    }
    
    public function execCmd($shell_cmd, $args) {
        
        $cmd  = self::merge_cmd_args($shell_cmd, $args);
        
        $this->addCommand(new rXMLRPCCommand('execute', $cmd));
        
        return $this->success();
    }
    
   public function execBackground($shell_cmd, $args) {
        

        $cmd = $shell_cmd . ' ' . escapeshellarg($args). ' > /dev/null &';
        
        $what = array('sh', '-c', $cmd);

        
       //var_dump($what);

        $this->addCommand(new rXMLRPCCommand('execute', $what));

        return $this->success();
    }
    

    public function getExitCode(&$output) {
        
        
        
        if(!preg_match('/\{(.*)\}$/', $output, $matches)) {
            return false;
            
        }
        
        $new = preg_replace('/\{(.*)\}$/', '', $output);
    
        $output = $new;
        
        return json_decode(str_replace('\\"', '"', $matches[0]) );
    }
        
    public static function test($dirname, $o) {
        /*
         * Test's to check if $arg1 exists from rtorrent userid
         * 
         *  @param string target - full path
         *  @param string option to use with test
         * 
         *  Example: $this->remote_test('/tmp', 'd');
         *  For test command options see: http://linux.about.com/library/cmd/blcmdl1_test.htm
         */
         $shell = self::get();
            $shell->addCommand( new \rXMLRPCCommand('execute', array('test','-'.$o, $dirname)));
        return (bool)$shell->success();
    }
}


?>