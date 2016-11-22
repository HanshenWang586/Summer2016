<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$db = new DatabaseQuery;
$db->execute("INSERT INTO book_sections (section_tag) VALUES ('{$_GET['section_tag']}.1')");

HTTP::redirect('index.php');
?>