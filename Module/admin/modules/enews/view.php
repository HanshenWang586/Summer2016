<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$db = new DatabaseQuery;
$rs = $db->execute("SELECT * FROM enews WHERE enews_id=".$_GET['enews_id']);
$row = $rs->getRow();

echo $row['message'];
?>