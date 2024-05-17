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
        // add if models is for error page
        $errms = "\$f3 = \$args[0];\n\tprint \"<b><i style=\\\"color:orange\\\">Route to \$f3->PATH path is open!</i></b><br>\";";

        if($title == "ERROR"){
            $htmlt = <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {{@ERROR.code}} ~ {{@ERROR.status}}</title>
    <style>
      body { 
        font-family: sans-serif; 
        font-size: 1rem;
        margin: 0;
        padding: 3rem;
        line-height: normal;
      }
      section { color: green; }
      h1 { line-height: .5rem; }
      h1,code { color: red; }
      h3 { color: orange; }
      pre {
        font-family: monospace;
        margin-top: -1.5rem;
        line-height: .5rem;
      }
    </style>
  </head>
  <body>
    <h1>Error {{@ERROR.code}} : {{@ERROR.status}}</h1>
    <h3># {{@ERROR.text}}</h3> 
    <pre>
      <code>
<loop from="{{ @i=0 }}" to="{{ @i < @error_count }}" step="{{ @i++ }}">
{{((@error_count-1) == @i ?  '└──' : '├──').@error_trace[@i]}}<br/>
</loop>
      </code>
    </pre>
    <section>
      <p>You are trying to access <b>{{@PATH}}</b> that is not associate with our web, follow the instructions below;</p>
      <ul>
        <repeat group="{{ @recommended }}" key="{{ @ikey }}" value="{{ @ival }}">
          <li>{{ @ival }}</li>
        </repeat>
      </ul>
    </section>
  </body>
</html>
HTML;
            $errms = join("\n\t",[
                "\$f3 = \$args[0];",
                "\$f3->set(\"recommended\",[",
                "    \"It used to appear message from your browser that you are not able to access this web\",",
                "    \"This is only example error messages that you can build something else\",",
                "]);",
                "\$f3->error_trace = array_values(array_filter(preg_split('/\n/',\$f3->get(\"ERROR.trace\"))));",
	            "\$f3->error_count = count(\$f3->error_trace);",
                "http_response_code(\$f3->get(\"ERROR.code\"));",
                "\$f3->view('app/templates/error.htm');"
            ]);
            file_exists('app/templates/error.htm') || file_put_contents('app/templates/error.htm',$htmlt);
        }
        
        if(AUTO_CACHES){
            return "<?php function(...\$args){//===== $title FUNCTION START HERE ==========>\n\t\n\t$errms\n\t\n}?>";
        }else{
            return "<?php \$this->$name = function(...\$args){//=====  $title FUNCTION START HERE ==========>\n\t\n\t$errms\n\t\n}?>";
        }
    }
    	
}
