<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->set_menu_id(18);
$pap->setTitle('Guides');

$gl = new GuidesList;
$body = $gl->displayAdmin();

$pap->setTag('main', $body);
$pap->output();
?>