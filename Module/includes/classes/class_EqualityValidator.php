<?php
class EqualityValidator extends DataValidator {
	
	public function __construct(&$formObserver) {
		parent::__construct($formObserver);
	}

	public function validate($data_tag_1, $data_tag_2, $error_message) {
		if ($this->formObserver->getDatum($data_tag_1) != $this->formObserver->getDatum($data_tag_2))
			$this->notifyObserver($error_message);
	}
}
?>