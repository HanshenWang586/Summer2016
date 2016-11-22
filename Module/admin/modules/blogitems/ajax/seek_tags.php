<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$tag_stub = $_GET['tag_stub'];
$links = array();

$db = new DatabaseQuery;
$rs = $db->execute("SELECT DISTINCT tag
					FROM blog_tags
					WHERE tag LIKE '".$db->clean($tag_stub)."%'
					ORDER BY tag
					LIMIT 10");

	while ($row = $rs->getRow())
		$links[] = "<a href=\"javascript:void(null);\" onClick=\"blogUseSuggestedTag('{$row['tag']}');\">{$row['tag']}</a>";

echo implode(', ', $links);
?>