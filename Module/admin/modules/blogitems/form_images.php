<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('blog');

$bi = new BlogImage($_GET['image_id']);
$body = $bi->displayForm($_GET['blog_id']);

$bil = new BlogImageList($_GET['blog_id']);
$bil->setAdmin(1);
$body .= $bil->display();

$pap->setTag('main', $body);
$pap->output();
?>