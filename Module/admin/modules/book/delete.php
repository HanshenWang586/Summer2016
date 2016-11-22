<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$db = new DatabaseQuery;
$db->execute("UPDATE book_sections SET live=0 WHERE section_id={$_GET['section_id']}");

HTTP::redirect('index.php');
?>