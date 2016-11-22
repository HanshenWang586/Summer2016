<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('listings');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT COUNT(*) AS tally, DATE_FORMAT(ts_added, '%Y-%m') AS yearmonth
					FROM listings_data
					WHERE status=1
					GROUP BY yearmonth
					ORDER BY yearmonth DESC");

$body .= "<table cellspacing=\"1\" class=\"gen_table\">
<tr><td><b>Year-Month</b></td><td><b>Added</b></td></tr>";

	while ($row = $rs->getRow())
	{
	$total += $row['tally'];
	$body .= "<tr><td>{$row['yearmonth']}</td><td>{$row['tally']}".(++$i == 1 ? " (".round($row['tally']*date('t')/date('j')).") " : '')."</td></tr>";
	}
	
$body .= "<tr><td>Total</td><td>$total</td></tr>
</table>";

$pap->setTag('main', $body);
$pap->output();
?>