<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('users');

$paul = new AdminUserList;
$body = $paul->display();

$pap->setTag('main', $body);
$pap->output();
?>