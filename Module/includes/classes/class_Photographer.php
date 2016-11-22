<?php
class Photographer {

	public function __construct($photographer_id = '') {
		if (ctype_digit($photographer_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
							   FROM gallery_photographers
							   WHERE photographer_id = '.$photographer_id);
			$this->setData($rs->getRow());
		}
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function getName() {
		return $this->photographer;
	}
}
?>