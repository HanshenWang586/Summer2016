<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ci = new CalendarItemRecurring($_GET['calendar_id']);
$ci->toggleLive();

HTTP::redirect('recurring.php');
?>
