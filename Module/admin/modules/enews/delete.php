<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$db = new DatabaseQuery;
$db->execute("DELETE FROM enews WHERE enews_id=".$_GET['enews_id']);
$db->execute("DELETE FROM log_enews WHERE enews_id=".$_GET['enews_id']);


HTTP::redirect('index.php');
?>