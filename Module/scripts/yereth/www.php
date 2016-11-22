<?php
require($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');

error_reporting(E_ALL);

$db = $GLOBALS['site']->db();

$results = $db->query('blog_content', "!content LIKE '%http://gokunming.com%'", array('getFields' => 'blog_id, content, content_cache, content_stripped', 'transpose' => array('selectKey' => 'blog_id', 'selectValue' => true)));

$total_replaced = 0;

foreach($results as $blog_id => $result) {
	foreach(array('content', 'content_cache', 'content_stripped') as $field) {
		$result[$field] = str_replace('http://gokunming.com', 'http://www.gokunming.com', $result[$field]);
	}
	unset($result['blog_id']);
	$r = $db->update('blog_content', array('blog_id' => $blog_id), $result);
	if ($r > 0) $total_replaced += (int) $r;
}

echo $total_replaced . " total occurrences in " . count($results) . " articles\n";