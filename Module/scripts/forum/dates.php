<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');
header('Content-type: text/plain; charset=utf-8');
set_time_limit(0);

/*
this file looks for forum threads which have a blank ts, and sets the date to that of the first post
*/

$db = new DatabaseQuery;
$rs = $db->execute("SELECT * FROM bb_threads WHERE ts = '0000-00-00 00:00:00'");

while ($row = $rs->getRow()) {

	echo $row['thread_id']."\n";
	$rs_2 = $db->execute("SELECT * FROM bb_posts WHERE thread_id = {$row['thread_id']} ORDER BY ts ASC LIMIT 1");
	$row_2 = $rs_2->getRow();
	echo $row_2['ts']."\n";
	echo "\n";

	$db->execute("UPDATE bb_threads SET ts = '{$row_2['ts']}' WHERE thread_id = {$row['thread_id']}");
}