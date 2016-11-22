<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('bokm');

if ($_GET['candidate_id'] != '') {
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT *
						FROM bokm_candidates
						WHERE candidate_id = {$_GET['candidate_id']}");
	$row = $rs->getRow();
}

$body = "<form action=\"form_candidate_proc.php\" method=\"post\">
".($_GET['candidate_id']!='' ? "<input type=\"hidden\" name=\"candidate_id\" value=\"{$_GET['candidate_id']}\">" : '')."
Candidate: <input name=\"candidate\" value=\"{$row['candidate']}\" size=\"65\"><br />
Listings code: <input name=\"listings_code\" value=\"{$row['listings_code']}\" size=\"65\"><br />
<input type=\"submit\" value=\"Save\">
</form>";

$pap->setTag('main', $body);
$pap->output();
?>