<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$media = new AdMedia;
$media->setData($_POST);
$media->setFiles($_FILES);
$media->save();
if (request($_POST['deployment_id']) > 0) {
	HTTP::redirect('form_deploy.php?deployment_id='.$_POST['deployment_id']);
} else HTTP::redirect('media.php?advertiser_id='.$media->getAdvertiserID());
?>