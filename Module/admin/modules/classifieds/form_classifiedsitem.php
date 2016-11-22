<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('classifieds');

$cl = new ClassifiedsItem($_GET['classified_id']);
$cl->setPage($_GET['page']);
$cl->setSearchString($_GET['ss']);
$body = $cl->displayAdminForm();

$pap->setTag('main', $body);
$pap->output();
?>