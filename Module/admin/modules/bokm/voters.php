<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('bokm');

$db = new DatabaseQuery;
$rs = $db->execute('SELECT DISTINCT user_id
					FROM bokm_votes');

$body .= "Total: ".$rs->getNum();

while ($row = $rs->getRow()) {
	$rs_2 = $db->execute("	SELECT *
							FROM public_users
							WHERE user_id = {$row['user_id']}
							ORDER BY ts_registered DESC");

	while ($row_2 = $rs_2->getRow())
		$body .= debug($row_2);
}
	
$pap->setTag('main', $body);
$pap->output();
?>