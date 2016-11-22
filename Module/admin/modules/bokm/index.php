<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('bokm');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT *
					FROM bokm_candidates
					ORDER BY candidate");

$body = "<table class=\"gen_table\" cellspacing=\"1\">
<tr>
<td><b>Candidate</b></td>
<td><b>Listings Code</b></td>
<td colspan=\"3\"></td>
</tr>";

while ($row = $rs->getRow()) {
	$body .= "<tr>
	<td>{$row['candidate']}</td>
	<td>{$row['listings_code']}</td>
	<td><a href=\"form_candidate.php?candidate_id={$row['candidate_id']}\">Edit</a></td>
	<td><a href=\"delete_candidate.php?candidate_id={$row['candidate_id']}\" onClick=\"return conf_del()\">Delete</a></td>
	<td><a href=\"form_categories.php?candidate_id={$row['candidate_id']}\">Categories</a></td>
	</tr>";
}
	
$body .= '</table>';

$pap->setTag('main', $body);
$pap->output();
?>