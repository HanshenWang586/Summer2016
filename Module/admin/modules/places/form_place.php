<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('places');
$pap->setTitle('Place');

$place = new Place($_GET['city_id']);
$body = $place->getForm();

$pap->setTag('main', $body);
$pap->output();
?>