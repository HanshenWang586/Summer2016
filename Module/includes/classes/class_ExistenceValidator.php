<?php
class ExistenceValidator extends DataValidator {
	
	public function __construct(&$formObserver) {
		parent::__construct($formObserver);
	}

	public function validate($data_tag, $error_message) {
		if (strlen($this->formObserver->getDatum($data_tag)) == 0)
			$this->notifyObserver($error_message);
	}
}
?>