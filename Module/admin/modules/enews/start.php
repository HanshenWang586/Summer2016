<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('enews');

$body .= eNews::getStartForm();

$pap->setTag('main', $body);
$pap->output();
?>