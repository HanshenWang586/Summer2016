<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('modules');

$am = new AdminModule($_GET['module_id']);
$body = $am->displayForm();

$pap->setTag('main', $body);
$pap->output();
?>