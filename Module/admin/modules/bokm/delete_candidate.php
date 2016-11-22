<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$db = new DatabaseQuery;

if ($_GET['candidate_id'] != '') {
	$db->execute("	DELETE FROM bokm_candidates
					WHERE candidate_id = {$_GET['candidate_id']}");
}

HTTP::redirect('index.php');
?>