<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('listings');

$pager = new AdminPager;
$pager->setLimit(20);
$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);

$ll = new ListingsList;
$data = $ll->getSquashList($pager);

$body .= $pager->getNav();
$body .= $data;
$body .= $pager->getNav();

$pap->setTag('main', $body);
$pap->output();
?>