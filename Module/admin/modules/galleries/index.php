<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('gallery');

$pager = new AdminPager;
$pager->setLimit(20);
$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);

$plist = new PhotoList;
$hold = $plist->displayAdmin($pager);

$body .= $pager->getNav();
$body .= $hold;
$body .= $pager->getNav();

$pap->setTag('main', $body);
$pap->output();
?>