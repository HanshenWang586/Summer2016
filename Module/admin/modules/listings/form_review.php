<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('listings');

$review = new Review($_GET['review_id']);
$body = $review->displayAdminForm();

$pap->setTag('main', $body);
$pap->output();
?>