<?php 
function gps_converter($str,$arr=false){
    // example used
    // var_dump(\__fn::gps_converter('0°38\'12.4"S 112°42\'04.7"E',true));
    // var_dump(\__fn::gps_converter('-0.636778, 112.701306'));
    mb_regex_encoding('UTF-8'); 
    mb_internal_encoding('UTF-8');
    $string = (string) $str;
    preg_match('/[\'"°]/', trim($string) , $match);
    $string = array_map('trim',array_values(array_filter(preg_split('/,|\s/',$string))));
    if($match){
        $imp = ', ';
        $scheme = [gps_dms_parser($string[0]),gps_dms_parser($string[1])];
    }else{
        $imp = ' ';
        $scheme = [gps_dec_parser('lat',$string[0]),gps_dec_parser('long',$string[1])];
    }
    return $arr ? [
        'latitude' => $scheme[0],
        'longitude' => $scheme[1]
    ] : implode($imp,$scheme);
}
if(!function_exists('gps_dec_parser')){
    function gps_dec_parser($degN,$var){
        if($degN == 'lat' )
            $degArrs = ['S','N'];
        if($degN == 'long' )
            $degArrs = ['W','E'];
        $var = (string) $var;
        preg_match('/\-/',$var,$mtch);
        $deg = $mtch ? $degArrs[0] : $degArrs[1];
        $var = explode(".",$mtch ? substr($var,1) : $var);
        $dig = explode(".",(string) floatval("0.".$var[1])*60);
        $dig1 = strlen($dig[0]) < 2 ? "0{$dig[0]}" : $dig[0];
        $dig2 = (string) round(floatval("0.".$dig[1])*60,1);
        preg_match('/\./',$dig2,$mtch);
        $dig2 = $mtch ? $dig2 : "$dig2.0";
        $dig2 = strlen($dig2) <= 3 ? "0$dig2" : $dig2;           
        return  "{$var[0]}°$dig1'$dig2\"$deg";
    }
}
if(!function_exists('gps_dms_parser')){
    function gps_dms_parser($var){
        $var = preg_split('/[\'"°]/', $var);
        $int = (abs($var[2])/60)+(floatval($var[3])/3600);
        $int = substr((string) floatval($int),2);
        $neg = strtoupper(preg_replace('/[^SWNE]/','',$var[4]));
        $neg = $neg == "S" || $neg == "W" ? '-' : null ;        
        return  round("$neg{$var[0]}.$int",6);
    }
}