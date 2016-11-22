<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$bs = new BookSection;
$bs->setData($_POST);
$bs->save();

HTTP::redirect('index.php');
?>