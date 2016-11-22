<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('users');

$au = new AdminUser($_GET['user_id']);
$body = $au->displayForm();

$pap->setTag('main', $body);
$pap->output();
?>