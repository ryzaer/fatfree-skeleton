<?php function send_response($args){
    $code=isset($args->code) ? $args->code : 200;
    $mime=isset($args->mime) ? $args->mime : null;
    $file=isset($args->file) ? $args->file : null;
    $text=isset($args->text) ? $args->text : null;
    $time=isset($args->time) ? $args->time : null;
    $save=isset($args->save) && is_bool($args->save) ? $args->save : false;

    !$time or date_default_timezone_set($time);
    http_response_code($code);
    // header('Access-Control-Allow-Origin: 192.168.1.129');  
    // header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: PUT,POST');
    !$mime or header("Content-Type:$mime");    
    !$text or print $text;    
    if(!$text && file_exists($file)){
        readfile($file);
        $save or unlink($file);
    }
}