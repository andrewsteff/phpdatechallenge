<?php

  class MyDate {
	# main function of the class
    public static function diff($start, $end) {
		$adt = array('years' => null, 'months' => null, 'days' => null, 'total_days' => null, 'invert' => null );
		$start_date_parts = self::check_str_date($start); # [0]-year; [1]-month; [2]-day; [3]-false if not a date
		$end_date_parts = self::check_str_date($end); # [0]-year; [1]-month; [2]-day; [3]-false if not a date
		$invert = false;
		if (($start_date_parts[3] == true) and ($end_date_parts[3] == true)) {
			# so, given dates are good
			$adt['total_days'] = self::total_days_diff(	$start_date_parts, $end_date_parts, $invert);
			$adt['invert'] = $invert;
			# now try to count difference in years, month, days
			$diff_dmy = self::calculate_difference_in_dmy($start_date_parts, $end_date_parts);
			$adt['years'] = $diff_dmy['years']; 
			$adt['months'] = $diff_dmy['months']; 
			$adt['days'] = $diff_dmy['days']; 
		}
		return (object) $adt;
    }
	# calculates absolute quantity of days between two dates
    private static function total_days_diff(&$start_date_parts, &$end_date_parts, &$invert) {
      $start_absolute_num = self::calculate_absolute_day_number($start_date_parts[0], $start_date_parts[1], $start_date_parts[2], 2);
	  $end_absolute_num = self::calculate_absolute_day_number($end_date_parts[0], $end_date_parts[1], $end_date_parts[2], 2);
	  if ($end_absolute_num > $start_absolute_num) {
		$diff_in_days = $end_absolute_num - $start_absolute_num;
		$invert = false;
	  }
	  else {
		$diff_in_days = $start_absolute_num - $end_absolute_num;
		$invert = true;
		$temp_date_parts = $start_date_parts;
		$start_date_parts = $end_date_parts;
		$end_date_parts = $temp_date_parts;
	  }
      return intval($diff_in_days);
    }
	# checks if given date satisfies pattern yyyy/mm/dd
    private static function check_str_date($StrDate) {
		# array [0]-year, [1]-month, [2]-day [3]-true if date valid, false if not
		$date_parts = array(0 => null, 1 => null, 2 => null, 3 => true);
		$temp_date = $StrDate;
		$part_numb = 0;
		while (($temp_date != "") and ($part_numb <= 2)) {
			$date_part = '';
			$delim_pos = stripos($temp_date, '/');
			if ($delim_pos !== false)
			{
				$date_part = substr($temp_date, 0, stripos($temp_date, '/'));
				$temp_date = substr($temp_date, stripos($temp_date, '/')+1);
			}
			else
			{
				$date_part = $temp_date;
				$temp_date = '';
			}
			
			if (! is_numeric($date_part)) {
				$date_parts[3] = false;
			}
			else {
				$int_date_part = intval($date_part);
				if (! ($int_date_part > 0)) {$date_parts[3] = false;	}	
				if ($part_numb == 0) {	if ($int_date_part > 3000) {$date_parts[3] = false;}}
				if ($part_numb == 1) {	if ($int_date_part > 12) {$date_parts[3] = false;}	}
				if ($part_numb == 2) {	if ($int_date_part > 31) {$date_parts[3] = false;}	}
			}
			if ( !($date_parts[3] == false) ) {
				$date_parts[$part_numb] = $int_date_part;
			}
			$part_numb += 1;
		}
		return (array) $date_parts;
    }
	# calculates absolute number of day (JD) from year, month, day
    private static function calculate_absolute_day_number($yr, $mn, $dy, $alg_num) {
		$dyear = $yr; $dmonth = $mn; $dday = $dy;  
		if ($dmonth < 3) {
			$dyear = $dyear - 1;
			$dmonth = $dmonth + 12;
		};
		# there are two formulaes, currently using that one in section else
		if ($alg_num = 1) {
			$JD = floor( $dyear * 365.25 ) + floor( $dmonth * 30.6 + 0.7 ) + $dday;
		}
		else {
			$À = floor($dyear / 100);
			$Â = 2 - $À + floor($À/4);
			$Ñ = floor(365.25 * $dóear);
			$JD = $Ñ + floor(30.6001 * ($dmonth+$dday)) + $dday + 0.5 + 1720994.5 + $Â;
		}
		return $JD;
	}
	# calculates difference in years, months and days
	private static function calculate_difference_in_dmy($sdp, $edp) {
		$diff_dmy = array('years' => null, 'months' => null, 'days' => null);
		# first difference in full months
		$temp_months = ($edp[0] * 12 + $edp[1]) - ($sdp[0] * 12 + $sdp[1]);
		$diff_dmy['years'] = intval(floor($temp_months / 12));
		$diff_dmy['months'] = $temp_months - intval(floor($temp_months / 12) * 12);
		$diff_dmy['days'] = 0;
		if ($edp[2] >= $sdp[2]) {
			$diff_dmy['days'] = $edp[2] - $sdp[2];
		}
		else {
			# here decrements month
			if ($diff_dmy['months'] > 0) {
				$diff_dmy['months'] = $diff_dmy['months'] - 1;
			}
			else {
				$diff_dmy['months'] = 11;
				$diff_dmy['years'] = $diff_dmy['years'] - 1;
			}
			
			# here we must know how many days in previous to last month
			$temp_edp = $edp; 
			if ($temp_edp[1] > 1) {
				$temp_sdp[0] = $temp_edp[0];
				$temp_sdp[1] = $temp_edp[1] - 1; 
			}
			else {
				$temp_sdp[1] = 12; 
				$temp_sdp[0] = $temp_edp[0] - 1; 
			}
			$temp_sdp[2] = 1;
			$temp_edp[2] = 1;
			
			$invert = false;
			$qua_days = self::total_days_diff($temp_sdp, $temp_edp, $invert);
			$diff_dmy['days'] = ($qua_days - $sdp[2]) + $edp[2];
		}
		return $diff_dmy;
	}
  }
