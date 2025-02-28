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
            // for js get script and style
            $this->get_script_const = <<<JS
        /** add some custom style and script per page */
        this.onStyle = %s,
        this.onScript = %s;
JS;
            // this is js for get page name
            $this->get_path_pgname = <<<JS
    getPart = () => {
        var path = window.location.pathname.split("/"),
            part = path[path.length - 1].trim();
            return part ? part : 'index'
    };
JS;
            // this is alternative getScript funtion in jQuery
            $this->get_script_funct = <<<JS
    /**GET SCRIPT */
    addScript = () =>{
        if(this.onStyle[this.getPart()])
            this.onStyle[this.getPart()].forEach((val) => this.getStyle(val));
        if(this.onScript[this.getPart()])
            this.onScript[this.getPart()].forEach((val) => this.getScript(val));
    };
    getScript = (url) => {
        if (document.querySelector(`script[src="\${url}"]`)){
            /*console.log(url + ' already loaded');*/
            return Promise.resolve(); 
        }
        return new Promise((resolve, reject) => {
            const script = document.createElement("script");
            script.src = url;
            script.async = true;
            script.onload = resolve;
            script.onerror = reject;
            document.body.appendChild(script);
        })
    };
    getStyle = (url) => {
        if (document.querySelector(`link[href="\${url}"]`)) {
            /*console.log(url + ' already loaded');*/
            return Promise.resolve(); 
        }
        return new Promise((resolve, reject) => {
            const link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = url;
            link.onload = resolve;
            link.onerror = reject;
            document.head.appendChild(link);
        })
    };
JS;
            $this->spa_script_const = <<<JS
        /** default page container header, main, footer */
        this.siteHead = "header",
        this.siteMain = "main",
        this.siteFoot = "footer",
        this.namePage = "pg-stat-name",
        this.nameStat = "pg-stat-load";
        const getMain = document.querySelector(this.siteMain);
        /** make name page as same as url */
        if(!getMain.getAttribute(this.namePage)){
            getMain.setAttribute(this.namePage,this.getPart());
        }
    
