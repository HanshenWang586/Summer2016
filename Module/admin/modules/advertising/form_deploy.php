<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('advertising');

$media = new AdMedia($_GET['media_id']);
$body = $media->getDeployForm($_GET['deployment_id']);

$pap->setTag('main', $body);
$pap->output();
?>