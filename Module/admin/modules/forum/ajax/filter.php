<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ftl = new ForumThreadList;
$ftl->setSearchString($_GET['ss']);
$pager = new AdminPager;
$pager->setLimit(20);
$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);
$data = $ftl->displayAdmin($pager);

$body .= $pager->getNav();
$body .= $data;
$body .= $pager->getNav();

echo HTTP::compress($body);
?>