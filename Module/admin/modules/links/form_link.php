<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('links');

$ll = new LinksLink($_GET['link_id']);
$body = $ll->display_form($_GET['folder_id']);

$pap->setTag('main', $body);
$pap->output();
?>