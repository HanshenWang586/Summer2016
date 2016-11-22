<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('advertising');

$location = new AdLocation($_GET['location_id']);
$body = '<h3>'.$location->getDescription().'</h3>';
$body .= $location->getCurrentAds();

$pap->setTag('main', $body);
$pap->output();
?>