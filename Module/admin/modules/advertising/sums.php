<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('advertising');

$date = isset($_GET['date']) ? "'{$_GET['date']}'" : 'NOW()';

$db = new DatabaseQuery;
$rs = $db->execute("SELECT	TO_DAYS(end_date) - TO_DAYS(start_date) AS run,
							fee,
							location_id
					FROM ads_deployments
					WHERE TO_DAYS(end_date) > TO_DAYS($date)
					AND TO_DAYS($date) > TO_DAYS(start_date)
					AND fee != 0");

$body .= '<pre>';
while ($row = $rs->getRow()) {
	$body .= print_r($row, true);
	$per_day = $row['fee']/$row['run'];
	$body .= $per_day;
	$total += $per_day*31;
}
$body .= '</pre>
Total: '.$total;

$pap->setTag('main', $body);
$pap->output();
?>