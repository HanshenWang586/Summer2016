<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$db = new DatabaseQuery;
if ($_POST['candidate_id']!='') {
	$db->execute("	UPDATE bokm_candidates
					SET candidate = '{$_POST['candidate']}',
						listings_code = '{$_POST['listings_code']}'
					WHERE candidate_id = {$_POST['candidate_id']}");
	$candidate_id = $_POST['candidate_id'];
}
else {
	$db->execute("	INSERT INTO bokm_candidates (candidate, listings_code)
					VALUES ('{$_POST['candidate']}', '{$_POST['listings_code']}')");
	$candidate_id = $db->getNewID();
}

HTTP::redirect("form_categories.php?candidate_id=$candidate_id");
?>