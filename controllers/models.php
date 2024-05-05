<?php
/*
 | EXAMPLE USE:
 | models::yourCustom_Function('string');
 | models::get('__location/folder')->myFunctions('string');
 */
class models
{
    private static $ths;
    private $anonym,
            $folder,
            $write,
            $regx  = '/^<\?php(\s+|\t+|\n+)function\(/m';
            
    function __construct(){
        // define default folders of functions & temporary caching
        defined('TEMP_MODELS') || define('TEMP_MODELS','tmp/');
        defined('VIEW_MODELS') || define('VIEW_MODELS','app/models/');
        defined('AUTO_MODELS') || define('AUTO_MODELS',true);
        defined('AUTO_CACHES') || define('AUTO_CACHES',false);
        is_dir(VIEW_MODELS) || mkdir(VIEW_MODELS,0755,true);
        if(AUTO_CACHES)
            is_dir(TEMP_MODELS) || mkdir(TEMP_MODELS,0755,true);
        if(!file_exists(TEMP_MODELS."/.htaccess")){
            preg_match('/(Apache|LiteSpeed)/s',$_SERVER['SERVER_SOFTWARE'],$htsupport);
            if($htsupport)
                file_put_contents(TEMP_MODELS."/.htaccess","deny from all",true);
        }
        $this->folder = VIEW_MODELS;
        $this->write  = AUTO_MODELS;
        if($this->write)
            is_dir("app/templates") || mkdir("app/templates",0755,true);
    }

    static function get($dirs=null,$call=true){
        // however duplication is will ignored
        // enven use 
        $dirs = preg_replace('/\\\+|\/+/s','/',VIEW_MODELS."/$dirs");
        self::$ths = new \models(); 
        if(is_string($dirs)){
            is_dir($dirs) || mkdir($dirs,0755,true);
            self::$ths->folder = $dirs; 
            self::$ths->write  = $call; 
        }
        return self::$ths;
    }
    
    static function __callStatic($var,$arg)
    {
        self::$ths = new \models();       
        self::$ths->get_source_function($var);
        return call_user_func_array(self::$ths->$var,$arg);
    } 
    
    function __call($var,$arg)
    {
        $this->get_source_function($var); 
        return call_user_func_array($this->$var,$arg); 
    }

    private function get_source_function($var){
        if ($this->folder) {
            $file = preg_replace('/\/+/','/',"{$this->folder}/$var.php");
            if ($this->write && !file_exists($file))
            {
                file_put_contents($file,$this->fn_schema($var));
                chmod($file,0644);
            }  
            if(file_exists($file)){
                if(AUTO_CACHES){
                    $fnames = substr(md5("{$_SERVER['HTTP_HOST']}/{$_SERVER['PHP_SELF']}"),0,6)."fc2n0.".substr(md5($file),0,13);
                    $anonym = preg_replace('/\\\+|\/+/s','/',TEMP_MODELS."/$fnames.php");
                    if(!file_exists($anonym) || $this->chk_modified($file,$anonym)){
                        $text = $this->write_anonym($file,"<?php \$this->$var = function(");
                        file_put_contents($anonym,$text);
                    }
                    $file = $anonym;
                }                
                require($file);
            } 
        }
    } 
    private function chk_modified($src1,$src2)
    {
        $write = true;
        if(file_exists($src1) && file_exists($src2)){
            $file1 = date("Y-m-d H:i:s", filemtime($src1));
            $file2 = date("Y-m-d H:i:s", filemtime($src2));
            if($file2 > $file1) $write = false;
        }
        return $write;
    }
    private function write_anonym($path,$head){
        $node = fopen($path,"r");
        $text = null;
        $nums = 0;
        while($strs = fread($node,250)){
            if($nums==0){
                preg_match($this->regx,$strs,$check);
                if($check){                     
                    $strs = preg_replace($this->regx,'',$strs);
                    $text.= "{$head}$strs";
                }else{
                    $text.= $strs;
                }
            }else{
                $text.= $strs;
            }
            $nums++;
        }  
        fclose($node);
        return $text;
    }    
    private function fn_schema($name){
        $title = strtoupper($name);
        if(AUTO_CACHES){
            return "<?php function(\$f3,\$res,\$hdl){//===== $title FUNCTION START HERE ==========>\n\t\n\tprint \"<b><i style=\\\"color:orange\\\"> function $name is ready to use!</i></b><br>\";\n\t\n}?>";
        }else{
            return "<?php \$this->$name = function(\$f3,\$res,\$hdl){//===== FUNCTION START HERE ==========>\n\t\n\tprint \"<b><i style=\\\"color:orange\\\"> function $name is ready to use!</i></b><br>\";\n\t\n}?>";
        }
    }
    	
}
