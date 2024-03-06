<?php
/*
 | infomation 
 | \__fn::basedirs(
    string location, 
    string (regular expression) specific files need to show, 
    bool assoc type output if true, false is default);
 | How to use
 | \__fn::basedirs('home/folder') // show only folder
 | \__fn::basedirs('home/folder','/\.jp(e?)g/') // output array files type .jpg [...]
 | \__fn::basedirs('home/folder','/\.jp(e?)g/',true) // output array assoc file type .jpg [ 'home/folder' => [...] ]
 */
function basedirs($location,$regxfile=null,$assoc=false){
    $location = is_array($location) ? $location : [$location];
    $basedirs = [];
    foreach ($location as $dir) {
        if($handle = is_dir($dir) ? opendir($dir) : null)
            while($chk = readdir($handle)){
                if($chk != "." && $chk != ".."){
                    $type = preg_replace('/\\\+|\/+/','/',$dir."/".$chk);
                    $push = true;
                    if($regxfile){                            
                        preg_match($regxfile,$type,$mtch);
                        if(!$mtch)
                            $push = false;                            
                    }
                    if($push)
                        if($assoc){
                            $basedirs[$dir][] = $chk;
                        }else{
                            $basedirs[] = $type;
                        }
                }    
            } 
    }    
    return $basedirs;  
}
