<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->set_menu_id(18);
$pap->setTitle('Guides');

$guide = new Guide($_GET['guide_id']);
$body = $guide->displayPageForm($_GET['page']);

$pap->setTag('main', $body);
$pap->output();
?>