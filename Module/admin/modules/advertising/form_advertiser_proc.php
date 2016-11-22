<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$advertiser = new Advertiser;
$advertiser->setData($_POST);
$advertiser->save();

HTTP::redirect('media.php?advertiser_id='.$advertiser->getAdvertiserID());
?>