<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('cms');
$pap->setTitle('CMS');

$gcms = new GeneralCMS($_GET['content_id']);
$body = $gcms->displayForm();

$pap->setTag('main', $body);
$pap->output();
?>