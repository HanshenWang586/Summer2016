<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('gallery');

$photo = new Photo($_GET['photo_id']);
$body = $photo->displayUploadForm($_GET['x']);

$pap->setTag('main', $body);
$pap->output();
?>