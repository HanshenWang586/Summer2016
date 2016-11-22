<?php
class DateValidator extends DataValidator {
	function __construct(&$formObserver) {
		parent::__construct($formObserver);
	}

	function validate($date_array, $date_earliest, $error_message) {
		$mm = $this->formObserver->getDatum($date_array[1]);
		$dd = $this->formObserver->getDatum($date_array[2]);
		$yyyy = $this->formObserver->getDatum($date_array[0]);

		$date = strtotime($yyyy.'-'.$mm.'-'.$dd);

		if ($date <= $date_earliest) $this->notifyObserver($error_message);
	}
}
?>