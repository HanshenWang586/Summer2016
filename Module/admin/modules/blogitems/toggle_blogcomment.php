<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$bc = new BlogComment($_GET['comment_id']);
$bc->toggleLive();

$url = 'comments.php';

if ($_GET['page'] != '')
	$url .= '?page='.$_GET['page'];

HTTP::redirect($url);
?>