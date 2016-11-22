<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('fromtheweb');

$ftw = new FromTheWeb($_GET['ftw_id']);
$body = $ftw->getForm();

$pap->setTag('main', $body);
$pap->output();
?>