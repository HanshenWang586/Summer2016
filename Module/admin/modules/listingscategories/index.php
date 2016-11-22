<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('listings_categories');

$lcl = new ListingsCategoryList;
$body = $lcl->displayAdmin();

$pap->setTag('main', $body);
$pap->output();
?>