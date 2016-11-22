<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

if ($_POST['candidate_id'] != '') {
	$db = new DatabaseQuery;
	$db->execute("	DELETE FROM bokm_cands2categories
					WHERE candidate_id = {$_POST['candidate_id']}");

	if (count($_POST['category_ids'])) {
		foreach ($_POST['category_ids'] as $category_id) {
			$db->execute("	INSERT INTO bokm_cands2categories (candidate_id, category_id)
							VALUES ({$_POST['candidate_id']}, $category_id)");
		}
	}
}

HTTP::redirect();
?>