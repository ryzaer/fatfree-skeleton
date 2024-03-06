<?php
namespace ascii;

class env {

	static function emit($arrs)
    {
        $result=[];
        $space =[];
        foreach ($arrs as $key => $value) {
            if(!is_numeric($key) && is_string($key)){
                if(is_array($value)){ 
                    $value = json_encode($value);
                }elseif(is_bool($value)){
                    $value = $value ? 'true' : 'false';
                }elseif(is_null($value)){
                    $value = 'null';
                }
                $key = preg_replace('/_+/','_',strtoupper(preg_replace('/[^0-9a-zA-Z_]/i','_',trim($key))));
                $space[] = strlen($key);
                $result[]="$key%s= $value";
                $_ENV[$key] = $value;
            }
        }
        $dump =[];
        $dmax = max($space)+1;
        for ($i=0; $i < count($space) ; $i++) { 
            $dump[] = sprintf($result[$i],str_repeat(' ',( $dmax - $space[$i])));
        }
        return implode("\n",$dump);
    }
}