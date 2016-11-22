<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('public_users');

$user = new User($_GET['user_id']);
$body .= $user->getAdminForm();

$pap->setTag('main', $body);
$pap->output();
?>