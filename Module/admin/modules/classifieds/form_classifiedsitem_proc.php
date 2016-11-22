<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ci = new ClassifiedsItem;
$ci->setData($_POST);
$ci->saveAdmin();

if (ctype_digit($_POST['thread_id'])) {
	$ft = new ForumThread($_POST['thread_id']);
	$ft->toggleLive();
}

HTTP::redirect('list.php'.ContentCleaner::buildGetString(array('page' => $_POST['page'], 'ss' => $_POST['ss'])));
?>