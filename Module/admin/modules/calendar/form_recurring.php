<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('calendar');

$ci = new CalendarItemRecurring($_GET['calendar_id']);
$body = $ci->displayForm();

$pap->setTag('main', $body);
$pap->output();
?>
