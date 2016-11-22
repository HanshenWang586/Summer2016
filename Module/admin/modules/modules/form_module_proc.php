<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$am = new AdminModule;
$am->setData($_POST);
$am->save();

HTTP::redirect('index.php');
?>