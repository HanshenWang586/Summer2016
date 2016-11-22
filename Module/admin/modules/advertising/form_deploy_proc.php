<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$media = new AdMedia($_POST['media_id']);
$media->setData($_POST);
$media->saveDeployment();

HTTP::redirect('index.php?advertiser_id='.$media->getAdvertiserID());
?>