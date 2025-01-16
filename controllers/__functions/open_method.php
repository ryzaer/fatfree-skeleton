<?php
// example use
// __fn::open_method(args_algo,str);
// $str = __fn::open_method('encrypt','str');
// print __fn::open_method('decrypt',$str);
// $str = __fn::open_method('ehash','str'); // for hash result
// print __fn::open_method('dhash',$str);
// $str = __fn::open_method('en_tiger','str');
// print __fn::open_method('de_tiger',$str);
// $str = __fn::open_method('eh_sha','str'); // for hash result
// print __fn::open_method('dh_sha',$str);
// try more for algo 40 chars array list
function open_method($args, $string, $salt=6621) {
    $blow = defined('APP_CRYPT') ? APP_CRYPT : $salt;    
    // sha1|ripemd160|haval160,5|haval160,4|haval160,3|tiger160,3 (40chars)
    $arrs = [
        'sha'    => 'sha1',
        'tiger'  => 'tiger160,3',
        'haval1' => 'haval160,3',
        'haval2' => 'haval160,4',
        'haval3' => 'haval160,5'
    ];
    // default using algo ripemd 160bit
    $action = $args;    
    $algo   = 'ripemd160';
    $mode   = substr($args,0,3);    
    foreach ([
        'en_' => 'encrypt',
        'de_' => 'decrypt',
        'dh_' => 'dhash',
        'eh_' => 'ehash'
    ] as $key => $act) {
        if($mode == $key){
            $algo   = substr($args,3);
            $algo   = isset($arrs[$algo]) ? $arrs[$algo] : false ;
            $action = $act;
        }
    }    

    if(!function_exists('ssl_crypto_parse')){
        function ssl_crypto_parse($act, $text, $method, $key, $iv){
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
                $str = ($act == 'decrypt') ? base64_decode($text) : $text;
                $str = openssl_decrypt($str, $method, $key, 0, $iv); 
            }
            return $str;
        }
    }

    if($algo){
        $hash = substr(hash($algo, $blow),0,40);
        return ssl_crypto_parse($action,$string,"AES-256-CBC",substr($hash,0,24),substr($hash,24,40));
    }else{
        return null;
    }
}