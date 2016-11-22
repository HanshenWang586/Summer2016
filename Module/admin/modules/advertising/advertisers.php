<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('advertising');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT *
					FROM ads_advertisers
					ORDER BY advertiser");

$body = "<table class=\"gen_table\" cellspacing=\"1\">
<tr>
<td><b>Advertiser</b></td>
<td colspan=\"3\"></td>
</tr>";

	while ($row = $rs->getRow()) {
		$body .= "<tr>
		<td>{$row['advertiser']}</td>
		<td><a href=\"form_advertiser.php?advertiser_id={$row['advertiser_id']}\">Edit</a></td>
		<td><a href=\"media.php?advertiser_id={$row['advertiser_id']}\">Media</a></td>
		<td><a href=\"index.php?advertiser_id={$row['advertiser_id']}\">Deployments</a></td>
		</tr>";
	}

$body .= '</table>';

$pap->setTag('main', $body);
$pap->output();
?>