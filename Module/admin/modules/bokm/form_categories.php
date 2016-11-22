<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('bokm');

$db = new DatabaseQuery;
$rs = $db->execute('SELECT *
					FROM bokm_categories c
					ORDER BY position');

$body = "<form action=\"form_categories_proc.php\" method=\"post\">
<input type=\"hidden\" name=\"candidate_id\" value=\"{$_GET['candidate_id']}\">";

while ($row = $rs->getRow()) {
	$rs_2 = $db->execute("	SELECT *
							FROM bokm_cands2categories
							WHERE candidate_id = {$_GET['candidate_id']}
							AND category_id = {$row['category_id']}");
							
	$body .= "<input type=\"checkbox\" name=\"category_ids[]\" value=\"{$row['category_id']}\"".($rs_2->getNum() > 0 ? ' checked' : '')."> {$row['category']}<br /><br />";
}

$body .= "<input type=\"submit\" value=\"Save\">
</form>";

$pap->setTag('main', $body);
$pap->output();
?>