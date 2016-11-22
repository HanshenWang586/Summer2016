<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('public_users');

$db = new DatabaseQuery;
$rs = $db->execute('SELECT COUNT(*) AS tally, area_en FROM public_users u, gk4_areas a
					WHERE u.area_id = a.area_id
					AND status & 1
					GROUP BY a.area_id
					ORDER BY area_en');

$body .= "<table cellspacing=\"1\" class=\"gen_table\">
<tr><td><b>Area</b></td><td><b>#</b></td></tr>";

while ($row = $rs->getRow()) {
	$area_total += $row['tally'];
	$body .= "<tr><td>{$row['area_en']}</td><td>{$row['tally']}</td></tr>";
}

$body .= "<tr><td>Total</td><td>$area_total</td></tr>
</table>";

$pap->setTag('main', $body);
$pap->output();
?>