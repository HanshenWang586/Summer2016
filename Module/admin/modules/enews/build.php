<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('enews');

$enews = new eNews(request($_GET['enews_id']));

$body = $enews->displayForm();
$pap->setTag('main', $body);
$pap->output();
?>