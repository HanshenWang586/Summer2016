<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('blog');

$bl = new BlogList;
$body = $bl->getRestreamer();

$pap->setTag('main', $body);
$pap->output();
?>