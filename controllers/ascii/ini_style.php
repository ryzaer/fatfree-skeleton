<?php
namespace ascii;

class ini_style {

	static function emit($str)
    {
        $rslt = [];
        $prnt=null;
        if($str)
            foreach (preg_split('/\n|\r/',$str) as $value){
                // $prms = substr(trim($value),0,1) ==';' ? preg_replace('/;.*/s','',trim($value)) : trim($value);
                $prms =  trim($value);
                // check if have a parent here 
                if(substr($prms,0,1) ==';')
                    $prms = trim(preg_replace('/;.*/s','',$prms));

                if($prms !== '' && $prm = explode('=',$prms)){
                    $key = null;
                    if(count($prm) > 1){
                        if(count($prm) > 2){
                            $key = null;
                            $val = [] ;
                            foreach ($prm as $k => $v) {
                                if($k > 0 ){
                                    $val[] = $v;
                                } else {
                                    $key = trim($v);
                                }
                            }            
                            $val = trim(preg_replace('/\s;.*/s','',implode('=',$val)));
                        }else{
                            $key = trim($prm[0]);
                            $v   = preg_replace('/"/','',trim(preg_replace('/\s;.*/s','',$prm[1])));
                            $val = (strtolower($v) == 'true' ? true : (strtolower($v) == 'false' ? false : (strtolower($v) == 'null' ? null : (is_numeric($v) ? abs($v) : $v))));
                        }                                  
                    }else{
                        // must have a parent here examlple [parent]
                        if(substr($prm[0],0,1)=='[' && substr($prm[0],-1)==']')
                            $prnt = substr(substr($prm[0],1),0,-1);
                    }
                    // if have parent
                    if($prnt && $key)
                        $rslt[$prnt][$key] = $val;
                }
            }
        return $rslt;
    }
}