JS;
            $this->spa_script_funct = <<<JS
    route = (event) => {
        event = event || window.event;
        event.preventDefault();
        window.history.pushState({}, "", event.target.href);                   
        this.getPage();
    };
    getPage = async () => { 
        const mainElement = document.querySelector(this.siteMain);
        var grab=true,page = this.getPart();

        /** make page status processing */
        if(!mainElement.getAttribute(this.nameStat))
            mainElement.setAttribute(this.nameStat,'loaded');        

        /** make clear that content is not the same content*/
        if(mainElement.getAttribute(this.nameStat) == 'loaded'){
            /** check thee url with hash and in same page */
            if(window.location.hash)
                grab=false;

            /** check if query url available allow to load the page */
            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.size > 0)
                grab=true;

            /** check page name is not the same & grab value is true */
            if(grab || mainElement.getAttribute(this.namePage) != page){
                /** send status page processing */
                mainElement.setAttribute(this.nameStat,'process');

                var html = await fetch(page).then((data) => data.text()),
                htmc;
                /** handle error page 4** to 5** 
                 * 
                if(html.match(/<title>(\s+)?(4|5)\d{1,2}\s/)){
                    html = await fetch(page.replace(page,'404')).then((data) => data.text());
                }
                */
                /** parsing html template title */
                htmc = html.split(/<(\/)?title((\s+)?([\w-]+="[^"]*")?)+?>/ig)[5];
                document.title = htmc;

                /** parsing html template content */
                htmc = html.split(/<(\/)?main((\s+)?([\w-]+="[^"]*")?)+?>/ig)[5];
                mainElement.innerHTML = htmc;

                /** send content status page already loaded */
                mainElement.setAttribute(this.nameStat,'loaded');
            }
            
            /** this is handling hashtags */
            if(this.getHash(1)){
                var getIDElement = document.getElementById(this.getHash(1));
                !getIDElement || window.scrollTo(0, getIDElement.offsetTop);
            }
            /** send status named page same as url */
            mainElement.setAttribute(this.namePage,page);
            /** add another script here */
            if(typeof this.addScript === 'function')
                this.addScript()
        }else{
            console.log("page still loading");
        }
    };
    getHash = (ints) => {
        const hashData = window.location.hash.split("#");
        return ints > 1 ? hashData[ints] : hashData[1];
    };
JS;
            // for spa & pwa content
            $this->spa_script = <<<JS
class vanilaSPA {
    constructor() {         
        {$this->spa_script_const}
        {$this->get_script_const}
    }
    {$this->get_path_pgname}
    {$this->spa_script_funct}
    {$this->get_script_funct}
}
F3 = new vanilaSPA();
window.onpopstate = F3.getPage;
if(typeof F3.addScript === 'function')
    F3.addScript();
/*window.onload = F3.getPage;*/
document.addEventListener('click', function(event) {    
    /** Check if the clicked element is an <a> tag */ 
    const anchor = event.target.closest('a');    
    if (anchor){
        /** get the href attribute value to check if it starts with # */ 
        const href = anchor.getAttribute('href');
        if (anchor.hasAttribute('href') && !anchor.hasAttribute('target') && !href.startsWith('#') && !href.match(/(http(s)?:)?\/\//)) 
            F3.route(event)
    }
});
JS;
            $addSPAScript = isset($this->f3->APP['mode_spa']) && $this->f3->APP['mode_spa'] ? "\n{$this->spa_script}" : null;
            $this->pwa_script = <<<JS
var staticCacheName = "pwa-$strtotime"; 
self.addEventListener("install", function (e) {
    e.waitUntil(
    caches.open(staticCacheName).then(function (cache) {
        return cache.addAll(["/"])
    })
    )
}); 
self.addEventListener("fetch", function (event) {
    vent.respondWith(
    caches.match(event.request).then(function (response) {
        return response || fetch(event.request)
    })
    )
});$addSPAScript
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
            
            // add index vanila spa page
            $spa = <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{@APP.name}}</title>
        <link rel="manifest" href="manifest.json">
        <link rel="shortcut icon" href="assets/img/icon-192.png" type="image/x-icon">
        <link rel="stylesheet" href="assets/css/app.css?v={{time()}}">
    </head>
    <body>
        <header>
            <div class="container">
                <b>This is Header</b>
            </div>
        </header>
        <main>
            <div class="container">
                <noscript><b>You need to enable JavaScript to run this app.</b></noscript>
                <h3>Welcome! This is SPA Content page</h3>   
                <p>Simple FATFREE customizable MVC, functionally patterned, arrangement and writing custom function automatically also SPA & PWA Integration.</p>          
            </div>
        </main>
        <footer>
            <div class="container">
                <b>This is Footer</b>
            </div>
        </footer>
        <script src="assets/js/app.js?v={{time()}}"></script>
    </body>
</html>
HTML;
            // make spa index experimental
            if(AUTO_MODELS && $this->f3->APP['mode_spa']){
                $def_css=<<<CSS
@import url("https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css");
@import url("https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap");

html,
body{
  height:100%;  
}
body{
  font-family: "Lato", sans-serif;
  display:table;  
  width:100%;
}
header,
main,
footer{
  display:table-row;
  height:1px;    
}
main{
  height:100%;  
}
footer {
  left: 0;
  bottom: 0;
  width: 100%;
  /* background-color: #f1f1f1; */
  color: #444;
  text-align: center;
}
.container{
    padding: 10px;
}

/* Add next styles here */
CSS;
                file_exists("app/templates/spa_index.htm") || $this->f3->write("app/templates/spa_index.htm",$spa,true);
                // foreach (["scripts"=>"app.js","styles"=>"app.css"] as $key => $value) {
                foreach (["scripts"=>"app.js","styles"=>"app.css"] as $key => $value) {
                    is_dir("app/templates/{$key}") || mkdir("app/templates/{$key}",0755,true);
                    $add_style =  "";
                    if($key == "styles"){
                        $add_style = "\n$def_css" ;
                        /** only for css */
                        file_exists("app/templates/{$key}/{$value}") || $this->f3->write("app/templates/{$key}/{$value}","/** custom {$key} here */$add_style",true);
                    }
                }
            }

            $this->f3->set('assign',function(...$args){                
                /**
                 * connecting databases
                 */
                if(!$this->f3->db && count($this->f3->SQL) >= 3)
                    $this->f3->db = $this->f3->db(); 

                $this->f3->db || die('<i style="color:red">Assign function need database connection!<br>check \'SQL\' in [global] settings (ini file extension)</i>');
                
                $prm = explode('/',substr($this->f3->PATH,1));
                $htm = "{$prm[0]}.htm";
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
        <main>
            <h1>{{@data.content}}</h1>
        </main>
    </body>
</html>
HTML;
                file_exists("app/templates/$htm") || $this->f3->write("app/templates/$htm",$ptn,true);
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
                $host = isset($vals[0]) ? "host=".$vals[0] : ( $this->f3->SQL['host'] ? "host=".$this->f3->SQL['host'] : null);
                $user = isset($vals[1]) ? $vals[1] : ( $this->f3->SQL['user'] ? $this->f3->SQL['user'] : null);
                $pass = isset($vals[2]) ? $vals[2] : ( $this->f3->SQL['pass'] ? $this->f3->SQL['pass'] : null);
                $name = isset($vals[3]) ? ";dbname=".$vals[3] : ( $this->f3->SQL['name'] ? ";dbname=".$this->f3->SQL['name'] : null);
                $port = isset($vals[4]) ? ";port=".$vals[4] : ( $this->f3->SQL['port'] ? ";port=".$this->f3->SQL['port'] : null);
                $engn = isset($vals[5]) ? $vals[5] : ( $this->f3->SQL['engine'] ? $this->f3->SQL['engine'] : "mysql");
                return new \DB\SQL("$engn:$host$port$name",$user,$pass);
            }); 
            
            $fnscema = "<?php function %s(...\$args){\n\t//==== FATFREE INSTANCE OBJ [remove if not used] ====>\n\tif(\$f3=\\build::instance()){\n\t\t// write your code here\n\t\t// ..........\n\t\tprint \"<b><i style=\\\"color:purple\\\"> function %s created!</i></b><br>\";\n\t}\n}";
            $this->f3->set('fn',\__fn::get("{$this->f3->ROOT}/{$this->f3->BASE}/app/__functions",true,$fnscema));  
            $this->f3->set('text',function($file,$mime=null){
                return \Template::instance()->render($file,$mime);
            }); 
            $this->f3->set('put_contents',function($filePath, $contents, $flags = 0) {
                if (! is_dir($dir = implode('/', explode('/', $filePath, -1))))
                    mkdir($dir, 0777, true);
                file_put_contents($filePath, $contents, $flags);
            });
            $this->f3->set('view',function($file,$mime=null) {
                $file = "app/templates/$file";
                $call = is_callable($mime) ? $mime : null; 
                $mime = is_callable($mime) ? null : $mime; 
                if(AUTO_MODELS && $this->f3->APP){

                    $manifest = [];
                    foreach ($this->f3->APP as $key => $value) {
                        if($value && $key !=='mode_spa')
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
            location.reload()
    })
},1200);  
JS;
                            $script = \__fn::minify('js',$script);
                            print "\n<script>$script</script>";
                        }
                    }
                }
                // var_dump($this);
                // this is how spa works on view function
                if(AUTO_MODELS && $this->f3->APP['mode_spa']){
                    if(!function_exists('__add_pwa_pgscript')){
/**
 * Generates and stores minified versions of scripts and stylesheets for a given node path.
 *
 * This function processes an array of script or stylesheet paths, minifies the content if necessary,
 * and stores the processed files in the specified directory. It also updates a JSON file with the 
 * paths to these processed files for the given node path.
 *
 * @param string $node_path The path identifier for the node.
 * @param array $arrs An array of script or stylesheet paths to process.
 * @param string $cdir The directory where original files are located.
 * @param string $dir The directory where minified files will be stored.
 * @param string $fjson The filename of the JSON file that stores the processed file paths.
 * @param string $temp A temporary directory for storing the JSON file.
 * @param bool $shrink Indicates whether the files should be minified.
 * @return string JSON encoded array of paths to the processed files.
 */

                        function __add_pwa_pgscript($node_path,$arrs,$cdir,$dir,$fjson){
                            if($f3 = \Base::instance()){
                                $p_js = [];
                                $cdir = "$cdir/$node_path";
                                is_dir($cdir) || mkdir($cdir,0755,true);
                                is_dir($dir) || mkdir($dir,0755,true);
                                foreach ($arrs as $scr) {
                                    $putloc = $scr;
                                    if(!preg_match('/(http(s)?:)?\/\//',$scr)){
                                        file_exists("$cdir/$scr") || file_put_contents("$cdir/$scr","/* $scr */");
                                        $putscr = file_get_contents("$cdir/$scr");
                                        if(preg_match('/\/js/is',$dir)){
                                            $putloc = "$dir/".md5($scr).".js";
                                            if($f3->DEV['minified'])
                                                $putscr = \__fn::minify('js',$putscr);
                                        }else{
                                            $putloc = "$dir/$scr";
                                            if($f3->DEV['minified'])
                                                $putscr = \__fn::minify('css',$putscr);
                                        }
                                        
                                        file_put_contents($putloc,$putscr);
                                    }
                                    $p_js[] = $putloc;
                                }
                                if($p_js){                                
                                    $tmjson = "{$f3->TEMP}/$fjson";
                                    file_exists($tmjson) || file_put_contents($tmjson,"{}");
                                    $json = json_decode(file_get_contents($tmjson),true);
                                    unset($json[$node_path]);
                                    $arrs = json_encode(array_merge($json,[$node_path=>$p_js]));
                                    file_put_contents($tmjson,$arrs);
                                }else{
                                    $arrs = "[]";
                                }
                            }else{
                                $arrs = "[]";
                            }

                            return $arrs;
                        }
                    }
                    // get callback function and object property from $mime value
                    $prop = (object)[];
                    $prop->css ='[]';
                    $prop->js ='[]';
                    if($call){
                        call_user_func($call,$prop); 
                        $node_path = explode("/",substr($this->f3->PATH,1));
                        $node_path = $node_path[0] ? $node_path[0] : "index";
                        // generating css and json
                        if(is_array($prop->css))
                            $prop->css = __add_pwa_pgscript($node_path,$prop->css,"app/templates/styles","assets/css","page_css.json");
                        
                        // generating js and json
                        if(is_array($prop->js))
                            $prop->js = __add_pwa_pgscript($node_path,$prop->js,"app/templates/scripts","assets/js/node","page_js.json");
                        
                    }
                    
                    $spa_script = sprintf($this->pwa_script,$prop->css,$prop->js); 
                    if($this->f3->DEV['minified'])
                        $spa_script = \__fn::minify('js',$spa_script);

                    foreach (["scripts"=>"app.js","styles"=>"app.css"] as $key => $value) {
                        $asset = "assets/".substr($value,4);
                        is_dir($asset) || mkdir($asset,0755,true);
                        if($value == 'app.css')
                            /** this only writes once for example so use f3->write */
                            file_exists("$asset/$value") || $this->f3->write("$asset/$value",file_get_contents("app/templates/{$key}/$value"),true);
                        if($value == 'app.js')
                            if(!file_exists("$asset/$value") || $call)
                                /** if you use f3->write, it will add as chunks because fopen mode */
                                file_put_contents("$asset/$value",$spa_script,true);
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
                    foreach ($filename as $var => $sfile) {
                        preg_match("/\.$ext/",$sfile,$mtch);
                        if($mtch){
                            $file = "app/templates/{$src[$ext]}/$sfile";
                            // if after 2nd files not exists generate & include                              
                            if($var>0)  
                                file_exists($file) || file_put_contents($file,"/* $sfile */");
                            
                            if(file_exists($file)){
                                $source = file_get_contents($file);
                                if($this->f3->DEV['minified']){
                                    $asset[] = \__fn::minify($ext,$source);
                                }else{
                                    $asset[] = $source;
                                }
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
    static function instance($arg=null){
        if(self::$ins){
            return self::$ins->f3;
        }else{
            die("<h1>You not have base already, use build::base(string config) before use this instance</h1>");
        }
    }
    private function filter_routes($path){
        $path = array_values(array_filter(preg_split('/\//s',$path)));
        return $path ? $path : [''];
    }
    function route(...$route){
        $this->set_consts();
        $this->f3 = $this->f3;
        $path = $this->filter_routes($this->f3->PATH);
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
                    if(!$mtchs){
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
        
        $main = $this->filter_routes($main);
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
            if($this->f3->DEV['auto'] && isset($this->f3->POST['__update']) && $this->f3->POST['__update'] === 'stat')
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
        $arg = \parse\ini::emit($str);
        
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