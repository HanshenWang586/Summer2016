<?php
class EmailValidator extends DataValidator {
	
	public function __construct(&$formObserver) {
		parent::__construct($formObserver);
	}
	
	public function validate($data_tag, $error_message) {
		$email = $this->formObserver->getDatum($data_tag);
		
		if (!validateEmail($email)) {
			$this->notifyObserver($error_message);
		}
	}
}
?>