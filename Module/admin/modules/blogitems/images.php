<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('blog');

$pager = new AdminPager;
$pager->setLimit(42);
$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);

$bil = new BlogImageList;
$data = $bil->getAll($pager);

$body .= $pager->getNav();
$body .= $data;
$body .= $pager->getNav();

$pap->setTag('main', $body);
$pap->output();
?>