<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->set_menu_id(10);
$pap->setTitle('Listings');

$li = new ListingsItem($_GET['listing_id']);
$lc = new ListingsCategory;

$body = $li->displayAdminBrief();
$body .= $li->displayAdminCategories();
$body .= "<br />";
$body .= $lc->displayAdminCategoryAdder($_GET['listing_id']);

$pap->setTag('main', $body);
$pap->output();
?>