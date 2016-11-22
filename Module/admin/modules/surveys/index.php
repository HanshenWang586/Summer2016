<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('surveys');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT *
					FROM surveys
					ORDER BY end_date DESC
					LIMIT 10");

$body .= "<table cellspacing=\"1\" class=\"gen_table\">
<tr>
<td><b>Survey ID</b></td>
<td><b>Survey Code</b></td>
<td><b>Survey Name</b></td>
<td><b>Start Date</b></td>
<td><b>End Date</b></td>
<td></td>
<td></td>
</tr>";

	while ($row = $rs->getRow()) {
	$body .= "<tr>
	<td>{$row['survey_id']}</td>
	<td>{$row['survey_code']}</td>
	<td>{$row['survey_en']}</td>
	<td>{$row['start_date']}</td>
	<td>{$row['end_date']}</td>
    <td><a href=\"questions.php?survey_id={$row['survey_id']}\">Questions</a></td>
	<td><a href=\"results.php?survey_id={$row['survey_id']}\">Results</a></td>
	<td><a href=\"voters.php?survey_id={$row['survey_id']}\">Voters</a></td>
	</tr>";
	}
	
$body .= "</table>";

$pap->setTag('main', $body);
$pap->output();
?>