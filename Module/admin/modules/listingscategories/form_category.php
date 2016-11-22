<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->set_menu_id(9);
$pap->setTitle('Listings Categories');

$lc = new ListingsCategory($_GET['category_id']);
$body = $lc->displayForm();

$pap->setTag('main', $body);
$pap->output();
?>