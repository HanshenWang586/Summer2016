<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('modules');

$aml = new AdminModuleList;
$body = $aml->display();

$pap->setTag('main', $body);
$pap->output();
?>