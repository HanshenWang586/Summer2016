<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('classifieds');

$ss = isset($_GET['ss']) ? urldecode($_GET['ss']) : '';

$pager = new AdminPager;
$pager->setLimit(20);
$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);

$body = FormHelper::open('list.php', array('method' => 'get'))
.FormHelper::input('Search', 'ss', $ss)
.FormHelper::close();

$cl = new ClassifiedsList;
$data = $cl->displayAdmin($pager, $ss);

if ($data) {
	$body .= $pager->getNav();
	$body .= $data;
	$body .= $pager->getNav();
}
else
	$body .= 'No results.';

$pap->appendTitle(' > All');
$pap->setTag('main', $body);
$pap->output();
?>