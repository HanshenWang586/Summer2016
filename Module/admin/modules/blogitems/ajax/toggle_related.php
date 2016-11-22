<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$blog_id = $_GET['blog_id'];
$related_id = $_GET['related_id'];

$db = new DatabaseQuery;
$rs = $db->execute("SELECT *
					FROM blog_related
					WHERE related_id = $related_id
					AND blog_id = $blog_id");

	if ($rs->getNum()) {
		$rs = $db->execute("DELETE
							FROM blog_related
							WHERE related_id = $related_id
							AND blog_id = $blog_id");
	}
	else {
		$db->execute("	INSERT INTO blog_related (blog_id, related_id)
						VALUES ($blog_id, $related_id)");
	}
?>