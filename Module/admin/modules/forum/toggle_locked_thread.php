<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$thread = new ForumThread($_GET['thread_id']);
$thread->toggleLocked();

HTTP::redirect('index.php');
?>