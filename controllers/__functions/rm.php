<?php
// function for recursive remove
function rm($src){    
    if(!function_exists('__rmv_file')){
        function __rmv_file($src) {
            chmod($src,0755);
            @unlink($src);						
        }
    }
    if(!function_exists('__rmv_dirs')){
        function __rmv_dirs($src) {
            chmod($src,0755);     
            $dir = opendir( $src ); 		
            while( false !== ( $file = readdir( $dir ) ) ) { 
                if( $file != '.' && $file != '..' ) { 
                    if(is_dir("$src/$file")) 
                        __rmv_dirs("$src/$file");                     
                    if(is_file("$src/$file"))
                        __rmv_file("$src/$file");
                } 
            }			
            closedir($dir);
            @rmdir($src);
        }
    }
    if(is_dir($src))   
        __rmv_dirs($src); 
    if(is_file($src))
        __rmv_file($src);
}