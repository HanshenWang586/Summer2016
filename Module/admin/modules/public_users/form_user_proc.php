<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$user = new User;
$user->setData($_POST);
$user->saveAdmin();

HTTP::redirect('index.php');
?>