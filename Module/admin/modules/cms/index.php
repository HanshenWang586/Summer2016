<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('cms');
$pap->setTitle('CMS');

$gcms = new GeneralCMS;
$body = $gcms->display_search($_GET['content_id']);
$body .= $gcms->display_results($_GET['content_id']);

$pap->setTag('main', $body);
$pap->output();
?>