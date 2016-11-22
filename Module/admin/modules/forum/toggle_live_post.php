<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$post = new ForumPost($_GET['post_id']);
$post->toggleLive();

HTTP::redirect('list_posts.php?thread_id='.$post->getThreadID());
?>