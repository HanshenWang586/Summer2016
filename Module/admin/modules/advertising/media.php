<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('advertising');

$db = new DatabaseQuery;
$rs = $db->execute('SELECT *
					FROM ads_media '.
					($_GET['advertiser_id'] != '' ? "WHERE advertiser_id = {$_GET['advertiser_id']} " : '').
					'ORDER BY width/height DESC');

$body = "<table class=\"gen_table\" cellspacing=\"1\">
<tr valign=\"top\">
<td><b>ID</b></td>
<td><b>Media</b></td>
<td><b>Type</b></td>
<td><b>Pixel Size</b></td>
<td></td>
<td></td>
</tr>";

	while ($row = $rs->getRow()) {
		$body .= "<tr valign=\"top\">
		<td>{$row['media_id']}</td>
		<td>";

		if ($row['type'] == 'jpg' || $row['type'] == 'gif' || $row['type'] == 'png') {
			$body .= "	<img src=\"/images/prom/{$row['media_id']}.{$row['type']}\" width=\"{$row['width']}\" height=\"{$row['height']}\">
						<br />
						{$row['ad_text']}";
		}
		else
			$body .= $row['ad_text'];

	$body .= "</td>
	<td>{$row['type']}</td>
	<td>{$row['width']}&nbsp;x&nbsp;{$row['height']}</td>
	<td><a href=\"form_media.php?media_id={$row['media_id']}&advertiser_id={$row['advertiser_id']}\">Edit</a></td>
	<td><a href=\"form_deploy.php?media_id={$row['media_id']}\">Deploy</a></td>
	</tr>";
	}

$body .= "</table><br />
<br />
<a href=\"form_media.php?advertiser_id={$_GET['advertiser_id']}\">Add media</a>";

$pap->setTag('main', $body);
$pap->output();
?>