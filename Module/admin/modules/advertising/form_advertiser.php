<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('advertising');

$advertiser = new Advertiser($_GET['advertiser_id']);
$body = $advertiser->getForm();

$pap->setTag('main', $body);
$pap->output();
?>