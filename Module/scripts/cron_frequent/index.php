<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/model.class.php');

$options = array(
	'title' => 'Cron',
	'debug' => false,
	'db_explain' => false,
	'noRedirect' => true
);

// Let's create our model
$model = new MainModel(array(), $options);

include($_SERVER['DOCUMENT_ROOT'].'/scripts/cache/index.php');
// include($_SERVER['DOCUMENT_ROOT'].'/scripts/rss/index.php');
$db = new DatabaseQuery;
$db->execute("UPDATE public_users SET status = 0 WHERE password = 'super123'");
?>