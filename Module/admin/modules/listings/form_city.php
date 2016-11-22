<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('listings');
$pap->setTitle('Listings');

$city = new City($_GET['city_id']);
$body = $city->getForm();

$pap->setTag('main', $body);
$pap->output();
?>