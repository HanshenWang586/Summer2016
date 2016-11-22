<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$gcms = new GeneralCMS;
$gcms->setData($_POST);
$gcms->save();

HTTP::redirect('index.php?content_id='.$gcms->getContentID());
?>