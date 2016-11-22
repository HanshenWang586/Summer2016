<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$bi = new BlogItem($_GET['blog_id']);
$bi->delete();

HTTP::redirect('index.php');
?>