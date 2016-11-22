<?php
class DBValidator extends DataValidator
{
	function __construct(&$formObserver)
	{
	parent::__construct($formObserver);
	}
	
	function validate($sql, $error_message, $errorOnNoResult = false) {
		$db = new DatabaseQuery;
		$rs = $db->execute($sql);
		$rows = $rs->getNum();
		
		if (!$errorOnNoResult ? $rows > 0 : $rows == 0) {
			$this->notifyObserver($error_message);
		}
	}
}
?>