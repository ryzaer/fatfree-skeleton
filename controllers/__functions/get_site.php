<?php 
/**
 * Example use : 
 * $link = "http://localhost/__repository/code-base/server-secret.php";  
 * \__fn::get_site($link);
 * 
 * --- OR to post data and file ---
 * 
 * $head = (object)[
 *     // ignore if code not 200
 *     'code' => 200,
 *     // make header xhr server method as put
 *     'http' => ['X-HTTP-Method-Override: PUT']
 * ];
 * $data = [
 *     'name' => 'My latest upload', 
 *     'description' => 'Check out my newest project',
 *     'binary' => realpath('.')."/data.bin", 
 *     'picture' => realpath('.')."/photo.jpg", 
 * ];
 * $result = \__fn::get_site($link,$head,$data);
 * header("Content-Type:text/json");
 * print $result;
 */

function get_site(...$args){
    
    $rslt = null;
    $link = null;
    $arrs = false;
    $code = false;
    $prms = (object)[];
    $data = [];
    $blob = true;

    if(!function_exists("__makeCurlFile")){
        function __makeCurlFile($file){
            $mime = mime_content_type($file);
            $info = pathinfo($file);
            $name = $info['basename'];
            $output = new \CURLFile($file, $mime, $name);
            return $output;
        }
    }
    
    foreach ($args as $val) {
        if(is_string($val) && $val){
            // string of link
            $link = $val;
        }
        if(is_bool($val)){
            // (json type data only) param true will give array result, if not will empty / null 
            $arrs = $val;
        }
        if(is_numeric($val)){
            // allow result based by request code
            $code = $val;
        }
        if(is_object($val)){
            /* object are post format (underproject)
             * ->http array (send header) CURLOPT_HTTPHEADER exp.['X-HTTP-Method-Override: PUT']
             * ->code string
             */
            $prms = $val;
        }
        if(is_array($val)){
            foreach($val as $var => $vals){
                if(file_exists($vals)){
                    // if post file
                    $data[$var] = __makeCurlFile($vals);
                }else{
                    $data[$var] = $vals;
                }
            }
        }
    }
    
    if($link){ 
        
        $send = curl_init($link);
        
        curl_setopt($send,CURLOPT_RETURNTRANSFER,true); 
        curl_setopt($send,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($send,CURLOPT_SSL_VERIFYPEER,false);     
        
        if($data){
            curl_setopt($send,CURLOPT_POST,true);
            curl_setopt($send,CURLOPT_POSTFIELDS,$data);        
        }    
        
        /* if custom method available */  
        $code = isset($prms->code) ? $prms->code : $code;
        $http = isset($prms->http) && is_array($prms->http) && $prms->http ? $prms->http : [];
        
        if($http){
            curl_setopt($send,CURLOPT_HTTPHEADER,$http); 
        }else{
            curl_setopt($send,CURLOPT_HEADER,false);
        }        

        // if($file && $size){
        //     !$http or curl_setopt($send,CURLOPT_PUT,1);
        //     curl_setopt($send,CURLOPT_INFILE,$file) ;
        //     curl_setopt($send,CURLOPT_INFILESIZE,$size);
        // } 

        curl_setopt($send,CURLOPT_BINARYTRANSFER,true);        
        
        $u_agent = isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] ?  $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36' ;
        curl_setopt($send,CURLOPT_USERAGENT,$u_agent);
        curl_setopt($send,CURLOPT_CONNECTTIMEOUT,120);
        curl_setopt($send,CURLOPT_TIMEOUT,120);
        
        // allow result based by code
        $rslt = curl_exec($send);
        
        if($code){
            $getcode = curl_getinfo($send, CURLINFO_HTTP_CODE);
            if($getcode !== $code){
                $rslt = null;
            }
        }

        curl_close($send);
    }
    
    return $arrs ? json_decode($rslt,true) : $rslt;
}