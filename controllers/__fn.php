<?php
// auto call functions 
class __fn
{
    private static $ths;
    private $create = false,
            $folder = __DIR__."/__functions",
            $fn_scema,
            $souce  = [];
            
    function __construct(){
        $this->source[$this->folder] = $this->create;
    }

    static function get($dirs=null,$call=true,$fnscema = null){
        self::$ths = new \__fn();       
        self::$ths->fn_scema = $fnscema;
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
        // you can change this template function on $this->fn_scema
        $fnscema = "<?php function %s(...\$args){\n\tprint \"<b><i style=\\\"color:red\\\"> function %s not build yet!</i></b><br>\";\n}";
        if($this->fn_scema){
            $fnscema = $this->fn_scema;
        }
        return sprintf($fnscema,$name,$name);
    }    	
}
