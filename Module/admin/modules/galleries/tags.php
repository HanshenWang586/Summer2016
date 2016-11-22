<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('gallery');

$ptl = new PhotoTagList;
$body .= $ptl->displayAdmin();

$pap->setTag('main', $body);
$pap->output();
?>