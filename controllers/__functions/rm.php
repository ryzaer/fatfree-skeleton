<?php
// function for recursive remove
function rm($src){    
    if(is_dir($src)){    
        chmod($src,0755);     
        $dir = opendir( $src ); 		
        while( false !== ( $file = readdir( $dir ) ) ) { 
            if( $file != '.' && $file != '..' ) { 
                if(is_dir("$src/$file")) {
                    chmod("$src/$file",0755);
                    rm("$src/$file"); 
                }
                if(is_file("$src/$file")){ 
                    chmod("$src/$file",0755);
                    @unlink("$src/$file");						
                } 
            } 
        }			
        closedir($dir);
        @rmdir($src);
    }
    if(is_file($src)){ 
        chmod($src,0755);
        @unlink($src);						
    } 
}