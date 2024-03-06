<?php
// minifiy script default js, (can be minify css script instead)
// example minify('js','yourscript') or minify('js script') will return string
// recursive minify('js', 'script js1','script js2','html','script html1','script html2') will return array
function minify(...$text){
    $allows = 'js|css|html';
    $minify = 'js';
    $script = [];
    $ignore = "pre|code|textarea|blockquote";
    $regex1 = [
        // Remove breakline, space & comments /*....*/
        '/\/(\/)?(\s+)?\*+[\s\S]*?\*+(\s+)?\/(\/)?|\n+|\s+|\t+/',
        // Remove space after & before of symbol }{><]|()?!+=;,:
        '/(\s+)?(\}|\{|\>|\<|\]|\(|\)|\?|\!|\+|\||=|;|,|:)(\s+)?/i',
        // Remove white-space(s) outside the string and regex
        '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',	
        // Remove the last semicolon }
        '#(;+\}|\s+\})#',
        // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
        '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
        // --ibid. for js array to obj format From `foo['bar']` to `foo.bar`
        '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i', 
        // remove space before if,var,$
        '/(?<=(,|;|\{|\}))(\s+)(?=(if|var|\$))/i'
    ];
    $regex2 = [
        ' ',
        '$2',
        '$1$2',
        '}',
        '$1$3',
        '$1.$3',
        ''
    ];
    foreach ($text as $value) {
        $def_ext = false;
        if(isset($value) && is_string($value)){
            foreach (explode("|",$allows) as $key) {
                if($key && $key == $value){
                    $minify = $key;
                    $def_ext = true;
                }
            }
            if(!$def_ext){
                $script[$minify][] = $value;
            }
        }  
        if(isset($value) && is_object($value) && isset($value->ignore) && $value->ignore){
            $ignore = $value->ignore;
        }       
    }
    $regex3 = [
        // remove tab, space & comments for html 
        '/(\t+|\s+|<!--(.*?)-->)/',
        // remove breakline
        '/[\n\r]/i',
        '/(\s+)?(\}|\{|\>|\<|\]|\(|\)|=|;|:)(\s+)?/i',
        // remove space before if,var,$ html tags (also js include js in attr )
        '/(?<=(,|;))(\s+)(?=(if|var|\?|\!|\$))/i',
        // remove space before end of semicolon html singleton tag
        '/\s+?\/>/'
    ];  
    $regex4 = [$regex2[0],$regex2[6],$regex2[1],$regex2[6],'/>'];
    $count = count($script);
    foreach (explode("|",$allows) as $key) {
        if($key && isset($script[$key]) && $script[$key]){
            $shrink = implode("",$script[$key]);
            if($key == 'js'){
                $shrink = trim(preg_replace([
                    $regex1[0],
                    $regex1[1],
                    //$regex1[2],
                    $regex1[3],
                    $regex1[4],
                    $regex1[5],
                    $regex1[6],
                ],
                [
                    $regex2[0],
                    $regex2[1],
                    //$regex2[2],
                    $regex2[3],
                    $regex2[4],
                    $regex2[5],
                    $regex2[6],
                ],$shrink)); 
            }
            if($key == 'css'){
                $shrink = trim(preg_replace([
                    $regex1[0],
                    $regex1[1],
                    $regex1[3],
                    $regex1[6],
                ],
                [
                    $regex2[0],
                    $regex2[1],
                    $regex2[3],
                    $regex2[6],
                ],$shrink));  
            }
            if($key == 'html'){
                $shrink = implode("",$script[$key]);
                if(is_bool($ignore) && $ignore == true){
                    $shrink = preg_replace($regex3,$regex4,$shrink);
                }
                if(is_string($ignore)){
                    $bypass = preg_split('/(<\/?'.$ignore.'[^>]*>)/Uis', $shrink, null, PREG_SPLIT_DELIM_CAPTURE); 
                    $shrink = null;
                    foreach($bypass as $i => $path)
                    {
                        $not_filtered = false;
                        if($i % 4 == 2){
                            // this filter to make sure that ignore tags not catch up on this method
                            preg_match('/(<\/?'.$ignore.'[^>]*>)/i',$path,$match);
                            if($match){
                                $shrink .= $path;                            
                            }else{ 
                                $not_filtered = true;
                            }
                        }else{ 
                            $not_filtered = true;
                        }
                        if($not_filtered)
                            $shrink .= preg_replace($regex3,$regex4,$path);
                    }                    
                }
                
            }   
            if($count > 1){
                $script[$key] = $shrink;
            }else{
                $script = $shrink;
            }
        }
    }

    return $script ? $script : false ;
    
}