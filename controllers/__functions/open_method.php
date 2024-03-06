<?php
function open_method($action, $string, $crypt=6621) {
    if(defined('APP_CRYPT')){
        $crypt = !APP_CRYPT || is_array(APP_CRYPT) || is_bool(APP_CRYPT)? $crypt : APP_CRYPT;
    }
    if(!function_exists('open_crypto_parse')){
        function open_crypto_parse($act, $text, $method, $key, $iv){
            $str = false;
            if($act == 'encrypt' || $act == 'ehash'){
                $str  = openssl_encrypt($text, $method, $key, 0, $iv);
                if($act == 'encrypt')
                    $str = base64_encode($str);
                $pars = substr($str, 0, -2);
                $args = substr($str, strlen($pars));
                foreach(['0=','==','09'] as $char){
                    if($args == $char)
                        $str = $pars;
                }
            }
            if($act == 'decrypt' || $act == 'dhash'){
                $str = $act == 'decrypt' ? base64_decode($text) : $text;
                $str = openssl_decrypt($str, $method, $key, 0, $iv); 
            }
            return $str;
        }
    }					
    return open_crypto_parse($action,$string,"AES-256-CBC",hash('sha256', substr($crypt,0,ceil(strlen($crypt)/2))),substr(hash('sha256', substr($crypt,ceil(strlen($crypt)/2))), 0, 16));
}