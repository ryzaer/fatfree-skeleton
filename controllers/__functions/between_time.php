<?php function between_time(...$args){
	// acceptable datetime format
	// - date {Y-m-d H:i,Y-m-d H:i:s}
	// - time {H:i:s,H:i} 
	$val_time = [];
	$val_date = [];
	foreach ($args as $num => $time) {
		if($num <= 3 && $time && is_string($time)){
			if(strlen($time) == 5 || strlen($time) == 8){
				$val_time[] = strlen($time) == 5 ? "$time:00" : $time;
			}
			if(strlen($time) == 16 || strlen($time) == 19){
				$val_date[] = strlen($time) == 16 ? "$time:00" : $time;				
			}
		}
	}
	$exect = false;
	$count = count($val_time);
	$today = date("Y-m-d H:i:s");
	if($count>=2 && $count <= 4){
		$exect = true;
		// Convert the time strings to DateTime objects
		$time_a = date("Y-m-d {$val_time[0]}");
		$time_b = date("Y-m-d {$val_time[1]}");
		// Set time to compare range
		$time_c = isset($val_time[2])? date("Y-m-d {$val_time[2]}") : $today ;		
	}else{
		$count = count($val_date);
		if($count>=2 && $count <= 4){
			$exect = true;			
			$time_a = $val_date[0];
			$time_b = $val_date[1];
			$time_c = isset($val_date[2])? $val_date[2] : $today ;
		}
	}
	if($exect){
		// Convert the time strings to DateTime objects
		$time_a = DateTime::createFromFormat('Y-m-d H:i:s',$time_a);
		$time_b = DateTime::createFromFormat('Y-m-d H:i:s',$time_b);
		// Set time to compare range
		$time_c = DateTime::createFromFormat('Y-m-d H:i:s',$time_c);
		// Adjust the times format only if they occur on different days than the current time
		if ($val_time && $time_b < $time_a) {
			$time_b->modify('+1 day');
		}
		// Check if the current time is between time_a and time_b
		if ($time_c >= $time_a && $time_c <= $time_b) {
			return true;
		} else {
			return false;
		}
	}else{
		return $exect;
	}
};