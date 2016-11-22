<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ci = new CalendarItem($_GET['calendar_id']);
$ci->delete();

HTTP::redirect();
?>
