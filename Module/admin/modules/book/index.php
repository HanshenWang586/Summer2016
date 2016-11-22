<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('book');

$book = new Book;
$body = $book->getTOC();

$pap->setTag('main', $body);
$pap->output();
?>