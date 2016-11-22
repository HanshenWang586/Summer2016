<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('bokm');

$db = new DatabaseQuery;
$rs = $db->execute('SELECT *
					FROM bokm_categories c
					ORDER BY position');

while ($row = $rs->getRow()) {
	$body .= "<br /><b>{$row['category']}</b><br />";
	
	$rs_2 = $db->execute("	SELECT candidate, COUNT(*) AS tally
							FROM bokm_votes v, bokm_candidates c
							WHERE category_id={$row['category_id']}
							AND v.candidate_id=c.candidate_id
							GROUP BY c.candidate_id
							ORDER BY tally DESC");
						
	while ($row_2 = $rs_2->getRow())
		$body .= "{$row_2['candidate']}: {$row_2['tally']}<br />";
}
	
$pap->setTag('main', $body);
$pap->output();
?>