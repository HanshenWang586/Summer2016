<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$tags = explode(',', $_POST['tags']);
$blog_id = $_POST['blog_id'];
$db = new DatabaseQuery;

	foreach($tags as $tag)
		$tags_trimmed[] = trim($tag);

$tags = array_unique($tags_trimmed);


foreach ($tags as $tag) {
	$clean_tags[] = $db->clean($tag);
}

$tags = "'".implode("', '", $clean_tags)."'";

$related_ids = array();

// get already-related articles
$rs = $db->execute("SELECT title, related_id
					FROM blog_related r, blog_content c
					WHERE r.blog_id = $blog_id
					AND r.related_id = c.blog_id
					ORDER BY title ASC");

	if ($rs->getNum()) {
		while ($row = $rs->getRow()) {
			$related_ids[] = $row['related_id'];
			$bi = new BlogItem($row['related_id']);
			$links[] = "<a href=\"javascript:void(null);\" onClick=\"blogToggleRelated($blog_id, {$row['related_id']}, $('tags').value);\"><img src=\"../../images/minus.gif\" width=\"14\" height=\"14\"></a> ".$bi->getAdminTitleLinked();
		}

	$content .= HTMLHelper::wrapArrayInUl($links);
	}

$links = array();

// now get the possible new related articles
$rs = $db->execute("SELECT title, c.blog_id, COUNT(*) AS tally
					FROM blog_content c, blog_tags t
					WHERE t.blog_id = c.blog_id
					AND tag IN ($tags)
					AND c.blog_id != $blog_id
					".(count($related_ids) ? "AND c.blog_id NOT IN (".implode(', ', $related_ids).")" : '')."
					GROUP BY c.blog_id
					ORDER BY tally DESC, title ASC");

// display
if ($rs->getNum()) {
	while ($row = $rs->getRow()) {
		$bi = new BlogItem($row['blog_id']);
		$links[] = "<a href=\"javascript:void(null);\" onClick=\"blogToggleRelated($blog_id, {$row['blog_id']}, $('tags').value);\"><img src=\"../../images/plus.gif\" width=\"14\" height=\"14\"></a> ".$bi->getAdminTitleLinked()."&nbsp;({$row['tally']})";
	}

	$content .= HTMLHelper::wrapArrayInUl($links);
}

echo $content;
?>