<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('sites');

$site = new Site($_GET['site_id']);
$body .= $site->displayForm();

$pap->setTag('main', $body);
$pap->output();
?>