<?php
namespace ascii;
// php ^5.6
class table
{
	private static $stmt,$comment = false;
	private static function pad($string,$length, $pad_string=" ")
	{
		preg_match('/{(.*\s)?(\-?[0-9.]+)}/is',$string,$match);
		if($match){
			$item = $match[1] ? trim($match[1]) : '';
			$nums = $match[2];
			$string = "$item $nums";  
			return " $item". str_repeat($pad_string, $length - mb_strlen($string)-1) . "$nums ";
		}else{			
			return $string . str_repeat($pad_string, $length - mb_strlen($string));
		}
		
	}
	
	private static function bar($length)
	{
		return str_repeat('─', $length );
		
	}

	private static function prop($name, $cell, $args, $envf=null)
	{
		$self = self::$stmt ? self::$stmt : new table();
		$rs = null;
		
		$fn = function($w) use($self) { return $self::bar($w);};
		if($name === "head"){ 
			$br = "┬";
			$sp = "{$envf}┌%s┐";
		}
		if($name === "rule"){ 
			$br = "┼";
			$sp = "{$envf}├%s┤";
		}
		if($name === "body"){ 
			$fn = function($c,$w) use($self){ return $self::pad(" {$c} ", $w); };
			$br = "│";
			$sp = "{$envf}│%s│";
		}
		if($name === "foot"){ 
			$br = "┴";
			$sp = "{$envf}└%s┘";
		}
		if($name){			
			$rs = $name == "body" ? array_map($fn,$cell,$args) : array_map($fn,$args);
			$rs = implode($br,$rs);
			$rs = sprintf($sp,$rs);
		}
		
		return $rs;
	}

	private static function parse($arrs){
		$tbrc = [];
		$uidh = 0;
		foreach ($arrs as $array) {
			if(is_array($array)){				
				$head = [];
				$body = [];
				foreach ($array as $key => $val) {						
					if($uidh==0 && is_string($key) && $key ){
						$head[] = ucwords($key);
					}	

					if(is_string($val)){
						preg_match('/{(.*\s)?(\-?\d+)}/is',$val,$match);
						if($match){
							$item = $match[1] ? $match[1] : '';
							$nums = is_numeric($match[2]) ? number_format($match[2] , 0, ',', '.') : 0;
							$val = '{'.$item.$nums.'}';  
						}	
					}
								
					$body[] = is_array($val) ? json_encode($val) : (is_bool($val) ? ( $val? 'true' : 'false' ) : (is_null($val)?'null': (is_callable($val)?'':$val)));
								
				}
				if($head && $uidh==0){
					$tbrc[] = $head;
				}
				$tbrc[] = $body;				
			}
			$uidh++;
		}

		$tb[] = $tbrc[0];
		foreach ($tbrc as $n => $vars) {
			if($n > 0){
				$arr=[];
				$wdt= 0;
				foreach ($vars as $j => $value) {
					$col=preg_split('/\n/s',$value);
					if ((isset($width) ? $width : 0) < ($width = count($col))  ) {
						$wdt = $width;					
					}
					$arr[]= $col;
				}
				if($wdt){
					for ($i=0; $i < $wdt; $i++) { 
						$ts=[];
						foreach ($arr as $v) {
							$ts[]=isset($v[$i])?$v[$i]:"";
						}
						$tb[]=$ts;
					}
				}else{
					$tb[]=$vars;
				}
			}
		}
		
		return $tb;
	} 

	public static function emit(...$Args)
	{
		$self = self::$stmt ? self::$stmt : new table();
		$rows = [];
		foreach ($Args as $vals) {
			if(is_array($vals)){
				$rows = $vals;
			}
			if(is_string($vals)){
				$self::$comment = $vals;
			}
		}
		$rows = $self::parse($rows);
		$envf = $self::$comment;
		$drow = count($rows);

		if ($drow === 0) {
			return '';
		}
		$widths = [];
		foreach ($rows as $cells) {
			foreach ($cells as $j => $cell) {					
				$add = 2;			
				preg_match('/{(.*\s)?(\-?[0-9.]+)}/is',$cell,$match);
				if($match){
					$add = 0;					
				}
				if (($width = strlen($cell) + $add) >= (isset($widths[$j]) ? $widths[$j] : 0)) {					
					$widths[$j] = $width;					
				}
			}
		}
		
		$fn = function($c,$w) use($self){ return $self::pad(" {$c} ", $w);};
		$tb = function($w) use($self){return $self::bar($w);};
		
		$result=[];
		foreach ($rows as $i => $cells) {	
					
			if($i === 0){
				$result[] = $self::prop('head',$cells,$widths,$envf);
			}			
			if($i === 1){
				$result[] = $self::prop('rule',$cells,$widths,$envf);
			}

			$result[] = $self::prop('body',$cells,$widths,$envf);
			
			if($i === $drow-1){
				$result[] = $self::prop('foot',$cells,$widths,$envf);
			}
		}
		
		return implode("\n", $result);
	}
}