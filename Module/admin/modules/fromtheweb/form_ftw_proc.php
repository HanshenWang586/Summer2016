<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ftw = new FromTheWeb;
$ftw->setData($_POST);
$ftw->save();

HTTP::redirect('index.php');
?>