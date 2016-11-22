<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('bokm');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT *
					FROM bokm_categories c
					ORDER BY position");

while ($row = $rs->getRow()) {
	$candidates = array();
	$rs_2 = $db->execute("	SELECT * FROM bokm_cands2categories c2c, bokm_candidates c
							WHERE category_id={$row['category_id']}
							AND c.candidate_id=c2c.candidate_id
							ORDER BY candidate");

	while ($row_2 = $rs_2->getRow()) {
		if ($row_2['listings_code'] != '')
			$candidates[] = "#{$row_2['candidate']}#http://www.gokunming.com/en/listings/item/{$row_2['listings_code']}/#";
		else
			$candidates[] = $row_2['candidate'];
	}

	$copy .= "<b>{$row['category']}</b>\n".implode(', ', $candidates)."\n\n";
}

$body = "<textarea rows=\"50\" cols=\"100\">$copy</textarea>";

$pap->setTag('main', $body);
$pap->output();
?>