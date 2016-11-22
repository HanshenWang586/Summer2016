<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$enews = new eNews;
$enews->setData($_POST);
$enews->save();

HTTP::redirect('index.php');
?>