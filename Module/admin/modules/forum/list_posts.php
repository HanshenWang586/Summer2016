<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('forum');

$ft = new ForumThread($_GET['thread_id']);
$body = $ft->displayAdmin();

if ($ft->getNumberPosts() == 1) {

	$fp = new ForumPost($ft->getLatestPostID());

	$body .= '<h2>Shift To Classifieds Form</h2>';
	$classified = new ClassifiedsItem;
	$classified->setData($fp->getData());
	$body .= $classified->displayAdminForm();
}

$pap->setTag('main', $body);
$pap->output();
?>