<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$book = new Book;
$book->addSection($_GET['section_tag']);

HTTP::redirect('index.php');
?>