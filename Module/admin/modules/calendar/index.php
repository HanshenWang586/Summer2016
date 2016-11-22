<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('calendar');

$pager = new AdminPager;
$pager->setLimit(20);
$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);

$cal = new Calendar;
$cal->setAdminUser($admin_user);
$cal->setAdminDate($_GET['date']);
$data = $cal->displayWeek();

$body .= $pager->getNav();
$body .= $data;
$body .= $pager->getNav();

$pap->setTag('main', $body);
$pap->output();
?>
