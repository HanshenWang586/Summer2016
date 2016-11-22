<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('links');

$ll = new LinksFolder($_GET['folder_id']);
$body = $ll->display_admin();

$pap->setTag('main', $body);
$pap->output();
?>