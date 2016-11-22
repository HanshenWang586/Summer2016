<?php
class Province {
	
	public function __construct($province_id = '') {
		if (ctype_digit($province_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM provinces
								WHERE province_id = '.$province_id);
			$this->setData($rs->getRow());
		}
	}
	
	public function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value)
				$this->$key = $value;
		}
	}
	
	public function getName() {
		return $this->province;
	}
}
?>