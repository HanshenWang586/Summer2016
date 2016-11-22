<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$q = new SurveyQuestion($_POST['question_id']);
$q->saveOptions($_POST['choice_ids']);
HTTP::redirect('questions.php?survey_id=3');
?>