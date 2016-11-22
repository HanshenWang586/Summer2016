<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ci = new CalendarItem;
$ci->setData($_POST);
$ci->save();

HTTP::redirect('index.php?date='.$ci->getDate());
?>
