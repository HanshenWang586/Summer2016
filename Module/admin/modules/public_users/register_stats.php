<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('public_users');

$db = new DatabaseQuery;

$rs = $db->execute("SELECT COUNT(*) AS tally, DATE_FORMAT(ts_registered, '%Y-%m') AS yearmonth
					FROM public_users
					WHERE status & 1
					AND site_id = 1
					AND verified = 1
					GROUP BY yearmonth
					ORDER BY yearmonth DESC");

while ($row = $rs->getRow()) {
	$verified[$row['yearmonth']] = $row['tally'];
}

$rs = $db->execute("SELECT COUNT(*) AS tally, DATE_FORMAT(ts_registered, '%Y-%m') AS yearmonth
					FROM public_users
					WHERE status & 1
					AND site_id = 1
					GROUP BY yearmonth
					ORDER BY yearmonth DESC");

$body .= "<table cellspacing=\"1\" class=\"gen_table\">
	<tr>
	<td><b>Year-Month</b></td>
	<td><b>Signups</b></td>
	<td><b>Verified signups</b></td>
	</tr>";

while ($row = $rs->getRow()) {
	++$i;
	$signups_total += $row['tally'];
	$verified_total += $verified[$row['yearmonth']];

$body .= "<tr>
	<td>{$row['yearmonth']}</td>
	<td>{$row['tally']}".($i == 1 ? " (".round($row['tally']*date('t')/date('j')).") " : '')."</td>
	<td>{$verified[$row['yearmonth']]}".($i == 1 ? " (".round($verified[$row['yearmonth']]*date('t')/date('j')).") " : '')."</td>
	</tr>";
}

$body .= "<tr>
	<td>Total</td>
	<td>$signups_total</td>
	<td>$verified_total</td>
	</tr>
	</table><br />
	<br />
	";

$rs = $db->execute("SELECT COUNT(*) AS tally, YEAR(ts_registered) AS regyear
					FROM public_users
					WHERE status & 1
					AND site_id = 1
					AND verified = 1
					GROUP BY regyear
					ORDER BY regyear DESC");

while ($row = $rs->getRow())
	$verified[$row['regyear']] = $row['tally'];


$total_verified = 0;
$total_signups = 0;
$rs = $db->execute("SELECT COUNT(*) AS tally, YEAR(ts_registered) AS regyear
					FROM public_users
					WHERE status & 1
					AND site_id = 1
					GROUP BY regyear
					ORDER BY regyear DESC");

$body .= "<table cellspacing=\"1\" class=\"gen_table\">
	<tr>
	<td><b>Year</b></td>
	<td><b>Signups</b></td>
	<td><b>Verified</b></td>
	</tr>";

while ($row = $rs->getRow()) {
	$total_signups += $row['tally'];
	$total_verified += $verified[$row['regyear']];
	$body .= "<tr>
	<td>{$row['regyear']}</td>
	<td>{$row['tally']}".(++$i == 1 ? " (".round($row['tally']*(365+date('L'))/(1 + date('z'))).") " : '')."</td>
	<td>{$verified[$row['regyear']]}".(++$i == 1 ? " (".round($verified[$row['regyear']]*(365+date('L'))/(1 + date('z'))).") " : '')."</td>
	</tr>";
}

$body .= "<tr>
	<td>Total</td>
	<td>$total_signups</td>
	<td>$total_verified</td>
	</tr>
	</table>";

$pap->setTag('main', $body);
$pap->output();
?>