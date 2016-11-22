<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('public_users');

$pager = new AdminPager;
$pager->setLimit(20);
$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);

$body .= FormHelper::open('index.php', array('method' => 'method'));
$body .= FormHelper::input('', 'ss', $_GET['ss']);
$body .= '<br>';
$body .= FormHelper::checkbox('Hide live users', 'hideLive', $_GET['hideLive']);
$body .= FormHelper::checkbox('Hide unverified users', 'hideUnverified', $_GET['hideUnverified']);
$body .= FormHelper::checkbox('Hide banned users', 'hideBanned', $_GET['hideBanned']);
$body .= FormHelper::close();

$users = new UserList;
$data = $users->searchAdmin($pager, $_GET['ss']);

$body .= $pager->getNav();
$body .= $data;
$body .= $pager->getNav();

$pap->setTag('main', $body);
$pap->output();
?>