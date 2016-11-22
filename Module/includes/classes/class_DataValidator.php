<?php
class DataValidator {
	public function __construct(&$formObserver) {
		$this->formObserver =& $formObserver;
	}

	public function notifyObserver($errorMessage) {
		$this->formObserver->addError($errorMessage);
	}
}
?>