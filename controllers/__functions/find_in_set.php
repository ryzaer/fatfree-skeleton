<?php function find_in_set($arg,$str){
    // return int
	$num = 0;
	$arg = is_numeric($arg) ? abs($arg) : $arg;
	foreach (explode(',',$str) as $key => $val) {
		$val = trim($val);
		$val = is_numeric($val) ? abs($val) : $val;
		if($val == $arg)
			$num = $key+1;
	}
	return $num;
}