<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('advertising');

$media = new AdMedia($_GET['media_id']);
$media->setAdvertiserID($_GET['advertiser_id']);
$body = $media->getForm();

$pap->setTag('main', $body);
$pap->output();
?>