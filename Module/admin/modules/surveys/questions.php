<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('surveys');

$survey = new Survey($_GET['survey_id']);
$body = $survey->getQuestions();

$pap->setTag('main', $body);
$pap->output();
?>