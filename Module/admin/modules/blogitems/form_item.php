<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('blog');

if (ctype_digit($_GET['blog_id'])) {
	$n = new BlogItem($_GET['blog_id']);
	$body = $n->displayForm();

	$nil = new BlogImageList($_GET['blog_id']);
	$body .= $nil->display();
}
else {
	$n = new BlogItem;
	$body = $n->displayForm($admin_user->user_id);
}

$pap->setTag('main', $body);
$pap->output();
?>