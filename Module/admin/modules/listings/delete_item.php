<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$li = new ListingsItem($_GET['listing_id']);
$li->delete();

	if ($_GET['return'] == 'squash')
		HTTP::redirect("squashing.php?page={$_GET['page']}");
	else
		HTTP::redirect("index.php?category_id=$li->category_id");
?>