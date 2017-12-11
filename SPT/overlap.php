<?php

/**
 * Creating date collection between two dates
 *
 * <code>
 * <?php
 * # Example 1
 * date_range("2014-01-01", "2014-01-20", "+1 day", "Y-m-d");
 *
 * # Example 2. you can use even time
 * date_range("01:00:00", "23:00:00", "+1 hour", "H:i:s");
 * </code>
 */
function date_range($first, $last, $step = '+1 day', $output_format = 'Y-m-d' ) {

	$current = strtotime($first);
	$last = strtotime($last);
	$dates = array(date($output_format, $current));

	while( $current <= $last ) {
		$current = strtotime($step, $current);
		$dates[] = date($output_format, $current);
	}
	return $dates;
}

function overlapping($startdate,$enddate,$workingdays,$workinghours) {
	// $start_date = date_format($startdate, 'Y-m-d');
	// $end_date = date_format($enddate, 'Y-m-d');
	$dates = date_range($startdate,$enddate);
	$startdate = strtotime($startdate);
	$enddate = strtotime($enddate);
	
	$working_days = $workingdays; // --------------------------------
	$working_hours = $workinghours;
	$beg_h = floor($working_hours[0]); $beg_m = ($working_hours[0]*60)%60;
	$end_h = floor($working_hours[1]); $end_m = ($working_hours[1]*60)%60;
	$hours = 0;
	
	foreach ($dates as $value) {
		$date = strtotime($value);
		
		if (in_array(date('w',$date) , $working_days)) {
			$current_start = mktime($beg_h, $beg_m, 0, date('n',$date), date('j',$date), date('Y',$date));
			$current_end = mktime($end_h, $end_m, 0, date('n',$date), date('j',$date), date('Y',$date));

			if ($startdate >= $current_start && $enddate <= $current_end) {
				return ($enddate-$startdate)/3600;
			}
			else if ($startdate > $current_start && $startdate < $current_end && $enddate > $current_end) {
				$hours += ($current_end-$startdate)/3600;
			}
			else if ($startdate <= $current_start && $enddate >= $current_end) {
				$hours += ($current_end-$current_start)/3600;
			}
			else if ($startdate < $current_start && $enddate > $current_start && $enddate < $current_end) {
				$hours += ($enddate-$current_start)/3600;
			}
		}
	}
	return $hours;
}	

function ol($start, $end) {
	// $start = "2016-06-01 23:58:34";
	// $end = "2016-06-02 03:46:20";
	$wd = array(1,2,3,4,5);
	
	$whs = array(array(0, 24)); // --------------------------------24h

	$ol = 0;
	foreach ($whs as $wh) {
		$ol += overlapping($start, $end, $wd, $wh);
	}
// var_dump(round($ol, 1));
	return round($ol, 1);
}

?>