<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ci = new CalendarItemRecurring;
$ci->setData($_POST);
$ci->save();

HTTP::redirect('recurring.php');
?>
