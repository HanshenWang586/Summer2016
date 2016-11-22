<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('advertising');

$db = new DatabaseQuery;
$rs = $db->execute('SELECT *
					FROM ads_locations');

$body = "<table class=\"gen_table\" cellspacing=\"1\">
<tr valign=\"top\">
<td><b>Location ID</b></td>
<td><b>Description</b></td>
<td><b>Width</b></td>
<td><b>Height</b></td>
<td><b>Current<br />
deployments</b></td>
</tr>";

	while ($row = $rs->getRow()) {
		$rs_2 = $db->execute("	SELECT COUNT(*) AS tally
								FROM ads_deployments
								WHERE location_id={$row['location_id']}
								AND NOW() >= start_date
								AND NOW() <= end_date");

		$row_2 = $rs_2->getRow();
		$body .= "<tr>
		<td>{$row['location_id']}</td>
		<td><a href=\"location.php?location_id={$row['location_id']}\">{$row['description']}</a></td>
		<td>{$row['width']}</td>
		<td>{$row['height']}</td>
		<td>{$row_2['tally']}</td>
		</tr>";
	}

$body .= '</table>';

$pap->setTag('main', $body);
$pap->output();
?>