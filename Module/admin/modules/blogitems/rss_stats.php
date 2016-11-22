<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('blog');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT SUM(tally) AS tally, DATE_FORMAT(ts, '%Y-%m') AS yearmonth
					FROM log_rss_summary
					GROUP BY DATE_FORMAT(ts, '%Y-%m')
					ORDER BY ts DESC");

$body .= "<table cellspacing=\"1\" class=\"gen_table\">
<tr><td><b>Date</b></td><td><b>#</b></td></tr>";

while ($row = $rs->getRow())
	$body .= "<tr><td>{$row['yearmonth']}</td><td>{$row['tally']}</td></tr>";

$body .= '</table>';

$pap->setTag('main', $body);
$pap->output();
?>