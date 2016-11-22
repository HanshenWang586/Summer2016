<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('public_users');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT DISTINCT DATE_FORMAT(ts, '%Y-%m') AS yearmonth
					FROM log_logins
					ORDER BY yearmonth DESC");

$body .= "<table cellspacing=\"1\" class=\"gen_table\">
<tr>
<td><b>Year-Month</b></td>
<td><b>GoKunming</b></td>
<td><b>GoChengdoo</b></td>
</tr>";

	while ($row = $rs->getRow())
	{
		++$i;
		$tallies = array(0, 0, 0);
	
		$rs_2 = $db->execute("	SELECT COUNT(*) AS tally, site_id
								FROM log_logins
								WHERE ts LIKE '{$row['yearmonth']}%'
								GROUP BY site_id
								ORDER BY site_id");

		while ($row_2 = $rs_2->getRow()) {
		$tallies[$row_2['site_id']] = $row_2['tally'];
		}
	
	$totals[1] += $tallies[1];
	$totals[2] += $tallies[2];
	
	$body .= "<tr>
	<td>{$row['yearmonth']}</td>
	<td>{$tallies[1]}".($i == 1 ? " (".round($tallies[1]*date('t')/date('j')).") " : '')."</td>
	<td>{$tallies[2]}".($i == 1 ? " (".round($tallies[2]*date('t')/date('j')).") " : '')."</td>
	</tr>";
	}
	
$body .= "<tr>
<td>Total</td>
<td>{$totals[1]}</td>
<td>{$totals[2]}</td>
</tr>
</table>";

$pap->setTag('main', $body);
$pap->output();
?>