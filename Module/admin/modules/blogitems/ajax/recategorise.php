<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$blog_id = $_GET['blog_id'];
$category_id = $_GET['category_id'];
$categories = array(1 => 'News', 2 => 'Features', 3 => 'Travel');

$db = new DatabaseQuery;
$rs = $db->execute("UPDATE blog_content SET category_id = $category_id
					WHERE blog_id = $blog_id");

echo $categories[$category_id];
?>