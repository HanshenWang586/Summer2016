<?php
class DateManipulator {

	public static function convertUnixToFriendly($unix_timestamp, $options = array()) {
		if (date('Y-m-d') == date('Y-m-d', $unix_timestamp)) {
			$content = 'Today';
		}
		else if (date('Y-m-d', mktime(0, 0, 0, date('m'), date('d')-1, date('Y'))) == date('Y-m-d', $unix_timestamp)) {
			$content = 'Yesterday';
		}
		else if (date('Y-m-d', mktime(0, 0, 0, date('m'), date('d')+1, date('Y'))) == date('Y-m-d', $unix_timestamp)) {
			$content = 'Tomorrow';
		}

		if ($options['show_time'] && $content != '') {
			return $content.' '.DateManipulator::convertUnixToFormat('H:i', $unix_timestamp);
		}
		else {
			$format = 'F j';
			if ($options['show_year'])
				$format .= ', Y';
			if ($options['show_day'])
				$format = 'l, '.$format;

			return DateManipulator::convertUnixToFormat($format, $unix_timestamp);
		}
	}

	public static function convertUnixToFormat($format, $unix_timestamp) {
		return str_replace(' ', '&nbsp;', date($format, $unix_timestamp));
	}

	public static function convertYMDToFriendly($ymd, $options = array()) {
		if ($ymd != '') {
			$format = 'F j';
			if ($options['show_year'])
				$format .= ', Y';
			if ($options['show_day'])
				$format = 'l, '.$format;
			$bits = explode('-', $ymd);
			return str_replace(' ', '&nbsp;', date($format, mktime(0, 0, 0, ltrim($bits[1], '0'), ltrim($bits[2], '0'), $bits[0])));
		}
	}

	public static function convertYMToFriendly($ym, $options = array()) {
		if ($ym != '') {
			$format = 'F';
			if ($options['show_year'])
				$format .= ' Y';
			$bits = explode('-', $ym);
			return str_replace(' ', '&nbsp;', date($format, mktime(0, 0, 0, ltrim($bits[1], '0'), 1, $bits[0])));
		}
	}

	public static function convertYMDToUnix($ymd) {
		return strtotime($ymd);
	}
}
?>