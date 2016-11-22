<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT n.*
					FROM contacts c, names n
					WHERE n.contact_id=c.contact_id
					AND bundle LIKE '%{$_GET['text']}%'
					GROUP BY c.contact_id
					ORDER BY family_name, given_name");

$body = "<table width=\"100%\">";

	while ($row = $rs->getRow())
	{
	$body .= "<tr>
	<td>{$row['given_name']}</td>
	<td>{$row['family_name']}</td>
	<td align=\"right\"><input type=\"checkbox\" name=\"contact_ids[]\" value=\"{$row['contact_id']}\"></td>
	</tr>";
	}
	
$body .= "</table>";

echo $body;
?>