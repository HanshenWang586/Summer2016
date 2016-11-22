<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('book');

$booksection = new BookSection($_GET['section_id']);
$body = $booksection->getForm();

$pap->setTag('main', $body);
$pap->output();
?>