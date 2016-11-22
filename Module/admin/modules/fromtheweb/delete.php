<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$ftw = new FromTheWeb($_GET['ftw_id']);
$ftw->delete();

HTTP::redirect('index.php');
?>