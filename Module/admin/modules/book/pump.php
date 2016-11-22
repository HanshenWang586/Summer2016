<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

header("Content-type: text/plain; charset=utf-8");

$book = new Book;
echo $book->output();
?>