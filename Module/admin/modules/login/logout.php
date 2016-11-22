<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

unset($_SESSION['admin_user']);

HTTP::redirect('index.php');
?>