<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('surveys');

$survey = new SurveyQuestion($_GET['question_id']);
$body = $survey->getTextResultsList();

$pap->setTag('main', $body);
$pap->output();
?>