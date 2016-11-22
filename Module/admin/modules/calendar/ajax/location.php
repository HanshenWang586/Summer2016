<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$listing = new ListingsItem($_GET['location_id']);
echo $listing->getCalendarFormSummary();
?>