<?php
// auto call functions 
class __fn
{
    private static $ths;
    private $create = false,
            $folder = __DIR__."/__functions",
            $souce  = [];
            
    function __construct(){
        $this->source[$this->folder] = $this->create;
    }

    static function get($dirs=null,$call=true){
        self::$ths = new \__fn();       
        if(is_string($dirs)){
            is_dir($dirs) || mkdir($dirs,0755,true);
            self::$ths->source[$dirs] = $call; 
        }
        return self::$ths;
    }
    
    static function __callStatic($var,$arg)
    {
        if(!self::$ths){
            self::$ths = new \__fn();
        }        
        self::$ths->get_source_function($var);
        return call_user_func_array($var,$arg);
    } 
    
    function __call($var,$arg)
    {
        $this->get_source_function($var); 
        return call_user_func_array($var,$arg); 
    }

    private function get_source_function($var){
        foreach ($this->source as $dirs => $call) {
            if(!function_exists($var)){
                $file = preg_replace('/\\\+|\/+/s','/',"$dirs/$var.php");
                if ($call && !file_exists($file))
                {
                    file_put_contents($file,$this->preStructureFunc($var));
                    chmod($file,0644);
                }  
                if(file_exists($file)){
                    require($file);
                }                     
            } 
        }
    }   
    function preStructureFunc($name){
        return "<?php function $name(...\$args){\n\tprint \"<b><i style=\\\"color:red\\\"> function $name not build yet!</i></b><br>\";\n}";
    }    	
}
