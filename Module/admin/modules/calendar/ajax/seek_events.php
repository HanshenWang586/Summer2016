<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

if ($_GET['listing_id']
	&& $_GET['yyyy']
	&& $_GET['mm']
	&& $_GET['dd']) {

	$events = array();
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT *
						FROM calendar_events
						WHERE listing_id = {$_GET['listing_id']}
						AND live = 1
						AND event_date = '{$_GET['yyyy']}-{$_GET['mm']}-{$_GET['dd']}'");

	if ($rs->getNum()) {
		while ($row = $rs->getRow())
			$events[] = $row['description'];
	
		$f[] = FormHelper::element('', HTMLHelper::wrapArrayInUl($events));
		echo FormHelper::fieldset('Other Events', $f);
	}
}
?>