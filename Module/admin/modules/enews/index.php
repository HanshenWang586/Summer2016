<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('enews');

$db = new DatabaseQuery;
$rs = $db->execute('SELECT *
					FROM enews
					ORDER BY ts DESC LIMIT 10');

$body .= '<table cellspacing="1" class="gen_table">
<tr>
<td><b>eNews ID</b></td>
<td><b>Site ID</b></td>
<td><b>Subject</b></td>
<td><b>Created</b></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
</tr>';

while ($row = $rs->getRow()) {
	$body .= "<tr>
	<td>{$row['enews_id']}</td>
	<td>{$row['site_id']}</td>
	<td>{$row['subject']}</td>
	<td>{$row['ts']}</td>
	<td><a href=\"view.php?enews_id={$row['enews_id']}\" target=\"_blank\">View</a></td>
	<td><a href=\"build.php?enews_id={$row['enews_id']}\">Edit</a></td>
	<td><a href=\"test.php?enews_id={$row['enews_id']}\">Test</a></td>
	<td><a href=\"send.php?enews_id={$row['enews_id']}\" onclick=\"return confirm('Are you sure you want to send?');\">Send</a></td>
	<td><a href=\"delete.php?enews_id={$row['enews_id']}\" onclick=\"return conf_del();\">Delete</a></td>
	</tr>";
}
	
$body .= '</table>';
$pap->setTag('main', $body);
$pap->output();
?>