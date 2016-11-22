<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$user = new User($_GET['user_id']);
$user->ban();

HTTP::redirect('index.php');

?>