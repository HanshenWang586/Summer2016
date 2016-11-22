<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('public_users');

$up = new UserProfile($_GET['user_id']);

$pap->setTag('#MAIN#', $up->display());
$pap->output();
?>