<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('places');

$placelist = new PlaceList;
$body .= $placelist->getAdmin();

$pap->setTag('main', $body);
$pap->output();
?>