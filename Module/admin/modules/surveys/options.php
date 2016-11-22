<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('surveys');

$q = new SurveyQuestion($_GET['question_id']);
$body = $q->getOptionChooser();

$pap->setTag('main', $body);
$pap->output();
?>