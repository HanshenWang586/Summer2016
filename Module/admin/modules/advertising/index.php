<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('advertising');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT *
					FROM ads_advertisers a, ads_deployments d, ads_media m, ads_locations l
					WHERE a.advertiser_id = m.advertiser_id
					AND d.media_id = m.media_id
					AND d.location_id = l.location_id ".
					($_GET['advertiser_id'] != '' ? "AND a.advertiser_id = {$_GET['advertiser_id']} " : '').
					"AND TO_DAYS(end_date) >= TO_DAYS(NOW())
					ORDER BY end_date ASC, start_date DESC, a.advertiser_id");

$body = "<table class=\"gen_table\" cellspacing=\"1\">
<tr>
<td><b>ID</b></td>
<td><b>Current Advertiser</b></td>
<td><b>Location</b></td>
<td><b>Start date</b></td>
<td><b>End date</b></td>
<td><b>Fee</b></td>
<td></td>
</tr>";

	while ($row = $rs->getRow()) {
		$body .= "<tr>
		<td>{$row['deployment_id']}</td>
		<td><a href=\"media.php?advertiser_id={$row['advertiser_id']}\">{$row['advertiser']}</a></td>
		<td>{$row['description']}</td>
		<td>{$row['start_date']}</td>
		<td>{$row['end_date']}</td>
		<td>{$row['fee']}</td>
		<td><a href=\"form_deploy.php?deployment_id={$row['deployment_id']}\">Edit</a></td>
		</tr>";
	}

$body .= '</table><br /><br />';

$rs = $db->execute("SELECT *
					FROM ads_advertisers a, ads_deployments d, ads_media m, ads_locations l
					WHERE a.advertiser_id = m.advertiser_id
					AND d.media_id = m.media_id
					AND d.location_id = l.location_id ".
					($_GET['advertiser_id'] != '' ? "AND a.advertiser_id = {$_GET['advertiser_id']} " : '').
					"AND TO_DAYS(end_date) < TO_DAYS(NOW())
					ORDER BY end_date DESC, start_date DESC, a.advertiser_id");

$body .= "<table class=\"gen_table\" cellspacing=\"1\">
<tr>
<td><b>Expired Advertiser</b></td>
<td><b>Location</b></td>
<td><b>Start date</b></td>
<td><b>End date</b></td>
<td><b>Fee</b></td>
<td></td>
</tr>";

	while ($row = $rs->getRow()) {
		//$body .= debug($row);
		$body .= "<tr>
		<td><a href=\"media.php?advertiser_id={$row['advertiser_id']}\">{$row['advertiser']}</a></td>
		<td>{$row['description']}</td>
		<td>{$row['start_date']}</td>
		<td>{$row['end_date']}</td>
		<td>{$row['fee']}</td>
		<td><a href=\"form_deploy.php?deployment_id={$row['deployment_id']}\">Edit</a></td>
		</tr>";
	}

$body .= '</table>';

$pap->setTag('main', $body);
$pap->output();
?>