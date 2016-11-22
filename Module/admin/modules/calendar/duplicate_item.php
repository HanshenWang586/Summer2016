<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('calendar');

$ci = new CalendarItem($_GET['calendar_id']);
$ci->setDate($_GET['date']);
$body = $ci->displayForm(true);

$pap->setTag('main', $body);
$pap->output();
?>
