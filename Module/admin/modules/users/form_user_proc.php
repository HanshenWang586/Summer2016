<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pau = new AdminUser;
$pau->setData($_POST);
$pau->save();

HTTP::redirect('index.php');
?>