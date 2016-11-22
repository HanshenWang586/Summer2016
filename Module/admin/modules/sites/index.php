<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('sites');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT * FROM sites");

$body .= "<table cellspacing=\"1\" class=\"gen_table\">
<tr>
<td><b>Site</b></td>
<td></td>
</tr>";

	while ($row = $rs->getRow())
	{
	$body .= "<tr>
	<td>{$row['site_name']}</td>
	<td><a href=\"form_site.php?site_id={$row['site_id']}\">Edit</a></td>
	</tr>";
	}
	
$body .= "</table>";

$pap->setTag('main', $body);
$pap->output();
?>