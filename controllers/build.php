<?php 
// This CLASS is just an extension of fatfree class
// adding a few customs functionality
class build {
    public static $ins;
    private $const;
    private function set_consts(){
        if(!$this->const){
            $strtotime = strtotime(date('Y-m-d H:i:s'));
            // the constant to auto write custom views function in models folder
            $this->const = true;
            $this->pwa_script = <<<JS
var staticCacheName = "pwa-$strtotime"; 
self.addEventListener("install", function (e) {
    e.waitUntil(
    caches.open(staticCacheName).then(function (cache) {
        return cache.addAll(["/"]);
    })
    )
}); 
self.addEventListener("fetch", function (event) {
    vent.respondWith(
    caches.match(event.request).then(function (response) {
        return response || fetch(event.request);
    })
    )
});
JS;
            $tmp_location = isset($this->f3->TEMP_MODELS) ?  $this->f3->TEMP_MODELS : false;
            !$tmp_location || $this->f3->set('TEMP', $tmp_location); 
            defined('TEMP_MODELS') || define('TEMP_MODELS',!$tmp_location ? $this->f3->TEMP : $tmp_location); 
            // automake view models if not exists
            is_dir(TEMP_MODELS) || mkdir(TEMP_MODELS,0755,true);

            // the constant to auto write custom views function in models folder
            $auto_create = is_bool($this->f3->DEV['model']) ?  $this->f3->DEV['model'] : true; 
            defined('AUTO_MODELS') || define('AUTO_MODELS',$auto_create);

            // the constant to custom views function folder
            $view_folder = isset($this->f3->VIEW_MODELS) && is_string($this->f3->VIEW_MODELS) ? $this->f3->VIEW_MODELS : 'app/models/';
            defined('VIEW_MODELS') || define('VIEW_MODELS',$view_folder);
            // auto make view models if not exists
            is_dir(VIEW_MODELS) || mkdir(VIEW_MODELS,0755,true);

            $this->f3->set('assign',function(...$args){                
                /**
                 * connecting databases
                 */
                if(!$this->f3->db && count($this->f3->SQL) >= 3)
                    $this->f3->db = $this->f3->db(); 

                $this->f3->db || die('<i style="color:red">Assign function need database connection!<br>check \'SQL\' in [global] settings (ini file extension)</i>');
                
                $prm = explode('/',substr($this->f3->PATH,1));
                $htm = "app/templates/{$prm[0]}.htm";
                $ptn = <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{@data.title}}</title>
    </head>
    <body id="{{@uuid}}">
        <h1>{{@data.content}}</h1>
    </body>
</html>
HTML;
                file_exists($htm) || $this->f3->write($htm,$ptn,true);

                $main = true;    
                $call = [];            
                $deff = false;
                $this->f3->uuid = $this->f3->db->uuid();
                $this->f3->data['title'] = ucwords($prm[0]);
                $this->f3->data['content'] = 'Hello World';

                foreach ($args as $call) {
                    if(is_callable($call))
                        $deff = $call;
                    if(is_array($call)){
                        foreach ($call as $key => $var) {
                            if(is_string($key) && !is_numeric($key)){
                                if(is_callable($var))
                                    if($this->f3->REQUEST[$key] == $this->f3->db->uuid()){
                                        call_user_func($var,$this->f3);
                                        $main = false;
                                    }
                            }
                        }
                    } 
                }
                
                $call = $call ? ( $deff ? array_merge( [ '__default' => $deff ] , $call ) : $call ) : [];    
                
                if($main){
                    isset($call['__default']) && is_callable($call['__default']) ?  call_user_func($call['__default'],$this->f3) : die('you need to set assign minimum parameter callback is [ \'__default\' => function($f3){} ]');
                    $this->f3->view($htm);
                }else{
                    header("Content-Type:application/json");
                    print json_encode($this->f3->data,JSON_PRETTY_PRINT);
                }
                unset($this->f3->db);                
            });
            $this->f3->set('db',function(...$vals){
                $host = isset($vals[0]) ? "host=".$vals[0] : ( $this->f3->SQL['host'] ? "mysql:host=".$this->f3->SQL['host'] : null);
                $user = isset($vals[1]) ? $vals[1] : ( $this->f3->SQL['user'] ? $this->f3->SQL['user'] : null);
                $pass = isset($vals[2]) ? $vals[2] : ( $this->f3->SQL['pass'] ? $this->f3->SQL['pass'] : null);
                $name = isset($vals[3]) ? ";dbname=".$vals[3] : ( $this->f3->SQL['name'] ? ";dbname=".$this->f3->SQL['name'] : null);
                $port = isset($vals[4]) ? ";port=".$vals[4] : ( $this->f3->SQL['port'] ? ";port=".$this->f3->SQL['port'] : null);
                $engn = isset($vals[5]) ? $vals[5] : ( $this->f3->SQL['engine'] ? $this->f3->SQL['engine'] : "mysql");
                return new \DB\SQL("$engn:$host$port$name",$user,$pass);
            }); 
            
            $this->f3->set('fn',\__fn::get("{$this->f3->ROOT}/{$this->f3->BASE}/app/__functions",true)); 
            $this->f3->set('text',function($file,$mime=null){
                return \Template::instance()->render($file,$mime);
            }); 
            $this->f3->set('view',function($file,$mime=null) {
                if(AUTO_MODELS && $this->f3->APP){

                    $manifest = [];
                    foreach ($this->f3->APP as $key => $value) {
                        if($value)
                            if($key=='screenshots' || $key=='icons'){
                                $list_img = [];
                                $favicons = explode(";",$this->f3->APP[$key]);
                                foreach ( $favicons as $var => $val) {
                                    $val = trim($val);
                                    $get_icon = file_exists($val) ? getimagesize($val) : [ 100,100,'mime'=>'image/vnd.microsoft.icon'];
                                    
                                    if($key == 'screenshots'){
                                        $factory = "narrow";     
                                        if($get_icon[0] >=1000)
                                            $factory = "wide";
                                    }

                                    $arrimg = [
                                        'src' => $val,
                                        'type' => $get_icon['mime'],
                                        'sizes' => "{$get_icon[0]}x{$get_icon[1]}"
                                    ]; 

                                    if($key == 'screenshots')
                                        $arrimg = array_merge($arrimg,["form_factor" => $factory]);

                                    $list_img[]= $arrimg;

                                }
                                if($list_img)
                                    $manifest[$key] = $list_img;

                            }elseif($key == 'version'){
                                $manifest[$key] = $value.($this->f3->DEV['auto'] ? " Dev" : " Pro");
                            }else{
                                $manifest[$key] = $value;
                            }
                    }
                    file_put_contents("manifest.json", json_encode($manifest));                    
                }
                if(file_exists($file)){  
                    preg_match('/\.pug/',$file,$match);
                    if($match){
                        // adding parse pug template engine here
                        $args = is_array($mime) ? $mime : [];
                        $pug = new \Pug([
                            'pretty' => isset($args['pretty']) ? $args['pretty'] : ($this->f3->DEV['minified'] == true ? false:true) ,
                            'cache' => isset($args['cache']) ? $args['cache'] : $this->f3->TEMP,
                        ]);
                        print $pug->renderFile($file, isset($args['param']) ? $args['param'] : null);
                    }else{
                        preg_match('/\.htm/',$file,$match);
                        if($match){
                            // defaul template ngine
                            $mode = isset($this->f3->DEV['minified']) && is_bool($this->f3->DEV['minified']) ? $this->f3->DEV['minified'] : true;
                            $dump = $this->f3->text($file,is_string($mime) ? $mime : null);
                            $dump = $mode ? \minify\html::emit($dump) : \beautify\html::emit($dump);
                            print htmlspecialchars_decode($dump);
                        }
                        // DEV Mode active auto reloader if any script updated
                        if($this->f3->DEV['auto']){
                            $script = <<<JS
setInterval(async () => {
    const hContent = new Headers({
        'Content-Type': 'application/x-www-form-urlencoded'
    }),bContent = new URLSearchParams({
        '__update' : 'stat'
    }),response = await fetch(location.href,{
        method: 'post',
        headers: hContent,
        body:bContent
    });
    response.text().then(function(text){
        var body = document.querySelector('body'),
            text = text.length == 32 ? text : 'error-script';
        if(!body.dataset.stat)
            body.setAttribute('data-stat',text);
        if(body.dataset.stat != text)
            location.reload();
    })
},1200);  
JS;
                            $script = \__fn::minify('js',$script);
                            print "\n<script>$script</script>";
                        }
                    }
                }
            });
            $this->f3->set('script',function($ext,$filename=[]){
                $src = [
                    "js" => "scripts",
                    "css" => "styles"
                ];
                
                $index = isset($filename[0]) && is_string($filename[0]) ? $filename[0] : null;
                if(AUTO_MODELS && isset($src[$ext]) && $index){
                    is_dir("app/templates/{$src[$ext]}") || mkdir("app/templates/{$src[$ext]}",0755,true);
                    $asset = [] ;
                    foreach ($filename as $sfile) {
                        preg_match("/\.$ext/",$sfile,$mtch);
                        if($mtch){
                            $file = "app/templates/{$src[$ext]}/$sfile";
                            file_exists($file) || file_put_contents($file,"/* $sfile */");
                            $source = file_get_contents($file);
                            if($this->f3->DEV['minified']){
                                $asset[] = \__fn::minify($ext,$source);
                            }else{
                                $asset[] = $source;
                            }
                        }
                    }
                    
                    is_dir("assets/$ext") || mkdir("assets/$ext",0755,true); 
                    if($asset && $this->f3->APP && $ext=='js'){
                        // add pwa script here
                        if($this->f3->DEV['minified']){
                            $asset[] = \__fn::minify($ext,$this->pwa_script);
                        }else{
                            $asset[] = $this->pwa_script;
                        }
                    }
                    $index = $asset ? $index : null;
                    file_put_contents("assets/$ext/$index",implode($this->f3->DEV['minified'] ? "":"\n",$asset));
                    // file_put_contents("assets/js/$index",implode("\n",$asset)."\n",FILE_APPEND);
                }
                return file_exists("assets/$ext/$index") ? "assets/$ext/$index?__=".time() : null;
            });
            $this->f3->set('scripts',function(...$filename){
                return $this->f3->script('js',$filename);
            });
            $this->f3->set('styles',function(...$filename){
                return $this->f3->script('css',$filename);
            });
            $this->f3->set('jquery',function(...$filename){
                $asset = null ;
                $index = isset($filename[0]) && is_string($filename[0]) ? $filename[0] : 'index.js';
                unset($filename[0]);
                if(!is_dir("assets/js/plugin"))
                    mkdir("assets/js/plugin",0755,true);
                
                if(!file_exists('assets/js/plugin/jquery.min.js')){
                    print "<b style=\"color:red\"> to use this function, You must placed 'jquery.min.js' file in folder assets/js/plugin </b>";
                }else{
                    is_dir("app/templates/scripts") || mkdir("app/templates/scripts",0755,true);
                    $script[] = "assets/js/plugin/jquery.min.js";
                    foreach ($filename as $name) {
                        $script[] = "app/templates/scripts/$name";
                        if(!file_exists("app/templates/scripts/$name") && preg_match('/\.js/',$name))
                            file_put_contents("app/templates/scripts/$name","/* this is $name file script */");
                    }
                    $index = "assets/js/$index";
                    if(AUTO_MODELS){
                        $concat = [];
                        foreach ($script as $name) {
                            $concat[] = file_get_contents($name);                            
                        }
                        if($concat && $this->f3->APP){
                            // add pwa script here
                            if($this->f3->DEV['minified']){
                                $concat[] = \__fn::minify($ext,$this->pwa_script);
                            }else{
                                $concat[] = $this->pwa_script;
                            }
                        }
                        $concat = implode("\n",$concat);
                        if($this->f3->DEV['minified'])
                            $concat = \__fn::minify($concat);
                        file_put_contents($index,"/* {$this->f3->PACKAGE} */\n$concat");
                    }
                }
                
                return file_exists($index) ? "$index?__=".time() : null;
            });
        }
    }
    static function base($arg=null){
        self::$ins = new build();
        self::$ins->f3 = \Base::instance();   
        if($arg){
            self::$ins->config($arg);
        }     
        preg_match('/(Apache|LiteSpeed)/s',$_SERVER['SERVER_SOFTWARE'],$htsupport);
        if($htsupport) {
            file_exists('.htaccess') || file_put_contents('.htaccess',implode("\n",[
                "<IfModule mod_rewrite.c>",
                "    Options +FollowSymLinks -MultiViews",
                "    RewriteEngine On",
                "    RewriteCond %{REQUEST_FILENAME} !-f",
                "    RewriteRule ^ index.php [QSA,L]", 
                "    <FilesMatch \"\.(ini|env|cfg|conf)$\">",
                "        Order allow,deny",
                "        Deny from all",
                "    </FilesMatch>",
                "</IfModule>",
            ]));
        }
       return self::$ins;
    }

    function route(...$route){
        $this->set_consts();
        $this->f3 = $this->f3;
        $path = array_values(array_filter(preg_split('/\//s',$this->f3->PATH)));
        $main = null; // main route of url
        $nick = null; // alias
        $mcli = []; // mofified cli
        $ncli = null; // natif ajax|cli
        $mtds = null; // methods
        foreach (array_values(array_filter(preg_split('/\s/s',$route[0]))) as $n => $value) {
            if($n>0){
                $chkval = substr($value,0,1);
                if($chkval == '/'){
                    $main = substr($value,1);
                }
                if($chkval == '@'){
                    $nick = " $value";
                }
                if($chkval == '['){
                    preg_match('/\[(ajax|cli)\]/s',$value,$mtchs);
                    if(!$matchs){
                        $mcli = explode('|',preg_replace('/(\]|\[)/is','',$value));
                    }else{
                        $ncli = " $value";
                    }
                }
            }else{
                $mtds = $value;
            }
        }
        
        $router =  "$mtds$nick /$main$ncli";
        
        $main = preg_split('/\//is',$main);  
        $call = isset($route[1]) && $route[1] ? $route[1] : function(){print "$main page container not set!";}; 
        $ttl  =  0;
        $kbps =  0;
        if($main[0] == $path[0]){
            $allow = []; $ttnum = [];
            foreach ($route as $key => $value) {
                if($key > 1 && is_array($value)) {
                    $allow = $value;    
                }
                if($key > 1 && is_numeric($value)) {
                    $ttnum[] = $value;    
                }
            }

            $ttl = isset($ttnum[0]) ? $ttnum[0] : $ttl;
            $kbps= isset($ttnum[1]) ? $ttnum[1] : $kbps;
        
            foreach ([
                "origin", // string or false, the allowed origin host or wildcard
                "headers", // string or array, allowed headers
                "credentials", // bool, if cookies are allowed
                "expose" // string or array, which headers to expose to the client browser
                // "ttl" set in default fatfree route function 
            ] as $num => $cors) {
                // applying cors
                if(isset($mcli[$num]))
                    $this->f3->set("CORS.$cors",$mcli[$num]);
                if(isset($allow[$cors]) && $allow[$cors])
                    $this->f3->set("CORS.$cors",$allow[$cors]);
            }
            
            
            // custom callback for view models
            if(is_string($call)){
                preg_match('/models::.*->.*/s',$call,$matchs);    
                if($matchs){
                    $tcll = preg_split('/::|->/s',$call);
                    if(count($tcll) == 3)                    
                        $call = function($f3,$res,$hdl) use($tcll){                            
                            $call = [];
                            foreach ($tcll as $keys) {
                                foreach ($this->f3->PARAMS as $k => $v) {
                                    if("@$k" == $keys) 
                                        $keys =  $v ;
                                }
                                $call[] = $keys;
                            }
                            $func = $call[2];
                            \models::get($call[1])->$func($this->f3,$res,$hdl);                            
                        };
                }
            }
            // DEV Mode active auto reloader if any script updated
            if($this->f3->DEV['auto'] && $this->f3->POST['__update'] === 'stat')
                $call = function(){
                    $root = preg_replace('/\\\+|\/+/','/',"{$this->f3->ROOT}/{$this->f3->BASE}");
                    $time = 0;
                    $size = 0;
                    foreach (\__fn::open_folder("$root/app") as $file) {
                        if($file->isFile()){
                            $stat = stat(preg_replace('/\\\+|\/+/','/',$file->getRealPath()));
                            $size += $stat['size'];
                            if($time < $stat['mtime'])
                                $time = $stat['mtime'];
                        }
                    }
                    $stat = stat("$root/index.php");
                    $size += $stat['size'];
                    if($time < $stat['mtime'])
                        $time = $stat['mtime'];
                    header('Content-Type: text/plain');
                    print md5("$time-$size");
                };
            
            $this->f3->route($router,$call,$ttl,$kbps);
        }
        
    }
    function config($str){
        $str = file_exists($str) ? $this->f3->read($str) : $str;
        $arg = \ascii\ini_style::emit($str);
        
        foreach ($arg as $cst => $var) {
            $globals = null;
            if($cst == 'globals'){
                foreach ($var as $key => $val) {
                    $val = defined($val)? constant($val) : $val; 
                    $this->f3->set($key,$val);
                }
            }
        }

        $this->set_consts();
        
        foreach ($arg as $cst => $var) {
            if($cst == 'routes'){
                foreach ($var as $key => $val) {
                    $this->route($key,$val);
                }
            }
        }      

        return $this;
    }
    function reroute(...$args){
        $this->f3->reroute(...$args);
    }
    function mock(...$args){
        return $this->f3->mock(...$args);
    }
    function set(...$args){
        return $this->f3->set(...$args);
    }
    function get(...$args){
        return $this->f3->get(...$args);
    }
    function run(){
        $this->f3->run();
    }
}
