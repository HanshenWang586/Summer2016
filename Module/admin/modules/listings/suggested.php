<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->set_menu_id(10);
$pap->setTitle('Listings');

$ll = new ListingsList;
$body .= $ll->displayAdminSuggested();

$pap->setTag('main', $body);
$pap->output();
?>